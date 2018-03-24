<?php
namespace Admin\Controller;
use Think\Controller;
class WechatController extends QXController {
    //微信菜单页面
    public function Wbill()
    {
        $list = M('admin_wechat_menu')->where("rank = 0")->select();
        $this->assign("list",$list);
        $this->display("Wechat/Wbill");
    }

    //添加微信一级菜单
    public function addcaidan()
    {
        $this->display("Wechat/addcaidan");
    }
    //执行添加微信一级菜单
    public function toaddcaidan()
    {
        $Wechat = $_POST;
        $Wechat['rank'] = 0;
        $add = M("admin_wechat_menu")->data($Wechat)->add();
        if($add){
            $this->success("添加成功",U("Wechat/Wbill"));
        }else{
            $this->error("添加失败");
        }
    }
    //删除菜单
    public function caidandel()
    {
        $del = M("admin_wechat_menu")->data($_GET)->delete();
        if($del){
            $this->success("删除成功",U('Wechat/Wbill'));
        }else{
            $this->error("删除失败");
        }
    }
    //修改菜单
    public function caidanedit()
    {
        $id = $_GET['id'];
        $Wechat = M('admin_wechat_menu')->where("id = '{$id}'")->find();
        $this->assign("Wechat",$Wechat);
        $this->display('Wechat/edit');
    }
    //执行修改菜单
    public function tocaidanedit()
    {
        $id = $_POST['id'];
        $edit = $_POST;
        $edits = M("admin_wechat_menu")->where("id = '{$id}'")->data($edit)->save();
        if($edits){
            $this->success("修改成功",U("Wechat/Wbill"));
        }else{
            $this->error("修改失败");
        }
    }
    //查看一级菜单的子类菜单
    public function viewmenu()
    {
        $id = $_GET['id'];
        $view = M('admin_wechat_menu')->where("rank = '{$id}'")->select();
        $this->assign('id',$id);
        $this->assign('list',$view);
        $this->display("Wechat/Wlist");
    }
    //添加子类菜单
    public function addviewmenu()
    {
        $id = $_GET['id'];
        $this->assign('id',$id);
        $this->display('Wechat/addWlist');
    }
    //执行添加子类菜单
    public function toaddWlist()
    {
        $data = $_POST;
        $addW = M('admin_wechat_menu')->data($data)->add();
        if($addW){
            $this->success('添加子类菜单成功',U('Wechat/Wbill'));
        }else{
            $this->error("添加子类菜单失败");
        }
    }
    //删除子类
    public function deleW(){
        $id = $_GET['id'];
        $del = M('admin_wechat_menu')->where("id = '{$id}'")->delete();
        if($del){
            $this->success("删除成功",U('Wechat/Wbill'));
        }else{
            $this->error("删除子类菜单失败");
        }
    }

    //生成菜单数据
    public function Generatemenu()
    {
        $list = M('admin_wechat_menu')->select();
        $listA = array();
        $listB = array();
        foreach ($list as $key => $value) {
            if($value['rank'] == null || $value['rank'] == 0){
                $listA[] = $value;
            }else{
                $listB[] = $value;
            }
        }
        $listD = array();

        foreach ($listA as $key => $value) {
            $listE = array();
            $listE['name'] = $value['name'];
            $listE['sub_button'] = array();
            foreach ($listB as $keys => $values) {
                if($value['id'] == $values['rank']){
                    if($values ['type'] == "view" ) {
                        $listE['sub_button'][] = array(
                            'type' => $values['type'],
                            'name' => $values['name'],
                            'url' => $values['url'],
                        );
                    }
                    if($values['type'] == 'click'){
                        $listE['sub_button'][] = array(
                            'type' => $values['type'],
                            'name' => $values['name'],
                            'key' => $values['key']
                        );
                    }
                    if($values['type'] == 'miniprogram'){
                        $listE['sub_button'][] = array(
                            'type' => $values['type'],
                            'name' => $values['name'],
                            'url' => $values['url'],
                            'appid' => $values['appid'],
                            'pagepath' => $values['pagepath'],
                        );
                    }
                }
            }
            if(!$listE['sub_button']){
                unset($listE['sub_button']);
                $listE['type'] = $value['type'];
                if($listE['type'] == 'view'){
                    $listE['name'] = $value['name'];
                    $listE['url'] = $value['url'];
                }
                if($listE['type'] == 'click'){
                    $listE['name'] = $value['name'];
                    $listE['key'] = $value['key'];
                }
                if($listE['type'] == 'miniprogram'){
                    $listE['name'] = $value['name'];
                    $listE['url'] = $value['url'];
                    $listE['appid'] = $value['appid'];
                    $listE['pagepath'] = $values['pagepath'];
                }
            }
            $listD[] = $listE;
        }
        $listC = array();
        $listC['button'] = $listD;
        $listC = json_encode($listC, JSON_UNESCAPED_UNICODE);
         $URL = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".wx_access_token();
        $httpP = httpPost($URL,$listC);
        $results = json_decode($httpP,ture);
        if($results['errcode'] !== 0){
            $this->error("同步失败");
        }else{
            $this->success("同步成功");
        }
    }
}