<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_AddressService
{
	private $_soaddressModel;
	public function __construct() {
		$this->_soaddressModel = new Icwebadmin_Model_DbTable_OrderAddress();
	}
	/**
	 * 获取已经存储的用户收货地址
	 */
	public function getSoAddress($addressid)
	{
		$sqlstr ="SELECT a.*,
    	p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area
    	FROM order_address as a 
    	LEFT JOIN province as p ON a.province=p.provinceid 
        LEFT JOIN city as c ON a.city=c.cityid 
        LEFT JOIN area as e ON a.area = e.areaid
    	WHERE a.id='{$addressid}'";
		return $this->_soaddressModel->getByOneSql($sqlstr);
	}
	/**
	 * 获取已经存储的用户收货地址
	 */
	public function getSoAddressBySalesnumber($salesnumber)
	{
		$sqlstr ="SELECT a.*,
		p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area
		FROM order_address as a
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid
		WHERE a.salesnumber='{$salesnumber}'";
		return $this->_soaddressModel->getByOneSql($sqlstr);
	}
}