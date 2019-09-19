<?php
 
 namespace app\admin\common\validate;
 use think\Validate;
 
 class Lunwen extends Validate
 {
 	protected $rule=[
 		'lunwen_title|论文标题'=>'require',
 		'writer|作者'=>'require',
 		'major|专业'=>'require',
 		'rank_type|一级分类'=>'require',
 		'lunwen_rank|二级分类'=>'require',
 		'biaoji|质量'=>'require',
 		'addtime|上传时间'=>'require',
 	];
 }
?>