<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class User extends Validate
 {
 	protected $rule=[
 		'school_name|学校名称'=>'require',
 		'username|用户名'=>'require',
 		'password|密码'=>'require',
 		'phone|手机号'=>'require',
 		'email|邮箱'=>'require',
 		'password_confirm|确认密码'=>'require|confirm:password',
 	];
	
	
 }
?>