<?php
namespace App\Model;

use Think\Model;

class AppMoneyRecordModel extends Model
{
    public function addInfo($data)
    {
        $this->add($data);
    }
}