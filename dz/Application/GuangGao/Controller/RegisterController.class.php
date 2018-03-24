<?php
namespace GuangGao\Controller;
use Think\Controller;
class RegisterController extends Controller {
    //加载注册页面页面
    public function index()
    {
        $this->display("Login/register");
    }
    
}