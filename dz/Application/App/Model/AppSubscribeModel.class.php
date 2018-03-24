<?php
namespace App\Model;

use Think\Model;

class AppSubscribeModel extends Model
{

    public function addInfo($data)
    {
        return $this->add($data);
    }

    public function getInfo($openid, $field)
    {
        return $this->field($field)->where("openid = '{$openid}'")->find();
    }

    public function upInfo($openid, $data)
    {
        return $this->where("openid = '{$openid}'")->save($data);
    }

}