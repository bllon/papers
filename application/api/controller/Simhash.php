<?php
namespace app\api\controller;

class Simhash
{
	public function index($path)
	{
//		dump($this->hashCode('中国'));
//		dump($this->hashCode('美国'));
//		dump($this->hashCode('外国'));
//		dump($this->hashCode('阿拉斯加国'));
//		dump(decbin($this->hashCode('中国')));
//		dump(decbin($this->hashCode('美国')));
//		dump(decbin($this->hashCode('外国')));
//		dump(decbin($this->hashCode('阿拉斯加国')));
		dump($this->stringHash('不相信'));
		dump($this->stringHash('相信'));
		exit;
		$arr = $this->deal($path);
//		halt($arr);
		$data = [];
		foreach($arr as $v){
			foreach($this->participle($v) as $p){
				$data[] = md5($p);
			}
		}
		$arr = $this->allWord($data);
		dump($this->getWordPear($arr,$data));
	}
	
	//1.分词
	public function participle($str)
	{
		
		//引入自动加载文件
		include '../vendor/autoload.php';
	
		//创建对象
		$obj = new \ronylee\phpanalysis\Analysis;
	
		//调用方法
		$res = $obj->run($str);
		$res = explode(' ', $res);
		array_shift($res);
		return $res;
	}
	
	//2.统计所有词组
	public function allWord($arr)
	{
		$arr = array_unique($arr);
		$data = [];
		foreach($arr as $v){
			$data[] = $v;
		}
		return $data;
	}
	
	//3.获取词频
	public function getWordPear($arr,$tarr)
	{
		$data = [];	
		foreach($arr as $a){
			if(!in_array($a, $tarr)){
				$data[] = 0;
				continue;
			}
			$num = 0;
			for($j=0;$j<count($tarr);$j++){
				if($tarr[$j] == $a){
					$num +=1;
				}
			}
			$data[] = $num;
		}
		
		return $data;
	}
	
	
	//2.对词计算hash值
	public function hashCode($str) {
	    $str = (string)$str;
	    $hash = 0;
	    $len = strlen($str);
	    if ($len == 0 )
	        return $hash;
	
	    for ($i = 0; $i < $len; $i++) {
	        $h = $hash << 7;
	        $h -= $hash;
	        $h += ord($str[$i]);
	        $hash = $h;
	        $hash &= 0xFFFFFFFF;
	    }
	    return $hash;
	}
	
	
	//生成hash签名
	public function code($arr,$f=64)
	{
		//对于一个n维向量$arr		要生成一个f位的签名
		//算法如下:
		//1.随机产生f个n维的向量，r1,r2,r3...
		$d = [];
		for($i=0;$i<$f;$i++)
		{
			for($j=0;$j<count($arr);$j++){
				$d[$i][] = rand(-100, 100);
			}
		}
		
		//2.分别计算向量v和ri的点积	大于0则为1 否则为0
		$hash = [];
		for($i=0;$i<count($d);$i++){
			$num = 0;
			for($j=0;$j<count($arr);$j++){
				$num += $arr[$j]*$d[$i][$j];
			}
			if($num > 0){
				$hash[] = 1;
			}else{
				$hash[] = 0;
			}
		}
		return $hash;
	}
	
	 public function stringHash($source){
        if(empty($source)){
            return 0;
        }else{
            $x = ord($source[0]) << 7;
 
            $m = 1000003;
 
            $mask = gmp_sub(gmp_pow("2", 128), 1);
            $len = strlen($source);
 
            for($i = 0; $i < $len; $i++){
                $x = gmp_and(gmp_xor(gmp_mul($x, $m), ord($source[$i])), $mask);
            }
            $x = gmp_xor($x, $len);
            if(intval(gmp_strval($x)) == -1){
                $x = -2;
            }
            return $x;
        }
    }
	
	
	//处理要查重的论文，对其进行分句，并除去少于指定文字数量的句子
	public function deal($path)
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
		
		
	//	$pattern = '/[^,.;\s]+[,.;\s]/';
	//	$pat = "/[\x80-\xff]+/";
	//	preg_match_all($pat, $text,$matches);
	//	dump($matches);
		
		$content = []; 
		$pat = "/[\x80-\xff]+/";
		foreach($pages as $key=>$page){
			
			//除去空字符
			$str = preg_replace("/\s+|[<br>]+/", '', $pages[$key]->getText());
			preg_match_all($pat, $str,$matches);
			foreach($matches[0] as $match){
	//			preg_replace('/\s{1,}/', ' ',$match)
	
	//			\u2026|\uff1f|\uff0c|\uff1a|\u3001|\u3002|\uff01|\uff1b|\u201d|\u2019
//				if(mb_strlen($match)>=15){
					
					//除去标点符号
					preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]+/u', $match,$ma);
					foreach($ma as $m){
						foreach($m as $v){
//							if(mb_strlen($v)>=15){
								$content[] = $v;
//							}
						}	
					}
					
//				}
			}
			
		} 
		return $content; 
	}
	
	
	
	
}


?>