<?php
namespace Admin\Controller;
use Think\Controller;
class XiugaimimaController extends QXController {

	    public function index()
	    {
			$this->display('xiugaimima/index');
	    }  
	     
	    public function edit()
	    {

	    	$this->display("xiugaimima/update");
	    }
	    //修改密码
  		public function update()
      	{	
      		if($_GET){
      			//判断旧密码
	      		$pass = $_GET['val'];
	      		$username = session('username');
				// var_dump($username);
	      		// exit;
	      		$user= M('admin_user')->where("username = '{$username}' AND password = '{$pass}'")->select();
				$users= M('admin_users')->where("username = '{$username}' AND password = '{$pass}'")->select();
	      		
	      		if($user || $users){
	      			echo 1;
	      		}else{
	      			echo 0;
	      		}
      		}else{
      			//执行新密码修改
      			$pass = $_POST['newpwd'];
      			$npass = $_POST['repwd'];
	      		$username = session('username');
				if($pass !== $npass){
      				$this->error("两次密码不同");
      			}else{
      				$data['password'] = $pass;
      				$updates = M('admin_user')->where("username = '{$username}'")->data($data)->save();
      				$update = M('admin_users')->where("username = '{$username}'")->data($data)->save();
      				if($updates || $update ){
      					$this->success("修改密码成功",U("Index/index"));
      				}else{
      					$this->error('修改密码失败');
      				}
      			}	
      		}
    	}
}