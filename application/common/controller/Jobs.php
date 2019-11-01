<?php
/*
**任务类，生成任务队列
*/
namespace app\common\controller;
use think\Queue;
use think\queue\Job;

class Jobs
{
	public function index()
	{
		//生成任务
		$data = array(
			'type' => 'search',
			'key' => '222',
		);
		dump('生成任务');
		Queue::push('app\index\controller\Jobs@task1',$data,$queue=null);
		//三个参数依次为：需要执行的方法，传输的数据，任务名默认为default

	}

	/**
	*任务一
	*/
	public function task1(Job $job, $data)
	{
		//处理任务逻辑
		if($data['type'] == 'search'){
			$result = file_get_contents('http://www.baidu.com/s?wd='.$data['key']);
			if($result){
				echo "task1 success \n";
				$isJobDone = true;
			}else{
				echo "task1 failed \n";
				$isJobDone = false;
			}
		}else{
			echo "task1 failed \n";
			$isJobDone = false;
		}

		//执行结果处理
		if($isJobDone){

			//成功删除任务
			$job->delete();
		}else{

			//任务轮询四次后删除
			if($job->attempts()>3){
				//第一种方式：重新发布任务，该任务延迟10秒后再执行
				// $job->release(10);

				//第二种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
				// $job->failed();

				//第三种处理方式：删除任务
				$job->delete();
			}
		}
	}

	//任务2
	public function task2(Job $job, $data)
	{
		//处理任务逻辑
		if($data['type'] == 'search'){
			$result = file_get_contents('http://www.baidu.com/s?wd='.$data['key']);
			if($result){
				echo "task2 success \n";
				$isJobDone = true;
			}else{
				echo "task2 failed \n";
				$isJobDone = false;
			}
		}else{
			echo "task2 failed \n";
			$isJobDone = false;
		}

		//执行结果处理
		if($isJobDone){

			//成功删除任务
			$job->delete();
		}else{

			//任务轮询四次后删除
			if($job->attempts()>3){
				//第一种方式：重新发布任务，该任务延迟10秒后再执行
				// $job->release(10);

				//第二种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
				// $job->failed();

				//第三种处理方式：删除任务
				$job->delete();
			}
		}
	}
}