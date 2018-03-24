<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends QXController {
    public function index()
    {
      $Announce = M('admin_announce')->where("status = 0")->select();
      $this->assign("announce", $Announce);
      $this->display("Index/index");
    }
    //有效数据
    public function data()
    {
    	//获取总金额
    	$money = M()->table("dz_app_user as a")->where("a.status = 0 ")->field("sum(a.money) as money")->select();
    	$money = admin_fenToYuan($money[0]['money']);
    	//获取总收徒数量
    	$Enlightening = M()->table("dz_app_user as a")->where("a.status = 0 AND a.pid != 0")->field("count(a.pid) as Enlightening")->select();
    	//获取文章数量和阅读量和分享量
    	$article = M()->table('dz_admin_ruanwen as a')->field("count(a.id) as article,sum(a.read_num) as read_num,sum(a.share_num) as share_num")->select();
        //获取广告数量
        $guanggaonum = M()->table("dz_admin_guanggao as a")->field("count(a.id) as guanggaonum")->select();
        //获取广告展示量和点击量
        $guanggaoread = M()->table("dz_app_advertising_info as a")->field("sum(a.show_num) as show_num,sum(a.click_num) as click_num")->select();
        //获取广告点击率
        $successRate = floor(($guanggaoread[0]['click_num'] / $guanggaoread[0]['show_num'])*10000)/10000*100;
        $successRate = $successRate."%";

        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $beginWeek = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));

        //用户数据
        $userModel = new \Admin\Model\userInfoModel();
        $user = array();
        $user['all'] = $userModel->dataCount();//所有用户
        $user['follow'] = M('app_subscribe')->where("state = 1")->count('id');//关注用户
        $user['unfollow'] = M('app_subscribe')->where("state = 0")->count('id');//取消关注用户
        $user['today'] = $userModel->dataCount("create_time > {$beginToday}"); //今日用户
        $user['week'] = $userModel->dataCount("create_time > {$beginWeek}"); //最近7天用户
        $user['valid'] = $userModel->dataCount(array('status' => 0)); //有效用户数量
        $user['invalid'] = $userModel->dataCount(array('status' => 1)); //无效用户数量
        $user['black'] = $userModel->dataCount(array('status' => 2)); //黑名单用户数量
        $user['phone'] = $userModel->dataCount("phone != '' OR phone != NULL"); //已绑定手机
        $user['wechat'] = $userModel->dataCount("openid != '' OR openid != NULL"); //微信授权/关注公众号
        $user['miniapp'] = $userModel->dataCount("miniapp_openid != '' OR miniapp_openid != NULL"); //已绑定小程序
        $this->assign("user", $user);//用户数据

        //文章数据
        $articleInfo = array();
        $articleInfo = M()->table('dz_admin_ruanwen as a')->field("count(a.id) as total,sum(a.read_num) as total_read,sum(a.share_num) as total_share")->select();
        $articleInfo = $articleInfo[0];//文章总数，总阅读/分享
        $articleInfo['show'] = M('admin_ruanwen')->where('status = 0')->count('id');//显示的文章
        $articleInfo['hide'] = M('admin_ruanwen')->where('status = 1')->count('id');//隐藏的文章
        $articleInfo['category'] = M('admin_ruanwen_category')->count('id');//文章分类数量
        $articleInfoModel = new \Admin\Model\articleInfoModel();
        $articleInfo['valid_read'] = $articleInfoModel->dataSum('', 'read_num');//有效阅读总数
        $articleInfo['valid_share'] = $articleInfoModel->dataSum('', 'share_num');//有效分享总数
        $articleInfo['today_read'] = $articleInfoModel->dataSum("add_time > {$beginToday}", 'read_num');//今日阅读
        $articleInfo['today_share'] = $articleInfoModel->dataSum("add_time > {$beginToday}", 'share_num');//今日分享
        $articleInfo['week_read'] = $articleInfoModel->dataSum("add_time > {$beginWeek}", 'read_num');//最近7天阅读
        $articleInfo['week_share'] = $articleInfoModel->dataSum("add_time > {$beginWeek}", 'share_num');//最近7天分享
        $this->assign("articleInfo",$articleInfo);//文章数据

        //资金数据
        $moneyInfo = array();
        $moneyInfo['normal'] = admin_fenToYuan($userModel->dataSum('status = 0 OR status = 2', 'money'));//可用余额
        $moneyInfo['freeze'] = admin_fenToYuan($userModel->dataSum('status = 0 OR status = 2', 'money_freeze'));//冻结余额
        $moneyInfo['total'] = $moneyInfo['normal'] + $moneyInfo['freeze'];//总余额
        $moneyInfo['history'] = admin_fenToYuan($userModel->dataSum('status = 0 OR status = 2', 'money_total'));//历史累计余额
        $withdrawModel = new \Admin\Model\withdrawInfoModel();
        $moneyInfo['today_money'] = admin_fenToYuan($withdrawModel->dataSum("create_time > {$beginToday}", 'money'));//今日提现金额
        $moneyInfo['history_money'] = admin_fenToYuan($withdrawModel->dataSum('', 'money'));//历史提现金额
        $moneyInfo['waiting_money'] = admin_fenToYuan($withdrawModel->dataSum('status = 0', 'money'));//待审核提现金额
        $moneyInfo['success_money'] = admin_fenToYuan($withdrawModel->dataSum('status = 1', 'money'));//成功提现金额
        $moneyInfo['rejected_money'] = admin_fenToYuan($withdrawModel->dataSum('status = 2', 'money'));//驳回提现金额
        $moneyInfo['today_num'] = $withdrawModel->dataCount("create_time > {$beginToday}");//今日提现笔数
        $moneyInfo['history_num'] = $withdrawModel->dataCount();//历史提现笔数
        $moneyInfo['waiting_num'] = $withdrawModel->dataCount('status = 0');//待审核提现笔数
        $moneyInfo['success_num'] = $withdrawModel->dataCount('status = 1');//成功提现笔数
        $moneyInfo['rejected_num'] = $withdrawModel->dataCount('status = 2');//驳回提现笔数
        $this->assign("moneyInfo",$moneyInfo);//资金数据

        //广告数据:广告数量 上线 下线 总预算 总收入 模板 总展示/点击/点击率 最近7天展示/点击/点击率
        $adInfo = array();
        $adInfo = M()
            ->table('dz_admin_guanggao as a')
            ->field("count(a.id) as total,sum(a.expense) as total_expense,sum(a.budget) as total_budget")
            ->select();
        $adInfo = $adInfo[0];//广告总数，总预算/消费
        $adInfo['total_expense'] = admin_fenToYuan($adInfo['total_expense']);
        $adInfo['total_budget'] = admin_fenToYuan($adInfo['total_budget']);
        $adInfo['hide'] = M('admin_guanggao')->where('status = 1')->count('id');//下线的广告
        $adInfo['show'] = M('admin_guanggao')->where('status = 0')->count('id');//上线的广告
        $adInfo['template'] = M('admin_template')->count('id');//广告模板数量
        $advertisingInfoModel = new \Admin\Model\advertisingInfoModel();
        $adInfo['valid_show'] = $advertisingInfoModel->dataSum('', 'show_num');//有效展示总数
        $adInfo['valid_click'] = $advertisingInfoModel->dataSum('', 'click_num');//有效点击总数
        $adInfo['valid_rate'] = $this->getRate($adInfo['valid_click'], $adInfo['valid_show']);//历史点击率
        $adInfo['today_show'] = $advertisingInfoModel->dataSum("add_time > {$beginToday}", 'show_num');//今日展示
        $adInfo['today_click'] = $advertisingInfoModel->dataSum("add_time > {$beginToday}", 'click_num');//今日点击
        $adInfo['today_rate'] = $this->getRate($adInfo['today_click'], $adInfo['today_show']);//今日点击率
        $adInfo['week_show'] = $advertisingInfoModel->dataSum("add_time > {$beginWeek}", 'show_num');//最近7天展示
        $adInfo['week_click'] = $advertisingInfoModel->dataSum("add_time > {$beginWeek}", 'click_num');//最近7天点击
        $adInfo['week_rate'] = $this->getRate($adInfo['week_click'], $adInfo['week_show']);//7天点击率
        $this->assign("adInfo", $adInfo);//广告数据

        $this->assign("money",$money);//总金额
        $this->assign("Enlightening",$Enlightening);//总收徒数
        $this->assign("article",$article);//文章数量 阅读量 分享量
        $this->assign("guanggaonum",$guanggaonum);//广告数量
        $this->assign("guanggaoread",$guanggaoread);//广告展示量 点击量
        $this->assign("successRate",$successRate); //广告点击率
        $this->display("Index/data");
    }

    //计算点击率
    public function getRate($click, $show)
    {
        $rate = floor(($click / $show)*10000)/10000*100;
        if (is_nan($rate)) {
            return "0%";
        } else {
            return $rate . "%";
        }
    }

    //展示公告
    public function announce()
    {
    	$id = intval(I('get.id'));
		if ($id <= 0) {
			$this->error("id 不能为空！");
		}
		$announce = M('admin_announce')->where("id = {$id}")->find();
		if ($announce) {
			$announce['content'] = htmlspecialchars_decode($announce['content']);
			$this->assign('announce', $announce);
			$this->display("Index/announce");
		} else {
			$this->error("公告不存在！");
		}
    }



}