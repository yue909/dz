<?php
namespace Admin\Controller;
use Think\Controller;
class WithdrawController extends QXController {
   //用户提现记录
    public function list()
    {
        //单个用户查询
        if($_GET['id']){
            $User = M('app_money_withdraw'); // 实例化User对象
            $count      = $User->where("uid = '{$_GET['id']}'")->count();// 查询满足要求的总记录数
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $Page->parameter['id']   =   $_GET['id'];

            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性

            $userlist = $User->where("uid = '{$_GET['id']}'")->join("LEFT JOIN dz_app_user on dz_app_money_withdraw.uid = dz_app_user.id")->field("dz_app_money_withdraw.*,dz_app_user.username")->order("dz_app_money_withdraw.create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach ($userlist as $key => &$value) {
                $value['money'] =  admin_fenToYuan($value['money']);
                $value['money_freeze'] =  admin_fenToYuan($value['money_freeze']);
            }
        //姓名查询
        }elseif($_GET['aci']){
            $get = $_GET['aci'];

            $User = M('app_money_withdraw'); // 实例化User对象
            $count      = $User->where("dz_app_user.username like '%{$get}%'")->join("LEFT JOIN dz_app_user on dz_app_money_withdraw.uid = dz_app_user.id")->field("dz_app_money_withdraw.*,dz_app_user.username")->count();// 查询满足要求的总记录数
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $Page->parameter['aci']   =   $_GET['aci'];
            $show       = $Page->show();// 分页显示输出
            //分页跳转的时候保证查询条件

            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性

            $userlist = M('app_money_withdraw')->where("dz_app_user.username like '%{$get}%'")->join("LEFT JOIN dz_app_user on dz_app_money_withdraw.uid = dz_app_user.id")->field("dz_app_money_withdraw.*,dz_app_user.username")->order("dz_app_money_withdraw.create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach ($userlist as $key => &$value) {
                $value['money'] =  admin_fenToYuan($value['money']);
                $value['money_freeze'] =  admin_fenToYuan($value['money_freeze']);
            }
        }
        //状态查询
        elseif ($_GET['status']) {
            $status = $_GET['status']-1;
            $User = M('app_money_withdraw'); // 实例化User对象
            $count      = $User->where("status = '{$status}'")->count();// 查询满足要求的总记录数
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $Page->parameter['status']   =   $_GET['status'];

            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性

            $userlist = $User->where("dz_app_money_withdraw.status = '{$status}'")->join("LEFT JOIN dz_app_user on dz_app_money_withdraw.uid = dz_app_user.id")->field("dz_app_money_withdraw.*,dz_app_user.username")->order("dz_app_money_withdraw.create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach ($userlist as $key => &$value) {
                $value['money'] =  admin_fenToYuan($value['money']);
                $value['money_freeze'] =  admin_fenToYuan($value['money_freeze']);
            }
        }else{
            $User = M('app_money_withdraw'); // 实例化User对象
            $count      = $User->count();// 查询满足要求的总记录数
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性

            $userlist = $User->join("LEFT JOIN dz_app_user on dz_app_money_withdraw.uid = dz_app_user.id")->field("dz_app_money_withdraw.*,dz_app_user.username")->order("dz_app_money_withdraw.create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            foreach ($userlist as $key => &$value) {
                $value['money'] =  admin_fenToYuan($value['money']);
                $value['money_freeze'] =  admin_fenToYuan($value['money_freeze']);
            }
        }
        $this->assign('page',$show);// 赋值分页输出
        $this->assign("list",$userlist);
        $this->display("withdraw/list");
    }
    //同意提现
    public function withdraw()
    {
        //appid
        $appid = C('_APPID_');
        //商户号
        $mchid = C('_MCHID_');
        //密钥
        $key = C('_MCHID_KEY_');
        $id = $_GET['id'];
        $open = M('app_money_withdraw')->where("id = '{$id}'")->field("openid,money,partner_trade_no")->find();
        $res = $this->payToUser($open["openid"],$appid,$key,"零钱入账",$open['money'],$mchid,$open['partner_trade_no']);
        $result = $this->xmlToArray2($res);
        if($result['return_code'] == "SUCCESS") {
            if ($result['result_code'] == "SUCCESS") {
                $data = array(
                    'pay_time' => $result['payment_time'],
                    'payment_no' => $result['payment_no'],
                    'status' => 1
                );
                $results = M('app_money_withdraw')->where("id = '{$id}' AND openid = '{$open['openid']}'")->data($data)->save();
                //清除当比冻结金额
                $freeze = M("app_user")->where("openid = '{$open['openid']}'")->setDec('money_freeze',$open['money']);
               if ($results && $freeze) {
                    $this->success('提现审核成功');
                } else {
                    $this->success("提现成功，存储失败");
                }
            } else {
                $this->error('code:' . $result['err_code'] . ' msg:' . $result['err_code_des']);
            }
        } else {
            $this->error($result['return_msg']);
        }
    }

    //驳回提现
    public function reject()
    {
        $id = $_GET['id'];
        $reject = M('app_money_withdraw')->where("id = '{$id}'")->data(array("status" => 2))->save();
        if($reject){
            //返回用户金钱
            //获取用户id 和 提现金额
            $user = M('app_money_withdraw')->where("id = '{$id}'")->field('uid,money')->find();
            //增加用户余额,不奖励上级佣金
            // $userInfo = new \App\Model\AppUserModel();
            // $return = $userInfo->moneyInc(array("id" => $user['uid']), $user['money'], "驳回提现", false);

            //获取用户返现之前金额
            $beforemoney = M('app_user')->where("id = '{$user['uid']}'")->field('money')->find();
            //返回金额给用户
            $users = M("app_user")->where("id = '{$user['uid']}'")->setInc('money',$user['money']);
            //清除当比冻结金额
            $freeze = M("app_user")->where("id = '{$user['uid']}'")->setDec('money_freeze',$user['money']);
            //获取用户返现之后金额
            // $aftermoney = M('app_user')->where("id = '{$user['uid']}'")->field('money')->find();

            //将返现信息存入表中
            // $return = M("app_money_record")->data(array('uid' => $user['uid'],'money' => $user['money'],'before_money' => $beforemoney['money'],'after_money' => $aftermoney['money'],'create_time' => time()))->add();

            if($user && $freeze){
                $this->success("驳回提现成功");
            }else{
                $this->error("返回金额失败");
            }
        }else{
            $this->error("驳回提现失败");
        }
    }

    //企业向个人付款
    public function payToUser($openid='',$appid,$key,$desc,$amount,$mchid,$partner_trade_no){
        //微信付款到个人的接口
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $params["mch_appid"]        = $appid;   //公众账号appid
        $params["mchid"]            = $mchid;   //商户号 微信支付平台账号
        $params["nonce_str"]        = 'TX'.mt_rand(100,999);   //随机字符串
        $params["partner_trade_no"] = $partner_trade_no;           //商户订单号
        $params["amount"]           = $amount;          //金额
        $params["desc"]             = $desc;            //企业付款描述
        $params["openid"]           = $openid;          //用户openid
        $params["check_name"]       = 'NO_CHECK';       //不检验用户姓名
        $params['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];   //获取IP
        // var_dump($params);
        $str = 'amount='.$params["amount"].'&check_name='.$params["check_name"].'&desc='.$params["desc"].'&mch_appid='.$params["mch_appid"].'&mchid='.$params["mchid"].'&nonce_str='.$params["nonce_str"].'&openid='.$params["openid"].'&partner_trade_no='.$params["partner_trade_no"].'&spbill_create_ip='.$params['spbill_create_ip'].'&key='.$key;
        //md5加密 转换成大写
        $sign = strtoupper(md5($str));
        $params["sign"] = $sign;//签名
        $xml = $this->arrayToXml($params);
        return $this->curl_post_ssl($url, $xml);
    }
    
    // *  array转xml
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

    //发送请求
    function curl_post_ssl($url, $vars, $second=30){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_SSLCERT,"/www/wwwroot/dbili.cn/Wechat/apiclient_cert.pem");
        curl_setopt($ch,CURLOPT_SSLKEY,"/www/wwwroot/dbili.cn/Wechat/apiclient_key.pem");
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    //XML格式转回数组格式
    function xmlToArray2($xml) {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
}