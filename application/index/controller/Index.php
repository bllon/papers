<?php
/*
**index前台类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\index\controller\SocketRoom;
use app\common\model\Cate;
use app\common\model\Rank;
use app\common\model\Sele;
use app\common\model\Lunwen;
use app\admin\common\model\Notice;
use app\admin\common\model\Post as PostModel;
use app\common\model\Comment;
use app\common\model\Consumer;
use app\common\model\Collect;
use app\common\model\Borrow;
use app\common\model\Pass;
use app\api\controller\Simhash;
use app\api\controller\CosineSimilar;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;


class Index extends Base
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
		var_dump($ret);
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

	//首页
    public function index()
    {			
			
			$map = [];
			$map2 = [];
			//显示公开论文
			$map[] = ['lunwen_terms','=',1];

			$keywords = trim(Request::get('keywords'));
			if(!empty($keywords)){
				//搜索sphinx
				require_once '../extend/sphinx/sphinxapi.php';
				$sph = new \SphinxClient();
				$sph->SetServer('localhost', 9312);
				//第二个参数，默认是*，要查询的索引名字
				$ret = $sph->Query($keywords, 'papers');
				//提取出所有文章id
				if(isset($ret['matches'])){
					$id = array_keys($ret['matches']);
				}else{
					$id = [];
				}
				
				// var_dump($id);
				// //查询出所有文章
				// $lunwenList = Db::table('paper_lunwen')
				// 						->whereOr('id','in',$id)
				// 						->order('addtime','asc')
				// 						->select();

				$map[] = ['id','in',$id];
				$map2[] = ['id','in',$id];
			}

			
			
			//没登陆
			if(Session::get('user_id') == null){
				//实现搜索功能
		    	// $keywords = trim(Request::get('keywords'));
				// if(!empty($keywords)){
				// 	$map[] = ['lunwen_title','like',"%{$keywords}%"];
				// }
				
		    	$rank_name = Request::param('rank_name');
				
				
				if(isset($rank_name)){
					
					$map[] = ['rank_type','=',$rank_name];
					
					$lunwenList = Db::table('paper_lunwen')
								->where($map)
								->order('addtime','asc')
								->paginate(15);
							
					$this->view->assign('rankName',$rank_name);
					
				}else{
					
					$this->view->assign('rankName','全部论文');
					$lunwenList = Db::table('paper_lunwen')
								->where($map)
								->order('addtime','asc')
								->paginate(15);
				}
				
			}else{
				//既显示公开论文，也显示学校的
				$map2[] = ['school_name','=',Session::get('user_school')];
				//实现搜索功能
		  //   	$keywords = trim(Request::get('keywords'));
				// if(!empty($keywords)){
				// 	$map[] = ['lunwen_title','like',"%{$keywords}%"];
				// 	$map2[] = ['lunwen_title','like',"%{$keywords}%"];
				// }
				
		    	$rank_name = Request::param('rank_name');
				
				
				if(isset($rank_name)){
					//条件3
					
					$map[] = ['rank_type','=',$rank_name];
					$map2[] = ['rank_type','=',$rank_name];
					
					$lunwenList = Db::table('paper_lunwen')
								->whereOr([$map,$map2])
								->order('addtime','asc')
//								->select();
								->paginate(15);
//					dump($rank_name);
//					dump($lunwenList);		
					$this->view->assign('rankName',$rank_name);
					
				}else{
					
					$this->view->assign('rankName','全部论文');
					$lunwenList = Db::table('paper_lunwen')
								->whereOr([$map,$map2])
								->order('addtime','asc')
//								->select();
								->paginate(15);
								
//								dump($lunwenList);
				}
			}			
			
			
			
		
			//查询最新通告
			$noticeInfo = Notice::where('status',1)->order('create_time','desc')->find();
	    	$this->view->assign('noticeInfo',$noticeInfo);
			
			$this->view->assign('empty','<h3>没有论文</h3>');
			$this->view->assign('title','paper');
			$this->view->assign('lunwenList',$lunwenList);
			
			if(!empty($keywords)){
				$this->view->assign('keywords',trim($keywords));
			}			
			
	    	return $this->view->fetch('index');
    }

	
	//论文详情
	public function paperDetail()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/paperDetail');
		
		//获取缓存文件
		$content = $this->getCacheHtml();
		if($content){
			return $content;
		}


		$id = Request::param('id');
		$paperInfo = Lunwen::get(function($query) use ($id){
			$query->where('id',$id);
		});
//		var_dump($paperInfo);exit;
		$content = Paper($id);
		if(null == $content){
			if($paperInfo['lunwen_file'] !== null){
				$paperInfo['content'] = parserPdf(substr($paperInfo['lunwen_file'],1));
				
				//设置缓存
				setCache("paper:id:".$paperInfo['id'].":content",serialize(parserPdf(substr($paperInfo['lunwen_file'],1))),86400);
			}else{
				$arr = [];
				$arr[] = $paperInfo['content'];
				$paperInfo['content'] = $arr;
			}
		}else{
			$paperInfo['content'] = $content;
		}
		
		
		$this->view->assign('title',$paperInfo['lunwen_title']);
		$this->view->assign('paperInfo',$paperInfo);
//		$this->view->assign('paperfile',substr($paperInfo['lunwen_file'],1));
		return $this->buildHtml('paperDetail');
//		return $this->view->fetch('paperDetail');
	}

	//获取收藏和借阅状态
	public function paperStatu(){
		$id = Request::param('id');
		//是否收藏过
		$collect = Collect::where('user_id',Session::get('user_id'))->where('paper_id',$id)->find();
		$collectaction = 0;
		if($collect !==null){
			$collectaction = 1;
		}
		
		$borrow = Borrow::where('user_id',Session::get('user_id'))->where('paper_id',$id)->find();
		$borrowaction = 0;
		if($borrow !==null && $borrow['status']!==3){
			$borrowaction = 1;
		}

		$data = ['collectaction'=>$collectaction,'borrowaction'=>$borrowaction];
		return ['status'=>1,'message'=>json_encode($data)];
	}
	
	//添加阅读量
	public function incPv()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/paperDetail');
		$paperId = Request::param('id');
		$paper = Lunwen::where('id',$paperId)->setInc('pv');
		return "阅读使我快乐";
	}
	
	//处理论文上传
	public function savePaper()
	{
		$this->isLogin();

		if(Request::isPost()){
			//1.获取用户提交的文章信息
			$data = Request::param();
//			$data['cate'] = implode($data['cate'], ',');
//			return ['data'=>$data];
			$res = $this->validate($data,'app\common\validate\Paper');
			if(true !== $res){
				$this->error($res);
//				return ['status'=>-1,'message'=>$res];
			}else{
				//验证成功
				//获取一下上传的图片信息
				$title_img = $_FILES['title_img'];
				$paper = $_FILES['paper'];
				
				//文件限制
				$fileConfig = [
					'title_img'=>[
						'maxSize'=>20000,
						'ext'=>['jpg','jpeg','png','gif'],
					],
					'paper'=>[
						'maxSize'=>2000000,
						'ext'=>['pdf'],
					]
				];
				
				$checkImg = checkFile($title_img,$fileConfig['title_img']);
				$checkPaper = checkFile($paper,$fileConfig['paper']);
				
				if(false == $checkImg['check']){
					$this->error($checkImg['message']);
				}
				if(false == $checkPaper['check']){
					$this->error($checkPaper['message']);
				}
				
				//设置文件目录
				$imgpath = "uploads/images/".date('Y/m/d',time());
				$paperpath = "uploads/paper/".date('Y/m/d',time());
				
				if(!file_exists($imgpath)){
					mkdir($imgpath,0777,true);
				}
				if(!file_exists($paperpath)){
					mkdir($paperpath,0777,true);
				}
				
				if(!move_uploaded_file($title_img['tmp_name'], $imgpath.'/'.md5($checkImg['name']).'.'.$checkImg['type'])){
					$this->error('上传图片失败');
				}
				if(!move_uploaded_file($paper['tmp_name'], $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'])){
					$this->error('上传文档失败');
				}
				
				$data['title_img'] = $imgpath.'/'.md5($checkImg['name']).'.'.$checkImg['type'];
				$data['file_path'] = $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'];
				
				//将数据写到数据表中
				if(Paper::create($data)){
					$this->success('上传成功');
//					return ['status'=>1,'message'=>'上传成功'];
				}else{
					$this->error('上传失败');
//					return ['status'=>0,'message'=>'上传失败'];
				}
			}
			
		}else{
			$this->error('请求异常');
//			return ['status'=>-2,'message'=>'请求异常'];
		}
	}
	
	//关闭通告
	public function closeNotice()
	{
		//设置cookie关闭通告
		Cookie::set('notice','1',7200);
	}
	
	//论文分类页面
	public function rank()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/rank');
		$seleList = Sele::all();
		$this->view->assign('seleList',$seleList);
		$this->view->assign('title','论文分类');
		$this->view->assign('active','1');
		return $this->view->fetch('rank');
	}
	
	//显示二级分类论文
	public function sele()
	{
		$map = [];
		
		//显示公开论文
		$map[] = ['lunwen_terms','=',1];
		$map2 = [];
		//没登陆
		if(Session::get('user_id') == null){

			$sele_name = Request::param('sele_name');
				
				$map[] = ['lunwen_rank','=',$sele_name];
				
				$lunwenList = Db::table('paper_lunwen')
							->where($map)
							->order('addtime','desc')
							->paginate(15);
						
				$this->view->assign('seleName',$sele_name);
			
		}else{
			//既显示公开论文，也显示学校的
			$map2[] = ['school_name','=',Session::get('user_school')];
			
			
	    	$sele_name = Request::param('sele_name');	
				
				$map[] = ['lunwen_rank','=',$sele_name];
				$map2[] = ['lunwen_rank','=',$sele_name];
				
				$lunwenList = Db::table('paper_lunwen')
							->whereOr([$map,$map2])
							->order('addtime','desc')
//								->select();
							->paginate(15);

				$this->view->assign('seleName',$sele_name);
				
		}			
		
		
		
	
		//查询最新通告
		$noticeInfo = Notice::where('status',1)->order('create_time','desc')->find();
    	$this->view->assign('noticeInfo',$noticeInfo);
		
		$this->view->assign('empty','<h3>没有论文</h3>');
		$this->view->assign('title','paper');
		$this->view->assign('lunwenList',$lunwenList);
		
//		halt(count($lunwenList));
    	return $this->view->fetch('sele');
	}

	//论文查重页面
	public function paperpass()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');

		$token = substr(str_shuffle('qwertyuiopasdfghjklzxcvbnm1234567890'),0,15);
		Session::set('token',$token);
		$this->view->assign('title','论文查重');
		return $this->view->fetch('paperpass');
	}
	
	//执行查重，并返回结果
	public function doPass()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');
		
		$lock = new Lock();
		//获取锁			
		$identifier = $lock->getLock('test');	

//		if(getCache(Session::get('user_id').":passpaper")!==false){
//			$result = unserialize(getCache(Session::get('user_id').":passpaper"));
//			$this->view->assign('result',$result['result']);
//			$this->view->assign('p',$result['p']);
//			$this->view->assign('title','查重结果');
//			return $this->view->fetch('dopass');
//		}

		if(Request::isPost()){
			$userId = Request::param('user_id');
			$paperTitle = Request::param('papertitle');
			$paper = Request::file('paper');
			
			//3年前的时间
			$time = time(); //当前时间戳
			$date = date('Y',$time) - 3 . '-' . date('m-d H:i:s');//一年后日期
			$time = strtotime($date);
			
//			$lunwenList = Db::table('paper_lunwen')
//							->where('lunwen_title',$paperTitle)
//							->where('addtime', '>=', $time)
//							->order('addtime','desc')
//							->select();
			
			
			//创建对象
			$obj = new \ronylee\phpanalysis\Analysis;
		
			//调用分词方法
			$res = $obj->run($paperTitle);
			$res = explode(' ', $res);
			array_shift($res);
			
			//创建查询数组,保存关键字
			$map = [];
			
			foreach($res as $row){
//					$lunwenList = Db::query("SELECT * FROM paper_lunwen WHERE MATCH (lunwen_title) AGAINST ('".$row."');");
				if(mb_strlen($row)>=2){
					$map[] = '%'.$row.'%';
				}
			}
			
			//查询含有相似的标题
			$lunwenList = Db::table('paper_lunwen')
							->where('lunwen_title','like',$map,'and')
							->where('addtime', '>=', $time)
							->order('addtime','desc')
							->select();

			//先执行文件上传
			$paper = $_FILES['paper'];
				
			//文件限制
			$fileConfig = [
				'paper'=>[
					'maxSize'=>200000000,
					'ext'=>['pdf'],
				]
			];
			
			$checkPaper =  checkFile($paper,$fileConfig['paper']);
			
			if(false == $checkPaper['check']){
				$this->error($checkPaper['message']);
			}
			
			$paperpath = "uploads/paper/".date('Y/m/d',time());
			if(!file_exists($paperpath)){
				mkdir($paperpath,0777,true);
			}
			if(!move_uploaded_file($paper['tmp_name'], $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'])){
				$this->error('上传文档失败');
			}
			$data['file_path'] = $paperpath.'/'.md5($checkPaper['name']).'.'.$checkPaper['type'];
			
//			$content = parserPdf($data['file_path']);
			
			$result = passPaper($data['file_path']);
			
//			setCache(Session::get('user_id').":passpaper",serialize($result),3600);
			
//			$sim = new Simhash;
//			foreach($result['result'] as $v){
//				$sim->index($v);
//			}
//			$sim->index($data['file_path']);
			
//			dump($result['result']);
			
			unlink($data['file_path']);
			
			
		}
//			$this->view->assign('result',$result['result']);
//			$this->view->assign('p',$result['p']);
//			$this->view->assign('title','查重结果');
			
			//保存数据库并重定向
			
			
			$passResult = [
				'title'=>$paperTitle,
				'lunwenList'=>$lunwenList,
				'result'=>$result['result'],
				'p'=>$result['p'],
				'detail'=>$result['detail']
			];
			
			$data = [];
			$data['user_id'] = Session::get('user_id');
			$data['title'] = $paperTitle;
			$data['result'] = json_encode($passResult);
			$data['create_time'] = $_SERVER['REQUEST_TIME'];
			
			if($pass = Pass::create($data)){
				$this->decPassNum();
				
				//释放锁
				$lock->releaseLock('test', $identifier);				
				return redirect('passRsult')->params([
					'id' => $pass->id
				]);
			}else{
				//释放锁
				$lock->releaseLock('test', $identifier);
				$this->error('系统出错');
			}
			
//			return $this->view->fetch('dopass');
	}
	
	//查重结果页面
	public function passRsult()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');

		$id = Request::param('id');
		$passResult = Pass::get($id);
		if(empty($passResult)){
			return "系统出错";
		}
		$result = json_decode($passResult['result'],true);
		
		$this->view->assign('lunwenList',$result['lunwenList']);
		$this->view->assign('result',$result['result']);
		$this->view->assign('p',$result['p']);
		
		$this->view->assign('id',$id);
		
		$this->view->assign('papertitle',$result['title']);
		$this->view->assign('detail',$result['detail']);
		$this->view->assign('copywordnum',count($result['result']));
		$this->view->assign('title','查重结果');
		return $this->view->fetch('result');
	}
	
	//判断用户查重次数是否用完
	public function getPassNum(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');

		$id = Session::get('user_id');		
		$consumer = Consumer::get($id);
		if($consumer['passnum'] <= 0){
			return ['status'=>0,'message'=>'你的查重次数已用完'];
		}else{
			return ['status'=>1,'message'=>'存在查重机会'];
		}
	}
	
	//ajax请求增加查重次数
	public function decPassNum()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');

		$id = Session::get('user_id');
		$consumer = Consumer::get($id);
		$consumer->passnum = ['dec', 1];
		$consumer->save();
	}
	
	//获取检测结果文档
	public function getReport(){
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');
		
		$data = Request::param();
		$passResult = Pass::get($data['pass_id']);
		if(empty($passResult)){
			return "系统出错";
		}
		
		$html = [
			'html1'=>$data['html1'],
			'html2'=>$data['html2']
			];
		
		$data =	[
			'pass_id'=>$data['pass_id'],
			'html'=>json_encode($html)
		];
		
		//存储数据库
		if($id = Db::name('paper_create_pdf')->insertGetId($data)){
			return $id;
		}else{
			return 'error';
		}
		
	}
	
	
	//生成pdf文档
	public function toPdf(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/toPdf');
		
		//拿到当前的记录
		$id = Request::param('id');
		
		$data = Db::table('paper_create_pdf')->where('id',$id)->find($id);
		
		
		$html = json_decode($data['html']);
		
		require_once '../extend/pdfparser/vendor/autoload.php';
		
//		require_once '../extend/pdfparser/vendor/tecnickcom/tcpdf/tcpdf.php';
		
		//实例化TCPDF类 页面方向（P =肖像，L =景观）、测量（mm）、页面格式
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Nicola Asuni');
		$pdf->SetTitle('paper.com');
		$pdf->SetSubject('TCPDF Tutorial');
		$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
		
		
		// set default header data
		$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, 'YI论文', 'paper.com');
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array('stsongstdlight', '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array('stsongstdlight', '', PDF_FONT_SIZE_DATA));
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		
		// ---------------------------------------------------------
		
		// add a page
		$pdf->AddPage();
		
		// set font
		$pdf->SetFont('stsongstdlight', 'B', 20);
		
		$pdf->Write(0, '检测结果', '', 0, 'L', true, 0, false, false, 0);
		
		$pdf->Ln(4);
		
		
		// set core font
		$pdf->SetFont('stsongstdlight', '', 10);
		
		// output the HTML content
		//$pdf->writeHTML($html, true, false, false, true,'');
		
		$pdf->MultiCell(200,20,$html->html1,0,'J',false,1,'','',true,0,true,true,0,'T',false );
		
		$pdf->Ln(5);
		
		$pdf->MultiCell(650,20,$html->html2,0,'J',false,1,'','',true,0,true,true,0,'T',false );
		
		//$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
		
		// reset pointer to the last page
		$pdf->lastPage();
		
		// ---------------------------------------------------------
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output('pass.pdf', 'D');
		
	}

	//用户设置
	public function setting()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/setting');

		$userInfo = Consumer::get(Session::get('user_id'));
		Session::set('user_name',$userInfo['name']);

		Cookie::set('user_name',$userInfo['name']);
		Cookie::set('user_img',$userInfo['user_img']);
		$this->view->assign('title','用户设置');
		$this->view->assign('userInfo',$userInfo);
		return $this->view->fetch('setting');
	}
	
	//修改用户设置
	public function saveSetting()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/setting');

		$data = Request::param();

		if($data['password'] == $data['pass'] || sha1($data['password']) == $data['pass']){

			unset($data['password']);
		}else{
			$data['password'] = sha1($data['password']);

		}
		unset($data['pass']);

		//设置文件目录
		$imgPath = "uploads/user_img/";
		
		if($_FILES['user_img']['size'] !== 0){
			
			$user_img = Request::file('user_img');
		
			$info = $user_img->move($imgPath);
			
			if($info){
				$filepath = "uploads/user_img/".$info->getSaveName();
				$image = \think\Image::open($filepath);
				$image->thumb(128,128)->save($filepath);
				$data['user_img'] = "/uploads/user_img/".$info->getSaveName();
			}else{
				$this->error($info->getError());
			}
		}

		$data['id'] = Session::get('user_id');
		if(Consumer::update($data)){
			Session::set('user_name',$data['name']);
			$this->success('更改成功');
		}else{
			$this->error('更改失败');
		}
	}

	//显示用户详情页
	public function userDetail()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/userDetail');

		$id = Request::param('id');
		$comment = Comment::where('id',$id)->find();
		$user = $comment['reply_user'];
		$userInfo = Consumer::where('name',$user)->find();

		//查询收藏论文
		$collect = Collect::where('user_id',$userInfo['id'])->select();
		$collectNum = count($collect);

		//查询累计借阅
		$borrow = Borrow::where('user_id',$userInfo['id'])->where('status',3)->select();
		$borrowNum = count($borrow);
		
		//查询累计查重次数
		$pass = Db::table('paper_pass')->where('user_id',$id)->select();
		$passNum = count($pass);

		unset($collect);
		unset($comment);
		unset($$borrow);
		unset($id);
		$this->view->assign('title',"{$user}的详情信息");
		$this->view->assign('userInfo',$userInfo);
		$this->view->assign('collectNum',$collectNum);
		$this->view->assign('borrowNum',$borrowNum);
		$this->view->assign('borrowNum',$passNum);
		return $this->view->fetch('userDetail');
	}
	
	//显示贴子详情
	public function postDetail()
	{
		$this->hasPower(Session::get('user_id'), 'index/index/postDetail');

		$postId = Request::param('id');
		$postInfo = PostModel::get($postId);

		// $commentList = Comment::where('post_id',$postId)->select();

		// $commentList = $this->getCommlist($postId);

		// $html = $this->postHtml($commentList);

		// $this->view->assign('postHtml',$html);

		$this->view->assign('postInfo',$postInfo);
		// $this->view->assign('commentList',$commentList);
		$this->view->assign('title',$postInfo['title']);
		return $this->view->fetch('postDetail');
	}

	//ajax获取评论接口
	public function getComment(){
		$id = Request::param('id');

		$commentList = $this->getCommlist($id);

		$html = $this->postHtml($commentList);
		return ['status'=>1,'message'=>$html];
	}

	//获取评论列表HTML
	public function postHtml($commentList,&$s = ''){
		$str = &$s;

		foreach($commentList as $comment){

			if($comment['lv'] == 1){
				$str .= '<li class="list-group-item">
							    <h5 class="list-group-item-heading"><img src="'.getConsumerImg($comment['user_id']).'" style="width:30px;height:30px;margin:5px 0px;border-radius:100%;"/>&nbsp;<a href="'.'/index/index/userDetail/id/'.$comment['id'].'">'.$comment['reply_user'].'</a>&nbsp;&nbsp;&nbsp;<small>'.$comment['create_time'].'</small>&nbsp;&nbsp;<span><button class="btn btn-sm btn-warning reply" style="padding:1px 3px;cursor:pointer;"  data-toggle="modal" data-target=".replymodel" replyId="'.$comment['id'].'">回复</button></span></h5>
							    <p>&nbsp;&nbsp;'.$comment['content'].'</p>';				
			}else{
					$str .= '<div style="width:93%;margin:0 auto;border-top:1px solid #ddd;">
									    <h5 style="margin:0;"><img src="'.getConsumerImg($comment['user_id']).'" style="width:30px;height:30px;margin:5px 0px;border-radius:100%;"/>&nbsp;<a href="'.'/index/index/userDetail/id/'.$comment['id'].'">'.$comment['reply_user'].'</a>&nbsp;<small>回复</small>&nbsp;<a href="/index/index/userDetail/id/'.$comment['reply_id'].'">'.getPostUser($comment['reply_id']).'</a>&nbsp;&nbsp;&nbsp;<small>'.$comment['create_time'].'</small>&nbsp;&nbsp;<span><button class="btn btn-sm btn-warning reply" style="padding:1px 3px;cursor:pointer;"  data-toggle="modal" data-target=".replymodel" replyId="'.$comment['id'].'">回复</button></span></h5>
									    <p>&nbsp;&nbsp;'.$comment['content'].'</p>';
			}	

			
			$children = $comment['children'];

			if($children !== null){							    						
				$this->postHtml($children,$str);		
			}
			if($comment['lv'] == 1){
				$str .= '</li>';
			}else{
				$str .= '</div>';
			}
			  							
		}
		
		return $str;		
	}

	//递归建立所有评论
	public function getCommlist($postId,$reply_id = 0,&$result = array(),&$lv=0){       
	    $arr = Comment::where('post_id',$postId)->where('reply_id',$reply_id)->select();

	    if(empty($arr)){
	        return array();
	    }
	    $lv++;
	    foreach ($arr as $cm) {
	    	//来存放每一级的结果  
	        $thisArr=&$result[];
	        $cm['lv'] = $lv;
	        $cm["children"] = $this->getCommlist($postId,$cm["id"],$thisArr,$lv);    
	        $lv = $cm['lv'];
	        //来存放每一级的结果  
	        $thisArr = $cm;

	    }
	    return $result;
   }
	
	//循环遍历
	public function getReply($postId,$reply_id = 0){
		$data = [];
		$commentList = Comment::where('post_id',$postId)->where('reply_id',$reply_id)->select();
		foreach($commentList as $comment){
			if($comment['reply_id'] == 0){
				dump($comment);
				$data[$comment['id']] = $comment;
				$this->getReply($comment['id']);
			}else{
				dump($comment);
				
			}
		}
	}
	
	//显示更多贴子
	public function morePost(){
		$this->hasPower(Session::get('user_id'), 'index/index/postDetail');

		$sta = Cookie::get('postSchool');
		
		$map = [];
		if($sta !== null && $sta == 1){
			$map[] = ['u.school_name','=',Session::get('user_school')];
		}
		
		$postList = Db::table('paper_post')
							->alias('p')->join('paper_user u','p.user_id = u.id')
							->field('p.id,p.title,p.subtitle,p.content,p.writer,p.user_id,u.school_name,p.create_time')
							->where($map)
							->order('create_time','desc')
							->paginate(3);
		$this->assign('postList',$postList);
		$this->view->assign('title','贴子专区');
		return $this->view->fetch('morePost');
	}
	
	//选择学校区域贴子
	public function selectPost(){
		$this->hasPower(Session::get('user_id'), 'index/index/postDetail');

		$data = Request::param('sta');
		Cookie::set('postSchool',$data);
		return ['status'=>1,'message'=>$data];
	}
	
	//查重记录
	public function passrecord(){
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');
					
		$passList = Db::table('paper_pass')
							->order('create_time','desc')
							->paginate(10);
		
		$this->assign('passList',$passList);
		$this->view->assign('title','查重记录');
		return $this->view->fetch('passrecord');
	}
	
	//评论贴子
	public function commit()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/commit');

		$data = Request::param();		
		$res = $this->validate($data,'app\common\validate\Comment');
		if(true !== $res){
			$this->error($res);
		}
		
		if(Comment::create($data)){
			$this->success('评论成功');
		}else{
			$this->error('评论失败');
		}
	}
	
	//回复评论
	public function reply()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/reply');

		$data = Request::param();
		$res = $this->validate($data,'app\common\validate\Comment');
		if(true !== $res){
			$this->error($res);
		}
		
		if(Comment::create($data)){
			$this->success('评论成功');
		}else{
			$this->error('评论失败');
		}
	}
	
	//收藏论文
	public function collect()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/collect');

		$data = Request::param();
		if($data['action'] == 0){
			if(Collect::create($data)){
				return ['status'=>1,'message'=>'收藏成功'];
			}else{
				return ['status'=>0,'message'=>'收藏失败'];
			}
		}else if($data['action'] == 1){
			unset($data['action']);
			if(Collect::destroy($data)){
				return ['status'=>1,'message'=>'取消收藏成功'];
			}else{
				return ['status'=>0,'message'=>'取消收藏失败'];
			}
		}
		
	}
	
	
	//借阅论文
	public function borrow()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$data = Request::param();
		$borrow = Borrow::where('paper_id',$data['paper_id'])->find();
		if($borrow !== null){
			return ['status'=>-1,'message'=>'已被借走'];
		}
		
		if($data['action'] == 0){
			if(Borrow::create($data)){
				return ['status'=>1,'message'=>'预约成功,等待管理员通知'];
			}else{
				return ['status'=>0,'message'=>'借阅失败'];
			}
		}else if($data['action'] == 1){
			unset($data['action']);
			if(Borrow::destroy($data)){
				return ['status'=>1,'message'=>'取消借阅成功'];
			}else{
				return ['status'=>0,'message'=>'取消借阅失败'];
			}
		}
		
	}
	
	//显示借阅和收藏详情
	public function bcDetail()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/collect');
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$collectList = Collect::where('user_id',Session::get('user_id'))->select();
		$borrowList = Borrow::where('user_id',Session::get('user_id'))->select();
		$this->view->assign('title','收藏和借阅');
		$this->view->assign('collectList',$collectList);
		$this->view->assign('borrowList',$borrowList);
		return $this->view->fetch('bcDetail');
	}
	
	//取消预约
	public function cancel(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$data = Request::param();
		
		if(Borrow::destroy($data)){
			return ['status'=>1,'message'=>'取消成功'];
		}else{
			return ['status'=>0,'message'=>'取消失败'];
		}
	}
	
	//归还论文
	public function replyReturn(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$id = Request::param('id');
		$borrow = Borrow::where('id',$id)->find();
		if($borrow == null){
			return ['status'=>-1,'message'=>'已归还'];
		}
		
		$data = ['id'=>$id,'status'=>2];
		if(Borrow::update($data)){
			return ['status'=>1,'message'=>'预约归还成功'];
		}else{
			return ['status'=>0,'message'=>'预约归还失败'];
		}
	}
	
	//删除历史借阅
	public function deleborrow()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/borrow');

		$id = Request::param('id');
		if(borrow::destroy($id)){
			return ['status'=>1,'message'=>'删除成功'];
		}else{
			return ['status'=>0,'message'=>'删除失败'];
		}
	}
	
	//获取查重的句子
	public function getWord(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/paperpass');

		$id = Request::param('id');
		$wordInfo = Word::get($id);
		$this->view->assign('title','句子来源');
		$this->view->assign('wordInfo',$wordInfo);
		return $this->view->fetch('getWord');
	}
	
	//社区首页
	public function comunity(){
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/comunity');

		//获取所有房间
		//redis读取
		// $comunity = new Comunity;
		// $groupList = $comunity->getAllGroup();

		//数据库读取
		$groupList = Db::table('paper_group')->limit(10)->select();
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
		
		
		$data1 = array_slice($data, 0,10);
		$data2 = array_slice($data, 10,10);

		$this->view->assign('title','社区首页');
		$this->view->assign('groupList',$groupList);
		$this->view->assign('songList',$data1);
		$this->view->assign('recommendList',$data2);
		return $this->view->fetch('comunity');
	}

	//爬取流行音乐列表
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
		
		$this->view->assign('songList',$data);
		return $this->view->fetch('moreMusic');
	}

	//下载音乐
	public function downloadMusic()
	{
		$data = Request::param();
		$data = json_decode($data['data'],true);
		// var_dump("http://sq.sycdn.kuwo.cn/ad28f8ee60e115a8a30409ddf6f2df6f/5d74c0b4/resource/n2/49/69/2154608137.mp3");
		// var_dump($data['link']);exit;
		$url = $data['link'];
		$url = trim($url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$content = curl_exec($ch);

		$content=mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1,ASCII,UTF-8,GB2312,GBK,BIG5');//使用该函数对结果进行转码
		// $content= iconv( 'GBK','UTF-8',$content);
		// var_dump($content);
		// exit;
		
		curl_close($ch);
		preg_match('/<source src="([\s\S]*)" type=\"audio\/mpeg\">/',$content,$match);
		var_dump($match);

	}
	
}
