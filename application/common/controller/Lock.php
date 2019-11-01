<?php
/*
**redis分布式锁
*/
namespace app\index\controller;

class Lock
{
	
	public $redis = null;
	
	public function __construct(){
		$this->redis = new \Redis();
		$this->redis->connect('127.0.0.1',6379); 
	}
	
	
	/**
	 * 获取锁
	 * param $LockName string 锁的名字
	 * param $timeout int 锁的过期时间
	 */
	 
	public function getLock($LockName,$timeout = 100){
		
		//获取唯一标识
		$identifier = uniqid();
		
		$end = time()+$timeout;	//循环结束时间
		
		$timeout = ceil($timeout);	//秘钥过期时间
		
		while(time() < $end){
			
			//查看是否被上锁
			if($this->redis->setnx($LockName,$identifier)){//秘钥已设置
				
				//设置过期时间，防止死锁
				$this->redis->expire($LockName,$timeout);
				
				return $identifier;				
			}else if($this->redis->ttl($LockName) === -1){	//秘钥存在，未关联过期时间
				
				//防止上一次没设置过期时间
				$this->redis->expire($LockName,$timeout);
				
			}
			
			usleep(0.001);
		}
		
		return false;
	}
	
	
	/**
	 * 释放锁
	 * param $LockName string 锁的名字
	 * param $identifier string 锁的唯一标识
	 */
	 
	 public function releaseLock($LockName,$identifier){
	 	
		//判断其他用户有没有修改锁
		if($this->redis->get($LockName) == $identifier){
			
			$this->redis->multi();
			$this->redis->del($LockName);
			$this->redis->exec();
			
			return true;
		}else{
			
			return false;
		}
	 } 
}
?>