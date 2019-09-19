<?php
namespace app\index\controller;
use app\common\controller\Base;

/**
 * redis聊天社区
 */
class Comunity
{
	//连接客户端
	public function conn(){
		
		$redis = new \Redis();
		$host = '127.0.0.1';
		$port = 6379;
		$redis->connect($host,$port);
		return $redis;
	}
	
	/**
	 * 添加新群组
	 * $creator 创建者名称
	 * $name	群组名称
	 */
	public function createGroup($creator,$name){
		$redis = $this->conn();
		
		$redis->multi();
		
		//向集合中添加一个新群组
		$redis->zadd('group',1,$name);		
		
		//创建成员群组
		$redis->zadd($name,1,$creator);
		
		//记录成员所加入的群组
		$redis->zadd($creator.'hasGroup',0,$name);
		
		//创建消息群组
		//发送数据
		$data = [
			'from'=>'系统',
			'data'=>'恭喜创建新群组',
			'time'=>time()
		];
		
		$data = json_encode($data);
		$redis->zadd($name.'ChatMessage',1,$data);
		
		//记录群组的消息数量
		$redis->set($name.'ChatMessageCount',1);
		
		return $redis->exec();
		
		dump($redis->zrange('group',0,-1));
		dump($redis->zrange($name,0,-1));
		dump($redis->zrange($creator.'hasGroup',0,-1));
		dump($redis->zrange($name."ChatMessage",0,-1));
		dump($redis->get($name.'ChatMessageCount'));
	}
	
	/**
	 * 添加成员
	 * $person 成员名称
	 * $name	群组名称
	 */
	public function addPerson($person,$index,$name){		
		$redis = $this->conn();	
		$redis->zadd($name,$index,$person);
		
		//记录成员所加入的群组
		$redis->zadd($person.'hasGroup',0,$name);
	}
	
	//删除成员
	public function removePerson($person,$name){
		$redis = $this->conn();
		//加入黑名单
		$redis->sAdd($name.'BlackList',$person);
		 		
		return $redis->zDelete($name,$person);
	}
	
	//返回黑名单
	public function BlackList($name){
		$redis = $this->conn();
		return $redis->sMembers($name.'BlackList');
	}
	
	//解除黑名单限制
	public function removeBlack($person,$name){
		$redis = $this->conn();
		return $redis->sRem($name.'BlackList', $person); 
	}
	
	
	/**
	 * 获取房间所有成员
	 * $name	房间名
	 */	 
	public function getAllPerson($name){
		$redis = $this->conn();	
		return $redis->zrange($name,0,-1);
	}
	
	/**
	 * 发送信息
	 * $person	发送者
	 * $name	所属群组
	 * $data	发送数据
	 */
	public function sendMessage($name,$data){
		$redis = $this->conn();
		//当前会话数量
		$count = $redis->get($name.'ChatMessageCount');
		if($redis->zadd($name.'ChatMessage',$count+1,$data)){
			$redis->incr($name.'ChatMessageCount');
			return true;
		}else{
			return false;
		}	
			
	}
	
	//获得所有房间
	public function getAllGroup(){
		$redis = $this->conn();
		return $redis->zrange('group',0,-1);
	}
	
	public function info($name){
		$redis = $this->conn();
		$personList = $redis->zrange($name,0,-1);
		foreach($personList as $p){			
			$redis->zDelete($p.'hasGroup',$name);
		}
	}
	
	
	/**
	 * 关闭房间
	 * $creator	创建者
	 * $name	所创群组
	 */
	//关闭房间
	public function close($creator,$name){
		$redis = $this->conn();
		
		$personList = $redis->zrange($name,0,-1);		
		foreach($personList as $p){			
			$redis->zDelete($p.'hasGroup',$name);
		}
		
		$redis->multi();	
		
//		$redis->zDelete($creator.'hasGroup',$name);
		$redis->del($name.'ChatMessageCount');
		$redis->zRemRangeByRank($name."ChatMessage",0,-1);
		$redis->zRemRangeByRank($name,0,-1);
		$redis->zDelete('group',$name);
		
		return $redis->exec();
	}
	
	//打印所有信息
	public function dumpAll($name,$person){
		
		$redis = $this->conn();
		dump($redis->zrange('group',0,-1));
		dump($redis->zrange($name,0,-1));
		dump($redis->zrange($person.'hasGroup',0,-1));
		dump($redis->zrange($name."ChatMessage",0,-1));
		dump($redis->get($name.'ChatMessageCount'));
	}
	
	//获取房间的消息
	public function getMessage($name,$start=0,$stop=-1){
		$redis = $this->conn();
		$res = $redis->get($name.'ChatMessageCount');
		if($res == false){
			return '404';
		}
		return $redis->zrange($name."ChatMessage",$start,$stop);
	}
	
	//统计房间人数
	public function getCount($name){
		$redis = $this->conn();
		return $redis->zCount($name,1,2);
	}
	
	//删除
	public function zRemRangeByScore(){
		$redis = $this->conn();
		$redis->zRemRangeByScore('group',0,3);
	}
	
	//查看信息
	public function zrange($name){
		$redis = $this->conn();
		dump($redis->zrange($name,0,-1));
	}
}
?>