<?php
 
 namespace app\common\model;
 use think\Model;
 
 class Consumer extends Model
 {
 	protected $pk='id';	//默认主键
	protected $table='paper_consumer';	//默认数据表
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	protected $dateFormat = 'Y年m月d日';
		
	
	//修改器
	// public function setPasswordAttr($value)
	// {
	// 	return sha1($value);	//用sha1函数加密
	// }
 }
?>