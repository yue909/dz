<?php
namespace Admin\Controller;
use Think\Controller;
class QXController extends Controller {
    public function _initialize()
    {
         if(!session("username") && session('islogin') !== 2){
            $this->error("请先登录",U("Login/login"));

         }
        // 判断用户是否有权限
        $user = M("admin_user")->where("username = '".session("username")."'")->field("admin")->find();
        $users = M('admin_users')->where("username = '".session("username")."'")->field("admin")->find();
        if($user['admin'] !== '1' && $users['admin'] !== '2'){

        	$this->error("对不起,没有权限");

        }else{
        	//判断用户能访问哪些模块
        	//当前控制器
        	//如果不是超级管理员
        	if(!$user){
	        	$Controller = CONTROLLER_NAME;
	        	//当前方法
	        	$Action = ACTION_NAME;
	        	//获取当前登录用户ID
	        	$userid = M('admin_users')->where("username = '".session("username")."'")->field("id")->find();
	        	$userid = $userid['id'];
	        	//获取当前用户的角色
	        	$userQX = M("admin_users")
				        	->join("LEFT JOIN dz_admin_user_role on dz_admin_users.id = dz_admin_user_role.uid")
				        	->where("dz_admin_users.id = {$userid}")
				        	->field("dz_admin_user_role.rid")
				        	->select();
	        	//获取角色数量
	        	$jiaosenum = count($userQX);
	        	//获取角色单独的ID
	        	$jiaoseleibie = array();
	        	for($i = 0;$i < $jiaosenum; $i ++){
	        		$jiaoseleibie[] .= $userQX[$i]['rid'];
	        	}
	        	//通过角色ID查询角色拥有的权限
	        	$userquanxian = array();
	        	$array = array();
	        	$userjs = count($jiaoseleibie);
	        	$array = array();
	        	for ($i=0; $i < $userjs; $i++) {
	        		$users = M('admin_role_node')->join("LEFT JOIN dz_admin_node on dz_admin_role_node.nid = dz_admin_node.id")->where("dz_admin_role_node.rid = '{$jiaoseleibie[$i]}'")->field("dz_admin_node.mname,dz_admin_node.aname")->select();
	        		$userquanxian[] = $users;
	        		foreach ($users as $key => $value) {
		        		 $array[] .= $value['mname'].$value['aname'];
		        	}
	        	}
	        	$userQuanXian = $Controller.$Action;
	        	if(!in_array($userQuanXian, $array)){
	        		$this->error("对不起,没有访问权限");
	        	}else{
	        		$resultss = array();
	        		foreach ($userquanxian as $key => $value) {
	        			foreach ($value as $key => $val) {
	        				$resultss[] = $val;
	        			}
	        		}
	        		$bl = count($resultss);
	        		$yhsj = array();
	        		for($i = 0;$i < $bl;$i ++){
	        			$yhsj[] = M('admin_class')->where("contro = '{$resultss[$i]["mname"]}' AND Action = '{$resultss[$i]["aname"]}'")->select();
	        		}
	        		$bianli = array();
	        		$fatherid = array();
	        		foreach ($yhsj as $key => $value) {
	        			foreach ($value as $key => $value) {
	        					$bianli[] = $value;
	        					if(!in_array($value['class'],$fatherid)){
	        						$fatherid[] = $value['class'];
	        					}
	        			}
	        		}

	        		$fatherxx = array();
	        		$fathernum = count($fatherid);

	        		$tempB = array();

	        		for($i = 0;$i < $fathernum;$i ++){
	        			$tempA = array();
	        			$fid = M("admin_class")->where("id = '{$fatherid[$i]}'")->field('name')->find();
	        			foreach ($bianli as $item) {
	        				if ($item['class'] == $fatherid[$i]) {
	        					$tempA[] = $item;
	        				}
	        			}
	        			$tempB = array(
	        				'father' => $fid['name'],
	        				'fatherid' => $fatherid[$i],
	        				'list' => $tempA
	        			);
	        			$fatherxx[] = $tempB;
	        		}
	        		$this->assign('fatherxx',$fatherxx);
	        	}

       		}else{
       			//超级管理员
       			$bianli = M('admin_class')->select();

        		$fatherid = array();
        		foreach ($bianli as $item) {
        			if(!in_array($item['class'], $fatherid) && !empty($item['class'])) {
						$fatherid[] = $item['class'];
					}
        		}
   				$fatherxx = array();
        		$tempB = array();
        		for($i = 0;$i < count($fatherid);$i ++) {
        			$tempA = array();
        			$fid = M("admin_class")->where("id = '{$fatherid[$i]}'")->field('name')->find();
        			foreach ($bianli as $item) {
        				if ($item['class'] == $fatherid[$i]) {
        					$tempA[] = $item;
        				}
        			}
        			$tempB = array(
        				'father' => $fid['name'],
        				'fatherid' => $fatherid[$i],
        				'list' => $tempA
        			);
        			$fatherxx[] = $tempB;
        		}
        		$this->assign('fatherxx',$fatherxx);
       		}
        }
    }
}