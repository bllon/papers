<?php
namespace app\admin\common\model;
use think\Model;

class Role extends Model
{
	protected $pk = 'id';
	protected $table = 'paper_role';
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $dateFormat = 'Y/m/d';
}
?>