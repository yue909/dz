<?php
namespace GuangGao\Controller;
use Think\Controller;
class ApiController extends Controller {
    //检测用户是否绑定微信
    public function detectionwx()
    {
        $users = session("GuangGao");
        $user = M("guanggao_user")->where("id = '{$users['id']}'")->find();
        if(empty($user['openid'])){
            $data = array(
                'code' => 0,
                'msg' => "未绑定"
            );
        }else{
            session('GuangGao', $user);
            $data = array(
                'code' => 1,
                'avatar' => $user['avatar'],
                'msg' => "已绑定"
            );
        }
        $this->ajaxReturn($data);
    }
    //注册
    public function register()
    {
            $phonedata = session("smsxInfo");
            if($phonedata['phone'] != I('post.phone')){
                $data = array(
                    "code" => 0,
                    "msg"  => "手机号码错误"
                );
                $this->ajaxReturn($data);
            }

            //判断手机号或者账号是否已经存在
            $isaccount = M('guanggao_user')->where(array("account" => I("post.account")))->find();
            $isphone = M("guanggao_user")->where(array("phone" => I("post.phone")))->find();
            // var_dump($isaccount);
            // var_dump($isphone);
            // exit;
            if($isphone || $isaccount){
                $data = array(
                    'code' => 0,
                    'msg'  => '手机或账号已存在'
                );
                $this->ajaxReturn($data);
            }
            //获取用户注册信息
            $data = array(
                'account' => I("post.account"),//账号
                'password' => md5(I("post.password")),//密码
                'email' => I("post.email"),//邮箱
                'QQ' => I("post.QQ"),//QQ
                'phone' => I('post.phone'),//手机
                'name' => I("post.name"),//名字
                'create_time' => time(),
                'status' => 0,
                'Companyname' => I('post.Companyname')//企业名
            );
            //插入数据表
            $userdd = M('guanggao_user')->data($data)->add();
            if($userdd){
                $data = array(
                    'code' => 1,
                    'msg'  => '注册成功'
                );
                $this->ajaxReturn($data);
            }
    }

    //手机验证码请求
    public function coderequest()
    {
        $phone  = I('post.phone');
        if(session('sendSMSX') < time() || empty(session('sendSMSX'))){
            $code = rand(100000, 999999);
            $req = sendSMS($phone,$code);
            if($req['code'] !== 0){
               //验证码发送失败
                $data = array(
                            'code' => $req['code'],
                            'msg' => $req['msg'],

                        );
                $this->ajaxReturn($data);
            }
            //存储过期时间
            session('sendSMSX',time() + 60);
            //五分钟有效 一分钟才能再请求一次
            $data = array(
                    'phone' => $phone,
                    'code' => $code,
                    'time' => time() + 60 * 5
                );
            session('smsxInfo',$data);
            //返回Ajax
            $data = array(
                    'code' => 0,
                    'msg' => '验证码已发送到' . $phone . ",请注意查收!(五分钟有效)",
                    'data' => array()
                );
        }else{
            $data = array(
                    'code' => -1,
                    'msg' => intval(session('sendSMSX')) - time() . "秒后再提交！",
                    'data' => array()
                );
        }
        $this->ajaxReturn($data);
    }

    //判断是否有手机接口
    public function phone()
    {
        $user = session("GuangGao");
        if($user['phone'] == null || $user['phone'] == 0){
            $data = array(
                "type" => "手机号码不存在,请验证",
                'code' => 0
            );
            $this->ajaxReturn($data);
        }
    }

    //执行登录
    public function dologin()
    {

        // 验证码判断
        $verify = new \Think\Verify();
        $verifys = $verify->check($_POST['fcode'],'');
        if(!$verifys){
            $data = array(
                'code' => 0,
                'msg' => "验证码错误"
            );
            $this->ajaxReturn($data);
        }
        $user= array(
            'account' => I('post.account'),
            'password' => md5(I('post.password'))

        );
        $users = M("guanggao_user")->where($user)->find();
        if($users){
            session('GuangGao',$users);
            $data = array(
                'code' => 1,
                'msg'  => '登录成功'
            );
        }else{
            $data = array(
                'code' => 0,
                'msg'  => '登录失败,账号密码错误'
            );
        }
        $this->ajaxReturn($data);

    }

    //用户余额 广告信息
    public function balance()
    {
        $user = session("GuangGao");
        $users = M('guanggao_user')->where("id = '{$user['id']}'")->field('balances','consumption','phone')->find();
        $GuangGaolist = M('guanggao_title')->where("uid = '{$user['id']}'")->select();
        $data = array(
            'code' => 0,
            'msg' => '获取成功',
            'data' => $users,
            'list' => $GuangGaolist
        );
        $this->ajaxReturn($data);
    }

    //生成新的绑定二维码
    public function qrcode()
    {
        $data = array(
            'code' => -1,
            'msg' => '二维码加载失败，请稍后重试！'
        );
        $user = session("GuangGao");
        $user = M("guanggao_user")->where("id = '{$user['id']}'")->find();
        if (!empty($user['openid'])) {
            $data = array(
                'code' => -1,
                'msg' => '账号已绑定微信！'
            );
            $this->ajaxReturn($data);
        }
        $access_token = wx_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
        $info = '{"expire_seconds": 2592000, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "guanggao_' . $user['id'] . '"}}}';
        $req = httpPost($url, $info);
        $req = json_decode($req, true);
        if ($req && $req['ticket']) {
            $data = array(
                'code' => 0,
                'data' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($req['ticket']),
                'msg' => '获取二维码成功'
            );
        }
        $this->ajaxReturn($data);
    }

    //生成解除绑定二维码，带验证
    public function unbindQrcode()
    {
        $data = array(
            'code' => -1,
            'msg' => '二维码加载失败，请稍后重试！'
        );
        $user = session("GuangGao");
        $user = M("guanggao_user")->where("id = '{$user['id']}'")->find();
        if (empty($user['openid'])) {
            $data = array(
                'code' => -1,
                'msg' => '当前账号未绑定微信！'
            );
            $this->ajaxReturn($data);
        }
        $access_token = wx_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
        $info = '{"expire_seconds": 2592000, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "unbind_' . $user['id'] . '"}}}';
        $req = httpPost($url, $info);
        $req = json_decode($req, true);
        if ($req && $req['ticket']) {
            $data = array(
                'code' => 0,
                'data' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($req['ticket']),
                'msg' => '获取二维码成功'
            );
        }
        $this->ajaxReturn($data);
    }

}




