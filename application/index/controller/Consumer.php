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
					return ['status'=>-1,'message'=>'账号不存在'];
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

	//发送邮件
	public function sendMail()
	{
		$mail = new PHPMailer(true);
		$mail->CharSet='UTF-8';

		try {
		    //Server settings
		    $mail->SMTPDebug = 2;                                       // Enable verbose debug output
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
		    $mail->addAddress('papers_xubei@163.com');               // Name is optional
		    

		    

		    // Content
		    $mail->isHTML(true);                                  // Set email format to HTML
		    $mail->Subject = 'papers.com';
		    $mail->Body    = '你的验证码为:123456';
		    // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

		    $mail->send();
		    echo 'Message has been sent';
		} catch (Exception $e) {
		    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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
