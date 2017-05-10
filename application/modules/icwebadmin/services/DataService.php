<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_DataService
{
	private $_dataModel;
	public function __construct() {
		$this->_dataModel = new Icwebadmin_Model_DbTable_Model("data_apply");
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getApplyNum($typestr='')
	{
		$sqlstr = "SELECT count(spa.id) as num  FROM data_apply as spa
		LEFT JOIN user_profile as up ON up.uid = spa.uid
		WHERE spa.id!='' $typestr";
		return $this->_dataModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	*/
	public function getApply($offset,$perpage,$typestr='')
	{
		$sqlstr ="SELECT spa.*,u.uname,up.companyname,b.name as brandname,
		p.province,c.city,e.area,a.name as sname,a.address,a.zipcode,a.mobile,a.tel
    	FROM data_apply as spa
		LEFT JOIN user as u ON u.uid = spa.uid
		LEFT JOIN user_profile as up ON u.uid = up.uid
		LEFT JOIN brand as b ON spa.brandid = b.id
		LEFT JOIN order_address as a ON spa.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	
		WHERE spa.id!='' $typestr
		ORDER BY spa.id DESC LIMIT $offset,$perpage";
		$re = $this->_dataModel->getBySql($sqlstr);
		return $re;
	}
}