<?php
/**
**用户-论文 积分系统
**
**/

namespace app\index\controller;
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Integral
{
	//用户对应的文章积分变化
	public function paperIntegral(){
		$type = Request::param('type');
		$id = Request::param('id');

		//对应的积分变化类型
		$integralTable = array(
			1 => 0.1,	//点击进入详情
			2 => 1,		//阅读5分钟以上
			3 => 0.3,	//收藏
			4 => 2,		//借阅
			5 => 0.1	//点击继续阅读
		);

		//修改用户积分
		$map = [];
		$map[] = ['consumer_id','=',Session::get('user_id')];
		$map[] = ['paper_id','=',$id];
		$integral_Info = Db::table('paper_integral')->where($map)->find();

		if($integral_Info){

			$action = json_decode($integral_Info['action'],true);
			$action[$type] += 1;

			//积分修改限制次数
			switch($type){
				case 1:
					if($action[1]>4){
						return ['statu'=>0,'message'=>'该类积分已上限'];
					}
					break;
				case 2:
					if($action[2]>5){
						return ['statu'=>0,'message'=>'该类积分已上限'];
					}
					break;
				case 3:
					if($action[3]>2){
						return ['statu'=>0,'message'=>'该类积分已上限'];
					}
					break;
				case 4:
					if($action[4]>3){
						return ['statu'=>0,'message'=>'该类积分已上限'];
					}
					break;
				case 5:
					if($action[5]>10){
						return ['statu'=>0,'message'=>'该类积分已上限'];
					}
					break;
				default:
					break;
			}
			
			//修改记录
			$data = [
				'id' => $integral_Info['id'],
				'integral' => (float)($integral_Info['integral'] + $integralTable[$type]),
				'action' => json_encode($action),
				'update_time' => time()
			];
			if(Db::table('paper_integral')->where($map)->update($data)){
				return ['statu'=>1,'message'=>'恭喜你获得了 '.$integralTable[$type].' 积分'];
			}else{
				return ['statu'=>0,'message'=>'服务器异常'];
			}

		}else{
			//新建记录

			//积分添加动作
			$action = array(
				1 => 0,	//点击进入详情
				2 => 0,		//阅读5分钟以上
				3 => 0,	//收藏
				4 => 0,		//借阅
				5 => 0	//点击继续阅读
			);

			$action[$type] += 1;
			$action = json_encode($action);

			$data = [
				'consumer_id' => Session::get('user_id'),
				'paper_id' => $id,
				'integral' => (float)$integralTable[$type],
				'action' => $action,
				'update_time' => time()
			];
			if(Db::table('paper_integral')->insert($data)){
				return ['statu'=>1,'message'=>'恭喜你获得了 '.$integralTable[$type].' 积分'];
			}else{
				return ['statu'=>0,'message'=>'服务器异常'];
			}

		}

	}
}