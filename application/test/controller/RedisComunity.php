<?php
/*
**redis实现的聊天类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\index\controller\Comunity;
use app\index\controller\Lock;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;


class Index extends Base
{
	
	//创建一个房间
	public function createGroup(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/createGroup');		
		
		//随机生成房间名
		$groupName = Session::get('user_name').'__'.uniqid();
		
		$this->view->assign('title','创建房间');
		$this->view->assign('groupName',$groupName);
		return $this->view->fetch('createGroup');
	}
	
	//执行创建房间
	public function doCreateGroup(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/createGroup');

		//接收参数
		$data = Request::param();		
		
		$comunity = new Comunity;
		
		//创建一个社区，名为淘大小区
		$res = $comunity->createGroup($data['creator'],$data['groupName']);
		if(end($res)){
			
			$data = [
				'name'=>$data['groupName'],
				'creator'=>$data['creator'],
				'create_time'=>time()
			];			
			Db::name('paper_group')->data($data)->insert();

			$this->redirect('comunity',2,'创建成功');
		}else{
			$this->error('创建失败');
		}
	}
	
	//加入房间
	public function room(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/room');

		//获取房间id
		$id = Request::param('id');
		
		$comunity = new Comunity;
		$groupList = $comunity->getAllGroup();
		
		if(empty($groupList)|| !isset($groupList[$id]) || $groupList[$id] == null){
			echo "<script>alert('该房间不存在或已解散!');history.back();</script>";
			exit;
		}	

		//获取当前房间标题
		$roomTitle = $groupList[$id];
		
		//检查是否存在于黑名单
		$blackList = $comunity->BlackList($roomTitle);		
		
		if(in_array(Session::get('user_name'), $blackList)){
			echo "<script>alert('你已经被请出房间，请联系管理员解除!');history.back();</script>";
			exit;
		}
		
//		$comunity->dumpAll($roomTitle,Session::get('user_name'));
		
		//查询群主
		$info = Db::table('paper_group')->where('name',$roomTitle)->find();
			
//		echo "<br>";
//		$comunity->zrange('group',0,-1);
//		$comunity->zrange($roomTitle,0,-1);
//		$comunity->zrange($roomTitle.'ChatMessage',0,-1);
//		$comunity->zrange(Session::get('user_name').'hasGroup',0,-1);
		
		//获取当前房间的所有消息
		$messageList = $comunity->getMessage($roomTitle);

		foreach($messageList as $k=>$v){
			$messageList[$k] = json_decode($v);
		}
		
		$mcount = count($messageList);
//		dump($messageList);
		//加入房间
		if($info['creator'] == Session::get('user_name')){
			$comunity->addPerson(Session::get('user_name'),1,$roomTitle);
		}else{
			$comunity->addPerson(Session::get('user_name'),2,$roomTitle);
		}
		
//		echo "<br>";
//		echo "<br>";
//		$comunity->info($roomTitle);
		
		//统计所有人数
		$pcount = $comunity->getCount($roomTitle);
		
		$this->view->assign('title','创建房间');
		$this->view->assign('roomTitle',$roomTitle);
		$this->view->assign('messageList',$messageList);
		$this->view->assign('pcount',$pcount);
		$this->view->assign('mcount',$mcount);
		$this->view->assign('creator',$info['creator']);
		$this->view->assign('blackList',$blackList);
		return $this->view->fetch('room');
	}

	//获取新消息
	public function getMessage(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');

		$comunity = new Comunity;
		
		$data = Request::param();
		
		//获取当前的消息条数
		$mcount = $data['mcount'];
		
		//获取房间名
		$roomTitle = $data['room'];
		
		//获取当前房间的所有消息
		$messageList = $comunity->getMessage($roomTitle,$mcount);
		if($messageList == '404'){
			return '404';
		}
		
		if(empty($messageList)){
			return '';
		}
		
		//统计所有人数
		$pcount = $comunity->getCount($roomTitle);
		
		$data = [
			'pcount'=>$pcount,
			'messageList'=>json_encode($messageList)
		];
		
		return json_encode($data);
	}
	
	//获取房间所有成员
	public function getPerson(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');

		$comunity = new Comunity;
//		
		$data = Request::param();
		
		//获取房间名
		$roomTitle = $data['room'];
		
		$personList = $comunity->getAllPerson($roomTitle);
		
		if(empty($personList)){
			return '';
		}
		
		return json_encode($personList);
		
		
	}
	
	//踢出房间
	public function removePerson(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');

		$comunity = new Comunity;
//		
		$data = Request::param();
		
		return $comunity->removePerson($data['name'],$data['room']);
	}
	
	//解除踢出限制
	public function removeBlack(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');

		$comunity = new Comunity;
		
		$data = Request::param();
		return $comunity->removeBlack($data['name'],$data['room']);
	}
	
	//发送消息
	public function send(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');
		
		if(Request::isAjax()){
			$data = Request::param();
			
			//获取要发送的信息
			$message = $data['message'];
			
			//进行发送
			$comunity = new Comunity;
			
			//获取参数
			//发送者
			$person = Session::get('user_name');
			
			//房间名
			$name = $data['room'];
			//发送数据
			$data = [
				'from'=>$person,
				'data'=>$message,
				'time'=>time()
			];
			
			$data = json_encode($data);
			
			$res = $comunity->sendMessage($name,$data);
			
			if($res){
				return ['status'=>1,'message'=>'发送成功'];
			}else{
				return ['status'=>0,'message'=>'发送失败'];
			}
		}
		
	}
	
	
	//解算房间
	public function closeGroup(){
		$this->hasPower(Session::get('user_id'), 'index/index/room');
		
		$data = Request::param();
		//获取要发送的信息
		$room = $data['room'];
		$creator = Session::get('user_name');
		
		$comunity = new Comunity;
		$res = $comunity->close($creator,$room);
		
		return ['status'=>1,'message'=>$res];
		if(end($res)){
			if(Db::name('paper_group')->where('name',$room)->delete()){
				return ['status'=>1,'message'=>'解散成功'];
			}else{
				return ['status'=>0,'message'=>'解散失败'];
			}
		}else{
			return ['status'=>0,'message'=>'解散失败'];
		}
	}	
}
