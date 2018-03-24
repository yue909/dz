<?php
namespace Admin\Model;

use Think\Model;

/**
* 文章数据
*/
class articleInfoModel extends Model
{
    protected $tableName = 'app_article_info';

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