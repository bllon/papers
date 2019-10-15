<?php
 
 namespace app\common\validate;
 use think\Validate;
 
 class Consumer extends Validate
 {
 	protected $rule=[
 		'name|用户'=>'require|length:1,20|chsAlphaNum|unique:paper_consumer',
 		'email|邮箱'=>'require|email|unique:paper_consumer',
 		'password|密码'=>'require|alphaNum|length:6,20|confirm',
 		'password_confirm|确认密码'=>'require|confirm:password',
 	];
 }
?>