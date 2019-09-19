<?php
namespace app\admin\common\model;
use think\Model;

class Borrow extends Model
{
	protected $pk = 'id';
	protected $table = 'paper_borrow';
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	protected $dateFormat = 'Y/m/d';
}
?>