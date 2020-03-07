<?php
/**
*代码测试类
*/
namespace app\test\controller;

class Test
{
	//sphinx测试
	public function sphinx()
	{

		//搜索关键字
		$key = Request::param('key');

		//搜索sphinx
		require_once '../extend/sphinx/sphinxapi.php';
		$sph = new \SphinxClient();
		$sph->SetServer('localhost', 9312);
		//第二个参数，默认是*，要查询的索引名字
		$ret = $sph->Query($key, 'papers');
		//提取出所有文章id
		$id = array_keys($ret['matches']);
		var_dump($id);
		//查询出所有文章
		$lunwenList = Db::table('paper_lunwen')
								->whereOr('id','in',$id)
								->order('addtime','asc')
								->select();
		var_dump($lunwenList);
	}

	public function d()
	{
		var_dump('test ok');
	}
}