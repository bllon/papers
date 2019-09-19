<?php
namespace app\api\controller;

class CosineSimilar
{
	public function getCurrentTime ()
    {
        list ($msec, $sec) = explode(" ", microtime());
        return (float)$msec + (float)$sec;
    }
	
	public function index($str1,$str2)
	{
		
		$start_time = $this->getCurrentTime();

		$arr1 = $this->participle($str1);;
		$arr2 = $this->participle($str2);;
		$arr = $this->allWord($arr1, $arr2);
		$arr1 = $this->getWordPear($arr, $arr1);
		$arr2 = $this->getWordPear($arr, $arr2);
		
		$score = $this->similarity($arr1, $arr2);
		$end_time = $this->getCurrentTime();
		
		$time = $end_time-$start_time;
		echo "用时: ",$time,'s<br>';
		echo "CosineSimilar score: ",$score;
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
	public function allWord($arr1,$arr2)
	{
		$arr = array_merge($arr1,$arr2);
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
	
	//4.计算复杂相似度	利用余弦相似度公式
	public function similarity($arr1,$arr2)
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
}


?>