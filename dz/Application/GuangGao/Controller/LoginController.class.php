<?php
namespace GuangGao\Controller;
use Think\Controller;
class LoginController extends Controller {
    //加载登录页面
    public function login(){
        $this->display("Login/login");
    }
    
    //验证码
    public function verify()
    {
            $Verify = new \Think\Verify();  
            $Verify->fontSize = 18;  
            $Verify->length   = 4;
            $Verify->useNoise = false;  
            $Verify->codeSet = '0123456789';  
            $Verify->imageW = 130;  
            $Verify->imageH = 50;    
            $Verify->entry();  
    }
}