<?php
namespace app\api\controller;
use think\Db;

class Index
{
	public function index()
	{
		header("Access-Control-Allow-Origin:*");
		$page = isset($_GET['page']) ? $_GET['page'] : 1;
		$lunwenList = Db::table('paper_lunwen')
								->order('addtime','asc')
								->limit(($page-1)*5,5)
								->select();
		print_r(json_encode($lunwenList));
	}	
	
	public function detail()
	{
		$id = $_GET['id'];
		$paperInfo = Db::table('paper_lunwen')->where('id',$id)->find();
		$content = Paper($id);
		if(null == $content){
			if($paperInfo['lunwen_file'] !== null){
				$paperInfo['content'] = parserPdf(substr($paperInfo['lunwen_file'],1));
			}else{
				$arr = [];
				$arr[] = $paperInfo['content'];
				$paperInfo['content'] = $arr;
			}
		}else{
			$paperInfo['content'] = $content;
		}
		print_r(json_encode($paperInfo));
	}
}
?>