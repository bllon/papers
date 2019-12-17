<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//公共函数文件
use think\Db;
use app\common\model\Paper;
use app\common\model\Lunwen;
use app\admin\common\model\Term;
use app\admin\common\model\School;
use app\common\model\Consumer;
use app\admin\common\model\User;
use app\admin\common\model\Role;
use app\admin\common\model\Power;
use app\common\model\Comment;
use app\api\controller\Elastic;
use app\api\controller\CosineSimilar;

//获取用户所在学校
if(!function_exists('getSchoolOfConsumer'))
{
	function getSchoolOfConsumer($id)
	{
		$userInfo =Consumer::get($id);
		return $userInfo['school_name'];
	}
}

//根据主键获取权限名称
if(!function_exists('getPowerName'))
{
	function getPowerName($id)
	{
		$power = Power::get($id);
		return $power['name'];
	}
}

//根据主键获取角色名称
if(!function_exists('getRoleName'))
{
	function getRoleName($id)
	{
		$role = Role::get($id);
		return $role['name'];
	}
}

//根据主键获取管理员名称
if(!function_exists('getAdminName'))
{
	function getAdminName($id)
	{
		$admin = User::get($id);
		return $admin['username'];
	}
}


if(!function_exists('getConsumerId'))
{
	function getConsumerId($name)
	{
		$consumer = $Consumer::where('name',$name)->find();
		return $consumer['id'];
	}
}

if(!function_exists('getPaperName'))
{
	function getPaperName($id)
	{
		$paperInfo = Lunwen::get($id);
		if(mb_strlen($paperInfo['lunwen_title'])>18){
			return mb_substr($paperInfo['lunwen_title'], 0,18)."...";
		}
		return $paperInfo['lunwen_title'];
	}
}

if(!function_exists('getUserName'))
{
	function getUserName($id)
	{
		$userInfo = Consumer::get($id);
		return $userInfo['name'];
	}
}

if(!function_exists('subTitle'))
{
	function subTitle($title)
	{
		if(mb_strlen($title)>18){
			return mb_substr($title, 0,18)."...";
		}
		return $title;
	}
}
if(!function_exists('getPostUser'))
{
	function getPostUser($id)
	{
		$userInfo = Comment::get($id);
		return $userInfo['reply_user'];
	}
}

if(!function_exists('getPostUserId'))
{
	function getPostUserId($id)
	{
		$userInfo = Comment::get($id);
		return $userInfo['user_id'];
	}
}

if(!function_exists('getConsumerImg'))
{
	function getConsumerImg($id)
	{
		$consumerInfo = Consumer::get($id);
		return $consumerInfo['user_img'] != null ? $consumerInfo['user_img'] : '/static/images/icon.jpg';
	}
}

if(!function_exists('getConsumerImgOfName'))
{
	function getConsumerImgOfName($name)
	{
		$consumerInfo = Consumer::where('name',$name)->find();
		return $consumerInfo['user_img'] != null ? $consumerInfo['user_img'] : '/static/images/icon.jpg';
	}
}

//获取发贴学校
if(!function_exists('getSchool'))
{
	function getSchool($id)
	{
		$schoolInfo = School::get($id);
		return $schoolInfo['school_name'];
	}
}

//更换主题
if(!function_exists('updateColor'))
{
	//#009688
	function updateColor($color)
	{
		$red = $color{1}.$color{2};
		$green = $color{3}.$color{4};
		$blue = $color{5}.$color{6};
		$green = base_convert($green, 16, 10)-25;
		$blue = base_convert($blue, 16, 10)-23;
		
		$green = base_convert($green, 10, 16);
		$blue = base_convert($blue, 10, 16);
		return "#".$red.$green.$blue;
	}
}

//更换主题
if(!function_exists('setColor'))
{
	//#009688
	function setColor($color)
	{
		$red = $color{1}.$color{2};
		$green = $color{3}.$color{4};
		$blue = $color{5}.$color{6};
		$green = base_convert($green, 16, 10)-51;
		$blue = base_convert($blue, 16, 10)-46;
		
		$green = base_convert($green, 10, 16);
		$blue = base_convert($blue, 10, 16);
		return "#".$red.$green.$blue;
	}
}

//检验文件是否合格
function checkFile($file,$config)
{
	//获取文件大小和格式
	$filename =  explode('.',$file['name'],-1);
	$fileSize = $file['size'];
	$type = explode('/', $file['type'],2);
	$filename=$filename[0];
	$type=end($type);

	//检验文件格式
	if(!in_array($type,$config['ext'])){
		return ['name'=>$filename,'check'=>false,'message'=>'文件格式不对','type'=>$type];
	}
	
	//检验文件大小
	if($fileSize > $config['maxSize']){
		return ['name'=>$filename,'check'=>false,'message'=>'文件过大','type'=>$type];
	}	
	
	return ['name'=>$filename,'check'=>true,'message'=>'文件符合','type'=>$type];
}


//缓存所有论文内容
function setPaperCache()
{
	//1.实例化对象
	$redis = new \Redis();
	//2.定义主机和端口
	$host = '127.0.0.1';
	$port = 6379;
	//3.连接redis服务器
	$redis->connect($host , $port);
//		halt($redis->get('windows'));
	$paperList = Paper::all();
	
	foreach($paperList as $paper){
		$redis->set("paper:id:".$paper['id'].":content",serialize($this->parserPdf($paper['file_path'])));
	}
	
}

//设置缓存
function setCache($key,$value,$time)
{
	//1.实例化对象
	$redis = new \Redis();
	//2.定义主机和端口
	$host = '127.0.0.1';
	$port = 6379;
	//3.连接redis服务器
	$redis->connect($host , $port);
	
	return $redis->set($key,$value,$time);
}

//获取缓存
function getCache($key)
{
	//1.实例化对象
	$redis = new \Redis();
	//2.定义主机和端口
	$host = '127.0.0.1';
	$port = 6379;
	//3.连接redis服务器
	$redis->connect($host , $port);
	
	return $redis->get($key);
}

//清除缓存
function delCache($key)
{
	//1.实例化对象
	$redis = new \Redis();
	//2.定义主机和端口
	$host = '127.0.0.1';
	$port = 6379;
	//3.连接redis服务器
	$redis->connect($host , $port);
	
	return $redis->del($key);
	
}

//获取论文
function Paper($id)
{
	//1.实例化对象
	$redis = new \Redis();
	//2.定义主机和端口
	$host = '127.0.0.1';
	$port = 6379;
	//3.连接redis服务器
	$redis->connect($host , $port);
	return unserialize($redis->get("paper:id:".$id.":content"));
}



//读取pdf给前台显示
 function parserPdf($path)
{
	include_once '../extend/pdfparser/vendor/autoload.php';

	$parser = new \Smalot\PdfParser\Parser();
	// 调用解析方法，参数为pdf文件路径，返回结果为Document类对象
	$path = str_replace("\\", "/", $path);

	$document = $parser->parseFile($path);

	if(!$document){
		//解析出错
		return [];
	}

	// 获取所有的页
	$pages = $document->getPages();
	// var_dump($pages);exit;
	  //提取第一页的内容，想提取多页，可以按照下面的方法，用$key来控制要获取的页数
	// 逐页提取文本
//		$pattern = '/[^,.;\s]+[,.;\s]/';
//		$pat = "/[\x80-\xff]+/";
	$pat = "/([^\n]+)\n/";
	$content = []; 
	foreach($pages as $key=>$page){
		$content[$key] = '';
//		halt(iconv_set_encoding('utf8', 'gbk',$pages[$key]->getText()));
		preg_match_all($pat, $pages[$key]->getText(),$matches);

		if(!empty($matches[1])){
			foreach($matches[1] as $match){
				$content[$key] .= preg_replace("/\s+|[<br>]+/", ' ',$match)."<br>";
			}
		}else{
			//处理特殊情况
			$content[$key] = $pages[$key]->getText();
			// $content[$key] = preg_replace("/[\n]+/", '<br>',$pages[$key]->getText());
		}
				
	}
  
	return $content; 
}

//处理要查重的论文，对其进行分句，并除去少于指定文字数量的句子
function deal($path)
{
	include_once '../extend/pdfparser/vendor/autoload.php';
	$parser = new \Smalot\PdfParser\Parser();       
	// 调用解析方法，参数为pdf文件路径，返回结果为Document类对象
	$document = $parser->parseFile($path);
	// 获取所有的页
//	$text = $document->getText();
	
	//去掉空白字符
//	$text = preg_replace("/\s+|[<br>]+/", '', $text);
	
//	$arr = preg_split('/\.|\,|\?|\:|\"|\;|\、|\。|\，|\？|\：|\；|\!|\！/is', $text);
//	halt($arr);
	$pages = $document->getPages();
	$details  = $document->getDetails();
	
	
//	$pattern = '/[^,.;\s]+[,.;\s]/';
//	$pat = "/[\x80-\xff]+/";
//	preg_match_all($pat, $text,$matches);
//	dump($matches);
	$detail = [];
	
	$detail['Creator'] = $details['Creator'];
	$detail['CreationDate'] = $details['CreationDate'];
	$detail['Pages'] = $details['Pages']-1;
	$detail['wordNum'] = 0;
	$detail['frontWordNum'] = 0;
	
	$content = []; 
	$pat = "/[\x80-\xff]+/";
	foreach($pages as $key=>$page){
//		$endpage = 	$pages[$key]->getText();
//		dump($pages[$key]->getText());;
//		exit;
		
		if($key == 0){
			continue;
		}
		
		if($key == $detail['Pages']){
			$detail['endPage'] = $pages[$key]->getText();
		}
		//除去空字符
		$str = preg_replace("/\s+|[<br>]+/", '', $pages[$key]->getText());
		
		
		$detail['wordNum'] += mb_strlen($str);
		
		if($key != $detail['Pages']){
			$detail['frontWordNum'] += mb_strlen($str);
		}
		
		//记录每页字数
		$detail[$key] = mb_strlen($str);
		
		preg_match_all($pat, $str,$matches);
		foreach($matches[0] as $match){
//			preg_replace('/\s{1,}/', ' ',$match)

//			\u2026|\uff1f|\uff0c|\uff1a|\u3001|\u3002|\uff01|\uff1b|\u201d|\u2019
			if(mb_strlen($match)>=15){
				
				//除去标点符号
				preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]+/u', $match,$ma);
				foreach($ma as $m){
					foreach($m as $v){
						if(mb_strlen($v)>=15){
							$content[] = $v;
						}
					}	
				}
				
			}
		}
		
	} 
	return ['content'=>$content,'detail'=>$detail]; 
}



function passPaper($path)
{
	$res = [];
//	$result = Db::query("SELECT * FROM paper_word");
	$result = deal($path);
	
	$content = $result['content'];
	$num = count($content);
	$result['detail']['wordNum'] = 0;
	foreach($content as $v){
		$result['detail']['wordNum'] += mb_strlen($v);
	}
	
	//复制文字
	$result['detail']['copyWordNum'] = 0;
//	halt($content);
//	$cosine = new CosineSimilar;s
	foreach($content as $data){
//		$res[] = Db::query("SELECT * FROM paper_word WHERE MATCH (content) AGAINST ('".$data."');");
//		dump($data);
		foreach(Elastic::search($data) as $v){
			if(strpos($v['content'], $data) !== false){
				$res[] = ['id'=>$v['id'],'word'=>$data];
				$result['detail']['copyWordNum'] += mb_strlen($data);
				break;
			}
			
//			$cosine->index($v['content'], $data);
		}		
	}
	$result['detail']['copyWordP'] = round(($result['detail']['copyWordNum']/$result['detail']['wordNum'])*100,2);
	$result['detail']['frontCopyWordP'] = round(($result['detail']['copyWordNum']/$result['detail']['wordNum'])*100,2);
//	dump($res);
	$p = round((count($res)/$num)*100,2);
	$data = ['result'=>$res,'p'=>$p,'detail'=>$result['detail']];
	return $data;
}

//先数据库写入段落作为查重的库
function addPaperWord()
{
	//一次写入25条论文
	$res = Db::table('paper_word_id')->find();
	if($res == null){
		$n = 0;
	}else{
		$n = $res['id'];
	}
	$num = 0;//解析成功数量
	$need = 0;//遍历个数总量

	$data = [];
	$paperList = Db::table('paper_lunwen')->where('id','>=',$n+1)->cursor();	//tp5封装了PHP的生成器的新特性
	foreach($paperList as $paper){
		$compare = paper($paper['id']);//现有库的单篇论文
		if(false !==$compare){
			
			for($j=0;$j<count($compare);$j++){

				$str = preg_replace("/\s+|[<br>]+/", '', $compare[$j]);
//				dump($str);
				preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]+/u', $str,$matches);
				
//				Db::table('paper_word')->insert(['content'=>$str]);
//				dump($matches);
				$oneWord = '';
				$f = '';//临时变量
//				dump($matches);
				foreach($matches as $match){
					foreach($match as $k=>$v){
						dump($v);	
						while(strlen($v)>=255){
							if($oneWord!=''){
								Db::table('paper_word')->insert(['content'=>$oneWord]);
							}
							$oneWord = mb_substr($v,0, 85);
							Db::table('paper_word')->insert(['content'=>$oneWord]);
							
//							dump($oneWord);
							$v= mb_substr($v,85);
							if(strlen($v)<=255){
								$oneWord = $v;
							}
//							dump($v);
						}
						
						$f = $oneWord.$v;

						if(strlen($f)>255){
//							dump($oneWord);
							Db::table('paper_word')->insert(['content'=>$oneWord]);
							$oneWord = $v;
//							dump($oneWord);
						}else{
							$oneWord .=$v;
//							dump($oneWord);
						}
						
					}		
				}


			}

			$num++;
		}

		$need++;
		if($need >= 25)
			break;
	}

	var_dump('共写了: '.$num);
		
	//实际遍历个数 $n+$need
	//写入数据库
	$data = ['id'=>$n+$need];
	Db::table('paper_word_id')->where('id',$res['id'])->update($data);
}






