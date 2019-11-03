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
	 * 判断用户是否拥有权限
	 * @param $url	权限url
	 */
	static public function userHasPower($url)
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
			$need = Db::table('paper_power')->where('id',$need['pid'])->find();
		}


		//判断有无权限，是否为接口调用
		if(!in_array($need['id'], $hasPower)){
			$html = <<< 'INFO'
<body style="background-color:#333">
<h1 style="color:#eee;text-align:center;margin:200px">没有权限...</h1>
</body>
INFO;
			echo $html;
			exit;
			if(!$json){
				echo "<script>alert('对不起，你没有此权限');history.back();window.opener=null;window.open('','_self');window.close();</script>";
				exit;
			}else{
				echo '对不起，你没有此权限';
				exit;
			}	
		}
		
	}


	/**
	 * 判断管理员是否拥有权限
	 * @param $url	权限url
	 */
	static public function adminHasPower($url)
	{
		$admin_id = Session::get('admin_id');

		if(!is_null($admin_id)){
			//查询用户的角色
			$role = Db::table('paper_user')->where('id',$admin_id)->find();
			//角色名称
			$roleName = getRoleName($role['role_id']);
		}else{
			$roleName = '后台访问者';
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
			$need = Db::table('paper_power')->where('id',$need['pid'])->find();
		}


		//判断有无权限，是否为接口调用
		if(!in_array($need['id'], $hasPower)){
			$html = <<< 'INFO'
<body style="background-color:#333">
<h1 style="color:#eee;text-align:center;margin:200px">没有权限...</h1>
</body>
INFO;
			echo $html;
			exit;

			if(!$json){

				echo "<script>alert('对不起，你没有此权限');history.back();window.opener=null;window.open('','_self');window.close();</script>";
				exit;
			}else{
				echo "<!DOCTYPE html><html><head><title>没有权限</title></head><body><h1>没有权限</h1></body></html>";
				//跳转到无权限的信息页
				exit;
			}	
		}
		
	}
}