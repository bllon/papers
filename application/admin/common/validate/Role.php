<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class Role extends Validate
 {
 	protected $rule=[
 		'name|角色名称'=>'require',
 		'power_id|权限'=>'require',
 	];
	
	
 }
?>