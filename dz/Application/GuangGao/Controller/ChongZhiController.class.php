<?php
namespace GuangGao\Controller;
use Think\Controller;
class ChongZhiController extends Controller {
    //二维码
    public function erweima()
    {
        $users = session("GuangGao");
        //充值金额
        $money = intval($_GET['money'] * 100);
        //生成订单号
        $out_trade_no = time().mt_rand(100,999);
        vendor("phpqrcode.phpqrcode");
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 4;
        // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
        //$path = "images/";

        // 生成的文件名
        $urls = _URL_.U(CONTROLLER_NAME."/"."payment")."?money=".$money."&"."out_trade_no=".$out_trade_no."&"."uid=".$users['id'];

        //$fileName = $path.$size.'.png';
        $object = new \QRcode();
        //支付路径
        $object->png($urls, false, $level, $size);
    }
    //支付接口
    public function payment(){
        //判断是否是微信内访问
        if(strpos(I('server.HTTP_USER_AGENT'), "MicroMessenger") !== false){
            //微信访问
            $this->display("Index/exit");
            exit();
        }
        //判断是支付还是支付后
        $records = M('guanggao_record')->where("uid = '{$_GET['uid']}' AND trade = '{$_GET['out_trade_no']}'")->find();
        if($records){
            $this->display("Index/records");
            exit;
        }
        //添加到数据库
        $addrecord = M('guanggao_record')->data(array("uid" => $_GET['uid'],'create_time'=>time(),"trade" => $_GET['out_trade_no'],"recharge" =>$_GET['money'],"status"=>0))->add();
        if(!$addrecord){
            exit;
        }
        //appid
        $pay['appid'] = C('_APPID_');
        //mchid
        $pay['mch_id'] = C('_MCHID_');
        //随机字符串
        $pay['nonce_str'] = 'WZ'.mt_rand(100,999);
        //商品描述
        $pay['body'] = '广告账号余额';
        //商户订单号
        $pay['out_trade_no'] = $_GET['out_trade_no'];
        //金额
        $pay['total_fee'] = $_GET['money'];
        //附加用户ID回调
        $pay['attach']  = $_GET['uid'];
        //IP
        $pay['spbill_create_ip'] = get_client_ip();
        //异步
        $pay['notify_url'] = _URL_.U(CONTROLLER_NAME."/"."notify");
        //类型
        $pay['trade_type'] = "MWEB";
        //场景信息
        $scene_info = array(
            'h5_info'=>array(
                "type" => "Wap",
                "wap_url" => _URL_,
                "wap_name" => "爱阅赚"
            )
        );
        $pay['scene_info'] =  str_replace("\\/", "/",  json_encode($scene_info,JSON_UNESCAPED_UNICODE));
        // var_dump($pay);
        $pay['sign'] = $this->MakeSign($pay);
       //转XML
        $val = $this->arrayToXml($pay);
        //下单地址
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $result = $this->postXmlCurl($val,$url);
        $result = $this->xmlToArray2($result);
        //获取支付地址
        $pay = $result['mweb_url'];
        //调起支付
        echo "<script>window.location.href = '".$pay."';</script>";
    }
    //支付结果回调
    public function notify()
    {
        $result = file_get_contents("php://input");
        //XML转array
        $result = $this->xmlToArray2($result);

        if($result['return_code'] == "SUCCESS"){
            $data = array(
                "wxtrade" => $result['transaction_id'],
                "record_time" => time(),
                "status" => 1
            );
            $save = M('guanggao_record')->where("uid = '{$result['attach']}' AND trade = '{$result['out_trade_no']}'")->save($data);
            $resultmoney = intval($result['total_fee']);
            $useradd = M("guanggao_user")->where("id = '{$result['attach']}'")->setInc("money",$resultmoney);
            $balances = M("guanggao_user")->where("id = '{$result['attach']}'")->setInc("balances",$resultmoney);

            if($save && $useradd && $balances){
                $attr = array(
                    "return_code" => 'SUCCESS',
                    "return_msg" => "OK"
                );
                //转XML
                $attr = $this->arrayToXml($attr);
                echo $attr;
            }
        }else{
            echo $result['return_msg'];
        }
    }
    /**
    * 格式化参数格式化成url参数
    */
    public function ToUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
     /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($values)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->ToUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".C("_MCHID_KEY_");
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    // xml转array
    public  function xmlToArray2($xml) {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    // *  array转xmls
    // */
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
        if (is_numeric($val)) {
        $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else
        $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    //post请求xml
    function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);


        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            echo "curl出错，错误码".$error;
        }
    }
}