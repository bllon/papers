<?php
/**
*社区类
*/

namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use think\facade\Request;
use think\facade\Session;
use think\Db;
use think\facade\Env;

class Comunity extends Base
{
	//test
	public function test()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/comunity');

		//获取所有房间
		$groupList = Db::table('paper_group')->select();
		$this->view->assign('groupCount',count($groupList));
		$groupList = array_slice($groupList, 0,8);



		//获取音乐列表
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
		return $this->view->fetch('test');
	}

	//社区首页
	public function index(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/comunity');

		//获取所有房间
		$groupList = Db::table('paper_group')->select();
		$this->view->assign('groupCount',count($groupList));
		$groupList = array_slice($groupList, 0,8);



		//获取音乐列表
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

	//更多讨论列表
	public function moreGroup()
	{
		//数据库读取
		$groupList = Db::table('paper_group')->select();

		$this->view->assign('title','讨论列表');
		$this->view->assign('groupList',$groupList);
		return $this->view->fetch('moreGroup');
	}
	
	//爬取腾讯音乐最火MV
	public function MusicMv()
	{
		$path = Env::get('root_path').'/runtime/cache/mvHtml';
		$filename = $path.'/mv.json';
		if(file_exists($filename)){
			$content = file_get_contents($filename);
			$data = json_decode($content,true);
			if($data['expire'] + 86400 > time()){
				$content = $data['content'];
			}else{
				//缓存过期
				$content = $this->getUrlContent('https://u.y.qq.com/cgi-bin/musicu.fcg?-=mvlib578643394250538&g_tk=5381&loginUin=1192475069&hostUin=0&format=json&inCharset=utf8&outCharset=utf-8&notice=0&platform=yqq.json&needNewCode=0&data=%7B%22comm%22%3A%7B%22ct%22%3A24%7D%2C%22mv_tag%22%3A%7B%22module%22%3A%22MvService.MvInfoProServer%22%2C%22method%22%3A%22GetAllocTag%22%2C%22param%22%3A%7B%7D%7D%2C%22mv_list%22%3A%7B%22module%22%3A%22MvService.MvInfoProServer%22%2C%22method%22%3A%22GetAllocMvInfo%22%2C%22param%22%3A%7B%22start%22%3A0%2C%22size%22%3A20%2C%22version_id%22%3A7%2C%22area_id%22%3A15%2C%22order%22%3A0%7D%7D%7D');

				$data = ['expire'=>time(),'content'=>$content];

	        	file_put_contents($filename, json_encode($data));
			}

		}else{
			$content = $this->getUrlContent('https://u.y.qq.com/cgi-bin/musicu.fcg?-=mvlib578643394250538&g_tk=5381&loginUin=1192475069&hostUin=0&format=json&inCharset=utf8&outCharset=utf-8&notice=0&platform=yqq.json&needNewCode=0&data=%7B%22comm%22%3A%7B%22ct%22%3A24%7D%2C%22mv_tag%22%3A%7B%22module%22%3A%22MvService.MvInfoProServer%22%2C%22method%22%3A%22GetAllocTag%22%2C%22param%22%3A%7B%7D%7D%2C%22mv_list%22%3A%7B%22module%22%3A%22MvService.MvInfoProServer%22%2C%22method%22%3A%22GetAllocMvInfo%22%2C%22param%22%3A%7B%22start%22%3A0%2C%22size%22%3A20%2C%22version_id%22%3A7%2C%22area_id%22%3A15%2C%22order%22%3A0%7D%7D%7D');

			if(!is_dir($path)){
	        	// 如果静态目录不存在 则创建
	        	mkdir($path,0777,true);
	        }
	        $data = ['expire'=>time(),'content'=>$content];

	        file_put_contents($filename, json_encode($data));
		}
		$content = json_decode($content,true);

		$list = $content['mv_list']['data']['list'];
		$data = [];
		foreach($list as $row){
			// var_dump($row);
			$one = [];
			$one['vid'] = $row['vid'];
			$one['title'] = $row['title'];
			$one['picurl'] = $row['picurl'];
			
			$mvContent = $this->getUrlContent('https://u.y.qq.com/cgi-bin/musicu.fcg?data=%7B%22getMvUrl%22%3A%7B%22module%22%3A%22gosrf.Stream.MvUrlProxy%22%2C%22method%22%3A%22GetMvUrls%22%2C%22param%22%3A%7B%22vids%22%3A%5B%22'.$row['vid'].'%22%5D%2C%22request_typet%22%3A10001%7D%7D%7D&g_tk=5381&callback=jQuery11230544967815282688_1570755728410&loginUin=0&hostUin=0&format=jsonp&inCharset=utf8&outCharset=GB2312&notice=0&platform=yqq&needNewCode=0');
			$mvContent = trim($mvContent,')');
			$start = strrpos($mvContent, '(');
			$mvContent = substr($mvContent, 41);
			// var_dump($mvContent);
			$mvUrl = json_decode($mvContent,true);

			$mvUrl = $mvUrl['getMvUrl']['data'][$row['vid']]['mp4'];

			$one['mvUrl'] = [];
			foreach($mvUrl as $url){
				// var_dump($url['freeflow_url']);
				if($url['freeflow_url']){
					$one['mvUrl'][] = $url['freeflow_url'][0];
				}
			}

			$data[] = $one;
		}

		$filename = $path.'/mvUrl.json';

		$data = ['expire'=>time(),'content'=>$data];

		file_put_contents($filename, json_encode($data));
		// var_dump($content['mv_list']['data']['list']);
		return $data;
	}


	//爬取链接内容
	public function getUrlContent($url)
	{
		$url = trim($url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$content = curl_exec($ch);
		
		$content=mb_convert_encoding($content, 'UTF-8', 'Windows-1254,UTF-8,GBK,GB2312,BIG5');//使用该函数对结果进行转码
		// $content= iconv( 'GBK','UTF-8',$content);
		// var_dump($content);
		// exit;
		
		curl_close($ch);

		return $content;
	}


	//下载文件
	public function downFile($filename)
	{
		//获取文件的扩展名
	    $allowDownExt = array ('mp3');

	    //获取文件信息
	    $fileExt = pathinfo($filename);	 

	    //检测文件类型是否允许下载
	    if(!in_array($fileExt['extension'], $allowDownExt)) {
	        $this->error('文件类型不正确');
	    }		 

	    //设置脚本的最大执行时间，设置为0则无时间限制
	    set_time_limit(0);
	    ini_set('max_execution_time', '0');

	    //通过header()发送头信息
	    //因为不知道文件是什么类型的，告诉浏览器输出的是字节流
	    header('content-type:application/octet-stream');

	    //告诉浏览器返回的文件大小类型是字节
	    header('Accept-Ranges:bytes');

	    //获得文件大小
	    $filesize = filesize($filename);//(此方法无法获取到远程文件大小)

	    // $header_array = get_headers($filename, true);
	    // $filesize = $header_array['Content-Length'];

	    //告诉浏览器返回的文件大小
	    header('Accept-Length:'.$filesize);

	    //告诉浏览器文件作为附件处理并且设定最终下载完成的文件名称
	    header('content-disposition:attachment;filename='.basename($filename));

	    //针对大文件，规定每次读取文件的字节数为4096字节，直接输出数据
	    $read_buffer = 4096;
	    $handle = fopen($filename, 'rb');

	    //总的缓冲的字节数
	    $sum_buffer = 0;

	    //只要没到文件尾，就一直读取
	    while(!feof($handle) && $sum_buffer<$filesize) {

	        echo fread($handle,$read_buffer);
	        $sum_buffer += $read_buffer;

	    }

	    //关闭句柄
	    fclose($handle);
	}

	//更多音乐列表
	public function moreMusic()
	{
		//获取音乐列表
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

		$this->view->assign('title','音乐列表');
		$this->view->assign('songList',$data);
		return $this->view->fetch('moreMusic');
	}

	//下载音乐
	public function downloadMusic()
	{
		$data = Request::param();

		$url = $data['url'];
		$url = trim($url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$content = curl_exec($ch);

		//把mp3写入文件
		$path = dirname(dirname(dirname(__DIR__))).'/public/uploads/song/mp3/';

		if(!is_dir($path)){
			mkdir($path,0777,true);
		}

		$file = $path.$data['name'].'.mp3';
		file_put_contents($file, $content);

		$this->downFile($file);
		unlink($file);
		
		curl_close($ch);

	}

	//收藏音乐
	public function collectMusic()
	{
		$data = Request::param();

		if(Session::get('user_id') == null)
		{
			//没登录
			return ['status'=>0,'message'=>'请先登录'];
		}

		$data['consumer_id'] = Session::get('user_id');

		$map = [];
		$map[] = ['consumer_id','=',$data['consumer_id']];
		$map[] = ['name','=',$data['name']];

		$collectInfo = Db::table('paper_collectMusic')->where($map)->find();

		if($collectInfo){
			return ['status'=>2,'message'=>'已收藏'];
		}

		if(Db::table('paper_collectMusic')->insert($data)){
			return ['status'=>1,'message'=>'收藏成功'];
		}else{
			return ['status'=>0,'message'=>'收藏失败'];
		}
	}

	//更多mv
	public function moreMv()
	{
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

		$this->view->assign('title','MV列表');
		$this->view->assign('mvList',$mvList);
		return $this->view->fetch('moreMv');
	}
}