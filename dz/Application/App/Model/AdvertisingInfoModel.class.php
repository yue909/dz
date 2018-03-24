<?php
namespace App\Model;

use Think\Model;

class AdvertisingInfoModel extends Model
{
	protected $tableName = 'app_advertising_info';

	public function upInfo($gid, $type)
	{
		$info = $this->where(array("gid" => $gid, "date" => date("Y-m-d"), "date_h" => date("H")))->find();
		if (!empty($info)) {
			return $this->where(array("id" => $info['id']))->setInc($type, 1);
		} else {
			$data = array(
				'gid' => $gid,
				'date' => date("Y-m-d"),
				'date_h' => date("H"),
				'add_time' => time(),
				$type => 1
			);
			return $this->add($data);
		}
	}

}