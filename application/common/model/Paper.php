<?php
 
 namespace app\common\model;
 use think\Model;
 
 class Paper extends Model
 {
 	protected $pk='id';	//默认主键
	protected $table='paper_file';	//默认数据表
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	protected $dateFormat = 'Y/m/d';
	
	
	//获取器
	public function getStatusAttr($value)		//方法名固定		get字段Attr	$value为当前值
	{
		$status = ['1'=>'显示' , '0'=>'隐藏'];
		return $status[$value];
	}
	
 }
?>