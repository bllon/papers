<?php
/**
**论文推荐系统类
**
**/

namespace app\index\controller;
use app\common\controller\Tools;//导入工具类
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Recommend
{
	//未登录时，获取所有可看相同专业的论文
	public function publicRankPaper($paper_id,$rank)
	{
		$map = ['lunwen_terms','=',1];
		$map2 = ['lunwen_rank','=',$rank];

		//查询同专业的论文
		$list = Db::table('paper_lunwen')->where([$map,$map2])->where('id','<>',$paper_id)->order('pv','desc')->select();
		return $list;
	}


	//未登录时，获取所有可看的热门论文，不在$ids中的
	public function publicHotPaper($ids)
	{
		$num = 3 - count($ids)+1;
		//查询热门论文
		$list = Db::table('paper_lunwen')->where('lunwen_terms',1)->where('id','not in',$ids)->order('pv','desc')->select();
		$list = array_slice($list, 0,$num);
		return $list;
	}

	//登录时，获取所有可看相同专业的论文
	public function userCanReadRankPaper($paper_id,$rank,$school)
	{
		$map = ['lunwen_terms','=',1];
		$map2 = ['school_name','=',$school];

		$map3 = ['lunwen_rank','=',$rank];
		$map4 = ['id','<>',$paper_id];


		// $public = $this->publicRankPaper($paper_id,$rank); 
		//查询用户可看的论文
		$list = Db::table('paper_lunwen')->field('id')->whereOr([$map,$map2])->select();

		$ids = [];
		foreach($list as $v){
			$ids[] = $v['id'];
		}

		$map5 = ['id','in',$ids];
		//查询同专业的
		$list = Db::table('paper_lunwen')->where([$map3,$map4,$map5])->order('pv','desc')->select();
		return $list;
	}


	//登录时，获取所有可看的论文
	public function userCanReadPaper($paper_id,$school)
	{
		$map = ['lunwen_terms','=',1];
		$map2 = ['school_name','=',$school];

		$map3 = ['id','<>',$paper_id];


		// $public = $this->publicRankPaper($paper_id,$rank); 
		//查询用户可看的论文
		$list = Db::table('paper_lunwen')->field('id')->whereOr([$map,$map2])->select();

		$ids = [];
		foreach($list as $v){
			$ids[] = $v['id'];
		}

		$map4 = ['id','in',$ids];
		//查询同专业的
		$list = Db::table('paper_lunwen')->where([$map3,$map4])->order('pv','desc')->select();
		return $list;
	}



	//登录时，获取所有可看的热门论文，不在$ids中的
	public function userCanReadHotPaper($ids,$school)
	{
		$num = 3 - count($ids)+1;

		$map = ['lunwen_terms','=',1];
		$map2 = ['school_name','=',$school];

		//查询用户可看的论文
		$list = Db::table('paper_lunwen')->field('id')->whereOr([$map,$map2])->select();

		$data = [];
		foreach($list as $v){
			if(!in_array($v['id'],$ids)){
				$data[] = $v['id'];
			}
		}


		//查询热门论文
		$list = Db::table('paper_lunwen')->where('id','in',$data)->order('pv','desc')->select();
		$list = array_slice($list, 0,$num);
		return $list;
	}


	//论文积分最多的论文
	public function moreRead()
	{
		$push = [];//计算论文的积分

		$cursor = Db::table('paper_integral')->cursor();	//tp5封装了PHP的生成器的新特性 
		foreach($cursor as $item){ 
			if(isset($push[$item['paper_id']])){
				$push[$item['paper_id']] += (float)$item['integral'];
			}else{
				$push[$item['paper_id']] = (float)$item['integral'];
			}
		}

		var_dump($push);exit;

		//推送3个大家看得最多的论文
		for($i=0;$i>3;$i++){
			array_pop($push);
		}

		$push = array_keys($push);
	}



	//推送推荐论文
	public function push()
	{

		$consumer_id = Request::param('consumer_id');
		$paper_id = Request::param('paper_id');

		//查询该论文的专业信息
		$paperInfo = Db::table('paper_lunwen')->find($paper_id);

		//1.未登录推荐
		if($consumer_id == '' || $consumer_id == null){

			//同专业论文
			$list = $this->publicRankPaper($paper_id,$paperInfo['lunwen_rank']);
			if(count($list) >= 3){				
				$list = array_slice($list,0,3);
				return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($list)];
			}

			$ids = [];
			foreach($list as $v){
				$ids[] = $v['id'];
			}
			$ids[] = $paper_id;

			//热门论文
			$hotList = $this->publicHotPaper($ids);

			//综合推荐论文
			$pushList = array_merge($list,$hotList);
			
			return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($pushList)];
		}


		//查询该用户是否存在积分
		$consumer_info = Db::table('paper_integral')->where('consumer_id',$consumer_id)->find();

		//2.已登录，但用户没有积分行为
		if(!$consumer_info){

			//获取可看的同专业论文
			$list = $this->userCanReadRankPaper($paper_id,$paperInfo['lunwen_rank'],Session::get('user_school'));

			if(count($list) >= 3){				
				$list = array_slice($list,0,3);
				return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($list)];
			}

			$ids = [];
			foreach($list as $v){
				$ids[] = $v['id'];
			}
			$ids[] = $paper_id;

			//热门论文
			$hotList = $this->userCanReadHotPaper($ids,Session::get('user_school'));

			//综合推荐论文
			$pushList = array_merge($list,$hotList);
			return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($pushList)];
		}

		//3.已登录，并且存在积分行为
		//查询所有存在积分的用户
		$consumer = Db::query('select distinct(consumer_id) from paper_integral');

		//获取用户积分表
		$integral = $this->integral();
		$integral_index = $this->integral();

		//索引积分表
		$integral_index = Tools::arrFormat($integral_index,false);

		//相似用户，以及相似度
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
		

		//保存要相似用户的论文的id
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
		$push = Tools::hasIntegral($push);//所有的相似用户的论文id集合



		//登录用户可以看的论文
		$cans = $this->userCanReadPaper($paper_id,Session::get('user_school'));

		foreach($cans as $v){
			if(!in_array($v['id'],$push)){
				$push[] = $v['id'];
			}
		}

		$map = ['id','in',$push];
		$map2 = ['lunwen_rank','=',$paperInfo['lunwen_rank']];

		//相似用户的同专业论文
		$list = Db::table('paper_lunwen')->where([$map,$map2])->select();


		if(count($list) >= 3){				
			$list = array_slice($list,0,3);
			return ['status'=>1,'message'=>'成功获取推荐论文','data'=>json_encode($list)];
		}

		$data = [];
		foreach($list as $v){
			$data[] = $v['id'];
		}

		//获取可看的同专业论文
		$list2 = $this->userCanReadRankPaper($paper_id,$paperInfo['lunwen_rank'],Session::get('user_school'));

		foreach($list2 as $v){
			if(count($data) >= 3){
				break;
			}
			if(!in_array($v['id'], $data)){
				$data[] = $v['id'];
			}
		}

		$list = Db::table('paper_lunwen')->where('id','in',$data)->select();

		//热门论文
		$hotList = $this->userCanReadHotPaper($data,Session::get('user_school'));

		$pushList = array_merge($list,$hotPaper);

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