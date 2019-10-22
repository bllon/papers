<?php
/**
*社区类
*/

namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use think\facade\Session;
use think\Db;
use think\facade\Env;

class Comunity extends Base
{
	//社区首页
	public function index(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/comunity');

		//获取所有房间
		//redis读取
		// $comunity = new Comunity;
		// $groupList = $comunity->getAllGroup();

		//数据库读取
		$groupList = Db::table('paper_group')->select();
		$this->view->assign('groupCount',count($groupList));
		$groupList = array_slice($groupList, 0,8);
		// var_dump($groupList);exit;


		$filename = dirname(dirname(dirname(__DIR__))).'/public/uploads/song/music.txt';
		if(file_exists($filename)){
			$content = file_get_contents($filename);
			if($content){
				$data = json_decode($content,true);
			}else{
				$data = [];
			}
		}else{
			$data = [];

			//获取音乐列表
			$content = $this->getUrlContent("http://www.333ttt.com/up/top16.html");

			preg_match_all('/<li data-id=\"\d+\" data-title=([\s\S]*?)<\/li>/',$content,$matches);

			if(isset($matches[0])){
				foreach($matches[0] as $k => $v){
					preg_match('/data-title=\"([\s\S]*?)\"/',$v,$match);
					$data[$k]['name'] = $match[1];

					preg_match('/html\">([\s\S]*?)<\/a>/',$v,$match);
					$name = explode("-", $match[1]);
					$data[$k]['singer'] = $name[1];

					preg_match('/title=\"按鼠标右键->另存为可以下载歌曲\" href=\"([\s\S]*?)\"/',$v,$match);
					$data[$k]['url'] = $match[1];

				}
			}
			$path = dirname(dirname(dirname(__DIR__))).'/public/uploads/song/';

			if(!is_dir($path)){
				mkdir($path,0777,true);
			}
			file_put_contents($path.'music.txt', json_encode($data));
		}
		
		$this->view->assign('songNum',count($data));

		$data1 = array_slice($data, 0,10);
		$data2 = array_slice($data, 10,10);


		//获取mv
		$path = Env::get('root_path').'/runtime/cache/mvHtml';
		$filename = $path.'/mvUrl.json';
		if(file_exists($filename)){
			$content = file_get_contents($filename);

			$data = json_decode($content,true);

			if($data['expire'] + 86400 > time()){
				$content = $data['content'];
			}else{
				//缓存过期
				$content = [];
			}
		}else{
			$content = [];
		}

		if($content){
			$mvList = $content;
		}else{
			$mvList = $this->MusicMv();
			$mvList = $mvList['content'];
		}
		$this->view->assign('mvCount',count($mvList));
		$mvList = array_slice($mvList, 0,8);

		$this->view->assign('title','社区首页');
		$this->view->assign('groupList',$groupList);
		$this->view->assign('songList',$data1);
		$this->view->assign('recommendList',$data2);
		$this->view->assign('mvList',$mvList);
		return $this->view->fetch('index');
	}
}