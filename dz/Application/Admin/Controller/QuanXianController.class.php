<?php
namespace Admin\Controller;
use Think\Controller;
class QuanXianController extends QXController {
    //角色列表
    public function jiaose()
    {
        $jiaose = M('admin_role')->select();
        $this->assign("jiaose",$jiaose);
        $this->display("quanxian/jiaose");
    }
    //添加角色
    public function tianjiajiaose()
    {
        $this->display("quanxian/tianjiajiaose");
    }
    //执行添加角色
    public function jsadd(){
        $add['name'] = $_POST['name'];
        $add['remark'] = $_POST['remark'];
        $add['status'] = 0;
        $result = M('admin_role')->data($add)->add();
        if($result){
            $this->success('角色添加成功',U("QuanXian/jiaose"));
        }else{
            $this->error('角色添加失败');
        }
    }

    //执行删除角色
    public function jiaosedel(){
        // var_dump($_GET);
        // exit;
        $del = M('admin_role')->where("id = '{$_GET['id']}'")->delete();
        if($del){
            $this->success('删除角色成功');
        }else{
            $this->error("删除角色失败");
        }
    }

    //修改角色
    public function jiaoseedit(){
        $edit = M('admin_role')->where("id = '{$_GET['id']}'")->find();
        $this->assign("edit",$edit);
        $this->display('quanxian/xiugaijiaose');
    }

    //执行修改角色
    public function editjiaose(){
        $edit['name'] = $_POST['name'];
        $edit['remark'] = $_POST['remark']; 
        $edit = M('admin_role')->where("id = '{$_POST['id']}'")->data($edit)->save();
        if($edit){
            $this->success('角色修改成功',U("QuanXian/jiaose"));
        }else{
            $this->error('修改角色失败');
        }
    }

    //执行角色分配权限
    public function uquanxianfenpei()
    {
        //清除角色所有权限
        $rid = $_POST['rid'];
        $results = M("admin_role_node")->where("rid = '{$rid}'")->select();
        if($results){
            M('admin_role_node')->where("rid = '{$rid}'")->delete();
        }


        $role = count($_POST['nid']);
        $id['cid'] = $_POST['nid'];
        for($i = 0;$i<$role;$i ++){
            $id['nid'] = $id['cid'][$i];
            $id['rid'] = $_POST['rid'];

            $add = M('admin_role_node')->data($id)->add();
        }

        if($add){
            $this->success("添加权限成功");
        }else{
            $this->error("权限清空成功");
        }

    }

    //权限列表
    public function liebiao()
    {
        $mod = M('admin_node');
        $count=$mod->Count();
        $pag=new \Think\Page($count,20);
        $pag->setConfig('prev','上一页');
        $pag->setConfig('next','下一页');
        $pag->setConfig('first','首页');
        $pag->setConfig('last','尾页');
        $pag->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $fen=$pag->show();
        $result=$mod->limit($pag->firstRow,20)->select();
        $this->assign('fen',$fen);
        $this->assign("result",$result);
        $this->display("quanxian/quanxian");
    }
    //添加权限
    public function tianjiaquanxian()
    {
        $list = M('admin_node_class')->select();
        $this->assign("list",$list);
        $this->display("quanxian/tianjiaquanxian");
    }
    // 执行添加权限
    public function qxadd(){

        $result['name'] = $_POST['name'];
        $result['mname'] = $_POST['controller'];
        $result['aname'] = $_POST['remark'];
        $result['describe'] = $_POST['miaoshu'];
        $result['status'] = 0;
        $addqx = M('admin_node')->data($result)->add();
        $addclass = M("admin_node_class_do")->data(array("cid" => $_POST['class'],"nid" => $addqx))->add();
        if($addqx && $addclass){
            $this->success("权限添加成功",U("QuanXian/liebiao"));
        }else{
            $this->error('权限添加失败');
        }
    }
    //执行删除权限
    public function quanxiandel(){
        $del = M('admin_node')->where("id = '{$_GET['id']}'")->delete();
        if($del){
            $this->success('删除权限成功');
        }else{
            $this->error("删除权限失败");
        }
    }
    //修改权限
    public function quanxianedit(){
        $edit = M('admin_node')->where("id = '{$_GET['id']}'")->find();
        $fenlei = M("admin_node_class")->select();
        $this->assign("fenlei",$fenlei);
        $this->assign("edit",$edit);
        $this->display('quanxian/xiugaiquanxian');
    }
    //执行修改权限
    public function editquanxian(){

        $result['name'] = $_POST['name'];
        $result['mname'] = $_POST['controller'];
        $result['aname'] = $_POST['remark'];
        $result['describe'] = $_POST['miaoshu'];
        $edit = M('admin_node')->where("id = '{$_POST['id']}'")->data($result)->save();
        //添加/修改权限分类
        //删除之前分类
        $delfenlei = M('admin_node_class_do')->where("nid = '{$_POST['id']}'")->delete();
        //添加至新分类
        $addfenlei = M('admin_node_class_do')->data(array("nid" => $_POST['id'],"cid" => $_POST['fenlei']))->add();
        if($edit || $delfenlei || $addfenlei){
            $this->success('修改权限成功',U("QuanXian/liebiao"));
        }else{
            $this->error('修改权限失败');
        }
    }
    //用户角色分配
    public function yonghujiaose(){

        $jiaose = M("admin_role")->select();
        $yonghu = $_GET['id'];
        $yonghu  = M('admin_users')->where("id = '{$yonghu}'")->find();
        $yonghujiaose = M("admin_user_role")->where("uid = '{$_GET['id']}'")->field('rid')->select();
        $result = array();
        foreach($yonghujiaose as $key => $value){
            foreach ($value as $key => $value) {
                $result[] .= $value;
            }
        }
        $this->assign("yonghujiaose",$result);
        $this->assign('yonghu',$yonghu);
        $this->assign('jiaose',$jiaose);
        $this->display("yonghu/jiaosefenpei");
    }
    //执行用户角色分配
    public function ujiaosefenpei()
    {
        //清除用户所有权限
        $rid = $_POST['rid'];
        $results = M("admin_user_role")->where("uid = '{$rid}'")->select();
        if($results){
            M('admin_user_role')->where("uid = '{$rid}'")->delete();
        }
        $role = count($_POST['nid']);
        $id['cid'] = $_POST['nid'];
        for($i = 0;$i<$role;$i ++){
            $id['rid'] = $id['cid'][$i];
            $id['uid'] = $_POST['rid'];
            $add = M('admin_user_role')->data($id)->add();
        }
        if($add){
            $this->success("添加角色成功");
        }else{
            $this->error("清空角色成功");
        }
    }
    //查看顶级菜单
    public function caidan(){
        $caidan = M('admin_class')->select();
        $parent = array();
        //拿出顶级菜单
        foreach ($caidan as $key => $value) {
            if($value['class'] == null){
                $parent[] = $value['id'];
            }
        }
        //顶级菜单
        $parentnum = count($parent);
        $parentA = array();
        for($i = 0;$i < $parentnum;$i ++){
            $parentname = M('admin_class')->where("id = '{$parent[$i]}'")->find();
            $parentA[] = $parentname;
        }
        $this->assign('parentA',$parentA);
        $this->display("quanxian/caidan");
    }
    //删除顶级菜单
    public function caidandel(){
        //判断顶级菜单下有没有菜单
        //获取顶级菜单ID
        $dingjicaidan = $_GET['id'];
        $caidan = M('admin_class')->where("class = '{$dingjicaidan}'")->select();
        if ($caidan) {
            $this->error("请先删除内部菜单");
        }else{
            $del = M('admin_class')->where("id = '{$dingjicaidan}'")->delete();
            if ($del) {
                $this->success('删除菜单成功');
            }
        }
    }
    //编辑顶级菜单
    public function caidanedit(){
        $dingjicaidan = $_GET['id'];
        $caidan = M("admin_class")->where("class = '{$dingjicaidan}'")->select();
        $this->assign('dingjicaidan',$dingjicaidan);
        $this->assign('caidan',$caidan);
        $this->display('quanxian/caidanedit');
        

    }
    //编辑次级菜单
    public function caidanedits()
    {
        $caidan = M('admin_class')->where("id = '{$_GET['id']}'")->find();
        $this->assign("caidan",$caidan);       
        $this->display("quanxian/caidanedits");
    }
    //执行次级菜单修改
    public function caidaneditts()
    {
        $edit['name'] = $_POST['name'];
        $edit['contro'] = $_POST['contro'];
        $edit['action'] = $_POST['action'];

        $caidan = M('admin_class')->where("id = '{$_POST['id']}'")->data($edit)->save();
        if($caidan){
            $this->success('修改成功',U("QuanXian/caidan"));
        }else{
            $this->error("修改失败");
        }
    }
    //执行次级菜单删除
    public function caidandels(){
        $dingjicaidan = $_GET['id'];
        $del = M('admin_class')->where("id = '{$dingjicaidan}'")->delete();
        if($del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //添加顶级菜单
    public function addcaidan(){
        $this->display("quanxian/addcaidan");
    }
    //执行添加顶级菜单
    public function toaddcaidan(){
        $add = M('admin_class')->data($_POST)->add();
        if($add){
            $this->success("添加菜单成功",U("QuanXian/caidan"));
        }else{
            $this->error("菜单添加失败");
        }
    }
    //添加次级菜单
    public function addscaidan()
    {
        // //获取父级ID
        // $bestid['class'] = $_GET['id'];
        // $bestid['name']
        // //添加子类菜单
        // $addcd = M('admin_class')->
        $this->assign('bestid',$_GET['id']);
        $this->display('quanxian/addscaidan');
    }
    //执行添加次级菜单
    public function insertcaidan()
    {

        $add['name'] = $_POST['name'];
        $add['contro'] = $_POST['contro'];
        $add['action'] = $_POST['action'];
        $add['class'] = $_POST['flid'];
        $adds = M('admin_class')->data($add)->add();
        if($adds){
            $this->success("添加成功",U('QuanXian/caidan'));
        }else{
            $this->error('添加失败');
        }
        
    }
    //添加权限分类
    public function addquanxianclass()
    {
        //执行分类添加
        if($_POST){
            $addclass = M('admin_node_class')->data($_POST)->add();
            if($addclass){
                $this->success("添加权限分类成功",U("QuanXian/lookclass"));
            }else{
                $this->error("添加权限分类失败");
            }
        }else{
        //加载添加页面
            $this->display("quanxian/quanxianclass");
        }
    }
    //查看权限分类
    public function lookclass()
    {
        if($_GET['delid']){
        //删除
            $sel = M("admin_node_class_do")->where("cid = '{$_GET['delid']}'")->field("nid")->select();
            if($sel){
                $this->error("内部有分配权限,请删除后再操作");
            }else{

                $dele = M("admin_node_class")->where("id = '{$_GET['delid']}'")->delete();
                if($dele){
                    $this->success("删除成功",U('QuanXian/lookclass'));
                }else{
                    $this->error("删除失败");
                }
            }
        }elseif($_GET['editid'] || $_POST){
            if($_GET){
                //加载修改页面
                $editclass = M('admin_node_class')->where("id = '{$_GET['editid']}'")->find();
                $this->assign("list",$editclass);
                $this->display("quanxian/editclass");
            }else{
                //执行修改
                $doeditclass = M('admin_node_class')->where("id = {$_POST['id']}")->data($_POST)->save();
                if($doeditclass){
                    $this->success('修改成功',U("QuanXian/lookclass"));
                }else{
                    $this->error("修改失败");
                }
            }
        }else{
        //加载主页
            $lookclass  = M('admin_node_class')->select();
            $this->assign("list",$lookclass);
            $this->display("quanxian/lookclass");
        }
    }
    //角色分配权限
    public function quanxianfenpei(){
        $jiaose = M('admin_role')->where("id = '{$_GET['id']}'")->find();
        $quanxian = M("admin_node")->select();
        $array = array();
        $fenlei  = M('admin_node_class')->join("LEFT JOIN dz_admin_node_class_do on dz_admin_node_class.id = dz_admin_node_class_do.cid")->field("dz_admin_node_class.*,dz_admin_node_class_do.nid")->select();
        foreach ($fenlei as $key => $value) {
            if($array[$value['id']]){
                array_push($array[$value['id']]['nid'],$value['nid']);
            }else{
               $temp = $value['nid'];
               unset($value['nid']);
               $array[$value['id']] = $value;
               $array[$value['id']]['nid'][] = $temp; 
            }
        }
        $quanxianguanli = M("admin_role_node")->where("rid = '{$_GET['id']}'")->field("nid")->select();
        $result = array();
        foreach($quanxianguanli as $key => $value){
            foreach ($value as $key => $value) {
                $result[] .= $value;
            }
        }
        $this->assign("class",$array);
        $this->assign("quanxianguanli",$result);
        $this->assign("jiaose",$jiaose);
        $this->assign("quanxian",$quanxian);
        $this->display("quanxian/quanxianfenpei");
    }
}