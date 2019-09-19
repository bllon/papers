<?php
namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\admin\common\model\User as UserModel;
use app\admin\common\model\School;
use app\admin\common\model\Post as PostModel;
use think\facade\Request;
use think\facade\Session;
use think\Db;


class User extends Base
{
	//后台登录界面
	public function login()
	{	
		$this->view->assign('title','管理员登录');
		return $this->view->fetch('login');
	}
	
	//验证后台登录
	public function checkLogin()
	{
		
		$data = Request::param();
		
		$map[] = ['email','=',$data['email']];
		$map[] = ['password','=',sha1($data['password'])];
		
		$result = UserModel::where($map)->find();
		if($result){
			Session::set('admin_id',$result['id']);
			Session::set('admin_name',$result['username']);
			Session::set('admin_level',$result['is_admin']);
			Session::set('school',$result['school_name']);
			
			$this->success('登陆成功','paper/lunwenList');
		}else{
			$this->error('登录失败');
		}
	}
	
	public function register()
	{
		return $this->view->fetch('register');
	}
	
	
	//注册用户
	public function doRegister()
	{
		
		$data = Request::param();
		$res = $this->validate($data,'app\admin\common\validate\User');
		if(true !== $res){
			$this->error($res);
		}
		
		if(UserModel::create($data)){
			$data['school_admin'] = $data['username'];
			
			if(School::create($data)){
				$this->success('注册成功','user/login');
			}else{
				$this->error('注册失败');
			}	
		}else{
			$this->error('注册失败');
		}
	}
	
	//用户设置
	public function setting()
	{
		$this->isLogin();
		
		$userInfo = UserModel::get(Session::get('admin_id'));
		$this->view->assign('userInfo',$userInfo);
		$this->view->assign('navActive','6');
		return $this->view->fetch('setting');
	}
	
	public function saveSetting()
	{
		
		$this->isLogin();
		$data = Request::param();

		if($data['password'] == $data['pass'] || sha1($data['password']) == $data['pass']){

			unset($data['password']);
		}else{
			$data['password'] = sha1($data['password']);

		}
		unset($data['pass']);

		//设置文件目录
		$imgPath = "uploads/user_img/";
		
		if($_FILES['user_img']['size'] !== 0){
			
			$user_img = Request::file('user_img');
		
			$info = $user_img->move($imgPath);
			
			if($info){
				$filepath = "uploads/user_img/".$info->getSaveName();
				$image = \think\Image::open($filepath);
				$image->thumb(128,128)->save($filepath);
				$data['user_img'] = "/uploads/user_img/".$info->getSaveName();
			}else{
				$this->error($info->getError());
			}
		}

		// var_dump($data);exit;
		
		if(UserModel::where('id',Session::get('admin_id'))->update($data)){
			$this->success('保存成功');
		}else{
			$this->error('保存失败');
		}
	}

	//文贴列表
	public function putList()
	{
		//判断用户是否登录
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/user/putList');
		
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
		if(!Session::has('admin_id')){
			return ['status'=>-1,'message'=>'你还没有登录'];
		}
		
		$res = $this->hasPower(Session::get('admin_id'), 'admin/user/putList',true);
		if(false == $res){
			return ['status'=>0,'message'=>'没有此权限'];
		}
		
		if(Session::get('admin_level') !== 1){
			return ['status'=>-1,'message'=>'你不是超级管理员'];
		}
		
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
		$this->isLogin();
		$this->is_admin();
		$this->hasPower(Session::get('admin_id'), 'admin/user/putList');
		
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
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/user/putList');
		
		$data = Request::param();
		$data['writer'] = Session::get('admin_name');
		$data['grade'] = Session::get('admin_level');
		$data['user_id'] = Session::get('admin_id');
		$res = $this->validate($data,'app\admin\common\validate\Post');
		if(true !== $res){
			$this->error($res);
		}
		if(PostModel::update($data)){
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}
	
	//发布文帖
	public function putPost()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/user/putList');
		
		$this->view->assign('title','发布贴子');
		$this->view->assign('navActive','6');
		return $this->view->fetch('putPost');
	}
	
	//执行发布文帖
	public function doPutPost()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/user/putList');
		
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
	
	//退出登录
	public function logout()
	{
		//1.清除session
		Session::delete('admin_id');
		Session::delete('admin_name');
		Session::delete('admin_level');
		Session::delete('school');
		//2.退出登录并跳转到登录页面
		$this->success('退出成功','admin/user/login');
	}
	

}
?>