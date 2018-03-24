<?php
namespace App\Model;

use Think\Model;

class AppArticleLogModel extends Model
{
     public function addInfo($data)
     {
          return $this->add($data);
     }

     public function getInfo($where, $field = "")
     {
          return $this->field($field)->where($where)->find();
     }
}