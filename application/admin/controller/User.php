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
	
	//后台注册
	public function register()
	{
		return $this->view->fetch('register');
	}
	
	
	//验证后台注册
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
	
	//管理员设置
	public function setting()
	{
		
		$userInfo = UserModel::get(Session::get('admin_id'));
		$this->view->assign('userInfo',$userInfo);
		$this->view->assign('navActive','6');
		return $this->view->fetch('setting');
	}
	
	//保存管理员设置
	public function saveSetting()
	{
		
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