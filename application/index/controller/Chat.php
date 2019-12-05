<?php
/*
**讨论房间类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use think\facade\Session;
use think\facade\Request;
use think\Db;


class Chat extends Base
{
	//创建房间
	public function createGroup(){	
		
		$roomKey = uniqid();
		//随机生成房间名
		$groupName = Session::get('user_name').'__'.$roomKey;
		
		$this->view->assign('title','创建房间');
		$this->view->assign('groupName',$groupName);
		$this->view->assign('roomKey',$roomKey);
		return $this->view->fetch('createGroup');
	}

	//执行创建房间
	public function doCreateGroup(){

		//接收参数
		$data = Request::param();

		// var_dump(mb_strlen($data['groupName']),mb_strlen($data['title']));exit;

		if(trim($data['groupName']) == '' || trim($data['title']) == ''){
			$this->error('房间名称或主题不能为空');
		}

		if(mb_strlen($data['groupName']) > 20){
			$this->error('房间名称过长');
		}

		if(mb_strlen($data['title']) > 30){
			$this->error('房间主题过长');
		}

		//初始化成员
		$person = [
			'normal'=>[],
			'black'=>[]
		];
		$person = json_encode($person);
			
		$data = [
			'name'=>$data['groupName'],
			'title'=>$data['title'],
			'creator'=>$data['creator'],
			'person'=>$person,
			'roomKey'=>$data['roomKey'],
			'create_time'=>time()
		];			
		$res = Db::name('paper_group')->data($data)->insert();
		if($res){
			$this->redirect('comunity/index',2,'创建成功');
		}else{
			$this->error('创建失败');
		}
		
	}

	//加入房间
	public function room(){

		//获取房间id
		$id = Request::param('id');

		$roomInfo = Db::table('paper_group')->find($id);
		
		if($roomInfo == null){
			echo "<script>alert('该房间不存在或已解散!');history.back();</script>";
			exit;
		}	

		
		$roomInfo['person'] = json_decode($roomInfo['person'],true);
		$blackList = $roomInfo['person']['black'];

		//检查是否存在于黑名单	
		
		if(in_array(Session::get('user_name'), $roomInfo['person']['black'])){
			echo "<script>alert('你已经被请出房间，请联系管理员解除!');history.back();</script>";
			exit;
		}

		$sta = true;
		//加入房间
		foreach($roomInfo['person']['normal'] as $v){
			if($v == Session::get('user_name')){
				//已经存在
				$sta = false;
			}
		}

		if($sta){
			//不存在，则更新
			$roomInfo['person']['normal'][] = Session::get('user_name');
			$data = [
				'id'=>$id,
				'person'=>json_encode($roomInfo['person'])
			];
			Db::table('paper_group')->update($data);
		}
		

		//获取当前房间标题
		$roomTitle = $roomInfo['name'];

		//获取房间钥匙
		$roomKey = $roomInfo['roomKey'];

		//房主
		$creator = $roomInfo['creator'];

		//房间标语
		$roomWord = $roomInfo['title'];
				
		//获取当前房间的所有消息
		
//		dump($messageList);
		//加入房间
		
		

		
		//统计所有人数
		
		$this->view->assign('title','聊天室 '.$id);
		$this->view->assign('roomTitle',$roomTitle);
		$this->view->assign('roomKey',$roomKey);
		$this->view->assign('creator',$creator);
		$this->view->assign('roomWord',$roomWord);
		$this->view->assign('blackList',$blackList);
		return $this->view->fetch('room');
	}


	//踢出人员
	public function removePerson(){
		$data = Request::param();
		
		$group = Db::table('paper_group')->where('name',$data['room'])->find();

		$groupId = $group['id'];

		$person = json_decode($group['person'],true);

		if(!in_array($data['name'], $person['black'])){
			
			//加入黑名单
			$person['black'][] = $data['name'];

			$data = [
				'id'=>$groupId,
				'person'=>json_encode($person)
			];
			if(Db::table('paper_group')->update($data)){
				return ['statu'=>1,'message'=>'已经加入黑名单'];
			}else{
				return ['statu'=>0,'message'=>'操作失败'];
			}
		}

	}


	//解除黑名单
	public function removeBlack(){
		$data = Request::param();
		
		$group = Db::table('paper_group')->where('name',$data['room'])->find();

		$groupId = $group['id'];

		$person = json_decode($group['person'],true);

		if(in_array($data['name'], $person['black'])){

			//解除黑名单
			foreach($person['black'] as $k=>$v){
				if($v == $data['name']){
					unset($person['black'][$k]);
				}
			}

			$data = [
				'id'=>$groupId,
				'person'=>json_encode($person)
			];
			if(Db::table('paper_group')->update($data)){
				return ['statu'=>1,'message'=>'已经解除黑名单'];
			}else{
				return ['statu'=>0,'message'=>'操作失败'];
			}
		}
	}

	//获取黑名单列表
	public function getBlackList(){
		$data = Request::param();
		
		$group = Db::table('paper_group')->where('name',$data['room'])->find();

		$person = json_decode($group['person'],true);

		return ['statu'=>1,'message'=>$person['black']];
	}

	//解散房间
	public function closeGroup(){
		$data = Request::param();
		
		if(Db::table('paper_group')->where('name',$data['room'])->delete()){
			return ['statu'=>1,'message'=>'成功解散房间'];
		}else{
			return ['statu'=>1,'message'=>'操作失败'];
		}
		
	}

}