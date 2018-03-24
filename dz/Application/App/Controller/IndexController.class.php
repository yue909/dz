<?php
namespace App\Controller;

use Think\Controller;

class IndexController extends AuthController {

	//进入主页
    public function index()
    {
        $this->display("Index/index");
    }

    //绑定手机号码
    public function bind()
    {
        $userInfo = session('userInfo');
        if (empty($userInfo['phone'])) {
            //未绑定
            $this->display("Index/bind");
        } else {
            //已绑定手机号
            $this->assign('phone', substr($userInfo['phone'], 0, 3) . "****" . substr($userInfo['phone'], 7, 4));
            $this->display('Index/phone');
        }
    }

    //加入qq群
    public function joinGroup()
    {
        $link = C('qq_group');
        header("Location: " . $link);
    }

    //App下载
    public function appDownload()
    {
        $link = C('app_download');
        header("Location: " . $link);
    }

    //邀请绑定上级
    public function invite()
    {
        $id = I('get.id');
        //查询上级的id是否存在
        $user = M('app_user')->where("id = '{$id}'")->field('pid')->find();
        if (!$user) {
            //重定向到首页
            $this->redirect('Index/index');
        }
        //查询当前登录用户是否有上级
        $userInfo = session('userInfo');
        $user = M('app_user')->where("id = '{$userInfo['id']}'")->field('pid')->find();
        if ($user && $user['pid'] == 0 && $userInfo['id'] !== $id) {
            $data = array(
                'pid' => $id
            );
            //绑定上下级关系
            M('app_user')->where("id = '{$userInfo['id']}'")->data($data)->save();
            //统计上级数量
            $enlightening = array(
                'uid' => $id,
                'pid' => $userInfo['id'],
                'time' => time()
            );
            M("app_user_enlightening")->data($enlightening)->add();
        }
        //重定向到首页
        $this->redirect('Index/index');
    }

    //公众号关注邀请二维码
    public function getQrcode() {
        //js生成
        $id = I('get.id'); //获取id参数
        $info = json_decode(rc4_base64_decode($id), true); //解码参数
        if ($info && $info['url']) {
            $this->assign("url", $info['url']);
            $this->display("qrcode");
        } else {
            echo "<center><h1>二维码失效</h1></center>";
        }
    }

    //公众号关注邀请二维码
    public function getQrcodeNew() {
        //gd库生成
        $userInfo = session('userInfo');
        $user = M('app_user')->where("id = '{$userInfo['id']}'")->field('id')->find();
        if (empty($user)) {
            echo "<center><h1>二维码失效</h1></center>";
            exit();
        }
        $access_token = wx_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
        $data = '{"expire_seconds": 2592000, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "' . $user['id'] . '"}}}';
        $req = httpPost($url, $data);
        $req = json_decode($req, true);
        $qrcode_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($req['ticket']);
        header("content-type:image/jpeg");
        $bg_url = "/www/wwwroot/dbili.cn/Wechat/qrcode002.jpg";
        $im = imagecreatetruecolor(1080, 1920);
        $bg_string = file_get_contents($bg_url);
        $bg = imagecreatefromstring($bg_string);
        imagecopy($im, $bg, 0, 0, 0, 0, 1080, 1920);
        imagedestroy($bg);
        $qrcode_string = file_get_contents($qrcode_url);
        $qrcode_data = imagecreatefromstring($qrcode_string);
        $width = imagesx($qrcode_data);
        $height = imagesy($qrcode_data);
        ImageCopyResampled($im, $qrcode_data, 710, 1060, 0, 0, 280, 280, $width, $height);
        imagejpeg($im);
        imagedestroy($im);
    }
}