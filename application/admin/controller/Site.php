<?php
namespace app\admin\controller;

use app\admin\common\controller\Base;
use app\admin\common\model\Site as SiteModel;
use app\admin\common\model\Notice;
use app\admin\common\model\Lunwen;
use app\admin\common\model\School;
use think\facade\Request;
use think\facade\Session;
use think\Db;
use QL\QueryList;

class Site extends Base
{
	//系统设置
	public function index()
	{
		
		$siteInfo = SiteModel::get(['status'=>1]);
		$this->view->assign('siteInfo',$siteInfo);
		
		$noticeList = Notice::order('create_time','desc')->limit(2)->select();
		$this->view->assign('noticeList',$noticeList);
		$this->view->assign('title','系统设置');
		$this->view->assign('navActive','5');
		return $this->view->fetch('index');
	}
	
	//保存站点的修改信息
	public function save()
	{

		//1.获取到数据
		$data = Request::param();
		if(isset($data['is_open'])){
			if($data['is_open'] == 'on'){
				$data['is_open'] = 1;
			}
		}else{
			$data['is_open'] = 0;
		}
		
		if(isset($data['is_reg'])){
			if($data['is_reg'] == 'on'){
				$data['is_reg'] = 1;
			}
		}else{
			$data['is_reg'] = 0;
		}
		//2.更新
		if(SiteModel::update($data)){
			$this->success('更新成功');
		}
		
		$this->error('更新失败');
	}
	
	//发布通知
	public function addNotice()
	{
		
		$data = Request::param();
		$rule = [
			'content|通告内容'=>'require',
			'type|通告类型'=>'require'
		];
		
		$res = $this->validate($data,$rule);
		
		if(true !== $res){
			$this->error($res);
		}
		
		if(Notice::create($data)){
			$this->success('发布成功');
		}else{
			$this->error('发布失败');
		}
	}
	
	//关闭通告
	public function closeNotice()
	{
		
		$id = Request::param('id');
		$data = ['status'=>0];
		if(Notice::where('id',$id)->update($data)){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}
	}
	
	//删除通告
	public function deleNotice()
	{
		
		$id = Request::param('id');
		if(Notice::destroy($id)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	
	//缓存管理
	public function cacheSet()
	{
		
		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);
		$paperList = Lunwen::where('lunwen_file','NOT NULL')->select();
		
		//缓存失效个数
		$stat = 0;
		foreach($paperList as $paper){
			if(false === $redis->get("paper:id:".$paper['id'].":content")){
				$stat++;
			}
		}
		$this->view->assign('stat',$stat);
		$this->view->assign('title','缓存设置');
		$this->view->assign('navActive','5');
		return $this->view->fetch('cacheSet');
	}
	
	//设置缓存
	public function setCache()
	{

		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);
		
		$paperList = Lunwen::where('lunwen_file','NOT NULL')->select();
		
		foreach($paperList as $paper){
			$redis->set("paper:id:".$paper['id'].":content",serialize(parserPdf(substr($paper['lunwen_file'],1))),86400);
		}
		
		return ['status'=>1,'message'=>'缓存成功'];
	}
	
	//清除缓存
	public function clearCache()
	{
		
		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);
		
		$paperList = Lunwen::where('lunwen_file','NOT NULL')->select();

		foreach($paperList as $paper){
			$redis->del("paper:id:".$paper['id'].":content");
		}
		
		return ['status'=>1,'message'=>'清除缓存成功'];
	}
	
	//查看缓存
	public function getCache()
	{
		
		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);
		$paperList = Lunwen::where('lunwen_file','NOT NULL')->select();
		
		foreach($paperList as $paper){
			dump($redis->get("paper:id:".$paper['id'].":content"));
		}
		
	}
	
	//添加论文
	public function addPaper()
	{
		
		$schoolList = School::all();
		$this->view->assign('title','添加论文');
		$this->view->assign('navActive','5');
		$this->view->assign('schoolList',$schoolList);
		return $this->view->fetch('addPaper');
	}
	
	//doAddPaper
	public function doAddPaper()
	{
		$data = Request::param();
		$number =$data['number']/15;
		include "../extend/querylist/vendor/autoload.php";
		
		$keywords = $data['keywords'];
		$keywords = urlencode($keywords);
		for($i=0;$i<$number;$i++){
			//请求地址
			$urlArr[] = "http://search.cnki.net/Search.aspx?q={$keywords}&rank=relevant&cluster=all&val=&p=".($i*15);
		}
		$GLOBALS['num'] = 0;
		$start = time();
		
		//多线程扩展
		QueryList::run('Multi',[
		    //待采集链接集合
		    'list' => $urlArr,
		    'curl' => [
		        'opt' => array(
		                    //这里根据自身需求设置curl参数
		                    CURLOPT_SSL_VERIFYPEER => false,
		                    CURLOPT_SSL_VERIFYHOST => false,
		                    CURLOPT_FOLLOWLOCATION => true,
		                    CURLOPT_AUTOREFERER => true,
		                    //........
		                ),
		        //设置线程数
		        'maxThread' => 100,
		        //设置最大尝试数
		        'maxTry' => 3 
		    ],
		    'success' => function($a){
		        //采集规则
		        $reg = array(
			        
					"title"=>array(".wz_tab .wz_content h3","text",'-img'),
					
					"url"=>array(".wz_tab .wz_content h3","html",'-img'),
					
					"content"=>array(".wz_tab .wz_content .width715",'text'),
					
					"info"=>array(".wz_tab .wz_content .year-count",'text','-.count'),
				);
				
		        $ql = QueryList::Query($a['content'],$reg)->data;
		        //打印结果，实际操作中这里应该做入数据库操作
			   //遍历数据
			   if(!empty($ql)){
			   		$data = Request::param();
			   		foreach($ql as $v){
				   		$school_name = $data['school'];
					   	$lunwen_title = $v['title'];
				   		$writer = "匿名";
						$major = "音乐";
						$rank_type = "文娱";
						$lunwen_rank = "歌曲创作";
						$biaoji = "精选";
						$addtime = time();
						$lunwen_terms = 1;
					   	$content = $v['content'];
						$info = $v['info'];
					   	preg_match_all("/href=\"([^\s\"]+)/", $v['url'],$match);
					   	$url = $match[1][0];
						$sql = "insert into paper_lunwen (school_name,lunwen_title,writer,major,rank_type,lunwen_rank,biaoji,addtime,lunwen_terms,content,url,info) 
						values('".$school_name."','".$lunwen_title."','".$writer."','".$major."','".$rank_type."','".$lunwen_rank."','".$biaoji."','".$addtime."','".$lunwen_terms."','".$content."','".$url."','".$info."')";
						if(Db::execute($sql)){
							$GLOBALS['num']++;
						}else{
							echo "插入失败1次";
						}
				   }
		   		}
		    }	
		]);
		
			$end = time();
			$s = $end - $start;
			echo "抓取数量:",$GLOBALS['num'],'条';
			unset($GLOBALS['num']);
			unset($data);
			unset($number);
			echo "耗费时间 ",$s,'s';
	}


	//设置redis缓存
	public function setPaperCache()
	{

		//一次缓存100个
		$res = Db::table('paper_cache_num')->find();
		if($res == null){
			$n = 0;
		}else{
			$n = $res['id'];
		}

		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);

		$list = Db::table('paper_lunwen')->where('id','>=',$n+1)->cursor();	//tp5封装了PHP的生成器的新特性
		$num = 0;//缓存成功数量
		$need = 0;//变量个数总量
		foreach($list as $paperInfo){
			
			$content = parserPdf(substr($paperInfo['lunwen_file'],1));
			if(!empty($content)){
				//解析成功的
				$bool = $redis->set("paper:id:".$paperInfo['id'].":content",serialize($content),86400);
				if($bool){
					//缓存成功
					$num += 1;
				}else{
					var_dump('缓存失败: '.$paperInfo['id']);
				}
			}else{
				//解析失败
				var_dump('文档解析失败: '.$paperInfo['id']);
			}

			$need += 1;
			if($need >= 25)
				break;			
		}
		var_dump('共缓存了: '.$num);
		
		//实际遍历个数 $n+$need
		//写入数据库
		$data = ['id'=>$n+$need];
		Db::table('paper_cache_num')->where('id',$n)->update($data);
	}

	//添加新的句子
	public function addWord()
	{
		addPaperWord();	
	}
}
?>