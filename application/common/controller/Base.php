<?php
/**
 * 基础控制器
 * 必须继承自think\Controller.php
 */
 
 namespace app\common\controller;
 use think\Controller;
 use app\common\model\Cate;
 use app\common\model\Site;
 use app\common\model\Rank;
 use app\admin\common\model\Post as PostModel;
 use app\common\model\Consumer;
 use think\facade\Session;
 use think\facade\Request;
 use think\Db;
 use think\facade\Env;
 
 
 class Base extends Controller
 {
 	/**
	 * 初始化方法
	 * 创建常量，公共方法
	 * 在所有的方法之前被调用
	 */
 	protected function initialize()
    {
    	//显示分类导航
    	$this->showNav();
		
		//检测网站是否关闭
		$this->is_open();

		//显示热门论文
		$this->showHotPaper();
		
		//显示所有贴子
		$this->showPost();
    }
	
	//页面静态化
	protected function buildHtml($templateFile,$info,$path='/runtime/cache') {

        $content = $this->view->fetch($templateFile);
//		var_dump($_SERVER['REQUEST_URI']);exit;
		$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] == '/' ? '/index/index/index.html':$_SERVER['REQUEST_URI'];
		$file = Env::get('root_path').$path.rtrim($_SERVER['REQUEST_URI'],'/');
		$path = dirname($file);

        if(!is_dir($path)){
        	// 如果静态目录不存在 则创建
        	mkdir($path,0777,true);
        }  
		try{
			//写入缓存
			file_put_contents($file, $content,LOCK_EX);

			//写入缓存时间
			$file = dirname($path).'/expire.txt';
			if(file_exists($file)){
				$data = file_get_contents($file);
				$data = json_decode($data,true);
				$data[$info['id']] = time() + 43200;
				file_put_contents($file, json_encode($data),LOCK_EX);
			}else{
				$data = [];
				$data[$info['id']] = time() + 43200;

				file_put_contents($file, json_encode($data),LOCK_EX);
			}

			return $content;
		}catch(\Exception $e){
			return $content;
		}	
			
    }
	
	//直接获取静态文件
	protected function getCacheHtml($info,$path='/runtime/cache'){
		//兼容网站域名
		$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] == '/' ? '/index/index/index.html':$_SERVER['REQUEST_URI'];
		$file = Env::get('root_path').$path.rtrim($_SERVER['REQUEST_URI'],'/');

		//读取缓存时间
		$file = dirname(dirname($file)).'/expire.txt';
		if(!file_exists($file)){
			//不存在缓存文件
			return false;
		}

		$data = file_get_contents($file);
		$data = json_decode($data,true);

		if(!isset($data[$info['id']])){
			//不存在缓存
			return false;
		}

		if(time() > $data[$info['id']]){
			//缓存失效
			return false;
		}

		$file = Env::get('root_path').$path.rtrim($_SERVER['REQUEST_URI'],'/');
		try{
			$content = @file_get_contents($file);
		}catch(\Exception $e){
			return false;
		}		
		if($content){
			return $content;
		}
	}
	
	//检查是否已经登录：防止重复登录：放在登录验证方法中调用
	protected function logined()
	{
		if(Session::has('user_id')){
			echo "<script>alert('你已经登录了!');history.back();</script>";
		}
	}
	
	//检查是否未登录:放在需要登录操作的方法的最前面，例如发布文章
	protected function isLogin()
	{
		if(!Session::has('user_id')){
			echo "<script>alert('你还没有登录!');location.href='/';</script>";
		}
	}
	
	//首页导航栏目
	protected function showNav()
	{
		if(Session::get('user_id') == null){
			$rankList = Rank::all(function($query){
				$query->distinct(true)->field('rank_name')
					->limit(6);
			});
			$allList = Rank::all(function($query){
				$query->distinct(true)->field('rank_name');
			});
			$schoolRank = [];
		}else{
			if(Session::get('user_school') == '未指定学校'){
				$rankList = Rank::all(function($query){
					$query->distinct(true)->field('rank_name')
						->limit(6);
				});
				$allList = Rank::all(function($query){
					$query->distinct(true)->field('rank_name');
				});
				$schoolRank = [];
			}else{
				$rankList = Rank::all(function($query){
					$query->where('school_name',Session::get('user_school'))
						->limit(6);
				});

				
				$schoolRank = Rank::all(function($query){
					$query->where('school_name',Session::get('user_school'));
				});

				
				$data = [];
				foreach($schoolRank as $school){
					$data[] = $school['rank_name'];
				}

				$allList = Rank::all(function($query) use ($data){
					$query->where('rank_name','NOT IN',$data)->distinct(true)->field('rank_name');
				});

			}
			
			
			
		}
		
		$userInfo = Consumer::get(Session::get('user_id'));
//		$userInfo['user_img'] = substr($userInfo['user_img'],1);
		$this->view->assign('userInfo',$userInfo);
		
		$this->view->assign('active','0');	
		$this->view->assign('rankList',$rankList);
		$this->view->assign('allList',$allList);
		$this->view->assign('schoolRank',$schoolRank);
	}
	
	//检测站点是否关闭
	public function is_open()
	{
		//1.获取当前站点状态
		$isOpen = Site::where('status',1)->value('is_open');
		
		//2.如果站点已经关闭，那我们只允许关闭前台，后台是不允许关闭
		if($isOpen==0 && Request::module()=='index'){
			//关闭网站
			$info= <<< 'INFO'
<body style="background-color:#333">
<h1 style="color:#eee;text-align:center;margin:200px">站点维护中...</h1>
</body>
INFO;
		exit($info);
		}
	}
	
	//检测注册是否关闭
	public function is_reg()
	{
		//1.获取当前站点状态
		$isReg = Site::where('status',1)->value('is_reg');
		return $isReg;
	}
	
	//显示所有贴子
	public function showPost()
	{
		
		$postList = PostModel::all(function($query){
			$query->limit(5);
		});
		$this->assign('postList',$postList);
	}

	//显示热门论文
	public function showHotPaper()
	{
		// filed('id,lunwentitle,')
		$hotPaper = Db::table('paper_lunwen')->field('id,lunwen_title,rank_type,lunwen_rank,pv')->order('pv','desc')->limit(10)->select();
		$this->assign('hotPaper',$hotPaper);
	}
	
	
	/**
	 * 判断是否拥有权限
	 * @param $user_id 用户id
	 * @param $url	权限url
	 * @param $json 是否为接口调用
	 */
	protected function hasPower($user_id,$url,$json = false)
	{

		if(!is_null($user_id)){
			//查询用户的角色
			$role = Db::table('paper_consumer')->where('id',$user_id)->find();
			//角色名称
			$roleName = getRoleName($role['role_id']);
		}else{
			$roleName = '游客';
		}

		//查询角色所拥有的的权限
		$power = Db::table('paper_role_power')
						->field('power_id')
						->where('name',$roleName)
						->find();

		$hasPower = explode(',', $power['power_id']);

		//查询所需要的权限
		$need = Db::table('paper_power')
						->field('id')
						->where('url',$url)
						->find();

		//判断有无权限，是否为接口调用
		if(!in_array($need['id'], $hasPower)){
			if(!$json){
				echo "<script>alert('对不起，你没有此权限');history.back();window.opener=null;window.open('','_self');window.close();</script>";
				exit;
			}else{
				return false;
			}	
		}else{
			if($json){
				return true;
			}	
		}
		
	}
	
 }
?>