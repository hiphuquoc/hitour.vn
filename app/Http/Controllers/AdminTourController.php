<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Upload;
use App\Http\Controllers\AdminImageController;
use App\Http\Controllers\AdminSliderController;
use App\Http\Controllers\AdminGalleryController;
use App\Models\TourContent;
use App\Models\TourTimetable;
use App\Models\TourLocation;
use App\Models\TourDeparture;
use App\Models\Tour;
use App\Models\RelationTourLocation;
use App\Models\RelationTourStaff;
use App\Models\RelationTourPartner;
use App\Models\Staff;
use App\Models\TourPartner;
use App\Models\TourPrice;
use App\Models\TourOption;
use App\Models\Seo;
use App\Models\QuestionAnswer;
use App\Services\BuildInsertUpdateModel;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TourRequest;
use App\Jobs\CheckSeo;

class AdminTourController extends Controller {

    public function __construct(BuildInsertUpdateModel $BuildInsertUpdateModel){
        $this->BuildInsertUpdateModel   = $BuildInsertUpdateModel;
    }

    public function list(Request $request){
        $params                         = [];
        /* Search theo tên */
        if(!empty($request->get('search_name'))) $params['search_name'] = $request->get('search_name');
        /* Search theo vùng miền */
        if(!empty($request->get('search_location'))) $params['search_location'] = $request->get('search_location');
        /* Search theo đối tác */
        if(!empty($request->get('search_partner'))) $params['search_partner'] = $request->get('search_partner');
        /* Search theo nhân viên */
        if(!empty($request->get('search_staff'))) $params['search_staff'] = $request->get('search_staff');
        /* paginate */
        $viewPerPage        = Cookie::get('viewTourInfo') ?? 50;
        $params['paginate'] = $viewPerPage;
        /* lấy dữ liệu */
        $list                           = Tour::getList($params);
        /* khu vực Tour */
        $tourLocations                  = TourLocation::all();
        /* đối tác */
        $partners                       = TourPartner::all();
        /* nhân viên */
        $staffs                         = Staff::all();
        return view('admin.tour.list', compact('list', 'params', 'viewPerPage', 'tourLocations', 'partners', 'staffs'));
    }

    public function view(Request $request){
        $tourLocations      = TourLocation::all();
        $tourDepartures     = TourDeparture::all();
        $staffs             = Staff::all();
        $partners           = TourPartner::all();
        $parents            = TourLocation::select('*')
                                ->with('seo')
                                ->get();
        $message            = $request->get('message') ?? null;
        $id                 = $request->get('id') ?? 0;
        $item               = Tour::select('*')
                                ->where('id', $request->get('id'))
                                ->with(['files' => function($query){
                                    $query->where('relation_table', 'tour_info');
                                }])
                                ->with(['questions' => function($query){
                                    $query->where('relation_table', 'tour_info');
                                }])
                                ->with('seo', 'content', 'timetables')
                                ->first();
        /* type */
        $type               = !empty($item) ? 'edit' : 'create';
        $type               = $request->get('type') ?? $type;
        return view('admin.tour.view', compact('item', 'type', 'tourLocations', 'tourDepartures', 'staffs', 'partners', 'parents', 'message'));

        // return redirect()->route('admin.tour.list');
    }

    public function create(TourRequest $request){
        try {
            DB::beginTransaction();
            /* upload image */
            $dataPath           = [];
            if($request->hasFile('image')) {
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $dataPath       = Upload::uploadThumnail($request->file('image'), $name);
            }
            /* insert page */
            $insertPage         = $this->BuildInsertUpdateModel->buildArrayTableSeo($request->all(), 'tour_info', $dataPath);
            $pageId             = Seo::insertItem($insertPage);
            /* insert tour_info */
            $insertTourInfo     = $this->BuildInsertUpdateModel->buildArrayTableTourInfo($request->all(), $pageId);
            $idTour             = Tour::insertItem($insertTourInfo);
            /* update tour_content */
            $insertTourInfo     = $this->BuildInsertUpdateModel->buildArrayTableTourContent($request->all(), $idTour);
            TourContent::select('*')
                            ->where('tour_info_id', $idTour)
                            ->delete();
            $idTourContent      = TourContent::insertItem($insertTourInfo);
            /* insert câu hỏi thường gặp */
            if(!empty($request->get('question_answer'))){
                foreach($request->get('question_answer') as $itemQues){
                    if(!empty($itemQues['question'])&&!empty($itemQues['answer'])){
                        QuestionAnswer::insertItem([
                            'question'          => $itemQues['question'],
                            'answer'            => $itemQues['answer'],
                            'relation_table'    => 'tour_info',
                            'reference_id'      => $idTour
                        ]);
                    }
                }
            }
            // /* lưu content vào file */
            // Storage::put(config('admin.storage.contentTour').$request->get('slug').'.blade.php', $request->get('content'));
            /* insert tour_timetable */
            if(!empty($request->get('timetable'))){
                foreach($request->get('timetable') as $timetable){
                    $insertTourTimetable    = [
                        'tour_info_id'  => $idTour,
                        'title'         => $timetable['tour_timetable_title'],
                        'content'       => AdminImageController::replaceImageInContentWithLoading($timetable['tour_timetable_content']),
                        'content_sort'  => AdminImageController::replaceImageInContentWithLoading($timetable['tour_timetable_content_sort'])
                    ];
                    TourTimetable::insertItem($insertTourTimetable);
                }
            }
            /* insert slider và lưu CSDL */
            if($request->hasFile('slider')&&!empty($idTour)){
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params         = [
                    'attachment_id'     => $idTour,
                    'relation_table'    => 'tour_info',
                    'name'              => $name
                ];
                AdminSliderController::uploadSlider($request->file('slider'), $params);
            }
            /* insert gallery và lưu CSDL */
            if($request->hasFile('gallery')&&!empty($idTour)){
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params         = [
                    'attachment_id'     => $idTour,
                    'relation_table'    => 'tour_info',
                    'name'              => $name
                ];
                AdminGalleryController::uploadGallery($request->file('gallery'), $params);
            }
            /* insert relation_tour_location */
            if(!empty($idTour)&&!empty($request->get('location'))){
                foreach($request->get('location') as $location){
                    $params     = [
                        'tour_info_id'      => $idTour,
                        'tour_location_id'  => $location
                    ];
                    RelationTourLocation::insertItem($params);
                }
            }
            /* insert relation_tour_staff */
            if(!empty($idTour)&&!empty($request->get('staff'))){
                foreach($request->get('staff') as $staff){
                    $params     = [
                        'tour_info_id'      => $idTour,
                        'staff_info_id'     => $staff
                    ];
                    RelationTourStaff::insertItem($params);
                }
            }
            /* insert relation_tour_partner */
            if(!empty($idTour)&&!empty($request->get('partner'))){
                foreach($request->get('partner') as $partner){
                    $params     = [
                        'tour_info_id'      => $idTour,
                        'partner_info_id'   => $partner
                    ];
                    RelationTourPartner::insertItem($params);
                }
            }
            DB::commit();
            /* Message */
            $message        = [
                'type'      => 'success',
                'message'   => '<strong>Thành công!</strong> Dã tạo Tour mới'
            ];
        } catch (\Exception $exception){
            DB::rollBack();
            /* Message */
            $message        = [
                'type'      => 'danger',
                'message'   => '<strong>Thất bại!</strong> Có lỗi xảy ra, vui lòng thử lại'
            ];
        }
        /* ===== START:: check_seo_info */
        CheckSeo::dispatch($request->get('seo_id'));
        /* ===== END:: check_seo_info */
        $request->session()->put('message', $message);
        return redirect()->route('admin.tour.view', ['id' => $idTour]);
    }

    public function update(TourRequest $request){
        try {
            DB::beginTransaction();
            $idTour             = $request->get('tour_info_id') ?? 0;
            /* upload image */
            $dataPath           = [];
            if($request->hasFile('image')) {
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $dataPath       = Upload::uploadThumnail($request->file('image'), $name);
            };
            /* update page */
            $updatePage         = $this->BuildInsertUpdateModel->buildArrayTableSeo($request->all(), 'tour_info', $dataPath);
            Seo::updateItem($request->get('seo_id'), $updatePage);
            /* update tour_info */
            $updateTourInfo     = $this->BuildInsertUpdateModel->buildArrayTableTourInfo($request->all(), $request->get('seo_id'));
            Tour::updateItem($idTour, $updateTourInfo);
            /* update tour_content */
            $insertTourInfo     = $this->BuildInsertUpdateModel->buildArrayTableTourContent($request->all(), $idTour);
            TourContent::select('*')
                            ->where('tour_info_id', $idTour)
                            ->delete();
            $idTourContent      = TourContent::insertItem($insertTourInfo);
            // /* lưu content vào file */
            // Storage::put(config('admin.storage.contentTour').$request->get('slug').'.blade.php', $request->get('content'));
            /* update tour_timetable */
            TourTimetable::select('*')
                            ->where('tour_info_id', $idTour)
                            ->delete();
            if(!empty($request->get('timetable'))){
                foreach($request->get('timetable') as $timetable){
                    $insertTourTimetable    = [
                        'tour_info_id'  => $idTour,
                        'title'         => $timetable['tour_timetable_title'],
                        'content'       => $timetable['tour_timetable_content'],
                        'content_sort'  => $timetable['tour_timetable_content_sort']
                    ];
                    TourTimetable::insertItem($insertTourTimetable);
                }
            }
            /* lưu content vào database */
            $updateContent      = $this->BuildInsertUpdateModel->buildArrayTableTourContent($request->all(), 'tour_info', $dataPath);
            /* update câu hỏi thường gặp */
            QuestionAnswer::select('*')
                            ->where('relation_table', 'tour_info')
                            ->where('reference_id', $idTour)
                            ->delete();
            if(!empty($request->get('question_answer'))){
                foreach($request->get('question_answer') as $itemQues){
                    if(!empty($itemQues['question'])&&!empty($itemQues['answer'])){
                        QuestionAnswer::insertItem([
                            'question'          => $itemQues['question'],
                            'answer'            => $itemQues['answer'],
                            'relation_table'    => 'tour_info',
                            'reference_id'      => $idTour
                        ]);
                    }
                }
            }
            /* update slider và lưu CSDL */
            if($request->hasFile('slider')&&!empty($idTour)){
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params         = [
                    'attachment_id'     => $idTour,
                    'relation_table'    => 'tour_info',
                    'name'              => $name
                ];
                AdminSliderController::uploadSlider($request->file('slider'), $params);
            }
            /* update gallery và lưu CSDL */
            if($request->hasFile('gallery')&&!empty($idTour)){
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params         = [
                    'attachment_id'     => $idTour,
                    'relation_table'    => 'tour_info',
                    'name'              => $name
                ];
                AdminGalleryController::uploadGallery($request->file('gallery'), $params);
            }
            /* update relation_tour_location */
            RelationTourLocation::deleteAndInsertItem($idTour, $request->get('location'));
            /* update relation_tour_staff */
            RelationTourStaff::deleteAndInsertItem($idTour, $request->get('staff'));
            /* update relation_tour_partner */
            RelationTourPartner::deleteAndInsertItem($idTour, $request->get('partner'));
            DB::commit();
            /* Message */
            $message        = [
                'type'      => 'success',
                'message'   => '<strong>Thành công!</strong> Các thay đổi đã được lưu'
            ];
        } catch (\Exception $exception){
            DB::rollBack();
            /* Message */
            $message        = [
                'type'      => 'danger',
                'message'   => '<strong>Thất bại!</strong> Có lỗi xảy ra, vui lòng thử lại'
            ];
        }
        /* ===== START:: check_seo_info */
        CheckSeo::dispatch($request->get('seo_id'));
        /* ===== END:: check_seo_info */
        $request->session()->put('message', $message);
        return redirect()->route('admin.tour.view', ['id' => $idTour]);
    }

    public function delete(Request $request){
        if(!empty($request->get('id'))){
            try {
                DB::beginTransaction();
                $idTour     = $request->get('id');
                /* lấy tour_option (with tour_price) */
                $infoTour   = Tour::select('*')
                                    ->where('id', $idTour)
                                    ->with(['files' => function($query){
                                        $query->where('relation_table', 'tour_info');
                                    }])
                                    ->with('seo', 'locations', 'staffs', 'partners', 'options.prices')
                                    ->first();
                /* xóa ảnh đại diện trong thư mục upload */
                if(!empty($infoTour->seo->image)&&file_exists(public_path($infoTour->seo->image))) unlink(public_path($infoTour->seo->image));
                if(!empty($infoTour->seo->image_small)&&file_exists(public_path($infoTour->seo->image_small))) unlink(public_path($infoTour->seo->image_small));
                /* xóa tour_content */
                TourContent::select('*')
                            ->where('tour_info_id', $idTour)
                            ->delete();
                /* xóa tour_timetable */
                TourTimetable::select('*')
                            ->where('tour_info_id', $idTour)
                            ->delete();
                /* xóa tour_option và tour_price */
                $arrayIdOption  = [];
                $arrayIdPrice   = [];
                foreach($infoTour->options as $option){
                    $arrayIdOption[]    = $option->id;
                    foreach($option->prices as $price){
                        $arrayIdPrice[] = $price->id;
                    }
                }
                TourPrice::select('*')->whereIn('id', $arrayIdPrice)->delete();
                TourOption::select('*')->whereIn('id', $arrayIdOption)->delete();
                /* xóa relation tour_location */
                $arrayIdTourLocation    = [];
                foreach($infoTour->locations as $location) $arrayIdTourLocation[] = $location->id;
                RelationTourLocation::select('*')->whereIn('id', $arrayIdTourLocation)->delete();
                /* xóa tour_staff */
                $arrayIdStaff           = [];
                foreach($infoTour->staffs as $staff) $arrayIdStaff[] = $staff->id;
                RelationTourStaff::select('*')->whereIn('id', $arrayIdStaff)->delete();
                /* xóa tour_partner */
                $arrayIdPartner         = [];
                foreach($infoTour->partners as $partner) $arrayIdPartner[] = $partner->id;
                RelationTourPartner::select('*')->whereIn('id', $arrayIdPartner)->delete();
                /* delete files - dùng removeSliderById cũng remove luôn cả gallery */
                if(!empty($infoTour->files)){
                    foreach($infoTour->files as $file) AdminSliderController::removeSliderById($file->id);
                }
                /* xóa seo */
                Seo::find($infoTour->seo->id)->delete();
                /* xóa tour_info */
                $infoTour->delete();
                DB::commit();
                return true;
            } catch (\Exception $exception){
                DB::rollBack();
                return false;
            }
        }
    }
}
