<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class Power extends Validate
 {
 	protected $rule=[
 		'name|权限名称'=>'require',
 		'url|权限方法'=>'require',
 	];
	
	
 }
?>