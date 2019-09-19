<?php
 
 namespace app\common\model;
 use think\Model;
 
 class Collect extends Model
 {
 	protected $pk='id';	//默认主键
	protected $table='paper_collect';	//默认数据表
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	protected $dateFormat = 'Y/m/d日';
 }
?>