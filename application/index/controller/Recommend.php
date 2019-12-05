<?php
/**
**论文推荐系统类
**
**/

namespace app\index\controller;
use app\common\controller\Tools;//导入工具类
use think\facade\Request;
use think\Db;

class Recommend
{
	//推送推荐论文
	public function push()
	{

		$consumer_id = Request::param('consumer_id');
		if($consumer_id == '' || $consumer_id == null){
			return ['status'=>0,'message'=>'获取推荐论文失败','data'=>[]];
		}

		//查询该用户是否存在积分
		$consumer_info = Db::table('paper_integral')->where('consumer_id',$consumer_id)->find();
		
		if(!$consumer_info){
			//不存在积分系统中	推荐最火的论文
			$push = [];//计算论文的积分

			$cursor = Db::table('paper_integral')->cursor();	//tp5封装了PHP的生成器的新特性 
			foreach($cursor as $item){ 
				if(isset($push[$item['paper_id']])){
					$push[$item['paper_id']] += (float)$item['integral'];
				}else{
					$push[$item['paper_id']] = (float)$item['integral'];
				}
			}

			//推送3个大家看得最多的论文
			for($i=0;$i<3;$i++){
				array_pop($push);
			}

			$push = array_keys($push);
			
			// 返回推荐的论文详情
			$pushList = Db::table('paper_lunwen')->where('lunwen_terms',1)->where('id','in',$push)->select();
			$pushList = array_slice($pushList, 0,3);
			return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($pushList)];
		}

		//查询所有存在积分的用户
		$consumer = Db::query('select distinct(consumer_id) from paper_integral');

		//获取用户积分表
		$integral = $this->integral();
		$integral_index = $this->integral();

		//索引积分表
		$integral_index = Tools::arrFormat($integral_index,false);



		$similarity_info = [];

		foreach($consumer as $k=>$v){
			if($v['consumer_id'] != $consumer_id){
				$similarity_info[$v['consumer_id']] = Tools::similarity($integral_index[$consumer_id],$integral_index[$v['consumer_id']]);
			}
		}
		arsort($similarity_info);//相似的用户，键是用户id，值是相似度
		
		if(count($similarity_info) > 3){
			//除去小于0.5的相似用户
			foreach($similarity_info as $k=>$v){
				if($v < 0.5){
					unset($similarity_info[$k]);
				}
				if(count($similarity_info) <= 3){
					break;
				}
			}
		}
		

		//保存要推荐的论文id
		$push = [];
		
		//取出相似用户的论文
		foreach($similarity_info as $u=>$s){
			
			foreach($integral[$u] as $p=>$i){
				//推荐用户没看过的论文
				// if(!in_array($p, Tools::hasIntegral($integral[$consumer_id]))){
				// 	$push[] = $p;
				// }

				if($i == 0){
					continue;
				}

				if(array_key_exists($p, $push)){
					//存在，判断积分值大小
					if($i > $push[$p]){
						$push[$p] = $i;
					}
				}else{
					$push[$p] = $i;
				}
			}
			
		}

		arsort($push);
		$push = Tools::hasIntegral($push);

		// 返回推荐的论文详情
		$pushList = Db::table('paper_lunwen')->where('lunwen_terms',1)->where('id','in',$push)->select();
		$pushList = array_slice($pushList, 0,3);
		return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($pushList)];
		
	}

	//获取积分表
	public function integral()
	{
		//积分矩阵表
		$integralTable = [];

		//查询所有存在积分的论文
		$paper = Db::query('select distinct(paper_id) from paper_integral');


		//查询用户-论文积分表
		$cursor = Db::table('paper_integral')->cursor();	//tp5封装了PHP的生成器的新特性 
		foreach($cursor as $item){ 
			if(!isset($integralTable[$item['consumer_id']])){
				$integralTable[$item['consumer_id']] = [];
			}

			$integralTable[$item['consumer_id']][$item['paper_id']] = $item['integral'];
		}

		foreach($paper as $v){
			foreach($integralTable as $k=>$item){				
				if(!isset($item[$v['paper_id']])){
					$integralTable[$k][$v['paper_id']] = 0;
				}
			}
		}

		foreach($integralTable as $k=>$item){				
			ksort($integralTable[$k]);
		}
		return $integralTable;
	}

	//打印用户-论文积分矩阵表
	public function print()
	{

		$integralTable = $this->integral();
		

		$paper = Db::query('select distinct(paper_id) from paper_integral');
		
		$table = '<table border="1"><thead><tr><th style="width:80px;">用户ID/论文ID</th>';
		foreach($paper as $v){
			$table .= '<th style="width:80px;">'.$v['paper_id'].'</th>';
		}
		$table .= '</tr></thead><tbody>';

		
		foreach($integralTable as $k=>$integral){
			$table .= '<tr style="text-align:center;"><td style="width:80px;">'.$k.'</td>';
			foreach($paper as $v){
				$table .= '<td style="width:80px;">'.$integral[$v['paper_id']].'</td>';
			}
			$table .= '</tr>';
		}
		$table .= '</tbody></table>';

		echo $table;

	}

}