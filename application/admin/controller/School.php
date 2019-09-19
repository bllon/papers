<?php
namespace app\admin\controller;
use app\admin\common\controller\Base;
use app\admin\common\model\School as schoolModel;
use app\admin\common\model\User;
use think\facade\Request;
use think\Db;
use think\facade\Session;

class School extends Base
{
	//学校列表
	public function schoolList()
	{
		$this->isLogin();
		$this->is_admin();
		$this->hasPower(Session::get('admin_id'), 'admin/school/schoolList');
		
		$schoolList = Db::table('paper_school')->order('create_time','asc')->paginate(10);
		$this->view->assign('schoolList',$schoolList);
		$this->view->assign('title','学校列表');
		$this->view->assign('navActive','4');
		return $this->view->fetch('schoolList');
	}
	
	//学校授权
	public function giveTerms()
	{
		$this->isLogin();
		$this->is_admin();
		$this->hasPower(Session::get('admin_id'), 'admin/school/schoolList');
		
		$id = Request::param('id');
		$key = Request::param('key');
		$data = [
			'id'=>$id,
			'status'=>$key,
		];
		if(schoolModel::update($data)){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}
	
	//管理员列表
	public function userList()
	{
		$this->isLogin();
		$this->is_admin();
		$this->hasPower(Session::get('admin_id'), 'admin/school/schoolList');
		
		$userList = Db::table('paper_user')->where('is_admin',0)->order('create_time','asc')->paginate(10);
		$this->view->assign('userList',$userList);
		$this->view->assign('title','管理员列表');
		$this->view->assign('navActive','4');
		return $this->view->fetch('userList');
	}
	
	//冻结管理员
	public function userStatus()
	{
		$this->isLogin();
		$this->is_admin();
		$this->hasPower(Session::get('admin_id'), 'admin/school/schoolList');
		
		$id = Request::param('id');
		$key = Request::param('key');
		$data = [
			'id'=>$id,
			'status'=>$key,
		];
		if(User::update($data)){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}
}
?>