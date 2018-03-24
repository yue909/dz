<?php


// *  array转xmls
// */
function arrayToXml($arr){
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
// xml转array
function xmlToArray2($xml) {
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}
//get请求 返回json格式
function getHttp($url) {
    $ch=curl_init();
    //设置传输地址
    curl_setopt($ch, CURLOPT_URL, $url);
    //设置以文件流形式输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //接收返回数据
    $data=curl_exec($ch);
    curl_close($ch);
    $jsonInfo=json_decode($data,true);
    return $jsonInfo;
}

//post请求 返回json格式
function postHttp($url,$json) {
    $ch=curl_init();
    //设置传输地址
    curl_setopt($ch, CURLOPT_URL, $url);
    //设置以文件流形式输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //设置已post方式请求
 	curl_setopt($ch, CURLOPT_POST, 1);
 	//设置post文件
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $data=curl_exec($ch);
    curl_close($ch);
    $jsonInfo=json_decode($data,true);
    return $jsonInfo;

}

//get
function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
}

//post
function httpPost($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    if ($data){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
}

//短信接口
function sendSMS($phone, $code) {
    $ch = curl_init();
    $req  = array(
        'code' => 0,
        'msg' => 'success'
    );
    // 必要参数
    $apikey = C('_SMS_KEY_');
    $mobile = $phone;
    $text = str_replace("#code#", $code, C('_SMS_TPL_'));
    // 发送短信
    $data = array('text' => $text, 'apikey' => $apikey, 'mobile' => $mobile);
    curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $json_data = curl_exec($ch);
    //如果curl发生错误，返回错误
    if(curl_error($ch) != ""){
        $req  = array(
            'code' => -1,
            'msg' => 'Curl error: ' . curl_error($ch)
        );
        return $req;
    }
    //解析返回结果（json格式字符串）
    $array = json_decode($json_data, true);
    if ($array['code'] !== 0) {
        $req = array(
            'code' => $array['code'],
            'msg' => $array['msg']
        );
        return $req;
    } else {
        return $req;
    }
}

//luosimao验证
function captchaVerified($response) {
    $url  = 'https://captcha.luosimao.com/api/site_verify';
    $data = array(
        'api_key'  => C('_LUOSIMAO_KEY_'),
        'response' => $response
    );
    $req  = array(
        'code' => 0,
        'msg' => 'success'
    );
    $ch   = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $json_data = curl_exec($ch);
    //如果curl发生错误，返回错误
    if(curl_error($ch) != ""){
        $req  = array(
            'code' => -1,
            'msg' => 'Curl error：' . curl_error($ch)
        );
        return $req;
    }
    //解析返回结果（json格式字符串）
    $array = json_decode($json_data, true);
    if ($array['res'] == 'success') {
        return $req;
    } else {
        $req  = array(
            'code' => $array['error'],
            'msg' => $array['msg']
        );
        return $req;
    }
}

//jssdk
function wx_share_init($url) {
    $wxconfig = array();
    vendor('wxSDK.class#jssdk');
    $appid = C('_APPID_'); //appid
    $appsecret = C('_APPSECRET_'); //appsecret
    $jssdk = new JSSDK($appid, $appsecret, $url);
    $wxconfig = $jssdk->GetSignPackage();
    return $wxconfig;
}

//wx access_token
function wx_access_token() {
    $wxconfig = array();
    vendor('wxSDK.class#jssdk');
    $appid = C('_APPID_'); //appid
    $appsecret = C('_APPSECRET_'); //appsecret
    $jssdk = new JSSDK($appid, $appsecret, '');
    $token = $jssdk->getAccessToken();
    return $token;
}

//小程序解密
function wx_miniapp_decode($appid, $sessionKey, $encryptedData, $iv) {
    vendor('wxSDK.class#wxBizDataCrypt');
    $data = array();
    $pc = new WXBizDataCrypt($appid, $sessionKey);
    $code = $pc->decryptData($encryptedData, $iv, $data);
    $req = array(
        'code' => $code,
        'data' => $data
    );
    return $req;
}

//rc4加解密
function rc4_code($pwd, $data) {
    $key[] ="";
    $box[] ="";

    $pwd_length = strlen($pwd);
    $data_length = strlen($data);

    for ($i = 0; $i < 256; $i++)
    {
        $key[$i] = ord($pwd[$i % $pwd_length]);
        $box[$i] = $i;
    }

    for ($j = $i = 0; $i < 256; $i++)
    {
        $j = ($j + $box[$i] + $key[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $data_length; $i++)
    {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;

        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;

        $k = $box[(($box[$a] + $box[$j]) % 256)];
        $cipher .= chr(ord($data[$i]) ^ $k);
    }

    return $cipher;
}

//安全URL编码
function base64_encode_new($data) {
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode(serialize($data)));
}

//安全URL解码
function base64_decode_new($string) {
    $data = str_replace(array('-', '_'), array('+', '/'), $string);
    $mod4 = strlen($data) % 4;
    ($mod4) && $data .= substr('====', $mod4);
    return unserialize(base64_decode($data));
}

//rc4对称加密&base64编码
function rc4_base64_encode($str) {
    return base64_encode_new(rc4_code(C('CODE_KEY'), $str));
}

//rc4对称解密&base64解码
function rc4_base64_decode($str) {
    return rc4_code(C('CODE_KEY'), base64_decode_new($str));
}

/*//log记录
function __log($str) {
    $data = file_get_contents(__DIR__ . '/log.txt');
    file_put_contents(__DIR__ . '/log.txt', "====" . date('Y-m-d H:i:s', time()) . "====\n" .  $str . "\n\n" . $data);
}*/

//

//随机名称
function randName() {
    $nicheng_tou = array('快乐的','冷静的','醉熏的','潇洒的','糊涂的','积极的','冷酷的','深情的','粗暴的','温柔的','可爱的','愉快的','义气的','认真的','威武的','帅气的','传统的','潇洒的','漂亮的','自然的','专一的','听话的','昏睡的','狂野的','等待的','搞怪的','幽默的','魁梧的','活泼的','开心的','高兴的','超帅的','留胡子的','坦率的','直率的','轻松的','痴情的','完美的','精明的','无聊的','有魅力的','丰富的','繁荣的','饱满的','炙热的','暴躁的','碧蓝的','俊逸的','英勇的','健忘的','故意的','无心的','土豪的','朴实的','兴奋的','幸福的','淡定的','不安的','阔达的','孤独的','独特的','疯狂的','时尚的','落后的','风趣的','忧伤的','大胆的','爱笑的','矮小的','健康的','合适的','玩命的','沉默的','斯文的','香蕉','苹果','鲤鱼','鳗鱼','任性的','细心的','粗心的','大意的','甜甜的','酷酷的','健壮的','英俊的','霸气的','阳光的','默默的','大力的','孝顺的','忧虑的','着急的','紧张的','善良的','凶狠的','害怕的','重要的','危机的','欢喜的','欣慰的','满意的','跳跃的','诚心的','称心的','如意的','怡然的','娇气的','无奈的','无语的','激动的','愤怒的','美好的','感动的','激情的','激昂的','震动的','虚拟的','超级的','寒冷的','精明的','明理的','犹豫的','忧郁的','寂寞的','奋斗的','勤奋的','现代的','过时的','稳重的','热情的','含蓄的','开放的','无辜的','多情的','纯真的','拉长的','热心的','从容的','体贴的','风中的','曾经的','追寻的','儒雅的','优雅的','开朗的','外向的','内向的','清爽的','文艺的','长情的','平常的','单身的','伶俐的','高大的','懦弱的','柔弱的','爱笑的','乐观的','耍酷的','酷炫的','神勇的','年轻的','唠叨的','瘦瘦的','无情的','包容的','顺心的','畅快的','舒适的','靓丽的','负责的','背后的','简单的','谦让的','彩色的','缥缈的','欢呼的','生动的','复杂的','慈祥的','仁爱的','魔幻的','虚幻的','淡然的','受伤的','雪白的','高高的','糟糕的','顺利的','闪闪的','羞涩的','缓慢的','迅速的','优秀的','聪明的','含糊的','俏皮的','淡淡的','坚强的','平淡的','欣喜的','能干的','灵巧的','友好的','机智的','机灵的','正直的','谨慎的','俭朴的','殷勤的','虚心的','辛勤的','自觉的','无私的','无限的','踏实的','老实的','现实的','可靠的','务实的','拼搏的','个性的','粗犷的','活力的','成就的','勤劳的','单纯的','落寞的','朴素的','悲凉的','忧心的','洁净的','清秀的','自由的','小巧的','单薄的','贪玩的','刻苦的','干净的','壮观的','和谐的','文静的','调皮的','害羞的','安详的','自信的','端庄的','坚定的','美满的','舒心的','温暖的','专注的','勤恳的','美丽的','腼腆的','优美的','甜美的','甜蜜的','整齐的','动人的','典雅的','尊敬的','舒服的','妩媚的','秀丽的','喜悦的','甜美的','彪壮的','强健的','大方的','俊秀的','聪慧的','迷人的','陶醉的','悦耳的','动听的','明亮的','结实的','魁梧的','标致的','清脆的','敏感的','光亮的','大气的','老迟到的','知性的','冷傲的','呆萌的','野性的','隐形的','笑点低的','微笑的','笨笨的','难过的','沉静的','火星上的','失眠的','安静的','纯情的','要减肥的','迷路的','烂漫的','哭泣的','贤惠的','苗条的','温婉的','发嗲的','会撒娇的','贪玩的','执着的','眯眯眼的','花痴的','想人陪的','眼睛大的','高贵的','傲娇的','心灵美的','爱撒娇的','细腻的','天真的','怕黑的','感性的','飘逸的','怕孤独的','忐忑的','高挑的','傻傻的','冷艳的','爱听歌的','还单身的','怕孤单的','懵懂的');

    $nicheng_wei = array('嚓茶','凉面','便当','毛豆','花生','可乐','灯泡','哈密瓜','野狼','背包','眼神','缘分','雪碧','人生','牛排','蚂蚁','飞鸟','灰狼','斑马','汉堡','悟空','巨人','绿茶','自行车','保温杯','大碗','墨镜','魔镜','煎饼','月饼','月亮','星星','芝麻','啤酒','玫瑰','大叔','小伙','哈密瓜，数据线','太阳','树叶','芹菜','黄蜂','蜜粉','蜜蜂','信封','西装','外套','裙子','大象','猫咪','母鸡','路灯','蓝天','白云','星月','彩虹','微笑','摩托','板栗','高山','大地','大树','电灯胆','砖头','楼房','水池','鸡翅','蜻蜓','红牛','咖啡','机器猫','枕头','大船','诺言','钢笔','刺猬','天空','飞机','大炮','冬天','洋葱','春天','夏天','秋天','冬日','航空','毛衣','豌豆','黑米','玉米','眼睛','老鼠','白羊','帅哥','美女','季节','鲜花','服饰','裙子','白开水','秀发','大山','火车','汽车','歌曲','舞蹈','老师','导师','方盒','大米','麦片','水杯','水壶','手套','鞋子','自行车','鼠标','手机','电脑','书本','奇迹','身影','香烟','夕阳','台灯','宝贝','未来','皮带','钥匙','心锁','故事','花瓣','滑板','画笔','画板','学姐','店员','电源','饼干','宝马','过客','大白','时光','石头','钻石','河马','犀牛','西牛','绿草','抽屉','柜子','往事','寒风','路人','橘子','耳机','鸵鸟','朋友','苗条','铅笔','钢笔','硬币','热狗','大侠','御姐','萝莉','毛巾','期待','盼望','白昼','黑夜','大门','黑裤','钢铁侠','哑铃','板凳','枫叶','荷花','乌龟','仙人掌','衬衫','大神','草丛','早晨','心情','茉莉','流沙','蜗牛','战斗机','冥王星','猎豹','棒球','篮球','乐曲','电话','网络','世界','中心','鱼','鸡','狗','老虎','鸭子','雨','羽毛','翅膀','外套','火','丝袜','书包','钢笔','冷风','八宝粥','烤鸡','大雁','音响','招牌','胡萝卜','冰棍','帽子','菠萝','蛋挞','香水','泥猴桃','吐司','溪流','黄豆','樱桃','小鸽子','小蝴蝶','爆米花','花卷','小鸭子','小海豚','日记本','小熊猫','小懒猪','小懒虫','荔枝','镜子','曲奇','金针菇','小松鼠','小虾米','酒窝','紫菜','金鱼','柚子','果汁','百褶裙','项链','帆布鞋','火龙果','奇异果','煎蛋','唇彩','小土豆','高跟鞋','戒指','雪糕','睫毛','铃铛','手链','香氛','红酒','月光','酸奶','银耳汤','咖啡豆','小蜜蜂','小蚂蚁','蜡烛','棉花糖','向日葵','水蜜桃','小蝴蝶','小刺猬','小丸子','指甲油','康乃馨','糖豆','薯片','口红','超短裙','乌冬面','冰淇淋','棒棒糖','长颈鹿','豆芽','发箍','发卡','发夹','发带','铃铛','小馒头','小笼包','小甜瓜','冬瓜','香菇','小兔子','含羞草','短靴','睫毛膏','小蘑菇','跳跳糖','小白菜','草莓','柠檬','月饼','百合','纸鹤','小天鹅','云朵','芒果','面包','海燕','小猫咪','龙猫','唇膏','鞋垫','羊','黑猫','白猫','万宝路','金毛','山水','音响');

    $tou_num = rand(0,331);

    $wei_num = rand(0,325);

    $nicheng = $nicheng_tou[$tou_num].$nicheng_wei[$wei_num];

    return $nicheng;
}

//元转分
function yuanToFen($i) {
    return $i * 100;
}

//分转元
function fenToYuan($i) {
    return intval($i);
    //return number_format(intval($i) / 100, 2, '.', '');
}

//元转分
function admin_yuanToFen($i) {
    return $i * 100;
}

//分转元
function admin_fenToYuan($i) {
    return number_format(intval($i) / 100, 2, '.', '');
}

//毫转元
function haoToYuan($i) {
    return number_format(intval($i) / 10000, 4, '.', '');
}

//版权声明
function copyrightNotice() {
    $html = '
    <link rel="stylesheet" type="text/css" href="https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/css/qqalert.css">
    <div style="font-size: 10px;color: #717171;padding-top: 18px;position: initial;padding-bottom: 18px;">
        <center>版权声明：本文源于网络，版权归原作者所有，如侵权请<span style="color: #55b2fd;" onclick="contactUs()">联系我们</span>删除</center>
    </div>
    <script>
        function contactUs() {
            alertQQUI("客服QQ号码：2195635021", "联系我们", "", "");
        }
        function alertQQUI(i,e,t,d){var l=document.getElementById("ui-dialog");return e=e||"温馨提示",i=i||"",d=d||"确定",null==l&&document.body.insertAdjacentHTML("beforeEnd",\'<div id="ui-dialog"><div class="ui-dialog show"><div class="ui-dialog-cnt" id="qq-alert"><div style="position: absolute;top: 2%;width: 25px;height: 25px;background: url(https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/close.png);background-size: contain;right: 1%;z-index: 99;" onclick="$(' . "\'#ui-dialog\'" . ').hide();"></div><header class="ui-dialog-hd ui-border-b"></header><div class="ui-dialog-bd"></div><div class="ui-dialog-ft"><button class="qq-button" type="button" data-role="button"></button></div></div></div><div>\'),l=document.getElementById("ui-dialog"),l.querySelectorAll(".ui-dialog-hd")[0].innerHTML=e,l.querySelectorAll(".ui-dialog-bd")[0].innerHTML=i,l.querySelectorAll(".qq-button")[0].innerHTML=d,l.querySelectorAll(".qq-button")[0].onclick=function(){l.style.display="none","function"==typeof t&&t()},l.style.display="block",!1}
    </script>
    ';
    return $html;
}

//虚拟数据
function virtualenlightening()
{
    //获取今天时间时间戳
    $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
    //虚拟数据数组
    $virtual = array(
        ['username' => "九卿臣",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515679013810257.jpg",
        'count' => 446,
        'money_total' => '4758369',
        'time' => 1515641740
        ],
        ['username' => "孤央",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515640298.jpg",
        'count' => 408,
        'money_total' => '3974890',
        'time' => 1515641740
        ],
        ['username' => "青冘",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515679013528597.jpg",
        'count' => 401,
        'money_total' => '3970067',
        'time' => 1515641740
        ],
        ['username' => "沉秋",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515661088783319.jpg",
        'count' => 378,
        'money_total' => '3894578',
        'time' => 1515641740
        ],
        ['username' => "酒自斟",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515679014767868.jpg",
        'count' => 359,
        'money_total' => '3547320',
        'time' => 1515641740
        ],
        ['username' => "孤傲王者",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515679014690028.jpg",
        'count' => 309,
        'money_total' => '3079742',
        'time' => 1515641740
        ],
        ['username' => "孤独",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515661089136325.jpg",
        'count' => 290,
        'money_total' => '2889455',
        'time' => 1515641740
        ],
        ['username' => "小芳",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515679013424602.jpg",
        'count' => 242,
        'money_total' => '2437579',
        'time' => 1515641740
        ],
        ['username' => "特里斯",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515661088197444.jpg",
        'count' => 167,
        'money_total' => '1597864',
        'time' => 1515641740
        ],
        ['username' => "卡戴珊",
        'avatar' => "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/ueditor/1515661088253267.jpg",
        'count' => 129,
        'money_total' => '1304576',
        'time' => 1515641740
        ]
    );
    foreach ($virtual as $key => $value) {
        $virtual[$key]['count'] = intval(($beginToday - $value['time']) / 84600) * 3 + $virtual[$key]['count'];
        $virtual[$key]['money_total'] = intval(($beginToday - $value['time']) / 84600) * 333  + $virtual[$key]['money_total'];
    }
    return $virtual;
}

/*
 *content: 根据数组某个字段进行排序
 * $arr    需要排序的数组
 * $field  数组里的某个字段
 * sort    1为正序排序  2为倒序排序
 * time :  2017年12月21日19:02:33
*/
function f_order($arr,$field,$sort){
    $order = array();
    foreach($arr as $kay => $value){
        $order[] = $value[$field];
    }
    if($sort==1){
        array_multisort($order,SORT_ASC,$arr);
    }else{
        array_multisort($order,SORT_DESC,$arr);
    }
    return $arr;
}