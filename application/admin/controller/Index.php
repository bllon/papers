<?php
namespace app\admin\controller;
use app\admin\common\controller\Base;
use think\facade\Session;

class Index extends Base
{
	public function index()
	{
		//判断用户是否登录
		$this->isLogin();
		$this->view->assign('navActive','0');
		return $this->view->fetch('index');
	}
	
	
}
?>