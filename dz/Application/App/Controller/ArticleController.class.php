<?php
namespace App\Controller;

use Think\Controller;

class ArticleController extends Controller
{

    public function _initialize()
    {
        //微信授权回调
        if (ACTION_NAME == 'auth_callback') {
            //获取回调state和code
            $data = array(
                'code' => I('get.code'),
                'state' => I('get.state'),
                'uri' => base64_decode(I('get.uri')),
            );
            //判断state,code值
            if (!empty($data['state']) && !empty($data['code']) && $data['state'] == session("state")) {
                //获取网页授权access_token & openid
                $aUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . C('_APPID_' ). "&secret=" . C('_APPSECRET_') . "&code=" . $data['code'] . "&grant_type=authorization_code";
                $access_token = getHttp($aUrl);
                if ($access_token && $access_token['openid']) {
                    session('articleOpenID', $access_token['openid']);
                    //重定向到授权前页面
                    redirect($data['uri'], 0);
                }
                exit("授权错误 code:1002"); //access_token获取失败
            } else {
                exit("授权错误 code:1001"); //非法授权
            }
        }
        if (strpos(I('server.HTTP_USER_AGENT'), "MicroMessenger") !== false) {
            if (C('article_openid') == true) {
                $openID = session('articleOpenID');
                if (empty($openID)) {
                    //前往授权获取openID
                    $this->redirectAuth(0);
                }
            } else {
                session('articleOpenID', 'UnauthorizedMode');
            }
        }
    }

    //分享成功统计
    public function share()
    {
        $id = I('get.id'); //获取id参数
        $info = json_decode(rc4_base64_decode($id), true);
        if (session($id) == false && !empty($info['id'])) {
            //更新文章分享次数
            M('admin_ruanwen')->where(array("id" => $info['id']))->setInc("share_num", 1);
            //文章分享数量统计
            $article_info = new \App\Model\AppArticleInfoModel();
            $article_info->upInfo('share_num');
            session($id, true);
        }
    }
    //点击广告统计
    public function advertising()
    {
        $id = I('get.id'); //获取id参数
        $info = json_decode(rc4_base64_decode($id), true);
        if (empty($info['gid']) || empty($info['url'])) {
            //广告参数错误
            header("Location: http://www.qq.com/");
        }
        if (session($id) == false && $info['gid']) {
            $advertising = M('admin_guanggao')->where("id = '{$info['gid']}' AND status = '0'")->find();
            //点击计费模式
            if ($advertising && $advertising['tgms'] == 1) {
                //查询广告单笔费用
                $list = M("admin_guanggao")->where("id='{$info['gid']}'")->field('money')->find();
                //添加单笔广告费用(消费金额)
                $addmonetary = M("admin_guanggao")->where("id='{$info['gid']}'")->setInc("expense", $list['money']);
                //消费金额 >= 预算金额 广告下线。
                if($addmonetary) {
                    //获取广告消费金额，预算金额
                    $list = M("admin_guanggao")->where("id='{$info['gid']}'")->field('expense,budget')->find();
                    //判断消费金额是否超出预算金额
                    if($list['expense'] > $list['budget']) {
                       //超出预算金额下线广告
                        $list = M("admin_guanggao")->where("id='{$info['gid']}'")->data(array("status" => 1))->save();
                    }
                }
            }
            //更新广告点击次数(以小时统计)
            $ad = new \App\Model\AdvertisingInfoModel();
            $ad->upInfo($info['gid'], 'click_num');
            session($id, true);
            header("Location: " . $info['url']);
        } else {
            header("Location: " . $info['url']);
        }
    }

    //小程序内预览文章，引导分享文章
    public function mPreview()
    {
        $id = I('get.id');
        //解码文章id与用户id
        $info = json_decode(rc4_base64_decode($id), true);
        //查询文章
        $article = M('admin_ruanwen')->field('id,thumb,title,describe,content,read_num,share_num,create_time')->where("id = '{$info['article']}' AND status = '0'")->find();
        //拼接分享url
        $article['share_url'] = C('_URL_') . U('App/Article/index', array('id' => $id));
        //解码html
        $article['title'] = htmlspecialchars_decode($article['title']);
        $article['content'] = htmlspecialchars_decode($article['content']);
        //时间戳转换成日期
        $article['create_time'] = date('Y-m-d', $article['create_time']);
        $this->assign('article', $article);
        $this->display('Article/mPreview');
    }

    //微信内预览文章,引导分享文章
    public function preview()
    {
        $id = I('get.id');
        //解码文章id与用户id
        $info = json_decode(rc4_base64_decode($id), true);
        //查询文章
        $article = M('admin_ruanwen')->field('id,thumb,title,describe,content,read_num,share_num,create_time')->where("id = '{$info['article']}' AND status = '0'")->find();
        //拼接分享url
        $article['share_url'] = C('_URL_') . U('App/Article/index', array('id' => $id));
        //解码html
        $article['title'] = htmlspecialchars_decode($article['title']);
        $article['content'] = htmlspecialchars_decode($article['content']);
        //时间戳转换成日期
        $article['create_time'] = date('Y-m-d', $article['create_time']);
        //分享统计id
        $shareId  = array("id" => $article['id']);
        $shareId  = rc4_base64_encode(json_encode($shareId));
        $this->assign('shareId', $shareId);
        $this->assign('article', $article);
        $this->display('Article/preview');
    }

	//阅读文章
	public function index()
	{
        //解码文章id与用户id
        $info = json_decode(rc4_base64_decode(I('get.id')), true);
        //文章信息，默认为空
        $article = '';
        //广告内容，默认为空
        $ad_html = '';
        //授权id
        $openid = session('articleOpenID');
        //uuid ip唯一标识符
        $uuid = md5(get_client_ip() . "-uuid-" . $openid);
        //加载指定文章
        $m = M('admin_ruanwen');
        if (!empty($info['article'])) {
            $article = $m->where("id = '{$info['article']}' AND status = '0'")->find();
        }
        //指定文章不存在，加载随机文章
        if (empty($article)) {
            $c = $m->where("status = '0'")->count();
            $r = mt_rand(0, $c - 1);
            $article = $m->where("status = '0'")->limit($r . ',1')->find();
        }
        //随机文章不存在，无文章可用
        if (empty($article)) {
            exit('<center>文章加载失败 error:10001</center>');
        }
        $uuid_log = true;
        if (!empty($openid)) {
            $uuid = md5("openid-" . $openid);
            //初始化阅读明细model
            $articleLogModel = new \App\Model\AppArticleLogModel();
            $uuid_log = $articleLogModel->getInfo("uuid = '{$uuid}'");
        }
        //uuid存在&uid存在&微信内访问则增加余额
        if (empty($uuid_log) && !empty($info['uid']) && strpos(I('server.HTTP_USER_AGENT'), "MicroMessenger") !== false) {
            //读取默认奖励金币
            $article_money = C('READ_MONEY');
            if (intval($article['article_money']) > 0) {
                //读取文章奖励金币
                $article_money = $article['article_money'];
            }
            //记录阅读明细
            $articleLogModel->addInfo(array(
                'article_id' => $article['id'],
                'uid' => $info['uid'],
                'gid' => 0,
                'money' => $article_money,
                'ip' => get_client_ip(),
                'user_agent' => I('server.HTTP_USER_AGENT'),
                'uuid' => $uuid,
                'status' => 0,
                'create_time' => time()
            ));
            //增加用户余额,并奖励上级佣金
            $user = new \App\Model\AppUserModel();
            $user->moneyInc(array("id" => $info["uid"]), $article_money, "文章阅读收益", true);
        }
        //根据文章查询广告
        $advertising = M('admin_guanggaoruanwen')
                       ->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaoruanwen.gid")
                       ->where("dz_admin_guanggaoruanwen.wid = '{$article['id']}' AND dz_admin_guanggao.status = 0")
                       ->select();
        //广告不存在，则根据分类查询广告
        if (empty($advertising)) {
            $advertising = M('admin_guanggaofenlei')
                       ->join("LEFT JOIN dz_admin_guanggao ON dz_admin_guanggao.id = dz_admin_guanggaofenlei.gid")
                       ->where("dz_admin_guanggaofenlei.wid = '{$article['category']}' AND dz_admin_guanggao.status = 0")
                       ->select();
        }
        //广告不存在，则查询所有广告
        if (empty($advertising)) {
            $advertising = M('admin_guanggao')->where("type = '0' AND status = '0'")->select();
        }
        if (!empty($advertising)) {
            //查询展示的广告(独立or多个) 代码可优化
            $ad_all = array();
            $ad_use = array();
            foreach ($advertising as $i => $item) {
                $ad_all[$item['display']][] = $item;
            }
            if (!empty($ad_all[0]) && !empty($ad_all[1])) {
                if (rand(0, 1) === 0) {
                    //单独广告展示
                    $ad_use[] = $ad_all[0][rand(0, count($ad_all[0]) - 1)];
                } else {
                    //多条广告同时展示
                    $ad_use = $ad_all[1];
                }
            } elseif (!empty($ad_all[0])) {
                //单独广告展示
                $ad_use[] = $ad_all[0][rand(0, count($ad_all[0]) - 1)];
            } elseif (!empty($ad_all[1])) {
                //多条广告同时展示
                $ad_use = $ad_all[1];
            }
            /*拼接html,广告记录/计费*/
            if (!empty($ad_use)) {
                $adInfoModel = new \App\Model\AdvertisingInfoModel();
                foreach ($ad_use as $i => $item) {
                    $id = $item['id'];
                    $template = M('admin_template')->where("id = '{$item['zsxg']}'")->find();
                    if (!empty($template) && !empty($template['code'])) {
                        //生成点击计费url
                        $url_id = array('url' => $item['url'], 'gid' => $id);
                        $url_id = rc4_base64_encode(json_encode($url_id));
                        $ad_url = C('_URL_')  . U('App/Article/advertising', array('id' => $url_id));
                        //拼接广告模板html
                        $html_text = $template['code'];
                        $html_text = str_replace('{url}', $ad_url, $html_text);
                        $html_text = str_replace('{id}', $item['id'], $html_text);
                        $html_text = str_replace('{title}', $item['title'], $html_text);
                        $html_text = str_replace('{pic}', $item['pic'], $html_text);
                        $html_text = str_replace('{remark}', $item['remark'], $html_text);
                        $html_text = htmlspecialchars_decode($html_text);
                        $ad_html  .= $html_text;
                        if (session(md5($uuid . $id)) == false && $id !== 0) {
                            //展示计费模式
                            if ($item['tgms'] == 3) {
                                //判断消费金额是否超出预算金额
                                if($item['expense'] + $item['money'] > $item['budget']) {
                                    //超出预算,下线广告
                                    M("admin_guanggao")->where("id='{$id}'")->data(array("status" => 1))->save();
                                } else {
                                    //未超出预算,记录消费金额
                                    M("admin_guanggao")->where("id='{$id}'")->setInc("expense", $item['money']);
                                }
                            }
                            //更新广告展示次数
                            session(md5($uuid . $id), true);
                            $adInfoModel->upInfo($id, 'show_num');
                        }
                    }
                }
            }
        }
        //更新文章阅读数量
        if (session($uuid) == false && !empty($article)) {
            //文章阅读数量统计
            M('admin_ruanwen')->where(array("id" => $article['id']))->setInc("read_num", 1);
            //文章阅读数量详细统计
            $article_info = new \App\Model\AppArticleInfoModel();
            $article_info->upInfo('read_num');
            session($uuid, true);
        }
        //解码html
        $article['title'] = htmlspecialchars_decode($article['title']);
        $article['content'] = htmlspecialchars_decode($article['content']);
        //时间戳转换成日期
        $article['create_time'] = date('Y-m-d', $article['create_time']);
        //分享统计id
        $shareId  = array("id" => $article['id']);
        $shareId  = rc4_base64_encode(json_encode($shareId));
        $this->assign('shareId', $shareId);
        $this->assign('article', $article);
        $this->assign('ad_html', $ad_html);
        $this->display('Article/index');
	}

    private function redirectAuth($type)
    {
        //初始化state和回调uri
        session('state', md5(time()));
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $redirect_uri = C('_URL_')  . U('Article/auth_callback', array('uri' => base64_encode($url)));
        $api = array(
            'snsapi_base', //静默
            'snsapi_userinfo' //获取用户信息
        );
        //重定向到微信授权
        redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . C('_APPID_' ) . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=" . $api[$type] . "&state=" . session("state") . "#wechat_redirect", 0);
    }
}