<?php
/**
 * 后台公共控制器
 */

namespace app\admin\common\controller;
use think\Controller;
use app\admin\common\model\User as UserModel;
use app\admin\common\model\Power;
use app\admin\common\model\Role;
use think\facade\Session;
use think\Db;


class Base extends Controller
{
	//初始化方法
	protected function initialize()
	{
		$this->saveUserInfo();
	}
	
	/**
	 * 检测用户是否登录
	 * 1.调用位置:	后台入口:admin.php/index/index()
	 */
	protected function isLogin()
	{
		if(!Session::has('admin_id')){
			$this->error('请先登录','admin/user/login');
		}
	}
	
	//检测是否是超级管理员
	protected function is_admin()
	{
		if(Session::get('admin_level') !== 1){
			$this->error('对不起，你没有此权限');
		}
	}
	
	
	protected function saveUserInfo()
	{
		$userInfo = UserModel::get(Session::get('admin_id'));
		$this->view->assign('userInfo',$userInfo);
	}
	
	/**
	 * 判断是否拥有权限
	 * @param $user_id 用户id
	 * @param $url	权限url
	 * @param $json 是否为接口调用
	 */
	protected function hasPower($user_id,$url,$json = false)
	{
		//查询用户的角色
		$role = UserModel::where('id',$user_id)->find();

		//角色名称
		$roleName = getRoleName($role['role_id']);

		//查询角色所拥有的的权限
		$power = Db::table('paper_role_power')
						->field('power_id')
						->where('name',$roleName)
						->find();
		$hasPower = explode(',', $power['power_id']);

		//查询所需要的权限
		$need = Db::table('paper_power')
						->field('id')
						->where('url',$url)
						->find();

		//判断有无权限，是否为接口调用
		if(!in_array($need['id'], $hasPower)){
			if(!$json){
				$this->error('对不起，你没有此权限');
			}else{
				return false;
			}	
		}else{
			if($json){
				return true;
			}	
		}
		
	}
}
?>