<?php
/*
**index首页类
*/
namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\admin\common\model\Notice;
use app\common\model\Consumer;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;



class Index extends Base
{
	//qq登录
	public function qqLogin()
	{
		$app_id = "101807120";
        //回调地址
        $redirect_uri = urlencode("http://xubeixyz123.com/index/index/qq_callback");
        $url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=".$app_id."&redirect_uri=".$redirect_uri."&scope=get_user_info&state=text";
        //跳转到$url
 
        //Step1：获取Authorization Code
 
        // header("location".$url);
        $this->redirect($url);
	}

	//qq登录的回调
	public function qq_callback()
	{
		// appid
        $app_id = "101807120";
        //appkey
        $app_secret = "045eafc38d5d3077b9ef9d07a9f7b5bd";
        //成功授权后的回调地址
        $my_url = urlencode("http://xubeixyz123.com/index/index/qq_callback");
        //获取code
        $code = $_GET['code'];
 
        //Step2：通过Authorization Code获取Access Token
 
        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=".$app_id."&redirect_uri=".$my_url."&client_secret=".$app_secret."&code=".$code."";
        //file_get_contents() 把整个文件读入一个字符串中。
        $response = file_get_contents($token_url);
 
        //Step3:在上一步获取的Access Token，得到对应用户身份的OpenID。
 
        $params = array();
        //parse_str() 函数把查询字符串（'a=x&b=y'）解析到变量中。
        parse_str($response,$params);
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=".$params['access_token']."";
        $str = file_get_contents($graph_url);
        // dump($str);die;
        // --->找到了字符串：callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"} )
        //
        // strpos() 函数查找字符串在另一字符串中第一次出现的位置，从0开始
        if(strpos($str,"callback")!==false)
        {
            $lpos = strpos($str,"(");
            // strrpos() 函数查找字符串在另一字符串中最后一次出现的位置。
            $rpos = strrpos($str,")");
            //substr(string,start,length) 截取字符串某一位置
            $str = substr($str,$lpos+1,$rpos-$lpos-1);
        }
        // json_decode() 函数用于对 JSON 格式-->{"a":1,"b":2,"c":3,"d":4,"e":5}<--的字符串进行解码，并转换为 PHP 变量,默认返回对象
        $user = json_decode($str);
        // dump($user->openid);die;
        session('openid',$user->openid);
 
        //Step4: 调用OpenAPI接口,得到json数据，要转换为数组
 
        $huiyuan = "https://graph.qq.com/user/get_user_info?access_token=".$params['access_token']."&oauth_consumer_key=".$app_id."&openid=".$user->openid."";
        //加上true，得到数组，否则默认得到对象
        $res = json_decode(file_get_contents($huiyuan),true);
        // dump($res['nickname']);dump($res);die;

        $re = Consumer::where('openid',$user->openid)->find();

        //如果没有找到，进行注册
        if(!$re){
            if($res['gender']=="男"){
                $res['gender'] = 0;
            }else{
                $res['gender'] = 1;
            }
            $data = [
                'openid'=>$user->openid,
                'name'=>$res['nickname'],
                'school_name'=>'未指定学校',
                'email'=>'未指定邮箱',
                'password'=>'',
                'passnum'=>0,
                'status'=>1,
                'user_img'=>$res['figureurl_qq_2'],
                'role_id'=>21,
                'gender'=>$res['gender'],
            ];
            if($user = Consumer::create($data)){
				$res = Consumer::get($user->id);
				Session::set('user_id',$res->id);
				Session::set('user_name',$res->name);
				Session::set('user_school',$res->school_name);

				Cookie::set('user_id',$res->id);
				Cookie::set('user_name',$res->name);
				Cookie::set('user_img',$res->user_img);
				$this->redirect('index');
			}else{				
				$this->redirect('index');
			}
        }else{
        	Session::set('user_id',$re['id']);
			Session::set('user_name',$re['name']);
			Session::set('user_school',$re['school_name']);

			Cookie::set('user_id',$re['id']);
			Cookie::set('user_name',$re['name']);
			Cookie::set('user_img',$re['user_img']);
            $this->redirect('index');
        }
	}

	//首页
    public function index()
    {			

		$rank_name = Request::param('rank_name');
	
		//查询最新通告
		$noticeInfo = Notice::where('status',1)->order('create_time','desc')->find();
    	$this->view->assign('noticeInfo',$noticeInfo);
		
		$this->view->assign('empty','<h3>没有论文</h3>');
		$this->view->assign('title','paper');
		// $this->view->assign('lunwenList',$lunwenList);

		$keywords = trim(Request::param('keywords'));

		if(!empty($keywords)){
			$this->view->assign('keywords',$keywords);
		}

		//显示方式
		if(Cookie::get('displayFunc') == null){
			$displayFunc = 3;
		}else{
			$displayFunc = Cookie::get('displayFunc');
		}
		$this->view->assign('displayFunc',$displayFunc);

		//上一次一级分类的页码数
		if(Cookie::get('currentPage') == null){
			$currentPage = 1;
		}else{
			$currentPage = Cookie::get('currentPage');
		}
		
		//上一次点击分分类名
		if($rank_name == Cookie::get('lastRank')){
			$currentPage = Cookie::get('currentPage');
		}else{
			$currentPage = 1;
			Cookie::set('lastRank',$rank_name);
		}			

		$this->view->assign('rankName',$rank_name);
		$this->view->assign('currentPage',$currentPage);
		
    	return $this->view->fetch('index');
    }

    //设置论文列表显示方式
    public function displayFunc()
    {
    	if(Request::isAjax()){
    		$data = Request::param();
	    	$type = intval($data['type']);//1方格，2列表

	    	Cookie::set('displayFunc',$type);
    	}
    }
	
	//关闭通告
	public function closeNotice()
	{
		//设置cookie关闭通告
		Cookie::set('notice','1',7200);
	}
	
	//显示二级分类论文页面
	public function sele()
	{					
		$sele_name = Request::param('sele_name');
	
		//查询最新通告
		$noticeInfo = Notice::where('status',1)->order('create_time','desc')->find();
    	$this->view->assign('noticeInfo',$noticeInfo);
		
		$this->view->assign('empty','<h3>没有论文</h3>');
		$this->view->assign('title','paper');
		// $this->view->assign('lunwenList',$lunwenList);

		//显示方式
		if(Cookie::get('displayFunc') == null){
			$displayFunc = 3;
		}else{
			$displayFunc = Cookie::get('displayFunc');
		}
		$this->view->assign('displayFunc',$displayFunc);

		//上一次二级分类的页码数
		if(Cookie::get('selePage') == null){
			$currentPage = 1;
		}else{
			$currentPage = Cookie::get('selePage');
		}
		$this->view->assign('currentPage',$currentPage);


		//获取上一次点击的二级分类
		if($sele_name == Cookie::get('lastSele')){
			$currentPage = Cookie::get('selePage');
		}else{
			$currentPage = 1;
			Cookie::set('lastSele',$sele_name);
		}			

		$this->view->assign('seleName',$sele_name);
		$this->view->assign('currentPage',$currentPage);
		
    	return $this->view->fetch('sele');
	}
}
