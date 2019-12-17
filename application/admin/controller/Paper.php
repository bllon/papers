<?php
namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\admin\common\model\Paper as PaperModel;
use app\admin\common\model\Cate;
use app\admin\common\model\Lunwen;
use app\admin\common\model\Rank;
use app\admin\common\model\Sele;

use think\facade\Request;
use think\facade\Session;
use think\Db;

class Paper extends Base
{
	
	//论文列表
	public function lunwenList(){
		
//		$lunwenList = Lunwen::where('school_name',Session::get('school'))->select();
		$lunwenList = Db::table('paper_lunwen')
								->where('school_name',Session::get('school'))
								->order('addtime','asc')
								->paginate(10);
		
		$this->view->assign('lunwenList',$lunwenList);
		$this->view->assign('title','论文列表');
		$this->view->assign('navActive','1');
		return $this->view->fetch('lunwenList');
	}
	
	//论文分类
	public function rankList(){
		
		$rankList = Rank::where('school_name',Session::get('school'))->select();
		$seleList = Sele::where('school_name',Session::get('school'))->select();
		
		$this->view->assign('rankList',$rankList);
		$this->view->assign('seleList',$seleList);
		$this->view->assign('title','论文分类');
		$this->view->assign('navActive','1');
		return $this->view->fetch('rankList');
	}
	
	//论文搜索
	public function search(){
		
		$school = Session::get('school');
		$map = [];
		if(!Session::get('admin_level')){
			$map[] = ['school_name','=',$school];
		}
		$lunwenList = [];
		if(Request::isGet()){
			
			$chance = Request::param('chance');
			$keywords = Request::param('keywords');
			if(trim($keywords) == ''){
				$lunwenList = [];
				
			}else{
				if($chance == 0){
					//按标题查询
	//				$data = Db::query("SELECT * FROM paper_lunwen WHERE MATCH (lunwen_title) AGAINST ('".$keywords."');");
	
					$map[] = ['lunwen_title','like','%'.$keywords.'%'];
					$lunwenList = Db::table('paper_lunwen')->where($map)->paginate(5);
					
					if(count($lunwenList) == 0){
						$lunwenList = [];
					}
				}else{
					//按作者查询
					$map[] = ['writer','=',$keywords];
					$lunwenList = Db::table('paper_lunwen')->where($map)->paginate(5);
					if(count($lunwenList) == 0){
						$lunwenList = [];
					}
					
				}
			}
			$this->view->assign('chance',$chance);
			$this->view->assign('keywords',$keywords);
		}
		$this->view->assign('lunwenList',$lunwenList);
		$this->view->assign('title','论文搜索');
		$this->view->assign('navActive','1');
		return $this->view->fetch('search');
	}
	
	//论文上传
	public function uploadLunwen(){
		
		$this->view->assign('title','论文上传');
		$rankList = Rank::where('school_name',Session::get('school'))->select();
		
		$map = [];
		$map[] = ['school_name','=',Session::get('school')];
		
		if(0 !== count($rankList)){
			$map[] = ['sele_type','=',$rankList[0]['rank_name']];
		}
		
		$seleList = Sele::where($map)->select();
		$this->view->assign('rankList',$rankList);
		$this->view->assign('seleList',$seleList);
		$this->view->assign('navActive','1');
		return $this->view->fetch('uploadLunwen');
	}
	
	//获取分类
	public function getSele()
	{

		$rankType = Request::param('rank_type');
		$map = [];
		$map[] = ['school_name','=',Session::get('school')];
		$map[] = ['sele_type','=',$rankType];
		$seleList = Sele::where($map)->select();
		return $seleList;
	}
	
	//执行论文上传
	public function doUploadLunwen(){

		$data = Request::param();
		$reul = 'app\admin\common\validate\Lunwen';
		$res = $this->validate($data,$reul);
		if(true !== $res){
			$this->error($res);
		}
		
		$data['addtime'] = strtotime($data['addtime']);
		
		//上传标题图片
		$titlePath = "uploads/lunwen/title_img/";
		$title_img = Request::file('lunwen_img');
		$info = $title_img->move($titlePath);
		if($info){
			//成功上传，保存缩略图
			$filepath = "uploads/lunwen/title_img/".$info->getSaveName();
			$image = \think\Image::open($filepath);
			$image->thumb(3000, 2000,\think\Image::THUMB_SCALING)->save($filepath);
			$data['lunwen_img'] = "/uploads/lunwen/title_img/".$info->getSaveName();
			
		}else{
			$this->error($info->getError());
		}
		
		
		
		//上传论文图片
//		$thumb_img = Request::file('thumb_img');
//		
//		foreach($thumb_img as $img){
//			$lunwenPath = "uploads/lunwen/thumb/";
//			$info = $img->move($lunwenPath);
//			if($info){
//				$filepath = "uploads/lunwen/thumb/".$info->getSaveName();
//				$image = \think\Image::open($filepath);
//				$image->thumb(3000, 2000,\think\Image::THUMB_SCALING)->save($filepath);
//				$data['thumb_img'][] = "/uploads/lunwen/thumb/".$info->getSaveName();
//			}else{
//				$this->error($info->getError());
//			}
//		}
		
		//上传论文文件
		$paperPath = "uploads/lunwen/pdf/";
		$title_file = Request::file('lunwen_file');
		$info = $title_file->move($paperPath);
		if($info){
			$data['lunwen_file'] = "/uploads/lunwen/pdf/".$info->getSaveName();	
		}else{
			$this->error($info->getError());
		}
		
		
		$data['major'] = $data['lunwen_rank'];
		$data['school_name'] = Session::get('school');
		if(Lunwen::create($data)){
			$this->success('上传成功','paper/uploadLunwen');
		}else{
			$this->error('上传失败');
		}
		
	}
	
	//论文公开权限
	public function giveTerms()
	{
		
		$id = Request::param('id');
		$key = Request::param('key');
		$data = [
			'id'=>$id,
			'lunwen_terms'=>$key,
		];
		if(Lunwen::update($data)){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}

	//编辑论文
	public function editPaper()
	{
		$data = Request::param();
		$paperInfo = Lunwen::where('id',$data['id'])->find();

		$this->view->assign('title','编辑论文');
		$rankList = Rank::where('school_name',Session::get('school'))->select();
		
		$map = [];
		$map[] = ['school_name','=',Session::get('school')];
		
		if(0 !== count($rankList)){
			$map[] = ['sele_type','=',$rankList[0]['rank_name']];
		}
		
		$seleList = Sele::where($map)->select();
		$this->view->assign('rankList',$rankList);
		$this->view->assign('seleList',$seleList);
		$this->view->assign('paperInfo',$paperInfo);
		$this->view->assign('navActive','1');

		return $this->view->fetch('editPaper');
	}

	//保存编辑论文
	public function doEditPaper()
	{

		$data = Request::param();

		$reul = 'app\admin\common\validate\Lunwen';
		$res = $this->validate($data,$reul);
		if(true !== $res){
			$this->error($res);
		}
		
		$data['addtime'] = strtotime($data['addtime']);
		
		$file = Request::file();

		if(!empty($file)){

			if(!empty($file['lunwen_img'])){
				//上传标题图片
				$titlePath = "uploads/lunwen/title_img/";

				$title_img = Request::file('lunwen_img');
				
				$info = $title_img->move($titlePath);
				if($info){
					//成功上传，保存缩略图
					$filepath = "uploads/lunwen/title_img/".$info->getSaveName();
					$image = \think\Image::open($filepath);
					$image->thumb(3000, 2000,\think\Image::THUMB_SCALING)->save($filepath);
					$data['lunwen_img'] = "/uploads/lunwen/title_img/".$info->getSaveName();
					
				}else{
					$this->error($info->getError());
				}
			}

			
			
			
			
			//上传论文图片
	//		$thumb_img = Request::file('thumb_img');
	//		
	//		foreach($thumb_img as $img){
	//			$lunwenPath = "uploads/lunwen/thumb/";
	//			$info = $img->move($lunwenPath);
	//			if($info){
	//				$filepath = "uploads/lunwen/thumb/".$info->getSaveName();
	//				$image = \think\Image::open($filepath);
	//				$image->thumb(3000, 2000,\think\Image::THUMB_SCALING)->save($filepath);
	//				$data['thumb_img'][] = "/uploads/lunwen/thumb/".$info->getSaveName();
	//			}else{
	//				$this->error($info->getError());
	//			}
	//		}
			
			if(!empty($file['lunwen_file'])){
				//上传论文文件
				$paperPath = "uploads/lunwen/pdf/";
				$title_file = Request::file('lunwen_file');

				$info = $title_file->move($paperPath);
				if($info){
					$data['lunwen_file'] = "/uploads/lunwen/pdf/".$info->getSaveName();	
				}else{
					$this->error($info->getError());
				}
			}
		}
		
		
		$data['major'] = $data['lunwen_rank'];
		$data['school_name'] = Session::get('school');

		if(Lunwen::update($data)){
			$this->success('保存成功');
		}else{
			$this->error('保存失败');
		}
	}
	
	//删除论文
	public function deleLunwen()
	{
		
		$id = Request::param('id');
		if(Lunwen::destroy($id)){
			return ['status'=>1,'message'=>'删除成功'];
		}else{
			return ['status'=>0,'message'=>'删除失败'];
		}
	}
	
	//添加一级分类
	public function addRank()
	{
		
		if(Request::isAjax()){
			$rank = Request::param('rank_type');
			if(trim($rank) == '' || !isset($rank)){
				return ['status'=>0,'message'=>'字段不符合要求'];
			}
			$data = [];
			$data['rank_name'] = $rank;
			$data['school_name'] = Session::get('school');
			
			if(Rank::create($data)){
				return ['status'=>1,'message'=>$rank];
			}else{
				return ['status'=>0,'message'=>'添加失败'];
			}
		}else{
			return ['status'=>-1,'message'=>'请求异常'];
		}
		
		
	}
	
	//添加二级分类
	public function addSele()
	{
		
		if(Request::isAjax()){
			$seleName = Request::param('sele_name');
			$seleType = Request::param('sele_type');

			if(trim($seleName) == '' || !isset($seleName)){
				return ['status'=>0,'message'=>'字段不符合要求'];
			}
			$data = [];
			$data['sele_name'] = $seleName;
			$data['sele_type'] = $seleType;
			$data['school_name'] = Session::get('school');
			
			if(Sele::create($data)){
				return ['status'=>1,'message'=>$seleName];
			}else{
				return ['status'=>0,'message'=>'添加失败'];
			}
		}else{
			return ['status'=>-1,'message'=>'请求异常'];
		}
				
	}
	
	//删除一级分类
	public function deleRank()
	{
		
		$rankId = Request::param('id');
		
		if(null !== $rankId){
			if(Rank::destroy($rankId)){
				return ['status'=>1,'message'=>'删除成功'];
			}else{
				return ['status'=>0,'message'=>'删除失败'];
			}
		}
	}
	
	//删除二级分类
	public function deleSele()
	{
		
		$seleId = Request::param('id');
		
		if(null !== $seleId){
			if(Sele::destroy($seleId)){
				return ['status'=>1,'message'=>'删除成功'];
			}else{
				return ['status'=>0,'message'=>'删除失败'];
			}
		}
	}
	
	//编辑一级分类
	public function editRank()
	{
		
		if(Request::isAjax()){
			$id = Request::param('id');
			$rank = Request::param('rank_type');
			$default_rank = Request::param('default_rank');
			if(trim($rank) == $default_rank){
				return ['status'=>1,'message'=>$default_rank];
			}
			if(trim($rank) == '' || !isset($rank)){
				return ['status'=>0,'message'=>'字段不符合要求'];
			}
			//修改所有的论文分类
			//....
			
			$seleList = Sele::where('school_name',Session::get('school'))->where('sele_type',$default_rank)->select();
			foreach($seleList as $sele){
				$data = [];
				$data['sele_type'] = $rank;
				$data['school_name'] = Session::get('school');
				if(!Sele::where('id',$sele['id'])->update($data)){
					return ['status'=>0,'message'=>'修改失败'];
				}
			}
			$data = [];
			$data['rank_name'] = $rank;
			$data['school_name'] = Session::get('school');
			$rankModel = Rank::get($id);
			
			if($rankModel->force()->save($data)){
				return ['status'=>1,'message'=>$rank];	
			}else{
				return ['status'=>0,'message'=>'修改失败'];
			}
		}else{
			return ['status'=>-1,'message'=>'请求异常'];
		}
	}
	//编辑二级分类
	public function editSele()
	{
		
		if(Request::isAjax()){
			$id = Request::param('id');
			$rank = Request::param('rank_type');
			$default_rank = Request::param('default_rank');
			if(trim($rank) == $default_rank){
				return ['status'=>1,'message'=>$default_rank];
			}
			if(trim($rank) == '' || !isset($rank)){
				return ['status'=>0,'message'=>'字段不符合要求'];
			}
			//修改所有的论文分类
			//....
			
			$seleList = Sele::where('school_name',Session::get('school'))->where('sele_type',$default_rank)->select();
			foreach($seleList as $sele){
				$data = [];
				$data['sele_type'] = $rank;
				$data['school_name'] = Session::get('school');
				if(!Sele::where('id',$sele['id'])->update($data)){
					return ['status'=>0,'message'=>'修改失败'];
				}
			}
			$data = [];
			$data['rank_name'] = $rank;
			$data['school_name'] = Session::get('school');
			$rankModel = Rank::get($id);
			
			if($rankModel->force()->save($data)){
				return ['status'=>1,'message'=>$rank];	
			}else{
				return ['status'=>0,'message'=>'修改失败'];
			}
		}else{
			return ['status'=>-1,'message'=>'请求异常'];
		}
	}
}
?>