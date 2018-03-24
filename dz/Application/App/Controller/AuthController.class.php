<?php
namespace App\Controller;

use Think\Controller;

class AuthController extends Controller
{
	public function _initialize()
	{
		//微信授权登录回调
		if (ACTION_NAME == 'auth_callback') {
			//获取回调state和code
			$data = array(
				'code' => I('get.code'),
			    'state' => I('get.state'),
				'type' => I('get.type'),
				'uri' => base64_decode(I('get.uri')),
			);
			//判断state,code值
			if (!empty($data['state']) && !empty($data['code']) && $data['state'] == session("state")) {
				//获取网页授权access_token & openid
				$aUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . C('_APPID_' ). "&secret=" . C('_APPSECRET_') . "&code=" . $data['code'] . "&grant_type=authorization_code";
				$access_token = getHttp($aUrl);
				if ($access_token && $access_token['openid']) {
					session('openID', $access_token['openid']);
					//是否获取用户信息
					if ($data['type'] == 1) {
						$getUserInfoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token['access_token'] . "&openid=" . $access_token['openid'] . "&lang=zh_CN";
						$userInfo = getHttp($getUserInfoUrl);
						if ($userInfo) {
							session('userInfo', $userInfo);
						}
					}
					//重定向到登录前页面
				    redirect($data['uri'], 0);
				}
				exit("授权错误，access_token获取失败！");
			} else {
				exit("授权错误");
			}
		}
		//短信登录回调
		if (ACTION_NAME == 'sms') {
	        $type = I('get.type');
	        $debug = C('SMS_DEBUG');
	        if ($type == 'send') {
	            /*//人机验证
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
	            if ($luotest_check['code'] !== 0) {
	                $data = array(
	                    'code' => -1,
	                    'msg' => '人机验证无效,请重试!',
	                    'data' => array()
	                );
	               $this->ajaxReturn($data);
	            }*/
	            //aes解码post提交数据
                $info = $this->decrypt(I("post.i"), cookie('pc'));
	            //获取手机号
	            $phone = $info['phone'];
	            //查询是否黑名单
				$blocked = M('app_user')->where("phone = '" . $phone . "' and status = '2'")->find();
				if ($blocked) {
					$data = array(
	                    'code' => -1,
	                    'msg' => '您的账号(' . $phone . ')已被禁止登录.',
	                    'data' => array()
	                );
	               $this->ajaxReturn($data);
				}
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
	            //aes解码post提交数据
	            $info = $this->decrypt(I("post.i"), cookie('pc'));
	            $phone = $info['phone'];
	            $code = $info['code'];
	            $data = session('smsInfo');
	            if ($data && $data['phone'] == $phone && $data['code'] == $code && $data['time'] > time() || $debug) {
		            //根据手机号查询用户数据
					$user = M('app_user')->where("phone = '" . $phone . "' and status = '0'")->find();
					if (!$user) {
						//首次登录 将信息存入数据库
						$data = array(
							'phone' => $phone,
							'username' => '普通用户',
							'avatar' => 'https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/avatar.jpeg',
							'money' => 0,
							'create_time' => time(),
							'update_time' => time()
						);
						$data['id'] = M('app_user')->add($data);
						session('userInfo', $data);
					} else {
						session('userInfo', $user);
					}
	                $data = array(
	                    'code' => 0,
	                    'msg' => "登录成功！",
	                    'data' => array()
	                );
	            } else {
	                $data = array(
	                    'code' => -1,
	                    'msg' => "短信验证码错误！",
	                    'data' => array()
	                );
	            }
	            $this->ajaxReturn($data);
	        }
		}

		if (strpos(I('server.HTTP_USER_AGENT'), "MicroMessenger") !== false) {
			//使用微信授权登录
			//$_SESSION['openID'] = null; //调试代码
			$openID = session('openID');
			$userInfo = session('userInfo');
			if (empty($openID)) {
				//获取openID
				$this->redirectAuth(0);
			} else {
				//根据openID查询用户数据
				//$user = M('app_user')->where("openid = '" . session('openID') . "' and status = '0'")->find();
				$user = M('app_user')->where("openid = '" . session('openID') . "'")->find();
				if (!$user) {
					if (!empty($userInfo)) {
						//首次登录 2.将信息存入数据库
						$data = array(
							'openid' => $openID,
							'username' => $userInfo['nickname'],
							'avatar' => substr($userInfo['headimgurl'], 0, strlen($userInfo['headimgurl']) - 1) . '132',
							'money' => 0,
							'create_time' => time(),
							'update_time' => time()
						);
						$data['id'] = M('app_user')->add($data);
						session('userInfo', $data);
					} else {
						//首次登录 1.获取用户信息
						$this->redirectAuth(1);
					}
				} else {
					if ($user['status'] == 0) {
						session('userInfo', $user);
					} else {
						$this->display('Index/blocked');
				        exit();
					}
				}
			}
		} else {
			//H5 手机号登录
			$userInfo = session('userInfo');
			if (empty($userInfo)) {
				$iv = md5(time() . rand(1000, 9999));
				cookie('pc', $iv);
				$this->display('Auth/login.v3');
				exit();
			}
		}
	}

	private function redirectAuth($type)
	{
		//初始化state和回调uri
		session('state', md5(time()));
		$redirect_uri = C('_URL_')  . U('Auth/auth_callback', array('uri' => base64_encode(C('_URL_')  . __ACTION__ . '.html'), 'type' => $type));
		$api = array(
			'snsapi_base', //静默
			'snsapi_userinfo' //获取用户信息
		);
		//重定向到微信授权
		redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . C('_APPID_' ) . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=" . $api[$type] . "&state=" . session("state") . "#wechat_redirect", 0);
	}

	private function decrypt($encryptStr, $key) {
        $localIV = substr($key, 0, 32);
        $encryptKey = substr($key, 0, 32);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $encryptedData = base64_decode($encryptStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);
        $encryptedData = substr($encryptedData, 0, -4);
        $encryptedData = json_decode($encryptedData, true);
        if (!is_array($encryptedData)) {
        	$data = array(
                'code' => -1,
                'msg' => '请求超时,请联系客服解决.',
                'data' => array()
            );
           $this->ajaxReturn($data);
        } else {
        	return $encryptedData;
        }
    }

}

?>