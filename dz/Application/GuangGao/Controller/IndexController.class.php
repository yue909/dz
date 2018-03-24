<?php
namespace GuangGao\Controller;
use Think\Controller;
class IndexController extends IsloginController {
	//进入主页
    public function index()
    {
        $user = session("GuangGao");
        $user = M("guanggao_user")->where("id = '{$user['id']}'")->find();
        $userG = M('guanggao_title')
                ->join("LEFT JOIN dz_app_advertising_info on dz_guanggao_title.gid = dz_app_advertising_info.gid")
                ->field("dz_guanggao_title.*,dz_app_advertising_info.show_num,dz_app_advertising_info.click_num")
                ->where("dz_guanggao_title.uid = '{$user['id']}'")
                ->select();
        $user['balances'] = admin_fenToYuan($user['balances']);
        $user['consumption'] = admin_fenToYuan($user['consumption']);
        foreach ($userG as $i => $item) {
            $userG[$i]['price'] = admin_fenToYuan($userG[$i]['price']);
            $userG[$i]['bprice'] = admin_fenToYuan($userG[$i]['bprice']);
        }

        $toAudit = M('guanggao_title')->where("uid = '{$user['id']}' AND status = 0")->count(); //待审核数量
        $total = M('guanggao_title')->where("uid = '{$user['id']}'")->count(); //全部广告
        $this->assign("users",$userG);
        $this->assign("userInfo",$user);
        $this->assign("toAudit",$toAudit);
        $this->assign("total",$total);
     	$this->display('Index/index');
    }
    //验证短信
    public function  verificationphone()
    {
    	//用户信息
    	$user = session('GuangGao');
    	$phone = I('post.phone');
    	$code = I('post.code');
    	$data = session("smsxInfo");
    	if($phone == $data['phone'] && $code == $data('code') && $data['time'] > time()){
    		$phoneadd = M('guanggao_user')->where("account = '{$user['account']}'")->data(array('phone' => $data['phone']))->save();
    		if($phoneadd){
    			$this->success("绑定成功,跳转首页",U('Index/index'));
    		}else{
    			$this->error("手机绑定失败");
    		}
    	}else{
    		$this->error('验证码错误或过期');
    	}
    }
    //推广管理
    public function indent()
    {
        $user = session("GuangGao");
        $userG = M('guanggao_title')->where("uid = '{$user['id']}'")->select();
        $this->assign("list",$user);
        $this->display("Index/orderManagement");
    }
    //充值
    public function InvoiceManagement()
    {
        $user = session("GuangGao");
        $user = M("guanggao_user")->where("id = '{$user['id']}'")->find();
        $list = M('guanggao_record')->where("uid = '{$user['id']}'")->order("create_time desc")->select();
        foreach ($list as $i => $item) {
            $list[$i]['create_time'] = date("Y-m-d H:i:s", $list[$i]['create_time']);
            $list[$i]['recharge'] = admin_fenToYuan($list[$i]['recharge']);
        }
        $user['balances'] = admin_fenToYuan($user['balances']);
        $user['consumption'] = admin_fenToYuan($user['consumption']);
        $toAudit = M('guanggao_title')->where("uid = '{$user['id']}' AND status = 0")->count(); //待审核数量
        $total = M('guanggao_title')->where("uid = '{$user['id']}'")->count(); //全部广告
        $this->assign("userInfo",$user);
        $this->assign("list",$list);
        $this->assign("toAudit",$toAudit);
        $this->assign("total",$total);
        $this->display("Index/InvoiceManagement");
    }


    //软文
    public function advertorial()
    {
        $user = session("GuangGao");


        //发送软文
        if($_POST){
            //查看用户余额
            $userbalance = M("guanggao_user")->where("id = '{$user['id']}'")->field('balances')->find();
            if($userbalance['balances'] < admin_yuanToFen(I('post.price'))){
                $this->error("余额不足,请充值");
            }
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =     './Public/Uploads/'; // 设置附件上传根目录
            $upload->savePath  =     ''; // 设置附件上传（子）目录
            // 上传文件
            $info   =   $upload->upload();
            if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
            }else{
                //上传成功
                //上传至OSS
                //加载OSS
                vendor('aliyun.autoload');
                $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
                $tg =array();
                $sq = array();
                foreach ($info as $key => $value) {
                    if($value['key'] == "tuiguang"){
                        $object = 'article/' . date('Y-m-d') . '/' . $value['savename'];//oss保存的文件名称
                        $file = $upload->rootPath . $value['savepath'] . $value['savename'];//文件路径，必须是本地的。
                        try{
                            //上传文件到oss
                            $ossClient->uploadFile(_OSS_BUCKET_, $object, $file);
                            //删除本地文件
                            unlink($file);
                            //拼接OSS地址
                            $thumb = '//' . _OSS_BUCKET_ . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                            $tg[] = $thumb;
                        }catch(OssException $e){
                            //oss上传失败
                            $this->error($e->getMessage());
                        }
                    }else{
                        $object = 'article/' . date('Y-m-d') . '/' . $value['savename'];//oss保存的文件名称
                        $file = $upload->rootPath . $value['savepath'] . $value['savename'];//文件路径，必须是本地的。
                        try{
                            //上传文件到oss
                            $ossClient->uploadFile(_OSS_BUCKET_, $object, $file);
                            //删除本地文件
                            unlink($file);
                            //拼接OSS地址
                            $thumb = '//' . _OSS_BUCKET_ . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                            $sq[] = $thumb;
                        }catch(OssException $e){
                            //oss上传失败
                            $this->error($e->getMessage());
                        }
                    }
                }
                $tgs = array();
                foreach ($tg as $key => $value) {
                    $tgs['tg'] .= $value."wzpt";
                }

                $sqs = array();
                foreach ($sq as $key => $value) {
                    $sqs['sq'] .= $value."wzpt";
                }
                $user = session("GuangGao");
                $data = array(
                    'title' => I("post.title"),
                    'url' => I("post.url"),
                    'create_time' => time(),
                    'uid' => $user['id'],
                    'tgimg' => $tgs['tg'],
                    'sqimg' => $sqs['sq'],
                    'price' => admin_yuanToFen(I('post.price')),
                    'bprice' => admin_yuanToFen(I("post.bprice")),
                    'pattern' => I('post.tgms'),
                    'describe' => I('post.miaoshu'),
                    'status' => 0
                );
                $adds = M("guanggao_title")->data($data)->add();
                $userdec = M("guanggao_user")->where("id = '{$user['id']}'")->setDec("balances",admin_yuanToFen(I('post.price')));
                if($adds && $userdec){
                    $this->success("添加成功",U("Index/index"));
                }else{
                    $this->error('添加失败');
                }
            }
        }else{
            $this->display("Index/patch");
        }
    }
    //退出登录
    public function loginout()
    {
        session_unset();
        session_destroy();
        $this->success('退出成功',U('Login/login'));
    }

}