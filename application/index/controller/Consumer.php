<?php
/*
**用户类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\common\model\Consumer as ConsumerModel;
use app\common\model\School;
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
				'name|账号'=>'require|length:1,20|chsAlphaNum',
		 		'password|密码'=>'require|length:5,20|alphaNum',
			];
			//754794f1c04f29f2119ed6fb128ab076e6e60a87
			// return ['status'=>-1,'message'=>sha1($data['password'])];
			$res = $this->validate($data,$rule);
			if(true !== $res){
				return ['status'=>-1,'message'=>$res];
			}else{
				$result = ConsumerModel::get(function ($query) use ($data){
					$query->where('name',$data['name'])
						->where('password',sha1($data['password']));
				});

				if(empty($result)){
					return ['status'=>-1,'message'=>'账号或密码错误'];
				}
				
				if($result['status'] == 0){
					return ['status'=>-1,'message'=>'账号被冻结'];
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

	
}
