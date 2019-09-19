<?php
namespace app\common\model;
use think\Model;

class Comment extends Model
{
	protected $pk = 'id';
	protected $table = 'paper_comment';
	
	protected $autoWriteTimestamp = true;	//开启自动时间戳
	protected $createTime = 'create_time';
	protected $updateTime = 'update_time';
	protected $dateFormat = 'Y年m月d日';
	
}
?>