<?php
namespace App\Controller;

use Think\Controller;

class WechatController extends Controller
{

    private $appId;
    private $appSecret;

    public function __construct()
    {
        $this->appId = C('_APPID_' );
        $this->appSecret = C('_APPSECRET_');
    }

    public function index()
    {
        header('Content-type:text');
        if (isset($_GET['echostr'])) {
            $this->valid();
        }else{
            $this->responseMsg();
        }
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = C('WECHAT_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $msgType = trim($postObj->MsgType);
            switch ($msgType) {
                case 'text':
                    $resultStr = $this->receiveText($postObj);
                    break;

                case 'event':
                    $resultStr = $this->receiveEvent($postObj);

            }
            echo $resultStr;
            exit;
        }else{
            echo "";
            exit;
        }
    }

    private function receiveEvent($object)
    {
        $event = $object->Event;
        $FromUserName = $object->FromUserName;
        $FromUserName = strval($FromUserName[0]);
        $subscribe = new \App\Model\AppSubscribeModel();
        if ($event == 'unsubscribe') {
            $req = $subscribe->getInfo($FromUserName, 'id,money,invite_id');
            if (!empty($req)) {
                if ($req['money'] > 0 && $req['invite_id'] !== NULL) {
                    //用户取消关注,邀请者取消奖励金币
                    $user = new \App\Model\AppUserModel();
                    $user->moneyDec(array("id" => $req['invite_id']), $req['money']);
                }
                //记录取消关注
                $subscribe->upInfo($FromUserName, array("state" => 0, "invite_id" => NULL, "money" => 0, "update_time" => time()));
            }
        }
        //关注扫码事件
        if ($event == 'SCAN') {
            $key = str_replace("qrscene_", "", $object->EventKey);
            if (strstr($key, "guanggao")) {
                $id = str_replace("guanggao_", "", $key);
                $bind = $this->guanggaoBind($object, $id, $FromUserName);
                return $bind;
            }
            if (strstr($key, "unbind")) {
                $id = str_replace("unbind_", "", $key);
                $unBind = $this->guanggaoUnBind($object, $id, $FromUserName);
                return $unBind;
            }
        }
        //未关注扫码事件
        if ($event == 'subscribe') {
            $key = str_replace("qrscene_", "", $object->EventKey);
            if (strstr($key, "guanggao")) {
                $id = str_replace("guanggao_", "", $key);
                $bind = $this->guanggaoBind($object, $id, $FromUserName);
                return $bind;
            }
            if (strstr($key, "unbind")) {
                $id = str_replace("unbind_", "", $key);
                $unBind = $this->guanggaoUnBind($object, $id, $FromUserName);
                return $unBind;
            } else {
                //关注，查询是否已关注过
                $req = $subscribe->getInfo($FromUserName, "id");
                if ($req) {
                    //已关注过，更新关注状态、时间
                    $subscribe->upInfo($FromUserName, array("state" => 1, "update_time" => time()));
                } else {
                    //新用户关注，给予邀请者随机奖励金币
                    $id = str_replace("qrscene_", "", $object->EventKey);
                    $coin = rand(50, 100);
                    if (!empty($id)) {
                        //分配奖励金币
                        $user = new \App\Model\AppUserModel();
                        $user->moneyInc(array("id" => $id), $coin, "邀请关注公众号", true);
                    }
                    //记录已关注
                    $subscribe->addInfo(array("openid" => $FromUserName, "invite_id" => $id, "money" => $coin, "update_time" => time()));
                }
                //关注自动回复消息
                $resultStr = $this->transmitText($object, "欢迎关注我们！不定期发放各种福利！\n\n想赚钱的粉丝注意了！\n\n每天参与爱阅赚的大侠们月入5000零花钱不是梦，还能收徒哟！！！\n\n偷偷告诉你币可以换成零花钱的哦！！！！！\n\n如有不明白的请回复数字 \"1\"\n\n想尝试的直接点击左下角【爱阅赚】！\n\n回馈老客户最新活动：立即点击（<a href=\"" . _URL_ . U('app/index/getQrcodeNew') . "\">我生成你的专属二维码</a>），每邀请一个好友将获得100-50币哟！！！！");
                return $resultStr;
            }
        }
    }


    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        if ($keyword == "二维码") {
            $qrcode_url = $this->initQrcode($object);
            $dateArray = array();
            if ($qrcode_url == "") {
                $dateArray[] = array(
                    "Title" => "点击进入免费注册 >>",
                    "Description" => "未注册，无法获取二维码",
                    "Picurl" => "http://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/gzh_register.png",
                    "Url" => _URL_
                );
            } else {
                $dateArray[] = array(
                    "Title" => "点击进入保存我的二维码 >>",
                    "Description" => "二维码已获取，马上查看",
                    "Picurl" => "http://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/gzh_erweima.png",
                    "Url" => $qrcode_url
                );
            }
            $resultStr = $this->transmitNews($object, $dateArray, 0);
            return $resultStr;
        } else {
            //$resultStr = $this->transmitText($object, C('transmitText'));

            $resultStr = $this->transmitText($object, "有疑问请加QQ群：<a href=\"https://jq.qq.com/?_wv=1027&k=5G4Hg3E\">点击加入</a> 联系客服");
            return $resultStr;
        }
    }

    private function initQrcode($object)
    {
        $openid = $object->FromUserName;
        $openid = strval($openid[0]);
        //查询是否存在链接
        $user = new \App\Model\AppUserModel();
        $userInfo = $user->wechatGetInfo($openid, "id,invite_url");
        if (empty($userInfo)) {
            return "";
        }
        if (empty($userInfo['invite_url'])) {
            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . wx_access_token();
            $qrcode_url = $this->getQrcode($userInfo['id']);
            $user->wechatUpInfo($openid, array("invite_url" => $qrcode_url));
        } else {
            $qrcode_url = $userInfo['invite_url'];
        }
        //链接与openid生成二维码id
        $qrcode_id = rc4_base64_encode(json_encode(array('openid' => $openid, 'url' => $qrcode_url)));
        $qrcode_url = _URL_ . U('App/Index/getQrcode', array('id' => $qrcode_id));
        return $qrcode_url;
    }

    private function getQrcode($id)
    {
        //生成带参数二维码
        $access_token = wx_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
        $data = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "' . $id . '"}}}';
        $req = httpPost($url, $data);
        $req = json_decode($req, true);
        if ($req['url']) {
            return $req['url'];
        } else {
            return 'error';
        }
    }

    private function guanggaoUnBind($object, $id, $openid)
    {
        $user = M('guanggao_user')->where("id = {$id}")->field('openid')->find();
        if ($user && !empty($user['openid'])) {
            if ($user['openid'] == $openid) {
                M('guanggao_user')->where("id = {$id}")->save(array(
                    'openid' => NULL,
                    'avatar' => NULL,
                    'nickname' => NULL
                ));
                return $this->transmitText($object, "账号解除绑定成功");
            }
        }
        return $this->transmitText($object, "请使用原来绑定的微信号扫码");
    }

    private function guanggaoBind($object, $id, $openid)
    {
        $user = M('guanggao_user')->where("id = {$id}")->field('openid')->find();
        if ($user && empty($user['openid'])) {
            $user_info = M('guanggao_user')->where("openid = '{$openid}'")->field('id')->find();
            if ($user_info) {
                return $this->transmitText($object, "当前微信已绑定过其他账号");
            }
            //绑定openid + 获取头像
            $token = wx_access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid=' . $openid . '&lang=zh_CN';
            $req = httpGet($url);
            $req = json_decode($req, true);
            if ($req) {
                $nickname = $req['nickname'];
                if (empty($nickname)) {
                    $nickname = '已绑定';
                }
                $info = array(
                    'openid' => $openid,
                    'avatar' => $req['headimgurl'],
                    'nickname' => $nickname
                );
                M('guanggao_user')->where("id = '{$id}'")->save($info);
                return $this->transmitText($object, "绑定成功");
            }
            return $this->transmitText($object, "绑定失败");
        } else {
            if ($openid == $user['openid']) {
                return $this->transmitText($object, "账号已绑定");
            }
            return $this->transmitText($object, "当前账号已绑定过其他微信");
        }
    }

    private function transmitNews($object, $arr_item, $flag = 0)
    {
        if(!is_array($arr_item))
        return;
        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
    ";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['Picurl'], $item['Url']);
        $newsTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <Content><![CDATA[]]></Content>
                        <ArticleCount>%s</ArticleCount>
                        <Articles>
                        $item_str</Articles>
                        <FuncFlag>%s</FuncFlag>
                    </xml>";

        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $flag);
        return $resultStr;
    }

    private function transmitText($object, $text)
    {
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $text);
        return $resultStr;
    }

}