<?php
/**
*贴子类
*/

namespace app\index\controller;
use app\common\controller\Base;//导入公共控制器
use app\admin\common\model\Post as PostModel;
use app\common\model\Comment;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;

class Posts extends Base
{
	//显示贴子详情
	public function postDetail()
	{

		$postId = Request::param('id');
		$postInfo = PostModel::get($postId);

		$commentList = Comment::where('post_id',$postId)->select();

		$this->view->assign('postInfo',$postInfo);
		$this->view->assign('commentNum',count($commentList));
		$this->view->assign('title',$postInfo['title']);
		return $this->view->fetch('postDetail');
	}

	//显示更多贴子
	public function morePost(){

		$sta = Cookie::get('postSchool');
		
		$map = [];
		if($sta !== null && $sta == 1){
			$map[] = ['u.school_name','=',Session::get('user_school')];
		}
		
		$postList = Db::table('paper_post')
							->alias('p')->join('paper_user u','p.user_id = u.id')
							->field('p.id,p.title,p.subtitle,p.content,p.writer,p.user_id,u.school_name,p.create_time')
							->where($map)
							->order('create_time','desc')
							->paginate(3);
		$this->assign('postList',$postList);
		$this->view->assign('title','贴子专区');
		return $this->view->fetch('morePost');
	}

	//选择学校区域贴子
	public function selectPost(){

		$data = Request::param('sta');
		Cookie::set('postSchool',$data);
		return ['status'=>1,'message'=>$data];
	}

	//评论贴子
	public function commit()
	{

		$data = Request::param();
		$data['content'] = strip_tags($data['content']);	
		$res = $this->validate($data,'app\common\validate\Comment');
		if(true !== $res){
			$this->error($res);
		}
		
		if(Comment::create($data)){
			$this->success('评论成功');
		}else{
			$this->error('评论失败');
		}
	}

	//回复评论
	public function reply()
	{

		$data = Request::param();
		$res = $this->validate($data,'app\common\validate\Comment');
		if(true !== $res){
			$this->error($res);
		}
		
		if(Comment::create($data)){
			$this->success('评论成功');
		}else{
			$this->error('评论失败');
		}
	}


	//递归建立所有评论
	public function getCommlist($postId,$reply_id = 0,&$result = array(),&$lv=0){       
	    $arr = Comment::where('post_id',$postId)->where('reply_id',$reply_id)->select();

	    if(empty($arr)){
	        return array();
	    }
	    $lv++;
	    foreach ($arr as $cm) {
	    	//来存放每一级的结果  
	        $thisArr=&$result[];
	        $cm['lv'] = $lv;
	        $cm["children"] = $this->getCommlist($postId,$cm["id"],$thisArr,$lv);    
	        $lv = $cm['lv'];
	        //来存放每一级的结果  
	        $thisArr = $cm;

	    }
	    return $result;
   }

   //获取评论列表HTML
	public function postHtml($commentList,&$s = '',$first = null){
		$str = &$s;

		foreach($commentList as $comment){

			if($comment['lv'] == 1){
				$str .= '<li class="list-group-item">
							    <h5 class="list-group-item-heading"><img src="'.getConsumerImg($comment['user_id']).'" style="width:45px;height:45px;margin:5px 0px;border-radius:100%;"/>&nbsp;<small style="font-size:14px;"><a href="'.'/index/consumer/userDetail/id/'.$comment['user_id'].'">'.getUserName($comment['user_id']).'</a></small>&nbsp;&nbsp;&nbsp;<small>'.$comment['create_time'].'</small>&nbsp;&nbsp;<span><button class="btn btn-sm btn-warning reply" style="padding:1px 3px;cursor:pointer;"  data-toggle="modal" data-target=".replymodel" replyId="'.$comment['id'].'" replyUser="'.getUserName($comment['user_id']).'">回复</button></span></h5>
							    <p style="padding:0 45px;">'.$comment['content'].'</p>';
				$first = $comment['user_id'];

			}else{
				$str .= '<div style="margin:0 45px;border-top:1px solid #ddd;">
								    <h5 style="margin:0;"><img src="'.getConsumerImg($comment['user_id']).'" style="width:30px;height:30px;margin:5px 0px;border-radius:100%;"/>&nbsp;<small style="font-size:13px;"><a href="'.'/index/consumer/userDetail/id/'.$comment['user_id'].'">'.getUserName($comment['user_id']).'</a></small>';				

				if($first && $first != getPostUserId($comment['reply_id'])){
					$str .= '&nbsp;&nbsp;&nbsp;<small>回复</small>&nbsp;<small style="font-size:13px;"><a href="/index/consumer/userDetail/id/'.getPostUserId($comment['reply_id']).'">@'.getPostUser($comment['reply_id']).'</a></small>&nbsp;&nbsp;&nbsp;<small>'.$comment['create_time'].'</small>&nbsp;&nbsp;<span><button class="btn btn-sm btn-warning reply" style="padding:1px 3px;cursor:pointer;"  data-toggle="modal" data-target=".replymodel" replyId="'.$comment['id'].'" replyUser="'.getUserName($comment['user_id']).'">回复</button></span></h5>
								    <p style="padding:0 30px;">'.$comment['content'].'</p>';
				}else{
					$str .= '&nbsp;&nbsp;&nbsp;<small>'.$comment['create_time'].'</small>&nbsp;&nbsp;<span><button class="btn btn-sm btn-warning reply" style="padding:1px 3px;cursor:pointer;"  data-toggle="modal" data-target=".replymodel" replyId="'.$comment['id'].'" replyUser="'.getUserName($comment['user_id']).'">回复</button></span></h5>
								    <p style="padding:0 30px;">'.$comment['content'].'</p>';
				}
				$str .= '</div>';

			}	

			
			$children = $comment['children'];

			if($children !== null){							    						
				$this->postHtml($children,$str,$first);		
			}
			if($comment['lv'] == 1){
				$str .= '</li>';
			}else{
				$str .= '</div>';
			}
			  							
		}
		
		return $str;		
	}

	//ajax获取评论接口
	public function getComment(){
		$id = Request::param('id');

		$commentList = $this->getCommlist($id);

		$html = $this->postHtml($commentList);
		return ['status'=>1,'message'=>$html];
	}


	// //循环遍历
	// public function getReply($postId,$reply_id = 0){
	// 	$data = [];
	// 	$commentList = Comment::where('post_id',$postId)->where('reply_id',$reply_id)->select();
	// 	foreach($commentList as $comment){
	// 		if($comment['reply_id'] == 0){
	// 			dump($comment);
	// 			$data[$comment['id']] = $comment;
	// 			$this->getReply($comment['id']);
	// 		}else{
	// 			dump($comment);
				
	// 		}
	// 	}
	// }
}