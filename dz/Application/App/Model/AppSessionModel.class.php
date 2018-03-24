<?php
namespace App\Model;

use Think\Model;

class AppSessionModel extends Model
{
    public function getSession($sessionId)
    {
        return $this->where("session_id = '{$sessionId}'")->find();
    }

    public function addSession($data)
    {
        return $this->add($data);
    }

    public function upSession($data)
    {
        $req = $this->where("openid = '{$data['openid']}'")->find();
        if ($req) {
            return $this->where("openid = '{$data['openid']}'")->save($data);
        } else {
            return $this->add($data);
        }
    }
}