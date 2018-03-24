<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends QXController {
	//加载公告页面
    public function index()
    {
      $Announce = M('home_announce')->where("status = 0")->select();
      $this->assign("announce", $Announce);
      $this->display("Index/index");
    }

    //展示公告
    public function announce()
    {
    	$id = intval(I('get.id'));
		if ($id <= 0) {
			$this->error("id 不能为空！");
		}
		$announce = M('home_announce')->where("id = {$id}")->find();
		if ($announce) {
			$announce['content'] = htmlspecialchars_decode($announce['content']);
			$this->assign('announce', $announce);
			$this->display("Index/announce");
		} else {
			$this->error("公告不存在！");
		}
    }

}