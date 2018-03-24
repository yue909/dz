<?php
namespace Admin\Controller;
use Think\Controller;
class YongHuController extends QXController {
    //用户列表
    public function liebiao()
    {
        $mod=M('admin_users');
        $count=$mod->Count();
        $pag=new \Think\Page($count,4);
        $pag->setConfig('prev','上一页');
        $pag->setConfig('next','下一页');
        $pag->setConfig('first','首页');
        $pag->setConfig('last','尾页');
        $pag->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $fen=$pag->show();
        $list=$mod->limit($pag->firstRow,4)->select();
        $this->assign('fen',$fen);
        $this->assign('list',$list);
        //加载后台模板
        $this->display("yonghu/liebiao");
    }

    //添加用户
    public function tianjia()
    {
        $this->display("yonghu/tianjia");
    }

    //执行添加
    public function insert(){
        $mod=M('admin_users');
        $data['username']=$_POST['username'];
      if($mod->where("username='".$data['username']."'")->select()){
            $this->error('用户名已存在，请更换',U('YongHu/tianjia'));
            exit;
        }
        $data['status']=$_POST['status'];
        $data['daili']=$_POST['daili'];
        $data['password']=$_POST['pwd'];
        $data['phone']=$_POST['phone'];
        if(!preg_match( "/^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$/",$data['phone'])){
            $this->error('手机格式不正确',U('YongHu/tianjia'));
            exit;
        }

        $data['scole']=$_POST['scole'];
        $data['guanggao']=$_POST['guanggao'];
        $data['shouru']=$_POST['shouru'];
        $data['jiesuan']=$_POST['jiesuan'];
        $data['email']=$_POST['email'];
        if(!preg_match('/^[_.0-9a-z-a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,4}$/',$data['email'])){
            $this->error('邮箱格式不正确',U('YongHu/tianjia'));
            exit;
            }
        $data['admin']=$_POST['admin'];
        $data['addtime']=time();
        $repwd=$_POST['repwd'];
        if($data['password']==$repwd){
            $res=$mod->add($data);
            if($res){
                $this->success('添加成功',U('YongHu/liebiao'));
            }else{
                $this->error('添加失败',U('YongHu/tianjia'));
            }

        }else{
            $this->error('两次密码不一致',U('YongHu/tianjia'));
        }
    }

    //删除用户列表
    public function delete(){
        if(M('admin_users')->delete($_GET['id'])){
            $this->success('删除成功',U('YongHu/liebiao'));
        }else{
            $this->error('删除失败',U('YongHu/liebiao'));
        }
    }


    //加载用户信息修改模板
    public function edit(){
        $mod=M('admin_users');
        $res=$mod->where("id={$_GET['id']}")->find();
        // var_dump($res);
        // exit;
        $this->assign('res',$res);
        $this->display('yonghu/update');
    }

    //用户信息修改
    public function update(){
        $mod=M('admin_users');
        if($_POST['pwd']==$_POST['repwd']){
            if(strlen($_POST['pwd'])==32){
                $mod->create();
                if($mod->save()){
                    $this->success('修改成功',U('YongHu/liebiao'));
                }else{
                    $this->error('修改失败',U('YongHu/liebiao'));
                }
            }else{
                $data['password']=$_POST['password'];
                $data['username']=$_POST['username'];
                $data['status']=$_POST['status'];
                $data['admin']=$_POST['admin'];
                $data['scole']=$_POST['scole'];
                $data['guanggao']=$_POST['guanggao'];
                $data['shouru']=$_POST['shouru'];
                $data['jiesuan']=$_POST['jiesuan'];
                $data['daili']=$_POST['daili'];
                $data['phone']=$_POST['phone'];

                if(!preg_match( "/^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$/",$data['phone'])){
                    $this->error('手机格式不正确',U('YongHu/liebiao'));
                    exit;
                }

                $data['email']=$_POST['email'];

                if(!preg_match('/^[_.0-9a-z-a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,4}$/',$data['email'])){
                    $this->error('邮箱格式不正确',U('YongHu/liebiao'));
                }

                $id=$_POST['id'];
                if($mod->where("id={$id}")->save($data)){
                    $this->success('修改成功',U('YongHu/liebiao'));
                }else{
                    $this->error('修改失败',U('YongHu/liebiao'));
                }
            }
        }else{

            echo time();
        }
    }

    //模糊查询
    public function search()
     {
        $where=array();
            //获取搜索条件
            if(!empty($_POST['aci'])){
                    $userForm=M('admin_users'); //实例化数据表
                    $where['id | addtime']=array('like',"%{$_POST['aci']}%");//写查询条件
                    $results = $userForm->where($where)->select();
                    $this->assign("list",$results);
                    $this->display("YongHu/liebiao");
            }elseif(!empty($_POST['bci'])) {
                    $userForm=M('app_user'); //实例化数据表
                    $where['id ']=array('like',"%{$_POST['bci']}%");//写查询条件
                    $results = $userForm->where($where)->select();
                    $this->assign("list",$results);
                    $this->display("YongHu/WhiteList");
            }else{
                    $userForm=M('admin_users'); //实例化数据表
                    $where['id | addtime']=array('like',"%{$_GET['id']}%");//写查询条件
                    $results = $userForm->where($where)->select();
                    $this->assign("list",$results);
                    $this->display("YongHu/liebiao");
        }
    }

    //前台用户列表页面
    public function query()
    {
        $this->display('yonghu/query');
    }


    //获取搜索用户信息
    public function querysearch()
    {
        //获取用户ID
        if($_POST['userid'] || $_GET['userid']){
            if($_POST['userid']){
                $_POST['userid'] = intval($_POST['userid']) - 3150;
            }else{
                $_POST['userid'] = $_GET['userid'];
            }
            $userid = $_POST['userid'];
        }else{
            $userid = $_GET['id'];
        }
        //获取用户信息
        $user = M('app_user')->where("id = '{$userid}'")->find();
        $user['money'] = admin_fenToYuan($user['money']);
        $user['money_freeze'] = admin_fenToYuan($user['money_freeze']);
        $user['money_total'] = admin_fenToYuan($user['money_total']);
        //获取用户最近十条阅读信息
        $article = M('app_article_log')
                    ->join("LEFT JOIN dz_admin_ruanwen on dz_app_article_log.article_id = dz_admin_ruanwen.id")
                    ->where("dz_app_article_log.uid = '{$userid}'")
                    ->field("dz_app_article_log.*,dz_admin_ruanwen.title")
                    ->order("dz_app_article_log.create_time desc")
                    ->limit(0,10)
                    ->select();
        if ($article) {
            foreach ($article as &$item) {
                $item['money'] = admin_fenToYuan($item['money']);
            }
        }
        //获取用户最近十条收入
        $money = M('app_money_record')->where("uid = '{$userid}'")->order("create_time desc")->limit(0,10)->select();
        if ($money) {
            foreach ($money as &$item) {
                $item['money'] = admin_fenToYuan($item['money']);
                $item['before_money'] = admin_fenToYuan($item['before_money']);
                $item['after_money'] = admin_fenToYuan($item['after_money']);
            }
        }
        //获取用户最近十条提现信息
        $withdraw = M('app_money_withdraw')->where("uid = '{$userid}'")->order("create_time desc")->limit(0,10)->select();
        if ($withdraw) {
            foreach ($withdraw as &$item) {
                $item['money'] = admin_fenToYuan($item['money']);
            }
        }
        $this->assign("withdraw",$withdraw);
        $this->assign('money',$money);
        $this->assign("article",$article);
        $this->assign("user",$user);
        $this->display('yonghu/query');
    }
    //拉黑用户
    public function block()
    {
        if($_GET['id']){
            //拉黑
            $userblock = M('app_user')->where("id = '{$_GET['id']}'")->data(array("status" => 2))->save();
            if($userblock){
                $this->success("拉黑成功",U("YongHu/querysearch?id={$_GET['id']}"));
            }else{
                $this->error('拉黑失败');
            }
        }else{
            //恢复
            $userblock = M('app_user')->where("id = '{$_GET['uid']}'")->data(array("status" => 0))->save();
            if($userblock){
                $this->success("恢复成功",U("YongHu/querysearch?id={$_GET['uid']}"));
            }else{
                $this->error('恢复失败');
            }
        }
    }

    //黑名单用户
    public function BlackList()
    {
        //获取用户信息
        $mod=M('app_user');
        $count=$mod->where("status=2")->Count();
        $pag=new \Think\Page($count,4);
        $pag->setConfig('prev','上一页');
        $pag->setConfig('next','下一页');
        $fen=$pag->show();
        $list=$mod->where("status=2")->order('id')->limit($pag->firstRow,4)->select();
        foreach ($list as &$item) {
            $item['money'] = admin_fenToYuan($item['money']);
            $item['money_total'] = admin_fenToYuan($item['money_total']);
        }
        $this->assign('fen',$fen);
        $this->assign('list',$list);
        $this->display("yonghu/BlackList");
    }

    //加入黑名单
    public function setBlack()
    {
        $id = $_GET['id'];
        $data = array(
            'status' => 2,
            'create_time' => time()
        );

        $doupdate = M("app_user")->where("id = '{$id}'")->data($data)->save();
        if($doupdate){
            $this->success('修改成功',U("YongHu/BlackList"));
        }else{
            $this->error("修改失败",U("YongHu/BlackList"));
        }
    }

    //白名单用户
    public function WhiteList()
    {
        $mod=M('app_user');
        $count=$mod->where("status=0")->Count();
        $pag=new \Think\Page($count,12);
        $pag->setConfig('prev','上一页');
        $pag->setConfig('next','下一页');
        $pag->setConfig('first','首页');
        $pag->setConfig('last','尾页');
        $pag->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $fen=$pag->show();
        $list=$mod->where("status=0")->order('id')->limit($pag->firstRow,12)->select();
        foreach ($list as &$item) {
            $item['money'] = admin_fenToYuan($item['money']);
            $item['money_total'] = admin_fenToYuan($item['money_total']);
        }
        $this->assign('fen',$fen);
        $this->assign('list',$list);
        $this->display("yonghu/WhiteList");
    }


    //取消黑名单
    public function setWhite()
    {
         $id = $_GET['id'];
          $data = array(
            'status' => 0,
            'create_time' => time()
        );

        $adupdate = M("app_user")->where("id = '{$id}'")->data($data)->save();
        if($adupdate){
            $this->success('修改成功',U("YongHu/WhiteList"));
        }else{
            $this->error("修改失败",U("YongHu/WhiteList"));
        }
    }


    //删除前台用户列表
    public function dodelete(){
        if(M('app_user')->delete($_GET['id'])){
            $this->success('删除成功',U('YongHu/WhiteList'));
        }else{
            $this->error('删除失败',U('YongHu/WhiteList'));
        }
    }

    //用户全部信息
    public function userrecord(){
        //用户所有收入
        if($_GET['income']){
            //获取用户ID
            $userid = $_GET['income'];
            //获取用户信息
            $user = M('app_user')->where("id = '{$userid}'")->find();
            $user['money'] = admin_fenToYuan($user['money']);
            $user['money_freeze'] = admin_fenToYuan($user['money_freeze']);
            $user['money_total'] = admin_fenToYuan($user['money_total']);
            //获取用户所有收入
            $money = M('app_money_record');
            $count = $money->where("uid = '{$userid}'")->order("create_time desc")->count();
            $Page  = new \Think\Page($count,5);
            $Page->parameter['income']   =   $_GET['income'];
            $show  = $Page->show();
            $money = $money->where("uid = '{$userid}'")->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            if($money){
                foreach($money as &$item){
                    $item['money'] = admin_fenToYuan($item['money']);
                    $item['before_money'] = admin_fenToYuan($item['before_money']);
                    $item['after_money'] = admin_fenToYuan($item['after_money']);
                }
            }
            $this->assign('list',$money);
            $this->assign('page',$show);
            $this->assign("user",$user);
            $this->display("yonghu/userquery");
        }
        //用户所有阅读
        elseif($_GET['read']){
            //获取用户ID
            $userid = $_GET['read'];
            //获取用户信息
            $user = M('app_user')->where("id = '{$userid}'")->find();
            $user['money'] = admin_fenToYuan($user['money']);
            $user['money_freeze'] = admin_fenToYuan($user['money_freeze']);
            $user['money_total'] = admin_fenToYuan($user['money_total']);
            //获取用户所有阅读
            $article = M('app_article_log')->join("LEFT JOIN dz_admin_ruanwen on dz_app_article_log.article_id = dz_admin_ruanwen.id")->where("dz_app_article_log.uid = '{$userid}'")->field("dz_app_article_log.*,dz_admin_ruanwen.title")->order("dz_app_article_log.create_time desc")->count();
            $Page  = new \Think\Page($article,5);
            $Page->parameter['read']   =   $_GET['read'];
            $show  = $Page->show();
            $article = M('app_article_log')->join("LEFT JOIN dz_admin_ruanwen on dz_app_article_log.article_id = dz_admin_ruanwen.id")->where("dz_app_article_log.uid = '{$userid}'")->field("dz_app_article_log.*,dz_admin_ruanwen.title")->order("dz_app_article_log.create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            if ($article) {
                foreach ($article as &$item) {
                    $item['money'] = admin_fenToYuan($item['money']);
                }
            }
            $this->assign('article',$article);
            $this->assign("user",$user);
            $this->assign('page',$show);
            $this->display("yonghu/userquery");
        }
        //用户所有提现
        else{
            //获取用户ID
            $userid = $_GET['withdraw'];
            //获取用户信息
            $user = M('app_user')->where("id = '{$userid}'")->find();
            $user['money'] = admin_fenToYuan($user['money']);
            $user['money_freeze'] = admin_fenToYuan($user['money_freeze']);
            $user['money_total'] = admin_fenToYuan($user['money_total']);
            //获取用户所有提现
            $withdraw = M('app_money_withdraw')->where("uid = '{$userid}'")->order("create_time desc")->count();
            $Page  = new \Think\Page($withdraw,5);
            $Page->parameter['withdraw']   =   $_GET['withdraw'];
            $show  = $Page->show();
            $withdraw = M('app_money_withdraw')->where("uid = '{$userid}'")->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            if ($withdraw) {
                foreach ($withdraw as &$item) {
                    $item['money'] = admin_fenToYuan($item['money']);
                }
            }
            $this->assign("user",$user);
            $this->assign('withdraw',$withdraw);
            $this->assign('page',$show);
            $this->display("yonghu/userquery");
        }
    }
}