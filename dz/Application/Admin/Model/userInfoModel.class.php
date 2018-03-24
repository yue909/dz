<?php
namespace Admin\Model;

use Think\Model;

/**
* app_user
*/
class userInfoModel extends Model
{
    protected $tableName = 'app_user';

    public function dataCount($where = null)
    {
        if (empty($where)) {
            return $this->count('id');
        } else {
            return $this->where($where)->count('id');
        }
    }

    public function dataSum($where = null, $field)
    {
        if (empty($where)) {
            return $this->sum($field);
        } else {
            return $this->where($where)->sum($field);
        }
    }
}