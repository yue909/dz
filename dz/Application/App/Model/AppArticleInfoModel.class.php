<?php
namespace App\Model;

use Think\Model;

class AppArticleInfoModel extends Model
{
    public function upInfo($type)
    {
        $info = $this->where(array("date" => date("Y-m-d"), "date_h" => date("H")))->find();
        if (!empty($info)) {
            return $this->where(array("id" => $info['id']))->setInc($type, 1);
        } else {
            $data = array(
                'date' => date("Y-m-d"),
                'date_h' => date("H"),
                'add_time' => time(),
                $type => 1
            );
            return $this->add($data);
        }
    }
}