<?php
namespace Home\Controller;
use Think\Controller;
class AnnounceController extends QXController {
	//公告列表
	public function lists() {
		$Announce = M('home_announce');
		$count    = $Announce->count();
		$Page     = new \Think\Page($count, 10);
		$show     = $Page->show();
		$list = $Announce->order('create_time')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
	    $this->display("Announce/lists");
	}

	//预览公告
	public function preview() {
		$id = intval(I('get.id'));
		if ($id <= 0) {
			$this->error("id 不能为空！");
		}
		$announce = M('home_announce')->where("id = {$id}")->find();
		if ($announce) {
			$announce['content'] = htmlspecialchars_decode($announce['content']);
			$this->assign('announce', $announce);
			$this->display("Announce/preview");
		} else {
			$this->error("公告不存在！");
		}
	}

	//删除公告
	public function delete() {
		$req = array('code' => -1, 'msg' => '删除异常！');
		$id = intval(I('get.id'));
		if ($id <= 0) {
			$this->error("id 不能为空！");
		}
		$delete = M('home_announce')->where("id = {$id}")->delete();
		if ($delete) {
			$req = array('code' => 1, 'msg' => '删除成功！');
		} else {
			$req = array('code' => 0, 'msg' => '删除失败,请稍后再试！');
		}
		$this->ajaxReturn($req);
	}

	//修改公告
	public function edit() {
		$op = I('get.op');
		$id = intval(I('get.id'));
		if (empty($op)) {
			$op = 'display';
		}
		if ($id <= 0) {
			$this->error("id 不能为空！");
		}
		if ($op == 'display') {
			$announce = M('home_announce')->where("id = {$id}")->find();
			if ($announce) {
				$announce['content'] = htmlspecialchars_decode($announce['content']);
				$this->assign('announce', $announce);
				$this->display("Announce/edit");
			} else {
				$this->error("公告不存在！");
			}
			
		} elseif ($op == 'save') {
			$req = array('code' => -1, 'msg' => '修改异常！');
			$title = I('post.title');
			$content = I('post.content');
			$status = I('post.status');
			$data = array(
				'title' => $title,
				'content' => $content,
				'status' => $status,
				'update_time' => time()
			);
			$update = M('home_announce')->where("id = {$id}")->save($data);
			if ($update) {
				$req = array('code' => 1, 'msg' => '修改成功！');
			} else {
				$req = array('code' => 0, 'msg' => '修改失败,请稍后再试！');
			}
			$this->ajaxReturn($req);
		}
	}

	//添加公告
	public function add() {
		$op = I('get.op');
		if (empty($op)) {
			$op = 'display';
		}
		if ($op == 'display') {
			$this->display("Announce/add");
		} elseif ($op == 'save') {
			$req = array('code' => -1, 'msg' => '发布异常！');
			$title = I('post.title');
			$content = I('post.content');
			$data = array(
				'title' => $title,
				'content' => $content,
				'author' => session("username"),
				'create_time' => time(),
				'update_time' => time(),
				'status' => 0
			);
			$insert = M('home_announce')->add($data);
			if ($insert) {
				$req = array('code' => 1, 'msg' => '发布成功！');
			} else {
				$req = array('code' => 0, 'msg' => '发布失败,请稍后再试！');
			}
			$this->ajaxReturn($req);
		}
	}
}