<?php
 
 namespace app\common\validate;
 use think\Validate;
 
 class Comment extends Validate
 {
 	protected $rule=[
 		'content|评论内容'=>'require',
 		'reply_user|评论人'=>'require',
 		'user_id|用户'=>'require',
 	];
	
 }
?>