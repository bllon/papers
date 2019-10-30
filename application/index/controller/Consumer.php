<?php
/*
**用户类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\common\model\Consumer as ConsumerModel;
use app\common\model\School;
use app\common\model\Collect;
use app\common\model\Borrow;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use think\Db;


class Consumer extends Base
{
	//登录
    public function login()
    {
    	if(Request::isAjax()){
    		
    		$data = Request::param();
			$rule=[
				'email|邮箱'=>'require|email',
		 		'password|密码'=>'require|length:5,20|alphaNum',
			];

			$res = $this->validate($data,$rule);
			if(true !== $res){
				return ['status'=>-1,'message'=>$res];
			}else{
				$result = ConsumerModel::get(function ($query) use ($data){
					$query->where('email',$data['email'])
						->where('password',sha1($data['password']));
				});

				if(empty($result)){
					return ['status'=>-1,'message'=>'邮箱或密码错误'];
				}
				
				if($result['status'] == 0){
					return ['status'=>-1,'message'=>'账户被冻结'];
				}

				$school = School::where('school_name',$result['school_name'])->find();
				if($school['status'] == 0){
					return ['status'=>-1,'message'=>'该学校已暂停服务'];
				}

				if(null !== $result){
					Session::set('user_id',$result['id']);
					Session::set('user_name',$result['name']);
					Session::set('user_school',$result['school_name']);

					Cookie::set('user_id',$result['id']);
					Cookie::set('user_name',$result['name']);
					Cookie::set('user_img',$result['user_img']);
					return ['status'=>1,'message'=>'登录成功'];
					
				}else{
					return ['status'=>0,'message'=>'登录失败'];
				}
			}
    	}else{
    		return ['status'=>-1,'message'=>'请求异常'];
    	}
    }
	
	//注册
	public function register()
	{
		if($this->is_reg() == 0){
			return ['status'=>-2,'message'=>'网站维护，暂停注册'];
		}
		
		if(Request::isAjax()){
    		$data = Request::param();
			$res = $this->validate($data,'app\common\validate\Consumer');

			if(true !== $res){
				return ['status'=>-1,'message'=>$res];
			}else{
				//判断验证码
				if(!$data['reg'] || $data['reg'] != Session::get('reg')){
					return ['status'=>-1,'message'=>'验证码错误'];
				}

				//判断学校名称是否合法
				$file = dirname(dirname(dirname(__DIR__))).'/public/uploads/campus/Allcampus.txt';
				$content = file_get_contents($file);
				$content = json_decode($content);

				if(!in_array($data['school_name'], $content)){
					return ['status'=>-1,'message'=>'学校名称不合法'];
				}
				
				$schoolList = School::all();
				$sta = 1;
				foreach($schoolList as $v){
					if($v['school_name'] == $data['school_name']){
						$sta = 0;
					}
				}
				if($sta){
					return ['status'=>-1,'message'=>'学校未注册'];
				}

				$data['password'] = sha1($data['password']);
				
				if($user = ConsumerModel::create($data)){
					$res = ConsumerModel::get($user->id);
					Session::set('user_id',$res->id);
					Session::set('user_name',$res->name);
					Session::set('user_school',$res->school_name);

					Cookie::set('user_id',$res->id);
					Cookie::set('user_name',$res->name);
					Cookie::set('user_img',$res->user_img);
					return ['status'=>1,'message'=>'注册成功'];	
				}else{
					return ['status'=>0,'message'=>'注册失败'];
				}
			}
    	}else{
    		return ['status'=>-1,'message'=>'请求异常'];
    	}
	}

	//获取高校名称
	public function campus()
	{
		$file = dirname(dirname(dirname(__DIR__))).'/public/uploads/campus/campus.txt';
		$content = file_get_contents($file);
		$data = json_decode($content,true);

		$list = [];
		foreach($data as $p){
			foreach($p['cities'] as $s){
				$list[] = $s;
			}
		}

		$data = [];
		$num = 0;
		foreach($list as $row){
			$num += count($row['universities']);
			$data = array_merge($data,$row['universities']);
		}
		
		// $list = '[';

		// foreach($data as $row){
		// 	$list .= '"'.$row.'",';
		// }
		// $list = rtrim($list,',');
		// $list .= ']';

		$file = dirname(dirname(dirname(__DIR__))).'/public/uploads/campus/Allcampus.txt';

		file_put_contents($file, json_encode($data));
	}

	//邮箱验证
	public function mailValidation()
	{
		$email = Request::param();

		// 用户IP
		//查询发送记录
		$sendInfo = Db::table('paper_sendmail_time')->where('ip',$_SERVER['REMOTE_ADDR'])->find();

		if($sendInfo){
			//存在记录，判断是否超过10分钟
			if(time() < $sendInfo['time']+600)
			{
				return ['status'=>0,'message'=>'发送太过频繁,稍后再试'];
			}

			//修改
			$data = ['time'=>time()];
			Db::table('paper_sendmail_time')->where('ip',$_SERVER['REMOTE_ADDR'])->update($data);

		}else{
			$data = ['ip'=>$_SERVER['REMOTE_ADDR'],'time'=>time()];
			Db::table('paper_sendmail_time')->insert($data);
		}

		$reg = '';
		for($i=0;$i<6;$i++){
			$reg .= mt_rand(0,9);
		}

		Session::set('reg',$reg);
		$data = ['email'=>$email['email'],'reg'=>$reg];


		$res = $this->sendMail($data);
		
		if($res){
			return ['status'=>1,'message'=>'成功发送邮件,请查收'];
		}else{
			return ['status'=>0,'message'=>'发送邮件失败，请检查邮箱是否异常'];
		}
	}



	//发送邮件
	public function sendMail($data)
	{
		$mail = new PHPMailer(true);
		$mail->CharSet='UTF-8';

		try {
		    //Server settings
		    $mail->SMTPDebug = 0;                                       // Enable verbose debug output
		    $mail->isSMTP();                                            // Set mailer to use SMTP
		    $mail->Host       = 'smtp.163.com';  // Specify main and backup SMTP servers
		    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
		    $mail->Username   = 'papers_xubei@163.com';                     // SMTP username
		    $mail->Password   = 'xubei1998526';                               // SMTP password
		    $mail->SMTPSecure = 'tls';
		                                     // Enable TLS encryption, `ssl` also accepted
		    $mail->Port       = 25;                                    // TCP port to connect to

		    //Recipients
		    $mail->setFrom('papers_xubei@163.com', 'papers.com');
		    $mail->addAddress($data['email']);               // Name is optional
		    

		    

		    // Content
		    $mail->isHTML(true);                                  // Set email format to HTML
		    $mail->Subject = 'papers.com';
		    $mail->Body    = "【论文库官方】 谢谢你对论文库的支持!<br>注册验证码为:".$data['reg']."<br><br>有活动将会第一时间推送给你<br>from 论文库团队";
		    // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

		    $mail->send();
		    // echo 'Message has been sent';
		    return true;
		} catch (Exception $e) {
		    // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		    return false;
		}
	}
	
	
	//用户退出登录
	public function logout()
	{
		Session::delete('user_id');
		Session::delete('user_name');
		Session::delete('user_school');

		Cookie::set('user_id','',time()-3600);
		Cookie::set('user_name','',time()-3600);
		Cookie::set('user_img','',time()-3600);

		return ['status'=>1,'message'=>'退出成功'];
	}

	//用户设置
	public function setting()
	{
		$this->isLogin();
		$this->hasPower(Session::get('user_id'), 'index/index/setting');

		$userInfo = ConsumerModel::get(Session::get('user_id'));
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
		if(ConsumerModel::update($data)){
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
		
		$userInfo = ConsumerModel::where('id',$id)->find();

		//查询收藏论文
		$collect = Collect::where('user_id',$userInfo['id'])->select();
		$collectNum = count($collect);

		//查询累计借阅
		$borrow = Borrow::where('user_id',$userInfo['id'])->where('status',3)->select();
		$borrowNum = count($borrow);
		
		//查询累计查重次数
		$pass = Db::table('paper_pass')->where('user_id',$id)->select();
		$passNum = count($pass);

		//用户收藏歌单
		$songList = Db::table('paper_collectMusic')->where('consumer_id',$id)->select();
		$musicNum = count($songList);

		unset($collect);
		unset($comment);
		unset($$borrow);
		unset($id);
		$this->view->assign('title',"{$userInfo['name']}的详情信息");
		$this->view->assign('userInfo',$userInfo);
		$this->view->assign('collectNum',$collectNum);
		$this->view->assign('borrowNum',$borrowNum);
		$this->view->assign('passNum',$passNum);
		$this->view->assign('musicNum',$musicNum);
		$this->view->assign('songList',$songList);
		return $this->view->fetch('userDetail');
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

	//取消收藏音乐
	public function unCollectMusic()
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
		$map[] = ['id','=',$data['id']];

		if(Db::table('paper_collectMusic')->delete($data)){
			return ['status'=>1,'message'=>'删除成功'];
		}else{
			return ['status'=>0,'message'=>'删除失败'];
		}

	}
}
