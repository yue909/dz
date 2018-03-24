<?php
namespace Home\Controller;
use Think\Controller;
class GongGaoController extends QXController {
    //管理公告
    public function guanli()
    {
        $this->display("gonggao/guanli");
    }
    //发布公告
    public function fabu()
    {
        
        $this->display("gonggao/fabu");

    }
    
}