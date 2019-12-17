<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class Lunwen extends Validate
 {
 	protected $rule=[
 		'lunwen_title|论文标题'=>'require',
 		'writer|作者'=>'require',
 		'rank_type|专业类'=>'require',
 		'lunwen_rank|专业'=>'require',
 		'biaoji|质量'=>'require',
 		'addtime|上传时间'=>'require',
 	];
 }
?>