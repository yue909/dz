<?php
namespace GuangGao\Model;

use Think\Model;

/**
* 广告数据
*/
class advertisingInfoModel extends Model
{
    protected $tableName = 'app_advertising_info';

    public function dataSum($where = null, $field)
    {
        if (empty($where)) {
            $num = $this->sum($field);
        } else {
            $num = $this->where($where)->sum($field);
        }
        if (empty($num)) {
            return 0;
        }
        return $num;
    }
}