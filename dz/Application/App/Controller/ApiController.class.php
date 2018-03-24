<?php
namespace App\Controller;

use Think\Controller;
use OSS\Core\OssException;

class ApiController extends Controller
{

    public function _initialize()
    {
        if (empty(session('userInfo'))) {
            $data = array(
                'code' => -1,
                'msg' => '登录过期'
            );
            exit(json_encode($data));
        }
    }

    //获取用户个人信息
    public function persnal()
    {
    	$persnal = session('userInfo');
    	$user = M('app_user')->where("id ='{$persnal['id']}'")->field("id,avatar,phone,username,money")->find();
        //累计收徒数量
        $num = M('app_user_enlightening')
                ->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.uid = dz_app_user.id")
                ->field("uid,count(uid),username,avatar")
                ->group("uid")
                ->limit("0,10")
                ->find();
        //今日时间戳
        $todaytimes = intval(strtotime(date("Y-m-d"),time()));
        //今日总金额
        $users = M('app_money_record')->where("uid = '{$persnal['id']}' AND create_time > {$todaytimes}")->field('sum(money)')->find();
        $user['todaymoney'] = fenToYuan($users['sum(money)']);
        $user['enlightening'] = $num['count(uid)'];
        $user['money'] = fenToYuan($user['money']);
        $user['money_total'] = fenToYuan($user['money_total']);
        //$user['id'] = 100000 + $user['id'];
        if ($user['id'] == 46) {
            $user['id'] = 777;
        } else {
            $user['id'] = 3150 + intval($user['id']);
        }
        if ($user['phone']) {
            //已绑定手机号，昵称显示手机号
            $user['username'] = substr($user['phone'], 0, 3) . "****" . substr($user['phone'], 7, 4);
            unset($user['phone']);
        }
    	if($user){
            $data = array(
                'code' => 0,
                'msg' => '获取成功',
                'data' => $user
            );
    		$this->ajaxReturn($data);
        }
    }
    //获取分类信息
    public function category()
    {
        $info = array();
        $info[] = array(
            'id' => 88888,
            'name' => '热门'
        );
        $category = M('admin_ruanwen_category')->field('id,name')->order('level desc')->select();
        foreach ($category as $item) {
            $info[] = $item;
        }
        if ($info) {
            $data = array(
                'code' => 0,
                'msg' => '分类获取成功',
                'data' => $info
            );
            $this->ajaxReturn($data);
        }
    }
    //根据分类取文章列表
    public function articleList()
    {
        $id = intval(I('get.id'));
        $start = intval(I('get.start'));
        $num = intval(I('get.num'));
        if ($num > 50) {
            $num = 50;
        }
        if ($id <= 0) {
            $data = array(
                'code' => 0,
                'msg' => 'id不能为空',
                'data' => array()
            );
            $this->ajaxReturn($data);
        }
        if ($id === 88888) {
            $article = M('admin_ruanwen')->field("dz_admin_ruanwen.id,dz_admin_ruanwen.thumb,dz_admin_ruanwen.title,dz_admin_ruanwen.describe,dz_admin_ruanwen.read_num,dz_admin_ruanwen.share_num,dz_admin_ruanwen.money as article_money,dz_admin_guanggaoruanwen.gid,dz_admin_guanggao.money as ad_money")->join("LEFT JOIN dz_admin_guanggaoruanwen ON dz_admin_ruanwen.id = dz_admin_guanggaoruanwen.wid")->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaoruanwen.gid")->where("dz_admin_ruanwen.status = 0")->order("id desc")->limit($start . ',' . $num)->select();

            $total = M('admin_ruanwen')->field('id')->where("status = 0")->count();
        } else {
            $article = M('admin_ruanwen')->field("dz_admin_ruanwen.id,dz_admin_ruanwen.thumb,dz_admin_ruanwen.title,dz_admin_ruanwen.describe,dz_admin_ruanwen.read_num,dz_admin_ruanwen.share_num,dz_admin_ruanwen.money as article_money,dz_admin_guanggaoruanwen.gid,dz_admin_guanggao.money as ad_money")->join("LEFT JOIN dz_admin_guanggaoruanwen ON dz_admin_ruanwen.id = dz_admin_guanggaoruanwen.wid")->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaoruanwen.gid")->where("dz_admin_ruanwen.status = 0 and dz_admin_ruanwen.category = {$id}")->order("id desc")->limit($start . ',' . $num)->select();

            $total = M('admin_ruanwen')->field('id')->where("status = 0 and category = {$id}")->count();
        }
        if ($article) {
            //去除重复文章
            $temp = array();
            $article_new = array();
            foreach ($article as $item) {
                if (!in_array($item['id'], $temp)) {
                    if ($item['article_money'] == 0) {
                        //广告佣金
                        $item['money'] = $item['ad_money'];
                    } else {
                        //文章佣金
                        $item['money'] = $item['article_money'];
                    }
                    if ($item['money'] == null) {
                        //全局默认佣金
                        $item['money'] = C('READ_MONEY');
                    }
                    //加入新数组
                    $article_new[$item['id']] = $item;
                    array_push($temp, $item['id']);
                } else {
                    //已加入，重新取最高佣金
                    /*if ($article_new[$item['id']]['money'] < $item['money']) {
                        $article_new[$item['id']] = $item;
                    }*/
                }
            }
            $article = array();
            foreach ($article_new as $item) {
                $item['title'] = htmlspecialchars_decode($item['title']);
                $article[] = $item;
            }
            $req = array(
                'article' => $article,
                'start' => $start,
                'num' => count($article),
                'total' => $total
            );
            $data = array(
                'code' => 0,
                'msg' => '获取成功',
                'data' => $req
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'code' => -1,
                'msg' => '获取失败',
                'data' => array()
            );
            $this->ajaxReturn($data);
        }
    }

    //获取文章详细信息
    public function articleDetail()
    {
        $id = intval(I('get.id'));
        $article = M('admin_ruanwen')->field('id,thumb,title,describe,content,read_num,share_num,create_time')->where("id = '{$id}' and status = 0")->find();
        if ($article) {
            //加密文章id和用户id
            $userInfo = session('userInfo');
            $share_data = array(
                'article' => $id,
                'uid' => $userInfo['id']
            );
            $share_data['id'] = rc4_base64_encode(json_encode($share_data));
            //解码html
            $article['title'] = htmlspecialchars_decode($article['title']);
            $article['content'] = htmlspecialchars_decode($article['content']);
            //时间戳转换成日期
            $article['create_time'] = date('Y-m-d', $article['create_time']);
            //拼接分享URL
            $article['share_url'] = C('_URL_') . U('App/Article/index', array('id' => $share_data['id']));
            //拼接引导分享URL
            $article['preview_url'] = C('_URL_') . U('App/Article/preview', array('id' => $share_data['id']));
            $req = array(
                'content' => $article['content'],
                'title' => $article['title'],
                'url' => $article['share_url'],
                'wechat_url' => $article['preview_url'],
                'time' => $article['create_time']
            );
            $data = array(
                'code' => 0,
                'msg' => '获取成功',
                'data' => $req
            );
            $this->ajaxReturn($data);
        } else {
            $data = array(
                'code' => -1,
                'msg' => '获取失败',
                'data' => array()
            );
            $this->ajaxReturn($data);
        }
    }

    //获取邀请链接
    public function inviteUrl()
    {
        $userInfo = session('userInfo');
        $url = array(
            'url' => C('_URL_')  . U("Index/invite", array('id' => $userInfo['id']))
        );

        //获取历史收徒数量
        $history = M('app_user_enlightening')->where("uid = '{$userInfo['id']}'")->field("count(uid) as stsl")->find();
        //获取今天的收徒数量
        //获取今天的时间戳
        $todaytimes = intval(strtotime(date("Y-m-d"),time()));
        $today = M('app_user_enlightening')->where("uid = '{$userInfo['id']}' AND time > '{$todaytimes}'")->field("count(uid)")->find();
        $data = array(
            'code' => 0,
            'msg' => '邀请链接获取成功',
            'data' => $url,
            'history' => $history["stsl"],
            'today' => $today['count(uid)']
        );
        $this->ajaxReturn($data);
    }

    //公众号绑定手机号
    public function sms()
    {
        $type = I('get.type');
        $debug = C('SMS_DEBUG');
        //是否已绑定手机
        $userInfo = session('userInfo');
        $info = M('app_user')->field('id,phone')->where("id = '{$userInfo['id']}'")->find();
        if ($info['phone']) {
            $data = array(
                'code' => -1,
                'msg' => '微信已绑定过手机号',
                'data' => array()
            );
            $this->ajaxReturn($data);
        }

        if ($type == 'send') {
            //验证验证码
            $luotest_response = I('post.luotest_response');
            if (empty($luotest_response)) {
                $data = array(
                    'code' => -1,
                    'msg' => '人机验证无效,请重试!',
                    'data' => array()
                );
                $this->ajaxReturn($data);
            }
            $luotest_check = captchaVerified($luotest_response);
            //$luotest_check['code'] == 0 验证成功
            if($luotest_check['code'] !== 0){
                $data = array(
                    'code' => -1,
                    'msg' => '人机验证无效,请重试!',
                    'data' => array()
                );
                $this->ajaxReturn($data);
            }
            //获取手机号
            $phone = I("post.phone");
            //手机是否绑定微信
            $phoneInfo = M('app_user')->field('id,openid')->where("phone = '{$phone}' and status = 0")->find();
            if ($phoneInfo && !empty($phoneInfo['openid'])) {
                $data = array(
                    'code' => -1,
                    'msg' => '手机号：' . $phone . '已绑定过其他微信',
                    'data' => array()
                );
                $this->ajaxReturn($data);
            }
            //发送短信
            if (session('sendSMS') < time() || empty(session('sendSMS'))) {
                $code = rand(100000, 999999);
                if (!$debug) {
                    $req = sendSMS($phone, $code);
                    if ($req['code'] !== 0) {
                        //验证码发送失败
                        $data = array(
                            'code' => $req['code'],
                            'msg' => $req['msg'],
                            'data' => array()
                        );
                        $this->ajaxReturn($data);
                    }
                }
                session('sendSMS', time() + 60);
                $data = array(
                    'phone' => $phone,
                    'code' => $code,
                    'time' => time() + 60 * 5
                );
                session('smsInfo', $data);
                $data = array(
                    'code' => 0,
                    'msg' => '验证码已发送到' . $phone . ",请注意查收!",
                    'data' => array()
                );
            } else {
                $data = array(
                    'code' => -1,
                    'msg' => intval(session('sendSMS')) - time() . "秒后再提交！",
                    'data' => array()
                );
            }
            $this->ajaxReturn($data);
        } elseif ($type == 'verify') {
            //验证短信
            $phone = I('post.phone');
            $code = I('post.code');
            $data = session('smsInfo');
            $userInfo = session('userInfo');
            if ($data && $data['phone'] == $phone && $data['code'] == $code && $data['time'] > time() || $debug) {
                $phoneInfo = M('app_user')->field('id,openid')->where("phone = '{$phone}' and status = 0")->find();
                if ($phoneInfo && !empty($phoneInfo['openid'])) {
                    //绑定过其他微信号
                    $data = array(
                        'code' => -1,
                        'msg' => '手机号：' . $phone . '已绑定过其他微信',
                        'data' => array()
                    );
                    $this->ajaxReturn($data);
                } elseif ($phoneInfo && !empty($phoneInfo['id'])) {
                    //手机号注册过、需要合并数据
                    $this->userBind($phoneInfo['id'], $userInfo['id']);
                    $data = array(
                        'code' => 0,
                        'msg' => '手机号：' . $phone . '关联成功',
                        'data' => array()
                    );
                    $this->ajaxReturn($data);
                } else {
                    //手机号未注册、未绑定
                    $iphone['phone'] = $phone;
                    $userphone = M('app_user')->where("id = '{$userInfo['id']}'")->data($iphone)->save();
                    if($userphone) {
                        //分配邀请奖励
                        $this->inviteReward($userInfo['id']);
                        //绑定手机号奖励
                        $user = new \App\Model\AppUserModel();
                        $user->moneyInc(array("id" => $user['pid']), 50, "邀请新用户奖励", true);
                        $data = array(
                            'code' => 0,
                            'msg' => '手机号：' . $phone . '绑定成功',
                            'data' => array()
                        );
                        $this->ajaxReturn($data);
                    }
                }
            } else {
                $data = array(
                    'code' => -1,
                    'msg' => "短信验证码错误！",
                    'data' => array()
                );
                $this->ajaxReturn($data);
            }
            $this->ajaxReturn($data);
        }
    }

    //累计收徒排行榜
    public function EnlighteningNumber()
    {
        $users = session("userInfo");
        $num = M('app_user_enlightening')->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.uid = dz_app_user.id")->field("uid,count(uid) as count,username,avatar")->group("uid")->order("count desc")->limit("0,10")->select();
        $user = M('app_user_enlightening')->where("uid = '{$users['id']}'")->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.uid = dz_app_user.id")->field("count(uid) as count,username,avatar")->group("uid")->select();
        // foreach($num as $key => $value){
        //     if($value['username'] == $user['username'] ){
        //       $user[0]['pm'] = $key+1;
        //     }
        // }
        if(!$user){
            $user[0]['count'] = 0;
            $user[0]['username'] = $users['username'];
            $user[0]['avatar'] = $users['avatar'];
        }

        //合并个人信息
        $num = array_merge(virtualenlightening(),$user);

        //通过数量排序 取10个
        $num = f_order($num,'count',2);
        $num = array_slice($num,0,10);
        $user[0]['pm'] = 206 . rand(20,99);

        foreach ($num as $key => $value) {
            if(in_array($user['avatar'],$value['avatar'])){
                $user[0]['pm'] = $key + 1;
            }
        }

        $data = array(
            'code' => 0,
            'msg' => "收徒排行榜获取成功",
            'data' => $num,
            'user' => $user[0]
        );
        $this->ajaxReturn($data);
    }


    //用户金额排行榜
    public function useramount()
    {
        $user = session("userInfo");
        $user_model = new \App\Model\AppUserModel();
        $user = $user_model->getInfo($user['id'], 'username,avatar,money_total');
        $money = M('app_user')->where("status = 0")->field('username,avatar,money_total')->order("money_total desc")->limit("0,10")->select();
        // foreach($money as $key => $value){
        //     $money[$key]['money_total'] = fenToYuan($money[$key]['money_total']);
        //     if($value['username'] == $user['username'] ){
        //       $user[0]['pm'] = $key+1;
        //     }
        // }
        //合并个人信息
        $users = array();
        $users[] = $user;
        $users[0]['money_total'] = fenToYuan($user['money_total']);
        $money = array_merge(virtualenlightening(),$users);
        //通过数量排序 取10个
        $money = f_order($money,'money_total',2);
        $money = array_slice($money,0,10);
        $users[0]['pm'] = 206 . rand(10,99);
        foreach ($money as $key => $value) {
            if($users[0]['avatar'] == $value['avatar']){
                $users[0]['pm'] = $key + 1;
            }
        }

        $users[0]['money_total'] = fenToYuan($users[0]['money_total']);

        $data = array(
            'code' => 0,
            'msg' => "统计金额成功",
            'data' => $money,
            'user' => $users[0]
        );
        $this->ajaxReturn($data);
    }

    //收徒信息
    public function todayrecruit()
    {
        $user = session('userInfo');
         //获取徒弟信息
        $users = M('app_user_enlightening')->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.pid = dz_app_user.id")->where("dz_app_user_enlightening.uid = '{$user['id']}' ")->select();
        $temp = array();
        foreach ($users as $i => $item) {
            if (!empty($item['id'])) {
                $temp[] = $item;
            }
        }
        $data = array(
            'code' => 0,
            'msg' => "统计收徒信息成功",
            'data' => $temp
        );
        $this->ajaxReturn($data);
    }

    //阅读明细
    public function Readdetails()
    {
        $user = session('userInfo');
        //获取阅读明细
        $users = M('app_article_log')->where("uid = '{$user['id']}'")->field('uid,create_time,money')->order("create_time desc")->select();
        foreach ($users as &$item) {
            $item['money'] = fenToYuan($item['money']);
        }
        $data = array(
            'code' => 0,
            'msg' => "统计阅读明细成功",
            'data' => $users
        );
        $this->ajaxReturn($data);
    }

    //今日盈利信息
    public function userprofit()
    {
        $user = session('userInfo');
        //今日时间戳
        $todaytimes = intval(strtotime(date("Y-m-d"),time()));
        $users = M('app_money_record')->where("uid = '{$user['id']}' AND create_time > {$todaytimes}")->limit(0,50)->select();
        foreach ($users as &$item) {
            $item['money'] = fenToYuan($item['money']);
        }
        $data = array(
            'code' => 0,
            'msg' => "统计今日收入信息成功",
            'data' => $users
        );
        $this->ajaxReturn($data);
    }

    //用户提现记录
    public function userwithdraw()
    {
        $user = session("userInfo");
        $users = M('app_money_withdraw')->where("uid = '{$user['id']}'")->field('money,create_time,status')->order("create_time desc")->limit("0,10")->select();
        foreach ($users as &$item) {
            $item['money'] = fenToYuan($item['money']);
        }
        $data = array(
            'code' => 0,
            'msg' => "统计提现记录成功",
            'data' => $users
        );
        $this->ajaxReturn($data);
    }

    //分配邀请奖励
    private function inviteReward($id)
    {
        $user = M('app_user')->where("id = '{$id}'")->field('pid')->find();
        if ($user && $user['pid'] > 0) {
            //增加用户余额,并给上级奖励佣金
            $user = new \App\Model\AppUserModel();
            $user->moneyInc(array("id" => $user['pid']), 50, "邀请新用户奖励", true);
        }
    }

    //手机账号数据绑定迁移至微信账号
    private function userBind($oldId, $newId)
    {
        //手机账号数据
        $old_info = M('app_user')->where("id = '{$oldId}'")->field('openid,phone,pid,money,miniapp_openid')->find();
        //微信账号数据
        $new_info = M('app_user')->where("id = '{$newId}'")->field('openid,phone,pid,money')->find();
        $info = array(
            'openid' => $new_info['openid'], //微信openid
            'pid' => $new_info['pid'], //微信账号上级id
            'phone' => $old_info['phone'], //手机号码
            'miniapp_openid' => $old_info['miniapp_openid'] //小程序id
        );
        M('app_user')->where("id = '{$newId}'")->save($info); //手机号码存储到微信账号
        M('app_user')->where("id = '{$oldId}'")->save(array('status' => 1)); //手机账号设置为无效
        //增加用户余额,不奖励上级佣金
        $user = new \App\Model\AppUserModel();
        $user->moneyInc(array("id" => $newId), $old_info['money'], "手机账号余额转给微信账号", false);
        M('app_money_record')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //充值记录转移
        M('app_money_withdraw')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //提现记录转移
        M('app_commission')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //佣金记录转移
        M('app_article_log')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //文章阅读记录转移
        M('app_user_sign')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //签到记录转移
        M('app_user_sign_winning_number')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //签到奖励转移
        M('app_user_enlightening')->where("uid = '{$oldId}'")->save(array("uid" => $newId)); //收徒记录转移
        M('app_user_enlightening')->where("pid = '{$oldId}'")->save(array("pid" => $newId)); //收徒记录转移
        M('app_subscribe')->where("invite_id = '{$oldId}'")->save(array("invite_id" => $newId)); //邀请关注记录转移
    }

    //收徒收入信息
    public function usermoneypupil()
    {
        //获取用户信息
        $user = session('userInfo');
        //获取历史收徒数量
        $history = M('app_user_enlightening')->where("uid = '{$user['id']}'")->field("count(uid) as stsl")->find();
        //获取今天的收徒数量
        //获取今天的时间戳
        $todaytimes = intval(strtotime(date("Y-m-d"),time()));
        $today = M('app_user_enlightening')->where("uid = '{$user['id']}' AND time > '{$todaytimes}'")->field("count(uid) as jrst")->find();
        //获取总收入
        $record = M('app_money_record')->where("uid = '{$user['id']}'")->field("sum(money) as record")->find();
        //获取今日收入
        $todayrecord = M('app_money_record')->where("uid = '{$user['id']}' AND create_time >'{$todaytimes}'")->field("sum(money) as todayrecord")->find();
        //获取用余额
        $money = M("app_user")->where("id = '{$user['id']}'")->field('money')->find();
        $data = array(
            'code' => 0,
            'type' => '统计成功',
            'money' => fenToYuan($money['money']),
            'todayhistory' => $today['jrst'],
            'history' => $history['stsl'],
            'todayrecord' => fenToYuan($todayrecord['todayrecord']),
            'record' => fenToYuan($record['record'])
        );
        $this->ajaxReturn($data);
    }

    //提现请求
    public function withdraw()
    {
        //appid
        $appid = C('_APPID_' );
        //商户号
        $mchid = C('_MCHID_');
        //密钥
        $key = C('_MCHID_KEY_');
        //获取提现金额
        $money = intval($_GET['money']);
        //获取用户信息
        $user = session('userInfo');
        //获取用户Openid
        $openid = $user['openid'];
        //查询用户金额
        $user = M('app_user')->where("openid = '{$openid}'")->field("money,id")->find();
        //判断是否有绑定公众号
        if($user){

            //判断是否绑定手机
            $phone = M('app_user')->where("openid = '{$openid}'")->field("phone")->find();

            if(!empty($phone['phone'])){

                if($user['money'] < $money){
                    //如果提现金额大于用户余额
                    $data = array(
                        'type' => '金额不足'
                    );

                    $this->ajaxReturn($data);
                } else {
                    //提现金额小于用户余额,生成提现订单信息
                    //商户订单号
                    $partner_trade_no = mt_rand(10000000,99999999);
                    $data = array(
                        'uid' => $user['id'],
                        'money' => $user['money'],
                        'openid' => $openid,
                        'money' => $money,
                        'create_time' => time(),
                        'partner_trade_no' => $partner_trade_no,
                        'status' => 0
                    );
                    //减去用户金钱
                    $resultss = M('app_user')->where("id = '{$user['id']}' AND openid = '{$openid}' AND status = 0")->setDec('money',$money);
                    //将金额加入冻结金额
                    $freeze = M('app_user')->where("id = '{$user['id']}' AND openid = '{$openid}' AND status = 0")->setInc('money_freeze',$money);
                    if ($resultss && $freeze){
                        //订单信息存入表中 提现状态为0
                        $withdraw = M("app_money_withdraw")->data($data)->add();
                        $data = array(
                            "code" => 0,
                            "type" => '提现请求成功,等待审核'
                        );
                    } else {
                        $data = array(
                            "code" => 1,
                            "type" => "提现请求失败"
                        );
                    }
                    $this->ajaxReturn($data);
                }

            }else{
                //没有绑定手机
                $data = array(
                    "code" => 1,
                    "type" => "提现请求失败,请绑定手机"
                );
                $this->ajaxReturn($data);

            }


        }else{
            //没有绑定
            $data = array(
                "code" => 1,
                "type" => "提现请求失败,请关注公众号"
            );
            $this->ajaxReturn($data);
        }
    }

   //今日签到和签到天数
    public function todaysign()
    {
        // 获取用户openid
        $userInfo = session("userInfo");
        //获取用户信息
        $user = M('app_user')->where("id = '{$userInfo['id']}'")->field("id")->find();
        //获取今天的日期
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $beginToday = date("Y-m-d",$beginToday);
        //判断用户今天是否签到
        $todaysign = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->find();
        //获取昨日的日期
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $beginYesterday=date("Y-m-d",$beginYesterday);
        //判断用户是否昨日有签到
        $yesterday = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginYesterday}'")->field("sign_day")->find();
        //获取签到天数
        $todaysignnum = M('app_user_sign')->where("uid = '{$user['id']}'")->order("date desc")->find();
        if ($todaysign) {
            //判断用户昨日是否签到
            if($yesterday){
                $data = array(
                    "code" => 0,
                    'type' => "用户已经签到,昨日签到",
                    "day" => $todaysignnum['sign_day']
                );
            }else{
                //昨日没签到
                $data = array(
                    "code" => 0,
                    'type' => "用户已经签到,昨日没,天数归零",
                    "day" => $todaysignnum['sign_day']
                );
            }
        } else {
            if($yesterday){
                if($todaysignnum['sign_day'] == null || $todaysignnum['sign_day'] == 0){
                    $data = array(
                        "code" => 0,
                        'type' => "用户今日没签到",
                        "day" => null
                    );
                }else{
                    $data = array(
                        "code" => 0,
                        'type' => "用户今日没签到",
                        "day" => $todaysignnum['sign_day']
                    );
                }

            }else{
                //昨日没签到
                $data = array(
                    "code" => 0,
                    'type' => "用户今日没签到",
                    "day" => null
                );

            }

        }
        $this->ajaxReturn($data);
    }

    //签到记录
    public function sign()
    {
        // 获取用户信息
        $userInfo = session("userInfo");
        //获取用户信息
        $user = M('app_user')->where("id = '{$userInfo['id']}'")->field("id")->find();
        //获取今天的日期
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $beginToday = date("Y-m-d",$beginToday);
        $beginTodays = strtotime($beginToday);

        //判断用户今天是否签到
        $todaysign = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->find();
        //判断用户今天是否有转发收入
        $userlog = M('app_article_log')->where("uid = '{$userInfo['id']}' AND create_time > '{$beginTodays}'")->find();
        if($userlog){
            if ($todaysign) {
                $data = array(
                    "code" => 0,
                    'type' => "用户已经签到"
                );
            } else {
                    //获取昨日的日期
                    $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                    $beginYesterday=date("Y-m-d",$beginYesterday);
                    //判断用户是否昨日有签到
                    $yesterday = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginYesterday}'")->field("sign_day")->find();
                    if($yesterday){
                        //签到天数+1
                        $today = M('app_user_sign')->data(array('sign_day' => $yesterday['sign_day'] + 1,'uid' => $user['id'],'sign_time' => time(),'date' => $beginToday))->add();
                        //查看用户签到天数为几
                        $find = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->field("sign_day")->find();
                        //增加用户余额,不奖励上级佣金
                        $users = new \App\Model\AppUserModel();
                        $addmoney = $users->moneyInc(array("id" => $user['id']), $find['sign_day']*10, "签到奖励金币", false);
                        //判断签到天数是否7天
                        if($today && $addmoney){
                            if($find['sign_day'] == 7){
                                //七天清零
                                $eliminate = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->data(array("sign_day" => 0))->save();
                                $data = array(
                                        'code' => 1,
                                        'type' => '签到成功'
                                );
                            }else{
                                $data = array(
                                        'code' => 1,
                                        'type' => '签到成功'
                                );
                            }
                        }
                    }else{
                        $users = M("app_user_sign")->where("uid = '{$user['id']}'")->find();
                        if($users){
                            //用户签到中断
                            $today = M('app_user_sign')->data(array('sign_day' => 1,'sign_time' => time(),'date' => $beginToday,'uid' => $user['id']))->add();
                            $users = new \App\Model\AppUserModel();
                            $addmoney = $users->moneyInc(array("id" => $user['id']), 10, "签到奖励金币", false);
                            if($today && $addmoney){
                                $data = array(
                                    'code' => 1,
                                    'flag' => 'zhongduan',
                                    'type' => '签到中断,第一天开始累计'
                                );
                            }
                        }else{
                        //签到天数为1
                        $today = M('app_user_sign')->data(array('sign_day' => 1,'uid' => $user['id'],'sign_time' => time(),'date' => $beginToday))->add();
                        $users = new \App\Model\AppUserModel();
                        $addmoney = $users->moneyInc(array("id" => $user['id']), 10, "签到奖励金币", false);
                        if($today && $addmoney){
                            $data = array(
                                'code' => 1,
                                'type' => '签到成功,第一天'
                            );
                        }
                    }
                }
            }
        } else {
            // 今日没有转发收入 不允许签到
            $data = array(
                "code" => 0,
                'type' => "用户今日没有转发收入,不允许签到"
            );
        }
        $this->ajaxReturn($data);
    }

    //绑定师徒
    public function Bindingm()
    {
        //获取用户信息
        $postStr = file_get_contents("php://input");
        $postStr = json_decode($postStr,true);
        $user = session("userInfo");

        //获取师傅ID
        $enid = $postStr['uid'] - 3150;
        //不能等于自己的ID
        if($enid == $user['id']){
            $data = array(
                'code' => 1,
                'msg' => '师傅不存在'
            );
            $this->ajaxReturn($data);
        }
        $data = array(
            'uid' =>  $enid,
            'pid' => $user['id'],
            'time' => time()
        );
        $enlightenings = M('app_user')->where("id = '{$enid}'")->find();
        if(!$enlightenings){
            $data = array(
                'code' => 1,
                'msg' => '师傅不存在'
            );
            $this->ajaxReturn($data);
        }
        $Bindingms = M('app_user_enlightening')->where(array('uid'=> $enid,"pid" => $user['id']))->find();
        $Bindingm = M('app_user_enlightening')->where(array("pid" => $user['id']))->find();
        if(!$Bindingms && !$Bindingm){
            //未绑定
            $Bindingm = M('app_user_enlightening')->data($data)->add();
            $Bindingmuser = M('app_user')->where("id = '{$user['id']}'")->data(array("pid" => $enid))->save();
            if($Bindingm && $Bindingmuser){
                $data = array(
                    'code' => 0,
                    'msg' => "绑定成功"
                );
            }else{
                $data = array(
                    'code' => 1,
                    'msg' => "绑定失败"
                );
            }
        }else{
            $data = array(
                'code' => 1,
                'msg' => '已绑定'
            );
        }
        $this->ajaxReturn($data);
    }

    //退出登录
    public function logOut()
    {
        session('userInfo', null);
        $data = array(
            "code" => 0,
            'msg' => "退出登录成功"
        );
        $this->ajaxReturn($data);
    }

    //上传头像
    public function uploadAvatar()
    {
        //获取用户信息
        $userInfo = session("userInfo");
        $data = array(
            "code" => -1,
            "msg" => "上传失败,请稍后重试！"
        );
        //上传缩略图 personPic
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728 ;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public/Uploads/'; // 设置附件上传根目录
        // 上传单个文件
        $info = $upload->uploadOne($_FILES['personPic']);
        if(!$info) {// 上传错误
            $this->ajaxReturn($data);
        }
        vendor('aliyun.autoload'); //加载aliyun oss
        //连接oss
        $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
        $object = 'avatar/' . date('Y-m-d') . '/' . $info['savename'];//oss保存的文件路径+名称
        $file = $upload->rootPath . $info['savepath'] . $info['savename'];//文件路径，必须是本地的。
        try{
            //上传文件到oss
            $ossClient->uploadFile(_OSS_BUCKET_, $object, $file);
            //删除本地文件
            unlink($file);
            //拼接oss地址
            $avatar = 'https://' . _OSS_BUCKET_ . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
            $userModel = new \App\Model\AppUserModel();
            $updata = $userModel->upInfo("id = {$userInfo['id']}", array("avatar" => $avatar, "update_time" => time()));
            if ($updata) {
                $data = array(
                    "code" => 0,
                    "msg" => "头像上传成功！",
                    "data" => $avatar
                );
            }
            $this->ajaxReturn($data);
        } catch(OssException $e) {
            //oss上传失败
            $this->ajaxReturn($data);
        }
    }

}