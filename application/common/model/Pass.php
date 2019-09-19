<?php
 
 namespace app\common\model;
 use think\Model;
 
 class Pass extends Model
 {
 	protected $pk='id';	//默认主键
	protected $table='paper_pass';	//默认数据表
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $dateFormat = 'Y/m/d';
 }
?>