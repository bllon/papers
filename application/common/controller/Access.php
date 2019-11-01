<?php

/**
*权限控制系统
**/

namespace app\common\controller;
use think\Request;
use think\facade\Session;
use think\Db;

class Access
{
	private $access = [];	//生成权限数组

	/**
	 * 判断是否拥有权限
	 * @param $user_id 用户id
	 * @param $url	权限url
	 * @param $json 是否为接口调用
	 */
	static public function hasPower($url,$json = false)
	{
		$user_id = Session::get('user_id');

		if(!is_null($user_id)){
			//查询用户的角色
			$role = Db::table('paper_consumer')->where('id',$user_id)->find();
			//角色名称
			$roleName = getRoleName($role['role_id']);
		}else{
			$roleName = '游客';
		}

		//查询角色所拥有的的权限
		$power = Db::table('paper_role_power')
						->field('power_id')
						->where('name',$roleName)
						->find();


		$hasPower = explode(',', $power['power_id']);

		//查询所需要的权限
		$need = Db::table('paper_power')
						->field('id,is_json,pid')
						->where('url',$url)
						->find();

		$json = intval($need['is_json']);

		while($need['pid'] != 0){
			$need = Db::table('paper_consumer')->where('id',$need['pid'])->find();
		}

		//判断有无权限，是否为接口调用
		if(!in_array($need['id'], $hasPower)){
			if(!$json){
				echo "<script>alert('对不起，你没有此权限');history.back();window.opener=null;window.open('','_self');window.close();</script>";
				exit;
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