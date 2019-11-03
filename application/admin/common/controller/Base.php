<?php
/**
 * 后台公共控制器
 */

namespace app\admin\common\controller;
use app\common\controller\Access;//导入权限类
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
		//判断权限认证
    	Access::adminHasPower($this->key());

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
	
	//保存管理员信息
	protected function saveUserInfo()
	{
		$userInfo = UserModel::get(Session::get('admin_id'));
		$this->view->assign('userInfo',$userInfo);
	}

	//获取当前模块/控制器/方法字符串
	public function key()
	{
		return request()->module().'/'.request()->controller().'/'.request()->action();
	}
	
}
?>