<?php
namespace app\admin\controller;
use app\admin\common\controller\Base;
use app\admin\common\model\Consumer as ConsumerModel;
use think\facade\Session;
use think\facade\Request;
use think\Db;

class Consumer extends Base
{
	public function consumerList()
	{
		//判断用户是否登录
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/consumer/consumerList');
		
		$consumerList = Db::table('paper_consumer')->where('school_name',Session::get('school'))->paginate(10);
		$this->view->assign('navActive','3');
		$this->view->assign('consumerList',$consumerList);
		return $this->view->fetch('consumerList');
	}
	
	
	//冻结用户账号
	public function banConsumer()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/consumer/consumerList');
		
		$consumerId = Request::param('id');		
		$data = ['status'=>0];
		if(null !== $consumerId){
			if(ConsumerModel::where('id',$consumerId)->update($data)){
				$this->success('冻结成功');
			}else{
				$this->error('冻结失败');
			}
		}
	}
	
	//恢复用户账号
	public function reConsumer()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/consumer/consumerList');
		
		$consumerId = Request::param('id');	
		$data = ['status'=>1];
		if(null !== $consumerId){
			if(ConsumerModel::where('id',$consumerId)->update($data)){
				$this->success('恢复成功');
			}else{
				$this->error('恢复失败');
			}
		}
	}
	
	
	//删除用户
	public function deleConsumer()
	{
		//权限判断
		$this->isLogin();
		$res = $this->hasPower(Session::get('admin_id'), 'admin/consumer/consumerList',true);
		if(false == $res){
			return ['status'=>0,'message'=>'没有此权限'];
		}
		
		
		$consumerId = Request::param('id');		
		if(null !== $consumerId){
			if(ConsumerModel::destroy($consumerId)){
				return ['status'=>1,'message'=>'删除成功'];
			}else{
				return ['status'=>0,'message'=>'删除失败'];
			}
		}
	}
	
	//搜索用户
	public function consumerSearch()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/consumer/consumerList');
		
		$school = Session::get('school');
		$map = [];
		if(!Session::get('admin_level')){
			$map[] = ['school_name','=',$school];
		}
		$consumerList = [];
		if(Request::isPost()){
			
			$chance = Request::param('chance');
			$keywords = Request::param('keywords');
			if(trim($keywords) == ''){
				$consumerList = [];
				
			}else{
				if($chance == 0){
					//按用户名查询
	//				$data = Db::query("SELECT * FROM paper_lunwen WHERE MATCH (lunwen_title) AGAINST ('".$keywords."');");
	
					$map[] = ['name','=',$keywords];
					$consumerList = ConsumerModel::where($map)->select();
					if(count($consumerList) == 0){
						$consumerList = [];
					}
				}else if($chance == 1){
					//按邮箱查询
					$map[] = ['email','=',$keywords];
					$consumerList = ConsumerModel::where($map)->select();
					if(count($consumerList) == 0){
						$consumerList = [];
					}
				}
			}
		}
		
		$this->view->assign('title','用户搜索');
		$this->view->assign('navActive','3');
		$this->view->assign('consumerList',$consumerList);
		return $this->view->fetch('consumerSearch');
	}
}
?>