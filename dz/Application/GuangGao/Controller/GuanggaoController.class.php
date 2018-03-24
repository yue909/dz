<?php
namespace GuangGao\Controller;
use Think\Controller;
class GuanggaoController extends IsloginController {
    //加载广告列表页面
    public function lists()
    {
        //获取用户信息
        $user = session("GuangGao");
        $userG = M('guanggao_title')->where("uid = '{$user['id']}'")->select();
        $this->assign("list",$userG);
        $this->display("Guanggao/list");
    }

    //修改广告
    public function edit()
    {
        //获取要修改的广告ID
        $list = $_POST['id'];
        //判断该广告是否是待审核状态 0待审核 1审核通过
        $lists = M('guanggao_title')->where("id = '{$list}' AND status = 0")->find();
        if($lists){
            $this->assign("list",$list);
            $this->display("Guanggao/edit");
        }else{
            $this->error("该广告已通过审核,无法修改");
        }
    }

    //执行修改广告
    public function doedit()
    {
        //获取要执行修改的广告信息
        $list = $_POST;
        //判断该广告是否是待审核状态 0待审核 1审核通过
        $lists = M('guanggao_title')->where("id = '{$list['id']}' AND status = 0")->find();
        if($lists) {
            $edits = M('guanggao_title')->where("id = '{$list['id']}'")->data($list)->save();
            if($edits) {
                $this->success("修改成功",U("Guanggao/list"));
            } else {
                $this->error("修改失败");
            }
        } else {
            $this->error("该广告已通过审核,无法修改");
        }
    }

    //添加广告
    public function add()
    {
        $this->display('Add/add');
    }

    //执行添加广告
    public function doadd()
    {
        //获取用户信息
        $user = session("GuangGao");
        //获取用户ID
        $userid = $user['id'];
        //获取广告信息
        $GuangGao = $_POST;
        $GuangGao['uid'] = $userid;
        $add = M('guanggao_title')->data($GuangGao)->add();
    }
}