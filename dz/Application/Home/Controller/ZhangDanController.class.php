<?php
namespace Home\Controller;
use Think\Controller;
class ZhangDanController extends QXController {
    //账单列表
    public function lists()
    {
        $users = M("admin_user_bill")->join("LEFT JOIN dz_admin_users on dz_admin_user_bill.uid = dz_admin_users.id")->field("dz_admin_user_bill.*,dz_admin_users.username")->select();
        $this->assign("users",$users);
        $this->display('zhangdan/liebiao');
    }
    //添加账单
    public function add()
    {
        $users = M('admin_users')->field('username,id')->select();
        // var_dump($users);
        // exit;
        $this->assign("users",$users);
        $this->display("zhangdan/tianjia");
    }
    //执行账单添加
    public function doadd()
    {   
        $data = array(
            'uid' => $_POST['userid'],
            'money' => $_POST['money'],
            'pattern' => $_POST['pattern'],
            'pname' => $_POST['pname'],
            'time' => time()
        );
        $add = M('admin_user_bill')->data($data)->add();
        if($add){
            $this->success('账单添加成功',U("ZhangDan/lists"));
        }else{
            $this->error('账单添加失败');
        }
    }
    //删除账单
    public function delete()
    {
        $del = M('admin_user_bill')->where("id = '{$_GET['id']}'")->delete();
        if($del){
            $this->success('删除成功',U('ZhangDan/lists'));
        }else{
            $this->error('删除失败');
        }
    }
    //订单修改
    public function edit()
    {
        $edit = M('admin_user_bill')->join("LEFT JOIN dz_admin_users on dz_admin_user_bill.uid = dz_admin_users.id")->where("dz_admin_user_bill.id = '{$_GET['id']}'")->field("dz_admin_user_bill.*,dz_admin_users.username")->find();
        // var_dump($edit);
        // exit;
        $this->assign("edit",$edit);
        $this->display("zhangdan/xiugaizhangdan");
    }
    //执行修改
    public function doedit()
    {
        $data = array(
            'pname' => $_POST['remark'],
            'money' => $_POST['money'], 
            'pattern' => $_POST['pattern'],
            'time' => time()
        );
        $edit = M('admin_user_bill')->where("id = '{$_POST['id']}'")->data($data)->save();
        if($edit){
            $this->success('修改成功',U("ZhangDan/lists"));
        }else{
            $this->error('修改失败');
        }
    }
    //查询
    public function search()
    {
        $names = $_POST['name'];
        $search = M('admin_user_bill')->join("LEFT JOIN dz_admin_users on dz_admin_user_bill.uid = dz_admin_users.id")->where("dz_admin_users.username like '%{$names}%'")->field("dz_admin_user_bill.*,dz_admin_users.username")->select();
        
        $this->assign("users",$search);
        $this->display('zhangdan/liebiao');
    }

}