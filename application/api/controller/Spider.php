<?php
namespace app\api\controller;

class Spider
{
	private static $instance = null;
	
	public $allUrl = [];	//爬取链接数组
	
	public $finalUrl = [];	//最终链接
	
	public $message = [];	//爬取详情信息
	
	private $starturl = null;	//爬取的第一个url
	
	private $nowUrl = null;	//当前爬取页面url
	
	private $num = 0;	//爬到次数
	
	private function __construct($starturl)
	{
		$this->starturl = $starturl;
	}
	
	private function __clone()
	{
		
	}
	
	//创建实例
	public static function getInstance($starturl){
		if(!self::$instance instanceof self){
			self::$instance = new self($starturl);
		}
		return self::$instance;
	} 
	
	//递归搜索页面
	public function getUrl($url){
		$this->getUrlContent($url);
		
	}
	
	//解析当前页面
	public function parseUrl($content,$url){
		
		$songlistArr['raw_url'] = $url;
		
		
		//名称
		$pat = "/<span>名称：<\/span>([^(<br)]+)<br \/>/";
		$matches = array();
		$ret=preg_match($pat, $content,$matches);
		if($ret > 0){
			$songlistArr['title'] = $matches[1];
			
		}else{
			$songlistArr['title'] = '';
			print "error:get title fail\n";
		}
		
		
		//创建人
		$pat = "/<span>创建人：<\/span>([^(<br)]*)<br \/>/";
		$matches = array();
		$ret=preg_match($pat, $content,$matches);
		if($ret > 0){
			$songlistArr['creator'] = $matches[1];
			
		}else{
			$songlistArr['creator'] = '';
			print "error:get creator fail\n";
		}
		
		
		//创建时间
		$pat = "/<span>更新时间：<\/span>([^(<br)]+)<br \/>/";
		$matches = array();
		$ret=preg_match($pat, $content,$matches);
		if($ret > 0){
			$songlistArr['create_date'] = $matches[1];
			
		}else{
			$songlistArr['create_date'] = '';
			print "error:get create_date fail\n";
		}
		
		//简介
		$pat = "/<p><span>简介：<\/span>([^(<\/p>)]+)<\/p>/";
		$matches = array();
		$ret=preg_match($pat, $content,$matches);
		if($ret > 0){
			$songlistArr['info'] = $matches[1];
			
		}else{
			$songlistArr['info'] = '';
			print "error:get info fail\n";
		}
		//歌曲
		$matches = array();
		$pat = "/<a title=\"([^\"]+)\" hidefocus=\"/";
		preg_match_all($pat, $content,$matches);
	//	echo "数据:\n";
	//	print_r($matches);
		$songlistArr['songs'] = array();
		for($i=0;$i<count($matches[0]);$i++){
			$song_title = $matches[1][$i];
			array_push($songlistArr['songs'],array('title' => $song_title));
		}	
		print_r($songlistArr);
//		$this->saveSonglist($songlistArr);
	}
	
	//存储数据到数据库
	public function saveSonglist($songlistArr){
		$conn = mysqli_connect('localhost', 'root', '', 'ls');
		mysqli_query($conn,'set names utf8');
		$songlist = array();
		$songlist['title'] = mysqli_escape_string($conn,$songlistArr['title']);
		$songlist['create_time'] = mysqli_escape_string($conn,$songlistArr['create_date']);
		$songlist['creator'] = mysqli_escape_string($conn,$songlistArr['creator']);
		$songlist['raw_url'] = mysqli_escape_string($conn,$songlistArr['raw_url']);
		$songlist['info'] = mysqli_escape_string($conn,$songlistArr['info']);
		$sql = "insert into songlist set ".
			" title='".$songlist['title']."'".
			" ,create_time='".$songlist['create_time']."'".
			" ,creator='".$songlist['creator']."'".
			" ,raw_url='".$songlist['raw_url']."'".
			" ,info='".$songlist['info']."';";
		mysqli_query($conn,$sql);
		$songlist_id = mysqli_insert_id($conn);
		foreach($songlistArr['songs'] as $song){
			$title = mysqli_escape_string($conn,$song['title']);
			$sql = "insert into song set title='".$title."'".
				" ,songlist_id='".$songlist_id."';";
			mysqli_query($conn,$sql);
		}
		mysqli_close($conn);
			
	}
	
	//获取页面内容
	public function getUrlContent($url)
	{
//		$run_time = new runtime();
//		$run_time->start();
//		//begin your code
//		//end your code
//		$run_time->stop();
		$this->nowUrl = $url;
		$ch = curl_init($url);	//https://www.kugou.com/yy/special/single/616816.html
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$content = curl_exec($ch);
		if($content){
			$this->message[] = ['目标链接: '.$url,'爬取成功','用时: '+'10ms'];
		}
		curl_close($ch);
		return $content;
	}
}
?>