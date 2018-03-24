<?php
namespace App\Model;

use Think\Model;

class AppSmsModel extends Model
{
    public function getInfo($phone)
    {
        return $this->where("phone = '{$phone}'")->order('create_time desc')->find();
    }

    public function addInfo($data)
    {
        return $this->add($data);
    }

    public function deleteInfo($phone)
    {
        return $this->where("phone = '{$phone}'")->delete();
    }

    public function errUp($phone)
    {
        return $this->where("phone = '{$phone}'")->setInc('err_num', 1);
    }

    public function useUp($phone)
    {
        return $this->where("phone = '{$phone}'")->save(array('status' => 1));
    }
}