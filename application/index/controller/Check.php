<?php
/**
*论文检查类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\common\model\Consumer;
use app\common\model\Pass;
use app\common\model\Word;
use think\facade\Request;
use think\facade\Session;
use think\Db;
// use app\api\controller\Simhash;

class Check extends Base
{
	//论文查重页面
	public function paperpass()
	{

		$token = substr(str_shuffle('qwertyuiopasdfghjklzxcvbnm1234567890'),0,15);
		Session::set('token',$token);
		$this->view->assign('title','论文查重');
		return $this->view->fetch('paperpass');
	}
	
	//执行查重，并返回结果
	public function doPass()
	{
		
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
					$map[] = $row.'%';
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

	}
	
	//查重结果页面
	public function passRsult()
	{

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

		$id = Session::get('user_id');		
		$consumer = Consumer::get($id);
		if($consumer['passnum'] <= 0){
			return ['status'=>0,'message'=>'你的查重次数已用完'];
		}else{
			return ['status'=>1,'message'=>'存在查重机会'];
		}
	}
	
	//ajax请求减少查重剩余次数
	public function decPassNum()
	{

		$id = Session::get('user_id');
		$consumer = Consumer::get($id);
		$consumer->passnum = ['dec', 1];
		$consumer->save();
	}
	
	//获取检测结果文档
	public function getReport(){
		
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
		$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, '论文库', 'paper.com');
		
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

	//查重记录
	public function passrecord(){
					
		$passList = Db::table('paper_pass')
							->where('user_id',Session::get('user_id'))
							->order('create_time','desc')
							->paginate(10);
		
		$this->assign('passList',$passList);
		$this->view->assign('title','查重记录');
		return $this->view->fetch('passrecord');
	}

	//获取查重的句子
	public function getWord(){

		$id = Request::param('id');
		$wordInfo = Word::get($id);
		$this->view->assign('title','句子来源');
		$this->view->assign('wordInfo',$wordInfo);
		return $this->view->fetch('getWord');
	}
}