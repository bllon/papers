<?php
namespace app\admin\common\model;
use think\Model;

class Lunwen extends Model
{
	protected $pk = 'id';
	protected $table = 'paper_lunwen';
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'addtime';
	protected $dateFormat = 'Y/m/d';
}
?>