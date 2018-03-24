<?php
namespace Home\Controller;
use Think\Controller;
class YongHuController extends QXController {
    //用户列表
    public function liebiao()
    {
        $mod=M('home_users');
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
    
    //执行插入
    public function insert(){
        $mod=M('home_users');
        $data['username']=$_POST['username'];
        if($mod->where("username='".$data['username']."'")->select()){
            $this->error('用户名已存在，请更换',U('YongHu/tianjia'));
            exit;
        }
        $data['password']=$_POST['pwd'];

        $data['phone']=$_POST['phone'];
        if(!preg_match( "/^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$/",$data['phone'])){
            $this->error('手机格式不正确',U('YongHu/tianjia'));
            exit;
        }

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
            if(res){
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
        if(M('home_users')->delete($_GET['id'])){
            $this->success('删除成功',U('YongHu/liebiao'));
        }else{
            $this->error('删除失败',U('YongHu/liebiao'));
        }       
    }


    //加载用户信息修改模板
    public function edit(){
        $mod=M('home_users');
        $res=$mod->where("id={$_GET['id']}")->find();
        // var_dump($res);
        // exit;
        $this->assign('res',$res);
        $this->display('yonghu/update');
    }

    //用户信息修改
    public function update(){
        $mod=M('home_users');
        if($_POST['pwd']==$_POST['repwd']){
            if(strlen($_POST['pwd'])==32){
                $mod->create();
                if($mod->save()){
                    $this->success('修改成功',U('YongHu/liebiao'));
                }else{
                    $this->error('修改失败',U('YongHu/liebiao'));
                }
            }else{
                $data['password']=$_POST['pwd'];
                $data['username']=$_POST['username'];
                $data['status']=$_POST['status'];
                $data['admin']=$_POST['admin'];
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

    public function search()
     {
        $where=array();
            //获取搜索条件
            if(!empty($_POST['aci'])){
                $userForm=M('home_users'); //实例化数据表
                $where['id | addtime']=array('like',"%{$_POST['aci']}%");//写查询条件
                $results = $userForm->where($where)->select();
                $this->assign("list",$results);
                $this->display("YongHu/liebiao");

        }
    }



}