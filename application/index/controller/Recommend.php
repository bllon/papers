<?php
/**
**论文推荐系统
**
**/

namespace app\index\controller;
use think\Db;

class Recommend
{
	public function push()
	{
		//积分矩阵表
		$integralTable = [];

		//查询用户-论文积分表
		$cursor = Db::table('paper_integral')->cursor();	//tp5封装了PHP的生成器的新特性 
		foreach($cursor as $item){ 
			if(!isset($integralTable[$item['consumer_id']])){
				$integralTable[$item['consumer_id']] = [];
			}

			$integralTable[$item['consumer_id']][$item['paper_id']] = $item['integral'];
		}

		var_dump($integralTable);
	}

	//计算两个矩阵的相似度
	public function similarity($item1,$item2)
	{

	}

	//利用生成器返回积分表数据
	public function integral(){
		$i = 1;
		while($row = Db::table('paper_integral')->find($i++)){
			yield $row;
		}		
	}

}