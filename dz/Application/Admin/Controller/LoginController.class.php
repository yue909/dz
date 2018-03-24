<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller {
    //加载登陆页面
    public function login(){
      $this->display("Login/login");
    }

    //验证信息
    public function yanzheng()
    {	

        $luotest_response = I('post.luotest_response');
        // if (empty($luotest_response)) {
        //     $this->error("验证码有误", U("Login/login"));
        //   }
            // $luotest_check = captchaVerified($luotest_response);

       if($luotest_check['code'] == 0){
    		$username=$_POST['username'];
    		$password=$_POST['password'];
            $row=M("admin_user")->where("username = '{$username}' AND password = '{$password}'")->find();
        	$row1=M("admin_users")->where("username = '{$username}' AND password = '{$password}'")->find();

            if($row1 || $row){
                //是否能够访问
                if($row1['status'] !=='1' && $row['status'] !=='1'){
                    $this->error('禁止访问');
                }
    			$_SESSION['username']=($row1 == true)?$row1['username']:$row['username'];
    			$_SESSION['islogin']=2;
                $this->success("登录成功",U("Index/index"));
            }else{
                $this->error("用户名或者密码有误",U("Login/login"));
            }           
            
        }
        else{
            // echo "000";
            $this->error("验证码有误", U("Login/login"));
        }
    }


    //退出登录
    public function logout()
    {
    	setCookie(session_name(),'',time()-100,'/');
        //session数组空值
        $_SESSION=array();
        //销毁session
        session_destroy();
        $this->success('退出成功',U("Login/login"));
    }
    
}