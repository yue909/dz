<?php
/*
    方倍工作室 http://www.cnblogs.com/txw1958/
    CopyRight 2013 www.fangbei.org  All Rights Reserved
*/
header('Content-type:text');
define("TOKEN", "zhuan_miniapp");
$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
    private $appId;
    private $appSecret;
    private $filePath;

    public function __construct()
    {
        $this->appId = C('_MINIAPP_APPID_');
        $this->appSecret = C('_MINIAPP_SECRET_');
        $this->filePath = __DIR__ . '/';
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

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
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
            $msgType = $postObj->MsgType;
            $sessionFrom = $postObj->SessionFrom;
            if ($msgType == "event") {
                //进入带来源的会话事件
                if (!empty($sessionFrom)) {
                    $access_token = $this->getAccessToken();
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/typing?access_token=' . $access_token;
                    $this->httpPost($url, '{"touser":"' . $fromUsername . '","command":"Typing"}');
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
                    $data = array(
                        'touser' => "$fromUsername",
                        'msgtype' => 'link',
                        'link' => array(
                            'title' => '有疑问请加群找客服',
                            'description' => "QQ群号：361343082 \n点击快速加入QQ群>>",
                            'url' => C('qq_group'),
                            'thumb_url' => 'https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515727841132821.png'
                        )
                    );
                    if ($sessionFrom == 'subscription') {
                        $data = array(
                            'touser' => "$fromUsername",
                            'msgtype' => 'image',
                            'image' => array(
                                'media_id' => '5qjhT0HXnuLM_dChV4ZywOKnxO6nwuwPr0eMY17WDDzm6a2RmInmfLMI-qPT72Vv'
                            )
                        );
                    }
                    $this->httpPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                $access_token = $this->getAccessToken();
                $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/typing?access_token=' . $access_token;
                $this->httpPost($url, '{"touser":"' . $fromUsername . '","command":"Typing"}');
                $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
                $data = array(
                    'touser' => "$fromUsername",
                    'msgtype' => 'link',
                    'link' => array(
                        'title' => '有疑问请加群找客服',
                        'description' => "QQ群号：361343082 \n点击快速加入QQ群>>",
                        'url' => C('qq_group'),
                        'thumb_url' => 'https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515727841132821.png'
                    )
                );
                $this->httpPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
            echo "";
            exit;
        }else{
            echo "";
            exit;
        }
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

    private function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode($this->get_php_file("access_token.php"));
        if ($data->expire_time < time()) {
          // 如果是企业号用以下URL获取access_token
          // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
          $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
          $res = json_decode($this->httpGet($url));
          $access_token = $res->access_token;
          if ($access_token) {
            $data->expire_time = time() + 7000;
            $data->access_token = $access_token;
            $this->set_php_file("access_token.php", json_encode($data));
          }
        } else {
          $access_token = $data->access_token;
        }
        return $access_token;
    }

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    private function httpPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($data){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    private function get_php_file($filename)
    {
        return trim(substr(file_get_contents($this->filePath . $filename), 15));
    }

    private function set_php_file($filename, $content)
    {
        $fp = fopen($this->filePath . $filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }

}
?>