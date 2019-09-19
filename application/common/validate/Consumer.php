<?php
 
 namespace app\common\validate;
 use think\Validate;
 
 class Consumer extends Validate
 {
 	protected $rule=[
 		'name|昵称'=>'require|length:1,20|chsAlphaNum|unique:paper_consumer',
 		'school_name|学校'=>'require|length:5,50|chs',
 		'email|邮箱'=>'require|email|unique:paper_consumer',
 		'password|密码'=>'require|alphaNum|length:6,20|confirm',
 		'password_confirm|确认密码'=>'require|confirm:password',
 	];
 }
?>