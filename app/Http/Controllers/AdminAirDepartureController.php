<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Upload;
use App\Http\Controllers\AdminSliderController;
use App\Models\AirDeparture;
use App\Models\Seo;
use App\Services\BuildInsertUpdateModel;
use App\Models\District;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\CheckSeo;

use App\Http\Requests\AirDepartureRequest;

class AdminAirDepartureController extends Controller {

    public function __construct(BuildInsertUpdateModel $BuildInsertUpdateModel){
        $this->BuildInsertUpdateModel  = $BuildInsertUpdateModel;
    }

    public function list(Request $request){
        $params         = [];
        /* Search theo tên */
        if(!empty($request->get('search_name'))) $params['search_name'] = $request->get('search_name');
        /* Search theo vùng miền */
        if(!empty($request->get('search_region'))) $params['search_region'] = $request->get('search_region');
        /* lấy dữ liệu */
        $list           = AirDeparture::getList($params);
        return view('admin.airDeparture.list', compact('list', 'params'));
    }

    public function view(Request $request){
        $id             = $request->get('id') ?? 0;
        $item           = AirDeparture::select('*')
                            ->where('id', $id)
                            ->with(['files' => function($query){
                                $query->where('relation_table', 'air_departure');
                            }])
                            ->with('seo')
                            ->first();
        $provinces      = Province::getItemByIdRegion($item->region_id ?? 0);
        $districts      = District::getItemByIdProvince($item->province_id ?? 0);
        $content        = null;
        if(!empty($item->seo->slug)){
            $content    = Storage::get(config('admin.storage.contentAirDeparture').$item->seo->slug.'.blade.php');
        }
        $message        = $request->get('message') ?? null; 
        $type           = !empty($item) ? 'edit' : 'create';
        $type           = $request->get('type') ?? $type;
        return view('admin.airDeparture.view', compact('item', 'type', 'content', 'provinces', 'districts', 'message'));
    }

    public function create(AirDepartureRequest $request){
        try {
            DB::beginTransaction();
            /* upload image */
            $dataPath               = [];
            if($request->hasFile('image')) {
                $name               = !empty($request->get('slug')) ? $request->get('slug') : time();
                $dataPath           = Upload::uploadThumnail($request->file('image'), $name);
            }
            /* insert page */
            $insertPage             = $this->BuildInsertUpdateModel->buildArrayTableSeo($request->all(), 'air_departure', $dataPath);
            $seoId                 = Seo::insertItem($insertPage);
            /* insert air_departure */
            $insertAirDeparture    = $this->BuildInsertUpdateModel->buildArrayTableAirDeparture($request->all(), $seoId);
            $idAirDeparture        = AirDeparture::insertItem($insertAirDeparture);
            /* lưu content vào file */
            $content            = $request->get('content') ?? null;
            $content            = AdminImageController::replaceImageInContentWithLoading($content);
            Storage::put(config('admin.storage.contentAirDeparture').$request->get('slug').'.blade.php', $content);
            /* insert slider và lưu CSDL */
            if($request->hasFile('slider')){
                $name           = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params         = [
                    'attachment_id'     => $idAirDeparture,
                    'relation_table'    => 'air_departure',
                    'name'              => $name
                ];
                AdminSliderController::uploadSlider($request->file('slider'), $params);
            }
            DB::commit();
            /* Message */
            $message        = [
                'type'      => 'success',
                'message'   => '<strong>Thành công!</strong> Đã tạo Điểm khởi hành bay mới'
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
        CheckSeo::dispatch($seoId);
        /* ===== END:: check_seo_info */
        $request->session()->put('message', $message);
        return redirect()->route('admin.airDeparture.view', ['id' => $idAirDeparture]);
    }

    public function update(AirDepartureRequest $request){
        try {
            DB::beginTransaction();
            $idAirDeparture         = $request->get('air_departure_id') ?? 0;
            /* upload image */
            $dataPath               = [];
            if($request->hasFile('image')) {
                $name               = !empty($request->get('slug')) ? $request->get('slug') : time();
                $dataPath           = Upload::uploadThumnail($request->file('image'), $name);
            }
            /* update page */
            $updatePage             = $this->BuildInsertUpdateModel->buildArrayTableSeo($request->all(), 'air_departure', $dataPath);
            Seo::updateItem($request->get('seo_id'), $updatePage);
            /* update AirDeparture */
            $updateAirDeparture    = $this->BuildInsertUpdateModel->buildArrayTableAirDeparture($request->all());
            AirDeparture::updateItem($idAirDeparture, $updateAirDeparture);
            /* lưu content vào file */
            $content            = $request->get('content') ?? null;
            $content            = AdminImageController::replaceImageInContentWithLoading($content);
            Storage::put(config('admin.storage.contentAirDeparture').$request->get('slug').'.blade.php', $content);
            /* insert slider và lưu CSDL */
            if($request->hasFile('slider')){
                $name               = !empty($request->get('slug')) ? $request->get('slug') : time();
                $params             = [
                    'attachment_id'     => $idAirDeparture,
                    'relation_table'    => 'air_departure',
                    'name'              => $name
                ];
                AdminSliderController::uploadSlider($request->file('slider'), $params);
            }
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
        return redirect()->route('admin.airDeparture.view', ['id'  => $idAirDeparture]);
    }

    public function delete(Request $request){
        if(!empty($request->get('id'))){
            try {
                DB::beginTransaction();
                $id         = $request->get('id');
                $info       = AirDeparture::select('*')
                                ->where('id', $id)
                                ->with(['files' => function($query){
                                    $query->where('relation_table', 'air_departure');
                                }])
                                ->with('seo')
                                ->first();
                /* delete bảng air_departure */
                AirDeparture::find($id)->delete();
                /* delete bảng seo */
                Seo::find($info->seo->id)->delete();
                /* xóa ảnh đại diện trong thư mục */
                $imageSmallPath     = Storage::path(config('admin.images.folderUpload').basename($info->seo->image_small));
                if(file_exists($imageSmallPath)) @unlink($imageSmallPath);
                $imagePath          = Storage::path(config('admin.images.folderUpload').basename($info->seo->image));
                if(file_exists($imagePath)) @unlink($imagePath);
                /* delete files */
                if(!empty($info->files)){
                    foreach($info->files as $file) AdminSliderController::removeSliderById($file->id);
                }
                DB::commit();
                return true;
            } catch (\Exception $exception){
                DB::rollBack();
                return false;
            }
        }
    }
}
