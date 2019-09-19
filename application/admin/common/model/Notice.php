<?php
namespace app\admin\common\model;
use think\Model;

class Notice extends Model
{
	protected $pk = 'id';
	protected $table = 'paper_notice';
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $dateFormat = 'Y年m月d日';
}
?>