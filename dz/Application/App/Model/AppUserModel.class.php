<?php
namespace App\Model;

use Think\Model;

class AppUserModel extends Model
{

    //字段增加
    public function upInc($where, $type, $num)
    {
        return $this->where($where)->setInc($type, $num);
    }

    //字段减少
    public function upDec($where, $type, $num)
    {
        return $this->where($where)->setDec($type, $num);
    }

    //添加数据
    public function addData($data)
    {
        return $this->add($data);
    }

    //where读取数据
    public function getData($where, $field)
    {
        return $this->field($field)->where($where)->find();
    }

    //根据id读取数据
    public function getInfo($id, $field)
    {
        return $this->field($field)->where("id = '{$id}'")->find();
    }

    //更新数据
    public function upInfo($where, $data)
    {
        return $this->where($where)->save($data);
    }

    //公众号openId读取数据
    public function wechatGetInfo($openid, $field)
    {
        return $this->field($field)->where("openid = '{$openid}' AND status = 0")->find();
    }

    //小程序openId读取数据
    public function miniappGetInfo($openid, $field)
    {
        return $this->field($field)->where("miniapp_openid = '{$openid}' AND status = 0")->find();
    }

    //公众号openId更新数据
    public function wechatUpInfo($openid, $data)
    {
        return $this->where("openid = '{$openid}' AND status = 0")->save($data);
    }

    //手机号更新数据
    public function phoneGetInfo($phone, $field)
    {
        return $this->field($field)->where("phone = '{$phone}' AND status = 0")->find();
    }

    //新增余额+计算佣金
    public function moneyInc($where, $money, $description, $brokerage = false)
    {
        $info = $this->where($where)->find();
        //用户不存在，添加失败
        if (empty($info)) {
            return false;
        }
        //记录收入明细
        $record = new \App\Model\AppMoneyRecordModel();
        $data = array(
            'uid' => $info['id'],
            'money' => $money,
            'description' => $description,
            'before_money' => $info['money'],
            'after_money' => $info['money'] + $money,
            'create_time' => time()
        );
        $record->addInfo($data);
        //增加余额，累计收入余额
        $this->upInc(array("id" => $info['id']), "money", $money);
        $this->upInc(array("id" => $info['id']), "money_total", $money);
        //是否计算佣金,不计算直接返回 true
        if (!$brokerage || $info['pid'] == 0 || $info['pid'] == null) {
            return true;
        }
        //读取公共佣金比例
        $stair = C('stair');
        $second_level = C('second_level');
        //初始化添加佣金列表
        $list = array();
        //读取pid的佣金配置
        $pidRatio = M('home_commission')->where("uid = '{$info['pid']}'")->field("second_level,stair")->find();
        //查询pid的上级信息
        $superior = $this->where("id = '{$info['pid']}'")->field("pid")->find();

        if ($superior['pid'] !== 0 || $superior['pid'] !== null) {
            //pid存在上级，判断pid是否自定义佣金比例 (二级)
            if ($pidRatio && $pidRatio['second_level']) {
                $second_level = $pidRatio['second_level'];
            }
            $list[] = array('uid' => $info['pid'], 'ratio' => $second_level);
            //读取superior的佣金配置
            $req = M('home_commission')->where("uid = '{$superior['pid']}'")->field("stair")->find();
            //判断superior是否自定义佣金比例 (一级)
            if ($req && $req['stair']) {

                $stair = $req['stair'];

            }
            $list[] = array('uid' => $superior['pid'], 'ratio' => $stair);
        } else {
            //pid不存在上级,判断是否自定义佣金比例 (一级)
            if ($pidRatio && !empty($pidRatio['stair'])) {
                $stair = $pidRatio['stair'];
            }
            $list[] = array('uid' => $info['pid'], 'ratio' => $stair);
        }

        //执行添加佣金
        foreach ($list as $item) {
            $com = ($money * 100) * ($item['ratio'] / 100);
            $req = M('app_commission')->data(array('uid' => $item['uid'], 'commission' => $com, 'create_time' => time()))->add();
        }
        return true;
    }

    //减少余额
    public function moneyDec($where, $money)
    {
        $info = $this->where($where)->find();
        //用户不存在，减少失败
        if (empty($info)) {
            return false;
        }
        //减少余额
        return $this->upDec(array("id" => $info['id']), "money", $money);
    }

}