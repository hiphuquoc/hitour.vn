<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seo;
use App\Models\Blogger;
use App\Models\Contentspin;
use App\Models\Keyword;
use App\Services\BuildInsertUpdateModel;
use Illuminate\Support\Facades\Cookie;

class AdminToolSeoController extends Controller {

    public function __construct(BuildInsertUpdateModel $BuildInsertUpdateModel){
        $this->BuildInsertUpdateModel   = $BuildInsertUpdateModel;
    }

    public function listBlogger(Request $request){
        $params         = [];
        if(!empty($request->get('search_name'))) $params['search_name'] = $request->get('search_name');
        $viewPerPage    = Cookie::get('viewBloggers') ?? 50;
        $list           = Blogger::select('*')
                            ->when(!empty($params['search_name']), function($query) use($params){
                                $query->where('name', 'like', '%'.$params['search_name'].'%')
                                        ->orWhere('url', 'like', '%'.$params['search_name'].'%')
                                        ->orWhere('email_manager', 'like', '%'.$params['search_name'].'%');
                            })
                            ->orderBy('id', 'DESC')
                            ->paginate($viewPerPage);
        return view('admin.toolSeo.listBlogger', compact('list', 'viewPerPage', 'params'));
    }

    public function addBlogger(Request $request){
        $result['flag']     = false;
        $result['content']  = null;
        if(!empty($request->get('dataForm'))){
            $dataForm   = [];
            foreach($request->get('dataForm') as $tmp){
                $dataForm[$tmp['name']] = $tmp['value'];
            }
            /* insert blogger_info */
            $insertBloggerInfo      = $this->BuildInsertUpdateModel->buildArrayTableBloggerInfo($dataForm);
            $idBloggerInfo          = Blogger::insertItem($insertBloggerInfo);
            if(!empty($idBloggerInfo)){ /* -> update thành công */
                $infoBlogger        = Blogger::find($idBloggerInfo);
                $result['flag']     = true;
                $result['content']  = view('admin.toolSeo.oneRowBlogger', ['item' => $infoBlogger, 'no' => '-', 'style' => 'background:rgba(0, 123, 255, 0.12);'])->render();
            }
            return json_encode($result);
        }
        
    }

    public function deleteBlogger(Request $request){
        if(!empty($request->get('id'))){
            Blogger::select('*')->where('id', $request->get('id'))->delete();
            echo true;
        }
        echo false;
    }

    public function listAutoPost(Request $request){
        $params         = [];
        if(!empty($request->get('search_name'))) $params['search_name'] = $request->get('search_name');
        $viewPerPage    = Cookie::get('viewAutoPost') ?? 50;
        $list           = Seo::select('*')
                            ->when(!empty($params['search_name']), function($query) use($params){
                                $query->where('title', 'like', '%'.$params['search_name'].'%')
                                        ->orWhere('slug', 'like', '%'.$params['search_name'].'%');
                            })
                            ->orderBy('type', 'DESC')
                            ->with('keywords', 'contentspin')
                            ->paginate($viewPerPage);
        return view('admin.toolSeo.listAutoPost', compact('list', 'viewPerPage', 'params'));
    }

    public function loadRowAutoPost(Request $request){
        $result         = null;
        if(!empty($request->get('id'))){
            $item       = Seo::select('*')
                            ->where('id', $request->get('id'))
                            ->with('keywords', 'contentspin')
                            ->first();
            $result     = view('admin.toolSeo.oneRowAutoPost', compact('item'));
        }
        echo $result;
    }

    public function loadFormContentspin(Request $request){
        $result         = null;
        if(!empty($request->get('id'))){
            $item       = Seo::select('*')
                            ->where('id', $request->get('id'))
                            ->with('contentspin')
                            ->first();
            $result     = view('admin.toolSeo.formContentspin', compact('item'));
            
        }
        echo $result;
    }

    public function createContentspin(Request $request){
        if(!empty($request->get('dataForm'))){
            $dataForm   = [];
            foreach($request->get('dataForm') as $tmp){
                $dataForm[$tmp['name']] = $tmp['value'];
            }
            /* thêm vào CSDL */
            Contentspin::select('*')
                ->where('seo_id', $dataForm['seo_id'])
                ->delete();
            Contentspin::insertItem([
                'seo_id'        => $dataForm['seo_id'],
                'title'         => $dataForm['title'],
                'description'   => $dataForm['description'],
                'content'       => $dataForm['content']
            ]);
            /* bật auto_post (điều kiện kiểm tra trong hàm) */
            self::handleChangeAutoPost($dataForm['seo_id'], 1);
        }
        echo $dataForm['seo_id'];
    }

    public function loadFormKeyword(Request $request){
        $result         = null;
        if(!empty($request->get('id'))){
            $item       = Seo::select('*')
                            ->where('id', $request->get('id'))
                            ->with('keywords')
                            ->first();
            $result     = view('admin.toolSeo.formKeyword', compact('item'));
        }
        echo $result;
    }

    public function createKeyword(Request $request){
        $result                 = null;
        if(!empty($request->get('id')&&!empty($request->get('strKeyword')))){
            $idSeo              = $request->get('id');
            /* kiểm tra xem có phải thêm từ khóa lần đầu? */
            $tmp                = Keyword::select('*')
                                    ->where('seo_id', $idSeo)
                                    ->first();
            $flagFirst          = !empty($tmp) ? false : true;
            /* thêm từ khóa */
            $arrayKeyword       = explode(',', $request->get('strKeyword'));
            foreach($arrayKeyword as $keyword){
                if(!empty($keyword)){
                    $tmp        = Keyword::select('*')
                                    ->where('seo_id', $idSeo)
                                    ->where('keyword', $keyword)
                                    ->first();
                    if(empty($tmp)){
                        $idKeyword = Keyword::insertItem([
                            'seo_id'    => $idSeo,
                            'keyword'   => $keyword
                        ]);
                        $result     .= '<span id="keyword_'.$idKeyword.'" class="bg-primary badgeKeyword">
                                            '.$keyword.'
                                            <i class="fa-solid fa-xmark" onClick="deleteKeyword('.$idKeyword.');"></i>
                                        </span>';
                        /* bật auto_post (điều kiện kiểm tra trong hàm) */
                        if($flagFirst==true) self::handleChangeAutoPost($idSeo, 1);
                    }
                }
            }
        }
        echo $result;
    }

    public function deleteKeyword(Request $request){
        $flag       = false;
        if(!empty($request->get('idKeyword'))){
            $infoKeyword    = Keyword::select('*')
                                ->where('id', $request->get('idKeyword'))
                                ->first();
            /* xoá keyword */
            $flag           = $infoKeyword->delete();
            /* kiểm tra nếu xóa từ khóa cuối cùng => chuyển auto_post về off */
            $idSeo          = $infoKeyword->seo_id ?? 0;
            $tmp            = Keyword::select('*')
                                ->where('seo_id', $idSeo)
                                ->first();
            if(empty($tmp)) self::handleChangeAutoPost($idSeo, 0);
        }
        echo $flag;
    }

    public function changeAutoPost(Request $request){
        $flag       = false;
        if(!empty($request->get('id'))){
            $flag   = Seo::updateItem($request->get('id'), ['auto_post' => $request->get('auto_post')]);
        }
        echo $flag;
    }

    public static function handleChangeAutoPost($idSeo, $status = 1){
        if(!empty($idSeo)){
            if($status==1){ /* bật */
                $tmp = Seo::select('*')
                        ->where('id', $idSeo)
                        ->with('keywords', 'contentspin')
                        ->first();
                /* bật auto_post nếu đủ cả keywords và contentspin */
                if(!empty($tmp->keywords)&&$tmp->keywords->isNotEmpty()&&!empty($tmp->contentspin)){
                    Seo::updateItem($idSeo, ['auto_post' => 1]);
                }
            }else { /* tắt */
                Seo::updateItem($idSeo, ['auto_post' => 0]);
            }
        }
    }
}
