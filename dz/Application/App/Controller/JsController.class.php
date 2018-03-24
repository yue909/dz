<?php
namespace App\Controller;

use Think\Controller;

class JsController extends Controller
{
    //jssdk
    public function api()
    {
        $url = $_GET['url'];
        $req = array(
            'code' => 0,
            'msg'=> 'success',
            'data' => array()
        );
        if (empty($url)) {
            $req = array(
                'code' => -1,
                'msg'=> 'url 不能为空',
                'data' => array()
            );
        } else {
            $config = wx_share_init($url);
            unset($config['rawString']);
            unset($config['url']);
            $config['debug'] = !1;
            $config['jsApiList'] = ['onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ','onMenuShareWeibo','onMenuShareQZone'];
            $req['data'] = $config;
        }
        $this->ajaxReturn($req);
    }
}