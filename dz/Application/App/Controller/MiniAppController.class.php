<?php
namespace App\Controller;

use Think\Controller;

class MiniAppController extends Controller
{
    private $openid;
    private $session_key;
    /*
    1.login
    (1).session id 判断是否存在
    (2).存在返回id,不存在jsCode转换并返回

    2.init
    (1).session id 判断是否存在
    (2).存在,获取openid等信息，不存在返回
    (3).
    */
    public function _initialize()
    {
        //小程序js code转session
        if (ACTION_NAME == 'wxJsCodeToSession') {
            //初始化返回数据
            $req = array(
                'code' => 1,
                'session' => '',
                'msg' => '获取成功'
            );
            //获取JsCode参数
            $code = I('get.code');
            //读取appid appsecret配置
            $appid = C('_MINIAPP_APPID_');
            $appsecret = C('_MINIAPP_SECRET_');
            $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
            $data = httpGet($url);
            $data = json_decode($data, true);
            if (!empty($data['session_key'])) {
                //生成3rd sessionID
                //$data['session_id'] = `head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 168`;
                $data['session_id'] = exec("head -n 80 /dev/urandom | tr -dc A-Za-z0-9 | head -c 168");
                //更新sessionID sessionKey Openid
                $session_db = new \App\Model\AppSessionModel();
                $session_db->upSession(array(
                    'openid' => $data['openid'],
                    'session_id' => $data['session_id'],
                    'session_key' => $data['session_key']
                ));
                $req['session'] = $data['session_id'];
            } else {
                $req['msg'] = '获取失败';
                $req['code'] = -1;
            }
            $this->ajaxReturn($req);
        }

        //接口统一验证
        $session_id = I('get.session_id');
        $session_db = new \App\Model\AppSessionModel();
        $session_info = $session_db->getSession($session_id);
        if (!empty($session_info)) {
            //查询是否绑定豆赚
            $this->openid = $session_info['openid'];
            $this->session_key = $session_info['session_key'];
        } else {
            //未登录，先跳转登录
            $req = array(
                'retCode' => -1,
                'retMsg' => "登录失败"
            );
            $this->ajaxReturn($req);
        }

    }

    public function userInfo()
    {
        $status = -1;
        $user = new \App\Model\AppUserModel();
        $data = $user->miniappGetInfo($this->openid, "id,phone,username,avatar,money");
        if (!empty($data)) {
            $status = 0;
            if ($data['phone']) {
                $data['username'] = substr($data['phone'], 0, 3) . "****" . substr($data['phone'], 7, 4);
                unset($data['phone']);
            }
            $data['money'] = fenToYuan($data['money']);
            //今日时间戳,取今日收入
            $todaytimes = intval(strtotime(date("Y-m-d"),time()));
            $todayrecord = M('app_money_record')->where("uid = '{$data['id']}' AND create_time > {$todaytimes}")->sum('money');
            $data['today'] = fenToYuan($todayrecord);
            $data['enlightening'] = M('app_user_enlightening')->where("uid = '{$data['id']}'")->count();
            $data['login'] = true;
        } else {
            $data['username'] = '点击登录';
            $data['avatar'] = 'https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/avatar.jpeg';
            $data['money'] = fenToYuan(0);
            $data['today'] = fenToYuan(0);
            $data['enlightening'] = '0';
            $data['login'] = false;
        }
        $status = 0;
        $req = array(
            'retCode' => 1,
            'status' => $status,
            'data' => $data
        );
        $this->ajaxReturn($req);
    }

    //获取分类信息
    public function category()
    {
        $info = array();
        $info[] = array(
            'id' => 88888,
            'title' => '热门'
        );
        $category = M('admin_ruanwen_category')->field('id,name')->select();
        if ($category) {
            foreach ($category as &$item) {
                $item['title'] = $item['name'];
                unset($item['name']);
                $info[] = $item;
            }
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
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
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
            $article = M('admin_ruanwen')
                       ->field("dz_admin_ruanwen.id,dz_admin_ruanwen.thumb,dz_admin_ruanwen.title,dz_admin_ruanwen.describe,dz_admin_ruanwen.read_num,dz_admin_ruanwen.share_num,dz_admin_ruanwen.money as article_money,dz_admin_guanggaoruanwen.gid,dz_admin_guanggao.money as ad_money")
                       ->join("LEFT JOIN dz_admin_guanggaoruanwen ON dz_admin_ruanwen.id = dz_admin_guanggaoruanwen.wid")
                       ->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaoruanwen.gid")
                       ->where("dz_admin_ruanwen.status = 0")
                       ->order("id desc")
                       ->limit($start . ',' . $num)
                       ->select();
            $total = M('admin_ruanwen')->field('id')->where("status = 0")->count();
        } else {
            $article = M('admin_ruanwen')
                       ->field("dz_admin_ruanwen.id,dz_admin_ruanwen.thumb,dz_admin_ruanwen.title,dz_admin_ruanwen.describe,dz_admin_ruanwen.read_num,dz_admin_ruanwen.share_num,dz_admin_ruanwen.money as article_money,dz_admin_guanggaoruanwen.gid,dz_admin_guanggao.money as ad_money")
                       ->join("LEFT JOIN dz_admin_guanggaoruanwen ON dz_admin_ruanwen.id = dz_admin_guanggaoruanwen.wid")
                       ->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaoruanwen.gid")
                       ->where("dz_admin_ruanwen.status = 0 and dz_admin_ruanwen.category = {$id}")
                       ->order("id desc")
                       ->limit($start . ',' . $num)
                       ->select();
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
                //预览文章url
                if ($user) {
                    $share_data = array(
                        'article' => $item['id'],
                        'uid' => $user['id']
                    );
                } else {
                    $share_data = array(
                        'article' => $item['id']
                    );
                }
                $share_id = rc4_base64_encode(json_encode($share_data));
                $item['url'] = C('_URL_') . U('App/Article/mPreview', array('id' => $share_id));
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

    //排行榜
    public function leaderboards()
    {
        $type = I('get.type');
        $data = array('code' => -1, 'msg' => '获取失败');
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id,phone,username,avatar,money,money_total");
        $userInfo = array();
        if ($type == 'disciple') {
            //累计收徒排名
            //$num = M('app_user_enlightening')->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.uid = dz_app_user.id")->field("uid,count(uid) as count,dz_app_user.id,avatar,username")->group("uid")->order("count desc")->limit("0,10")->select();
            /*if (!empty($user)) {
                $user = M('app_user_enlightening')->where("dz_app_user.id = '{$user['id']}'")->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.uid = dz_app_user.id")->field("uid,count(uid) as count,dz_app_user.id,avatar,username")->group("uid")->find();
                foreach($num as $key => $value){
                    if($value['id'] == $user['id'] ){
                      $user['pm'] = $key+1;
                    }
                }
            }*/
            //虚拟数据
            $num = virtualenlightening();
            //通过数量排序 取10个
            $num = f_order($num, 'count', 2);
            $num = array_slice($num, 0, 10);
            $data = array(
                'code' => 0,
                'msg' => "收徒排行榜获取成功",
                'data' => $num,
                'user' => $userInfo
            );
        } elseif ($type == 'total') {
            //累计收入排名
            //$money = M('app_user')->where("status = 0")->field('id,avatar,money_total,username')->order("money_total desc")->limit("0,10")->select();
            /*if (!empty($user)) {
                foreach($money as $key => $value){
                    $money[$key]['money_total'] = fenToYuan($money[$key]['money_total']);
                    if($value['id'] == $user['id'] ){
                      $user['pm'] = $key+1;
                    }
                }
                $user['money_total'] = fenToYuan($user['money_total']);
            } else {
                foreach($money as $key => $value){
                    $money[$key]['money_total'] = fenToYuan($money[$key]['money_total']);
                }
            }*/
            //虚拟数据
            $money = virtualenlightening();
            //通过数量排序 取10个
            $money = f_order($money, 'money_total', 2);
            $money = array_slice($money, 0, 10);
            $data = array(
                'code' => 0,
                'msg' => "金额排行榜获取成功",
                'data' => $money,
                'user' => $userInfo
            );
        }
        $this->ajaxReturn($data);
    }

    //收入明细
    public function record()
    {
        $type = I('get.type');
        $data = array('code' => -2, 'msg' => '未登录，获取失败！');
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id,phone,username,avatar,money,money_total");
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        switch ($type) {
            case 'today':
                //今日明细
                $todaytimes = intval(strtotime(date("Y-m-d"),time()));
                $users = M('app_money_record')->where("uid = '{$user['id']}' AND create_time > {$todaytimes}")->limit(0,50)->select();
                $temp = array();
                foreach ($users as $item) {
                    $tempA = array(
                        'num' => fenToYuan($item['money']),
                        'date' => date('Y-m-d', $item['create_time']),
                        'msg' => $item['description']
                    );
                    $temp[] = $tempA;
                }
                $data = array(
                    'code' => 0,
                    'msg' => "今日明细获取成功",
                    'type' => "今日明细",
                    'data' => $temp
                );
                break;

            case 'disciple':
                //收徒明细
                $users = M('app_user_enlightening')->join("LEFT JOIN dz_app_user on dz_app_user_enlightening.pid = dz_app_user.id")->where("dz_app_user_enlightening.uid = '{$user['id']}' ")->select();
                $temp = array();
                foreach ($users as $i => $item) {
                    if (!empty($item['id'])) {
                        $tempA = array(
                            'avatar' => $item['avatar'],
                            'username' => $item['username'],
                            'create_time' => date('Y-m-d', $item['create_time'])
                        );
                        $temp[] = $tempA;
                    }
                }
                $data = array(
                    'code' => 0,
                    'msg' => "收徒明细获取成功",
                    'type' => "收徒明细",
                    'data' => $temp
                );
                break;

            case 'read':
                //阅读明细
                $users = M('app_article_log')->where("uid = '{$user['id']}'")->limit(0,50)->field('uid,create_time,money')->order("create_time desc")->select();
                foreach ($users as &$item) {
                    $item['money'] = fenToYuan($item['money']);
                    $item['create_time'] = date('Y-m-d', $item['create_time']);
                }
                $data = array(
                    'code' => 0,
                    'msg' => "阅读明细获取成功",
                    'type' => "阅读明细",
                    'data' => $users
                );
                break;

            default:
                # code...
                break;
        }
        $this->ajaxReturn($data);
    }

    //提现页面金额/收徒信息接口
    public function usermoney()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，获取失败！',
            'info' => array(
                'money' => fenToYuan(0),
                'todayhistory' => 0,
                'history' => 0,
                'todayrecord' => fenToYuan(0),
                'record' => fenToYuan(0)
            )
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id,money,openid");
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        //是否绑定关联公众号
        $bind = true;
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
        if (empty($user['openid'])) {
            $bind = false;
        }
        $data = array(
            'code' => 0,
            'msg' => '统计成功',
            'info' => array(
                'money' => fenToYuan($user['money']),
                'todayhistory' => $today['jrst'],
                'history' => $history['stsl'],
                'todayrecord' => fenToYuan($todayrecord['todayrecord']),
                'record' => fenToYuan($record['record']),
                'bind' => $bind
            )
        );
        $this->ajaxReturn($data);
    }

    //获取提现记录
    public function userwithdraw()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，获取失败！'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id,money");
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        $users = M('app_money_withdraw')->where("uid = '{$user['id']}'")->field('money,create_time,status')->order("create_time desc")->limit("0,10")->select();
        $temp = array();
        foreach ($users as $item) {
            $tempA = array(
                'num' => fenToYuan($item['money']),
                'date' => date('Y-m-d', $item['create_time'])
            );
            switch ($item['status']) {
                case '0':
                    $tempA['status'] = '待审核';
                    break;
                case '1':
                    $tempA['status'] = '提现成功';
                    break;
                case '2':
                    $tempA['status'] = '驳回提现';
                    break;
                default:
                    $tempA['status'] = '异常错误';
                    break;
            }
            $temp[] = $tempA;
        }
        $data = array(
            'code' => 0,
            'msg' => "提现记录获取成功",
            'data' => $temp
        );
        $this->ajaxReturn($data);
    }

    //获取收徒邀请链接
    public function inviteUrl()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，获取失败！',
            'info' => array(
                'url' => null,
                'history' => 0,
                'today' => 0
            )
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        $url = C('_URL_')  . U("Index/invite", array('id' => $user['id']));
        //获取历史收徒数量
        $history = M('app_user_enlightening')->where("uid = '{$user['id']}'")->count('uid');
        //获取今天的时间戳
        $todaytimes = intval(strtotime(date("Y-m-d"), time()));
        //获取今天的收徒数量
        $today = M('app_user_enlightening')->where("uid = '{$user['id']}' AND time > '{$todaytimes}'")->count('uid');
        $data = array(
            'code' => 0,
            'msg' => '收徒邀请链接获取成功',
            'info' => array(
                'url' => $url,
                'history' => $history,
                'today' => $today
            )
        );
        $this->ajaxReturn($data);
    }

    //提交提现
    public function withdraw()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，提现失败!'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id,openid,money,phone");
        //未登录
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        $openid = $user['openid'];
        //未关注公众号
        if (empty($openid)) {
            $data = array(
                'code' => -3,
                'msg' => '请先关注公众号!'
            );
            $this->ajaxReturn($data);
        }
        //未绑定手机号
        if (empty($user['phone'])) {
            $data = array(
                'code' => -4,
                'msg' => '请先绑定手机号!'
            );
            $this->ajaxReturn($data);
        }
        //获取提现金额
        $money = intval($_GET['money']);
        if ($money > $user['money']) {
            //提现金额大于用户余额
            $data = array(
                'code' => -5,
                'msg' => '金额不足'
            );
            $this->ajaxReturn($data);
        }
        //提现金额小于用户余额,生成提现订单信息
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
                "msg" => '提现请求成功,等待审核'
            );
        } else {
            $data = array(
                "code" => -6,
                "msg" => "提现请求失败"
            );
        }
        $this->ajaxReturn($data);
    }

    //今日签到和签到天数
    public function signInfo()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，获取失败!'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
        //未登录
        if (empty($user)) {
            $step = array();
            for ($i = 1; $i <= 5; $i++) {
                $temp = array(
                    'current' => false,
                    'done' => false,
                    'text' => $i . '天',
                    'desc' => date('m-d', strtotime("-" . 0 + ($i-1) . " day"))
                );
                $step[] = $temp;
            }
            $data['num'] = 0;
            $data['sign'] = 0;
            $data['step'] = $step;
            $this->ajaxReturn($data);
        }
        //获取今天的日期
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $beginToday = date("Y-m-d", $beginToday);
        //判断用户今天是否签到
        $todaysign = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->find();
        $is_sign = 0;
        if ($todaysign) {
            $is_sign = 1;
        }
        //初始化签到天数、昨天&今天日期
        $num = 0;
        $today = date('Y-m-d', time());
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        //获取签到信息
        $signInfo = M('app_user_sign')->where("uid = '{$user['id']}'")->order("sign_day desc")->find();
        if ($signInfo['date'] == $yesterday || $signInfo['date'] == $today) {
            //昨日或今日已签到，设置签到天数
            $num = intval($signInfo['sign_day']);
            if ($signInfo['date'] == $yesterday && intval($signInfo['sign_day']) == 7) {
                //昨日签到7天，今日清零
                $num = 0;
            }
        }
        if ($num < 5) {
            $start = 1; //开始天数
            $start_day = $num - 1; //开始日期
        } else {
            $start = 3;
            $start_day = $num - 3;
        }
        //今日未签到 开始日期退回一天 +1
        if ($is_sign == 0) {
            $start_day = $start_day + 1;
        }
        $step = array();
        for ($i=1; $i <= 5; $i++) {
            $temp = array(
                'current' => false,
                'done' => false,
                'text' => $start . '天',
                'desc' => date('m-d', strtotime("-" . $start_day + ($i-1) . " day"))
            );
            //已签到的日子
            if ($start <= $num-1) {
                $temp['done'] = true;
            }
            //签到的最后一天
            if ($start == $num) {
                $temp['done'] = true;
                $temp['current'] = true;
            }
            $start++;
            $step[] = $temp;
        }
        $data = array(
            'code' => 0,
            'num' => $num,
            'sign' => $is_sign,
            'step' => $step,
            'msg' => '获取成功'
        );
        $this->ajaxReturn($data);
    }

    //收徒二维码
    public function inviteImg()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，获取失败!'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
        //未登录
        if (empty($user)) {
            $this->ajaxReturn($data);
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

    //签到记录
    public function signUp()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '未登录，签到失败!'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
        //未登录
        if (empty($user)) {
            $this->ajaxReturn($data);
        }
        //获取今天的时间戳
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        //今天是否有转发收入记录
        $todayRecord = M('app_article_log')->where("uid = '{$user['id']}' AND create_time > {$beginToday}")->count('id');
        if ($todayRecord <= 0) {
            $data = array(
                "code" => -3,
                "msg" => "对不起，您今日还没有转发收入，暂时不能签到，请先转发赚钱吧"
            );
            $this->ajaxReturn($data);
        }
        $beginToday = date("Y-m-d", $beginToday);
        //判断用户今天是否签到
        $todaysign = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->find();
        if ($todaysign) {
            $data = array(
                "code" => -3,
                'msg' => "今天已签到"
            );
        } else {
            //获取昨日的日期
            $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $beginYesterday=date("Y-m-d",$beginYesterday);
            //判断用户是否昨日有签到
            $yesterday = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginYesterday}'")->field("sign_day")->find();
            if($yesterday){
                //昨日已连续签到7天
                if ($yesterday['sign_day'] == 7) {
                    //签到天数从1开始
                    $today = M('app_user_sign')->data(array('sign_day' => 1,'uid' => $user['id'],'sign_time' => time(),'date' => $beginToday))->add();
                } else {
                    //签到天数+1
                    $today = M('app_user_sign')->data(array('sign_day' => $yesterday['sign_day'] + 1,'uid' => $user['id'],'sign_time' => time(),'date' => $beginToday))->add();
                }
                //判断签到天数是否7天
                if($today){
                    $days = M('app_user_sign')->where("uid = '{$user['id']}' AND date = '{$beginToday}'")->field("sign_day")->find();
                    //奖励金币数量
                    $coin = intval($days['sign_day']) * 10;
                    //余额奖励，不返上级佣金
                    $um = new \App\Model\AppUserModel();
                    $um->moneyInc(array("id" => $user['id']), $coin, "签到" . $days['sign_day'] . "天奖励", false);
                    $data = array(
                        'code' => 1,
                        'msg' => '签到成功，获得' . $coin . '金币奖励'
                    );
                }
            }else{
                $users = M("app_user_sign")->where("uid = '{$user['id']}'")->find();
                if($users){
                    //用户签到中断
                    $today = M('app_user_sign')->where("uid = '{$user['id']}'")->data(array('sign_day' => 1,'sign_time' => time(),'date' => $beginToday))->save();
                    if($today){
                        $data = array(
                            'code' => 1,
                            'msg' => '签到成功'
                        );
                    }
                }else{
                    //签到天数为1
                    $today = M('app_user_sign')->data(array('sign_day' => 1,'uid' => $user['id'],'sign_time' => time(),'date' => $beginToday))->add();
                    if($today){
                        $data = array(
                            'code' => 1,
                            'msg' => '签到成功'
                        );
                    }
                }
                //奖励金币数量
                $coin = 10;
                //余额奖励，不返上级佣金
                $um = new \App\Model\AppUserModel();
                $um->moneyInc(array("id" => $user['id']), $coin, "签到1天奖励", false);
                $data = array(
                    'code' => 1,
                    'msg' => '签到成功，获得' . $coin . '金币奖励'
                );
            }
        }
        $this->ajaxReturn($data);
    }

    //短信验证码发送/验证/登录
    public function sms()
    {
        //初始化返回消息
        $data = array(
            'code' => -2,
            'msg' => '账号已登录!'
        );
        //获取用户信息
        $user = new \App\Model\AppUserModel();
        $user = $user->miniappGetInfo($this->openid, "id");
        //已登录，直接返回
        if ($user) {
            $this->ajaxReturn($data);
        }
        $debug = C('SMS_DEBUG');
        $type  = I('get.type');
        $info  = $this->decrypt(I('get.i'), I('get.session_id'));
        $phone = $info['phone'];
        if ($type == 'send') {
            //查询是否黑名单
            $blocked = M('app_user')->where("phone = '" . $phone . "' and status = '2'")->find();
            if ($blocked) {
                $data = array(
                    'code' => -1,
                    'msg' => '您的账号(' . $phone . ')已被禁止登录.'
                );
               $this->ajaxReturn($data);
            }
            $sms = new \App\Model\AppSmsModel();
            $info = $sms->getInfo($phone);
            if (!empty($info) && ($info['status'] == 0 && (time() - $info['create_time']) <= 60)) {
                //短信60秒内发送过
                $data = array(
                    'code' => -3,
                    'msg' => ($info['create_time'] + 60 - time()) . '秒后重试'
                );
                $this->ajaxReturn($data);
            }
            $code = rand(100000, 999999);
            if (!$debug) {
                $req = sendSMS($phone, $code);
                if ($req['code'] !== 0) {
                    //验证码发送失败
                    $data = array(
                        'code' => $req['code'],
                        'msg' => $req['msg']
                    );
                    $this->ajaxReturn($data);
                }
            }
            $delete = $sms->deleteInfo($phone);
            $insert = $sms->addInfo(array(
                'phone' => $phone,
                'code' => $code,
                'status' => 0,
                'create_time' => time()
            ));
            if ($insert) {
                $data = array(
                    'code' => 0,
                    'msg' => '验证码已发送到' . $phone . ',请注意查收!'
                );
            } else {
                $data = array(
                    'code' => -4,
                    'msg' => '发送失败,请稍后再试!'
                );
            }
            $this->ajaxReturn($data);
        }
        if ($type == 'verify') {
            $code  = $info['code'];
            $sms = new \App\Model\AppSmsModel();
            $info = $sms->getInfo($phone);
            if (!empty($info) && $info['err_num'] < 5 && ($info['status'] == 0 && (time() - $info['create_time']) <= (60 * 5))) {
                //短信5分钟内未使用过 + 输入错误5次以下
                if ($info['code'] == $code || $debug) {
                    //验证码设置已使用
                    $sms->useUp($phone);
                    //手机号登录
                    $data = $this->initLogin($phone);
                    $this->ajaxReturn($data);
                } else {
                    $sms->errUp($phone);
                    $data = array(
                        'code' => -3,
                        'msg' => '验证码错误'
                    );
                    $this->ajaxReturn($data);
                }
            } else {
                $data = array(
                    'code' => -4,
                    'msg' => '验证码无效'
                );
                $this->ajaxReturn($data);
            }
        }
    }

    //手机号快捷登录
    public function phoneLogin()
    {
        $info = $this->decrypt(I('get.i'), I('get.session_id'));
        $appid = C('_MINIAPP_APPID_');
        $req = wx_miniapp_decode($appid, $this->session_key, $info['encryptedData'], $info['iv']);
        if ($req['code'] == 0) {
            $arr = json_decode($req['data'], true);
            $phone = $arr['purePhoneNumber'];
            if (strlen($phone) == 11) {
                //手机号登录
                $data = $this->initLogin($phone);
                $this->ajaxReturn($data);
            }
        }
        $data = array(
            'code' => -2,
            'msg' => '授权失败,请稍后重试'
        );
        $this->ajaxReturn($data);
    }

    public function decrypt($encryptStr, $session_id) {
        $localIV = substr($session_id, 0, 32);
        $encryptKey = substr($session_id, 0, 32);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $encryptedData = base64_decode($encryptStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);
        $encryptedData = substr($encryptedData, 0, -4);
        $encryptedData = json_decode($encryptedData, true);
        return $encryptedData;
    }

    //手机号登录&绑定
    private function initLogin($phone)
    {
        $data = array(
            'code' => 0,
            'msg' => '登录成功'
        );
        $user = new \App\Model\AppUserModel();
        $info = $user->phoneGetInfo($phone, 'id,miniapp_openid');
        if (!empty($info)) {
            if (empty($info['miniapp_openid'])) {
                //手机号关联小程序openid
                $user->upInfo("id = '{$info['id']}'", array(
                    "miniapp_openid" => $this->openid
                ));
                return $data;
            } else {
                if ($info['miniapp_openid'] == $this->openid) {
                    return $data;
                }
                return array(
                    'code' => -101,
                    'msg' => '手机号已绑定其他微信'
                );
            }
        }
        $blocked = $user->getData("phone = '" . $phone . "' and status = '2'", "id");
        if ($blocked) {
            $data = array(
                'code' => -102,
                'msg' => '您的账号(' . $phone . ')已被禁止登录.'
            );
            return $data;
        }
        //新账号，创建新账号并关联小程序openid
        $info = array(
            'miniapp_openid' => $this->openid,
            'phone' => $phone,
            'username' => '普通账号',
            'avatar' => 'https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/avatar.jpeg',
            'create_time' => time(),
            'update_time' => time()
        );
        $user->addData($info);
        return $data;
    }
}
