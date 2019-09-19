<?php
 
 namespace app\common\validate;
 use think\Validate;
 
 class Paper extends Validate
 {
 	protected $rule=[
 		'title|论文标题'=>'require|length:5,20|chsAlphaNum',
 		'user_id|作者'=>'require',
 		'cate|栏目'=>'require',
 	];
 }
?>