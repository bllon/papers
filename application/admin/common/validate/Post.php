<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class Post extends Validate
 {
 	protected $rule=[
 		'title|贴子标题'=>'require',
 		'subtitle|副标题'=>'require',
 		'content|内容'=>'require',
 		'writer|发贴人'=>'require',
 		'grade|发贴人级别'=>'require',
 		'user_id|发贴人主键'=>'require',
 	];
	
	
 }
?>