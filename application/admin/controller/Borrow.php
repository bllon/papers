<?php
namespace app\admin\controller;
use app\admin\common\controller\Base;
use app\admin\common\model\Borrow as borrowModel;
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Borrow extends Base
{
	//借阅列表
	public function borrowList()
	{
		//权限判断
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/borrow/borrowList');
		
		//两表联查，查出当前学校的借阅列表
		$borrowList = Db::table('paper_consumer')->alias('u')->join('paper_borrow b','b.user_id = u.id')->field('b.id,b.user_id,b.paper_id,b.status,b.create_time,b.update_time')->where('u.school_name',Session::get('school'))->paginate(10);
		$this->view->assign('borrowList',$borrowList);
		$this->view->assign('title','借阅列表');
		$this->view->assign('navActive','2');
		return $this->view->fetch('borrowList');
	}
	
	//确定借阅
	public function doborrow(){
		
		//权限判断
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/borrow/borrowList');
		
		$borrowId = Request::param('id');
		
		//完成借阅，修改状态
		$data = ['status'=>1];
		if(borrowModel::where('id',$borrowId)->update($data)){
			$borrowInfo = borrowModel::where('id',$borrowId)->find();

			//修改用户积分
			$map = [];
			$map[] = ['consumer_id','=',$borrowInfo['user_id']];
			$map[] = ['paper_id','=',$borrowInfo['paper_id']];
			$integral_Info = Db::table('paper_integral')->where($map)->find();
			if($integral_Info){
				$action = json_decode($integral_Info['action'],true);

				if($action[4] < 3){
					$action[4] += 1;

					$data = [
						'id' => $integral_Info['id'],
						'integral' => (float)($integral_Info['integral'] + 2),
						'action' => json_encode($action),
						'update_time' => time()
					];
					Db::table('paper_integral')->where($map)->update($data);
				}

			}

			$this->success('审核成功');
		}else{
			$this->error('审核失败,请联系超级管理员');
		}
	}
	
	//确定归还
	public function doreturn(){
		
		//权限判断
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/borrow/borrowList');
		
		$borrowId = Request::param('id');
		
		//完成归还，修改状态
		$data = ['status'=>3];
		if(borrowModel::where('id',$borrowId)->update($data)){
			$this->success('审核成功');
		}else{
			$this->error('审核失败,请联系超级管理员');
		}
	}
	
	//删除借阅信息
	public function deleborrow()
	{
		if(!Session::has('admin_id')){
			return ['status'=>-1,'message'=>'你还没有登录'];
		}
		
		//判断权限
		$res = $this->hasPower(Session::get('admin_id'), 'admin/borrow/borrowList',true);
		if(false == $res){
			return ['status'=>0,'message'=>'没有此权限'];
		}
		
		$id = Request::param('id');
		if(borrowModel::destroy($id)){
			return ['status'=>1,'message'=>'删除成功'];
		}else{
			return ['status'=>0,'message'=>'删除失败'];
		}
	}
}
?>