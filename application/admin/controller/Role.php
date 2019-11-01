<?php
namespace app\admin\controller;
use app\admin\common\controller\Base;
use app\admin\common\model\Role as RoleModel;
use app\admin\common\model\Rolepower;
use app\admin\common\model\Term;
use app\admin\common\model\Power;
use think\facade\Request;
use think\facade\Session;
use think\Db;

class Role extends Base
{
	//权限管理
	public function powerList(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$powerList = Db::table('paper_power')->order('create_time','asc')->paginate(5);
		
		$this->view->assign('navActive','7');
		$this->view->assign('powerList',$powerList);
		$this->view->assign('title','权限管理');
		return $this->view->fetch('powerList');
	}
	
	//添加权限
	public function addPower(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		$data = Request::param();
		$type = $data['r'];

		$powerList = Db::table('paper_power')->order('create_time','asc')->select();
		
		$this->view->assign('navActive','7');
		$this->view->assign('title','添加权限');
		$this->view->assign('type',$type);
		$this->view->assign('powerList',$powerList);
		return $this->view->fetch('addPower');
	}
	
	//执行添加权限
	public function doAddPower(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$data = Request::param();
		
		$res = $this->validate($data,'app\admin\common\validate\Power');
		
		if(true !== $res){
			$this->error($res);
		}
		
		if(Power::create($data)){
			$this->success('创建成功');
		}else{
			$this->error('创建失败,请检查bug');
		}
		
	}
	
	//编辑权限
	public function editPower(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$id = Request::param('id');
		
		$powerInfo = Power::get($id);
		
		$this->view->assign('navActive','7');
		$this->view->assign('title','编辑权限');
		$this->view->assign('powerInfo',$powerInfo);
		return $this->view->fetch('editPower');
	}
	
	//执行修改权限
	public function doEditPower(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$data = Request::param();
		
		$res = $this->validate($data,'app\admin\common\validate\Power');
		
		if(true !== $res){
			$this->error($res);
		}
		
		if(Power::update($data)){
			$this->success('修改成功','role/powerList');
		}else{
			$this->error('修改失败,请检查bug');
		}
	}

	//删除权限
	public function delPower(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$id = Request::param('id');
		
		if($id){
			if(Power::destroy($id)){
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}			
		}else{
			$this->error('没找到此权限,请检查bug');
		}
	}
	
	//角色列表
	public function roleList(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$roleList = Db::table('paper_role')->paginate(5);
		
		$this->view->assign('navActive','7');
		$this->view->assign('roleList',$roleList);
		$this->view->assign('title','角色管理');
		return $this->view->fetch('roleList');
	}

	//选择添加权限的模块，前台/后台
	public function selectRoleModel(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$this->view->assign('navActive','7');
		$this->view->assign('title','添加角色');
		return $this->view->fetch('selectRoleModel');
	}
	
	//添加角色
	public function addRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$type = (int)Request::param('role_type');
		if($type == 1){
			$powerList = Db::table('paper_power')->where('type',1)->where('pid',0)->order('create_time','asc')->select();
			$this->view->assign('type',1);
		}else{
			$powerList = Db::table('paper_power')->where('type',0)->where('pid',0)->order('create_time','asc')->select();
			$this->view->assign('type',0);
		}
		
		$this->view->assign('navActive','7');		
		$this->view->assign('powerList',$powerList);
		return $this->view->fetch('addRole');
	}
	
	//执行添加角色
	public function doAddRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$data = Request::param();
		
		if(!isset($data['power_id'])){
			$this->error('权限选择不能为空');
		}

		$data['power_id'] = implode(',', $data['power_id']);
		
		if(!RoleModel::create(['name'=>$data['name'],'sta'=>$data['sta']])){
			$this->error('添加失败');
		}

		if(Rolepower::create($data)){
			$this->success('创建成功','role/roleList');
		}else{
			$this->error('创建失败,请检查bug');
		}
		
		// foreach($data['power_id'] as $power){
		// 	$insertData = [
		// 		'name'=>$data['name'],
		// 		'power_id'=>$power
		// 	];
			
		// 	$res = $this->validate($insertData,'app\admin\common\validate\Role');
			
		// 	if(true !== $res){
		// 		$this->error($res);
		// 	}
			
		// 	if(Rolepower::create($insertData)){
		// 		continue;
		// 	}else{
		// 		$this->error('创建失败,请检查bug');
		// 	}
		// }


		// $this->success('创建成功','role/roleList');
		
	}
	
	//编辑角色
	public function editRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$id = Request::param('role_id');
		$roleInfo = Db::table('paper_role')->where('id',$id)->find();

		$roles = Db::table('paper_role_power')->where('name',$roleInfo['name'])->find();

		if($roleInfo['sta']){
			$powerList = Db::table('paper_power')->where('type',1)->where('pid',0)->order('create_time','asc')->select();
		}else{
			$powerList = Db::table('paper_power')->where('type',0)->where('pid',0)->order('create_time','asc')->select();
		}

		$hasRole = explode(',', $roles['power_id']);

		$roleInfo['roles_id'] = $roles['id'];
		
		$this->view->assign('navActive','7');
		$this->view->assign('title','编辑角色');
		$this->view->assign('roleInfo',$roleInfo);
		$this->view->assign('hasRole',$hasRole);
		$this->view->assign('powerList',$powerList);
		return $this->view->fetch('editRole');
	}
	
	//执行修改角色
	public function doEditRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$data = Request::param();
		
		if(!isset($data['power_id'])){
			$this->error('权限选择不能为空');
		}

		$data['power_id'] = implode(',', $data['power_id']);
		
		//如果修改了角色名字
		if($data['name'] != $data['defaultName']){
			$res = Rolepower::destroy(function($query) use ($data){
				$query->where('name',$data['defaultName']);
			});

			if(true !== $res){
				$this->error('修改失败,请检查bug');
			}
			
			//更新用户角色表
			$res = Db::name('paper_user_role')
				->where('role_name', $data['defaultName'])
				->update(['role_name' => $data['name']]);

			if(true !== $res){
				$this->error('修改失败,请检查bug');
			}

			//更新角色表
			$res = Db::name('paper_role')
				->where('name', $data['defaultName'])
				->update(['name' => $data['name']]);

			if(true !== $res){
				$this->error('修改失败,请检查bug');
			}
			
		}		
		
		
								
		$res = $this->validate($data,'app\admin\common\validate\Role');
		
		if(true !== $res){
			$this->error($res);
		}
		
		// var_dump($data);exit;

		if(Rolepower::update($data)){
			$this->success('修改成功','role/roleList');
		}else{
			$this->error('修改失败,请检查bug');
		}

				
	}
	
	//前台用户角色管理
	public function userRoleList(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$userRoleList = Db::table('paper_consumer')->field('id,name,role_id,create_time')->order('create_time','asc')->paginate(10);
		// var_dump($userRoleList);exit;
		$this->view->assign('navActive','7');
		$this->view->assign('title','用户角色管理');
		$this->view->assign('userRoleList',$userRoleList);
		return $this->view->fetch('userRoleList');
	}
	
	//后台用户角色管理
	public function adminRoleList(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		$adminRoleList = Db::table('paper_user')->field('id,username,role_id,create_time')->order('create_time','asc')->paginate(5);
		$this->view->assign('navActive','7');
		$this->view->assign('title','用户角色管理');
		$this->view->assign('adminRoleList',$adminRoleList);
		return $this->view->fetch('adminRoleList');
	}
	
	
	//编辑前台用户角色
	public function editUserRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		//获取用户id
		$id = Request::param('id');
		
		//查询用户角色
		
		$roleInfo = Db::table('paper_consumer')->where('id',$id)->find();

		$role = Db::table('paper_role')->where('id',$roleInfo['role_id'])->find();
		
		//查询所有角色
		$roleList = Db::table('paper_role')->where('sta',$role['sta'])->select();
		
		$this->view->assign('navActive','7');
		$this->view->assign('title','用户角色管理');
		$this->view->assign('roleInfo',$roleInfo);
		$this->view->assign('roleList',$roleList);
		return $this->view->fetch('editUserRole');
	}
	
	//修改前台用户角色
	public function doEditUserRole()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$data = Request::param();
		if(Db::name('paper_consumer')->where('id',$data['id'])->update($data)){
			$this->success('修改成功','userRoleList');
		}else{
			$this->error('修改失败，请检查bug');
		}	
	}
	
	
	//编辑后台用户角色
	public function editAdminRole(){
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');
		
		//获取用户id
		$id = Request::param('id');
		
		//查询用户角色
		
		$roleInfo = Db::table('paper_user')->where('id',$id)->find();

		$role = Db::table('paper_role')->where('id',$roleInfo['role_id'])->find();

		//查询所有角色
		$roleList = Db::table('paper_role')->where('sta',$role['sta'])->select();

		
		$this->view->assign('navActive','7');
		$this->view->assign('title','用户角色管理');
		$this->view->assign('roleInfo',$roleInfo);
		$this->view->assign('roleList',$roleList);
		return $this->view->fetch('editAdminRole');
	}
	
	//修改后台用户角色
	public function doEditAdminRole()
	{
		$this->isLogin();
		$this->hasPower(Session::get('admin_id'), 'admin/role/powerList');

		$data = Request::param();
		if(Db::name('paper_user')->where('id',$data['id'])->update($data)){
			$this->success('修改成功','adminRoleList');
		}else{
			$this->error('修改失败，请检查bug');
		}	
	}
	
	//生成权限表
	public function createTable()
	{
		$powerList = Db::table('paper_power')->where('type',1)->order('create_time','asc')->select();
		$data = '';
		foreach($powerList as $power){
			// if($power['pid']){
			// 	//子级权限

			// }else{
			// 	//父级权限

			// }
			$data .= $power['name']."\t".$power['name']."\t".$power['url']."\n";
		}
		// var_dump($powerList);
		$path = dirname(dirname(dirname(__DIR__))).'/runtime/cache/power/';

		if(!is_dir($path)){
			mkdir($path,0777,true);
		}
		file_put_contents($path.'power-table.json', $data);
	}
}
?>