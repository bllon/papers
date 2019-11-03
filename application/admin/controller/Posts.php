<?php
namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\admin\common\model\User as UserModel;
use app\admin\common\model\School;
use app\admin\common\model\Post as PostModel;
use think\facade\Request;
use think\facade\Session;
use think\Db;


class Posts extends Base
{

	//文贴列表
	public function putList()
	{
		
		$postList = Db::table('paper_post')->order('create_time','asc')->paginate(10);
		$this->view->assign('navActive','6');
		$this->view->assign('title','贴子列表');
		$this->view->assign('empty','没有贴子');
		$this->view->assign('postList',$postList);
		return $this->view->fetch('postList');
	}

	//删除文贴
	public function delepost()
	{
		
		$id = Request::param('id');
		if(PostModel::destroy($id)){
			return ['status'=>1,'message'=>'删除成功'];
		}else{
			return ['status'=>0,'message'=>'删除失败'];
		}
	}

	//编辑文贴
	public function editpost()
	{
		
		$id = Request::param('id');
		$postInfo = PostModel::get($id);
		$this->view->assign('title','编辑贴子');
		$this->view->assign('postInfo',$postInfo);
		$this->view->assign('navActive','6');
		return $this->view->fetch('editPost');
	}

	//保存编辑
	public function savePost()
	{
		
		$data = Request::param();
		$data['writer'] = Session::get('admin_name');
		$data['grade'] = Session::get('admin_level');
		$data['user_id'] = Session::get('admin_id');
		$res = $this->validate($data,'app\admin\common\validate\Post');
		if(true !== $res){
			$this->error($res);
		}
		if(PostModel::update($data)){
			$this->success('修改成功','putList');
		}else{
			$this->error('修改失败');
		}
	}
	
	//发布文帖
	public function putPost()
	{
		
		$this->view->assign('title','发布贴子');
		$this->view->assign('navActive','6');
		return $this->view->fetch('putPost');
	}
	
	//执行发布文帖
	public function doPutPost()
	{
		
		$data = Request::param();
		$data['writer'] = Session::get('admin_name');
		$data['grade'] = Session::get('admin_level');
		$data['user_id'] = Session::get('admin_id');
		$res = $this->validate($data,'app\admin\common\validate\Post');
		if(true !== $res){
			$this->error($res);
		}
		if(PostModel::create($data)){
			$this->success('发贴成功');
		}else{
			$this->error('发贴失败');
		}
	}
	
}
?>