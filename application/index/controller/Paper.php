<?php
/**
*论文控制器
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;

class Paper extends Base
{
	//获取首页分页论文
    public function getPage()
    {
    	$page = Request::param('currentPage') ? Request::param('currentPage'):1;
    	$page = intval($page);
    	Cookie::set('currentPage',$page);

    	$map = [];
		$map2 = [];
		//显示公开论文
		$map[] = ['lunwen_terms','=',1];

		$keywords = trim(Request::param('keywords'));

		//每页数量
		$num = Request::param('num');
		if(!empty($keywords)){
			//搜索sphinx
			require_once '../extend/sphinx/sphinxapi.php';
			$sph = new \SphinxClient();
			$sph->SetServer('localhost', 9312);
			//第二个参数，默认是*，要查询的索引名字
			$ret = $sph->Query($keywords, 'papers');

			//提取出所有文章id
			if(isset($ret['matches'])){
				$id = array_keys($ret['matches']);
			}else{
				$id = [];
			}

			$map[] = ['id','in',$id];
			$map2[] = ['id','in',$id];
		}

    	//没登陆
		if(Session::get('user_id') == null){
			
	    	$rank_name = Request::param('rank_name');
			
			
			if($rank_name != '全部论文'){
				
				$map[] = ['rank_type','=',$rank_name];
				
				$lunwenList = Db::table('paper_lunwen')
							->where($map)
							->order('addtime','asc')
							->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);

			}else{
				
				$lunwenList = Db::table('paper_lunwen')
							->where($map)
							->order('addtime','asc')
							->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);
			}
			
		}else{
			//既显示公开论文，也显示学校的
			$map2[] = ['school_name','=',Session::get('user_school')];
			
	    	$rank_name = Request::param('rank_name');
			
			
			if($rank_name != '全部论文'){
				//条件3
				$map[] = ['rank_type','=',$rank_name];
				$map2[] = ['rank_type','=',$rank_name];
				
				$lunwenList = Db::table('paper_lunwen')
							->whereOr([$map,$map2])
							->order('addtime','asc')
							->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);
				
			}else{
				$lunwenList = Db::table('paper_lunwen')
							->whereOr([$map,$map2])
							->order('addtime','asc')
							->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);
			}
		}
		return ['status'=>1,'message'=>'成功获取分页','data'=>['list'=>$lunwenList->items(),'pages'=>$lunwenList->render()]];
    }


    //获取sele页分页论文
    public function getSelePage()
    {
    	// var_dump(Request::param());exit;
    	$page = Request::param('currentPage') ? Request::param('currentPage'):1;

    	Cookie::set('selePage',$page);

    	$map = [];
		$map2 = [];
		//显示公开论文
		$map[] = ['lunwen_terms','=',1];

		//每页数量
		$num = Request::param('num');

		$sele_name = Request::param('sele_name');

		//没登陆
		if(Session::get('user_id') == null){
			if($sele_name != '全部论文'){
				$map[] = ['lunwen_rank','=',$sele_name];
			}			
			
			$lunwenList = Db::table('paper_lunwen')
						->where($map)
						->order('addtime','desc')
						->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);
			
		}else{
			//既显示公开论文，也显示学校的
			$map2[] = ['school_name','=',Session::get('user_school')];	
			
			if($sele_name != '全部论文'){
				$map[] = ['lunwen_rank','=',$sele_name];
				$map2[] = ['lunwen_rank','=',$sele_name];
			}	

			$lunwenList = Db::table('paper_lunwen')
						->whereOr([$map,$map2])
						->order('addtime','desc')
						->paginate($num,false,['page'=>$page,'path'=>'javascript:AjaxPage([PAGE]);']);
				
		}				
	
		return ['status'=>1,'message'=>'成功获取分页','data'=>['list'=>$lunwenList->items(),'pages'=>$lunwenList->render()]];
    }
}