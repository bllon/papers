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
			
			if(!$json){
				echo Access::nonePower();//输出无权限的HTML页面
				exit;
			}else{
				exit;;
			}	
		}
		
	}


	/**
	* 没有权限返回信息
	*/
	static public function nonePower()
	{
		$html = <<< 'INFO'
<!DOCTYPE html>
<html lang="en">
  <head>
  	<meta name="description" content="本科论文库">
    <title>本科论文库 - 错误提示</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="shortcut icon" href="/static/images/3.ico" />
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="/static/docs/css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="/static/font-awesome-4.7.0/css/font-awesome.min.css">     
  </head>
  <body>
      <div class="page-error" style="background:#f0f2f5;">
        <h4 class="text-danger"><i class="fa fa-exclamation-circle"></i> 错误: 对不起，你没有权限...</h4>
        <p class="text-danger">Error: Sorry, you do not have permission ...</p>
      </div>
  </body>
</html>
INFO;
		return $html;
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
			

			if(!$json){
				echo Access::nonePower();
				exit;
			}else{
				exit;
			}	
		}
		
	}
}