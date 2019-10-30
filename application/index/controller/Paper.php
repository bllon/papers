<?php
/**
*论文类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\common\model\Collect;
use app\common\model\Borrow;
use app\common\model\Lunwen;
use app\common\model\Rank;
use app\common\model\Sele;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;

class Paper extends Base
{
	//论文详情
	public function paperDetail()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/paperDetail');

		$id = Request::param('id');
		
		//获取缓存文件
		$content = $this->getCacheHtml(['id'=>$id]);
		if($content){
			return $content;
		}


		
		$paperInfo = Lunwen::get(function($query) use ($id){
			$query->where('id',$id);
		});
//		var_dump($paperInfo);exit;

		//获取redis缓存论文
		// $content = Paper($id);
		if(null == $content){
			if($paperInfo['lunwen_file'] !== null){
				$paperInfo['content'] = parserPdf(substr($paperInfo['lunwen_file'],1));
				
				//设置缓存
				// setCache("paper:id:".$paperInfo['id'].":content",serialize(parserPdf(substr($paperInfo['lunwen_file'],1))),86400);
			}else{
				$arr = [];
				$arr[] = $paperInfo['content'];
				$paperInfo['content'] = $arr;
			}
		}else{
			$paperInfo['content'] = $content;
		}
		
		
		$this->view->assign('title',$paperInfo['lunwen_title']);
		$this->view->assign('paperInfo',$paperInfo);
//		$this->view->assign('paperfile',substr($paperInfo['lunwen_file'],1));
		return $this->buildHtml('paperDetail',['id'=>$id]);
//		return $this->view->fetch('paperDetail');
	}
	
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

    //收藏论文
	public function collect()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/collect');

		$data = Request::param();
		if($data['action'] == 0){
			if(Collect::create($data)){
				return ['status'=>1,'message'=>'收藏成功'];
			}else{
				return ['status'=>0,'message'=>'收藏失败'];
			}
		}else if($data['action'] == 1){
			unset($data['action']);
			if(Collect::destroy($data)){
				return ['status'=>1,'message'=>'取消收藏成功'];
			}else{
				return ['status'=>0,'message'=>'取消收藏失败'];
			}
		}	
	}

	//借阅论文
	public function borrow()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$data = Request::param();
		$borrow = Borrow::where('paper_id',$data['paper_id'])->find();
		if($borrow !== null){
			return ['status'=>-1,'message'=>'已被借走'];
		}
		
		if($data['action'] == 0){
			if(Borrow::create($data)){
				return ['status'=>1,'message'=>'预约成功,等待管理员通知'];
			}else{
				return ['status'=>0,'message'=>'借阅失败'];
			}
		}else if($data['action'] == 1){
			unset($data['action']);
			if(Borrow::destroy($data)){
				return ['status'=>1,'message'=>'取消借阅成功'];
			}else{
				return ['status'=>0,'message'=>'取消借阅失败'];
			}
		}	
	}

    //获取收藏和借阅状态
	public function paperStatu(){
		$id = Request::param('id');
		//是否收藏过
		$collect = Collect::where('user_id',Session::get('user_id'))->where('paper_id',$id)->find();
		$collectaction = 0;
		if($collect !==null){
			$collectaction = 1;
		}
		
		$borrow = Borrow::where('user_id',Session::get('user_id'))->where('paper_id',$id)->find();
		$borrowaction = 0;
		if($borrow !==null && $borrow['status']!==3){
			$borrowaction = 1;
		}

		$data = ['collectaction'=>$collectaction,'borrowaction'=>$borrowaction];
		return ['status'=>1,'message'=>json_encode($data)];
	}

    //添加论文笔记
	public function addNotes()
	{
		$data = Request::param();
		//判断用户有没有登录
		if(Session::get('user_id') == null){
			return ['status'=>0,'message'=>'请先登录'];
		}
		// return ['status'=>0,'message'=>'添加笔记失败'];
		$map = [];
		$map[] = ['consumer_id','=',Session::get('user_id')];
		$map[] = ['paper_id','=',$data['paper_id']];
		//查询论文的笔记数量
		$noteList = Db::table('paper_notes')->where($map)->select();
		if(count($noteList) >= 30){
			return ['status'=>0,'message'=>'单篇笔记数量不能超过30个'];
		}

		$data['consumer_id'] = Session::get('user_id');
		$data['create_time'] = time();

		if(Db::table('paper_notes')->insert($data)){
			return ['status'=>1,'message'=>'添加笔记成功'];
		}else{
			return ['status'=>0,'message'=>'添加笔记失败'];
		}
	}

	//获取论文笔记
	public function paperNotes()
	{
		//判断用户有没有登录
		if(Session::get('user_id') == null){
			return ['status'=>0,'message'=>'请先登录'];
		}

		$data = Request::param();
		$id = $data['paper_id'];
		$map = [];
		$map[] = ['consumer_id','=',Session::get('user_id')];
		$map[] = ['paper_id','=',$id];

		$noteList = Db::table('paper_notes')->where($map)->select();

		if($noteList){
			return ['status'=>1,'message'=>'获取笔记成功','data'=>json_encode($noteList)];
		}else{
			return ['status'=>0,'message'=>'获取笔记失败'];
		}
	}

	//添加阅读量
	public function incPv()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/paperDetail');
		$paperId = Request::param('id');
		$paper = Lunwen::where('id',$paperId)->setInc('pv');
		return "阅读使我快乐";
	}

	//处理论文上传
	public function savePaper()
	{
		$this->isLogin();

		if(Request::isPost()){
			//1.获取用户提交的文章信息
			$data = Request::param();
			$res = $this->validate($data,'app\common\validate\Paper');
			if(true !== $res){
				$this->error($res);
			}else{
				//验证成功
				//获取一下上传的图片信息
				$title_img = $_FILES['title_img'];
				$paper = $_FILES['paper'];
				
				//文件限制
				$fileConfig = [
					'title_img'=>[
						'maxSize'=>20000,
						'ext'=>['jpg','jpeg','png','gif'],
					],
					'paper'=>[
						'maxSize'=>2000000,
						'ext'=>['pdf'],
					]
				];
				
				$checkImg = checkFile($title_img,$fileConfig['title_img']);
				$checkPaper = checkFile($paper,$fileConfig['paper']);
				
				if(false == $checkImg['check']){
					$this->error($checkImg['message']);
				}
				if(false == $checkPaper['check']){
					$this->error($checkPaper['message']);
				}
				
				//设置文件目录
				$imgpath = "uploads/images/".date('Y/m/d',time());
				$paperpath = "uploads/paper/".date('Y/m/d',time());
				
				if(!file_exists($imgpath)){
					mkdir($imgpath,0777,true);
				}
				if(!file_exists($paperpath)){
					mkdir($paperpath,0777,true);
				}
				
				if(!move_uploaded_file($title_img['tmp_name'], $imgpath.'/'.md5($checkImg['name']).'.'.$checkImg['type'])){
					$this->error('上传图片失败');
				}
				if(!move_uploaded_file($paper['tmp_name'], $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'])){
					$this->error('上传文档失败');
				}
				
				$data['title_img'] = $imgpath.'/'.md5($checkImg['name']).'.'.$checkImg['type'];
				$data['file_path'] = $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'];
				
				//将数据写到数据表中
				if(Paper::create($data)){
					$this->success('上传成功');
				}
			}
			
		}else{
			$this->error('请求异常');
		}
	}


	//论文分类页面
	public function rank()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/rank');
		$seleList = Sele::all();
		$this->view->assign('seleList',$seleList);
		$this->view->assign('title','论文分类');
		$this->view->assign('active','1');
		return $this->view->fetch('rank');
	}

}