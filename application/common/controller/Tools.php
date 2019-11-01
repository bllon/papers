<?php
namespace app\common\controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use think\Db;

class Tools
{
	//爬取链接内容
	static public function getUrlContent($url)
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
	static public function downFile($filename,$ext)
	{
		//获取文件的扩展名
	    $allowDownExt = array ($ext);

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


	//发送邮件
	static public function sendMail($data)
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


	//计算复杂相似度	利用余弦相似度公式
	static public function similarity($arr1,$arr2)
	{
		//计算分子
		$numerator = 0;
		for($i=0;$i<count($arr1);$i++)
		{
			$numerator += $arr1[$i]*$arr2[$i];
		}

		//计算分母
		$denominator1 = 0;
		for($i=0;$i<count($arr1);$i++)
		{
			$denominator1 += $arr1[$i]*$arr1[$i];
		}

		$denominator2 = 0;
		for($i=0;$i<count($arr2);$i++)
		{
			$denominator2 += $arr2[$i]*$arr2[$i];
		}
		$data = $numerator/(sqrt($denominator1)*sqrt($denominator2));
		
		unset($numerator);
		unset($denominator1);
		unset($denominator2);
		
		return $data;	
	}

	//关联数组转索引数组
	public function arrFormat(&$arr,$flag=true){

		if(!is_array($arr)){
			return $arr;
		}
		$newArr = [];
		foreach($arr as $k=>$v){
			if($flag){
				$newArr[] = $this->arrFormat($v);
			}else{
				$newArr[$k] = $this->arrFormat($v);
			}			
		}
		$arr = $newArr;
		return $arr;
	}

	//去掉数组中为0的元素，并返回所有的键值
	public function hasIntegral($arr){
		foreach($arr as $k=>$v){
			if($v == 0){
				unset($arr[$k]);
			}
		}

		return array_keys($arr);
	}


	//存储高校列表
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
}
