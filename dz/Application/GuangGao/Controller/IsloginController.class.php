<?php
namespace GuangGao\Controller;
use Think\Controller;
class IsloginController extends Controller {
    
    public function _initialize(){
    	//判断是否登录过
    	if(!session('GuangGao')){
    		$this->error("请先登录",U("Login/login"));
    	}
    }
}