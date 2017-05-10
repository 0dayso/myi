<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_AddressService
{
	private $_addressModel;
	private $_soaddressModel;
	public function __construct() {
		$this->_addressModel = new Default_Model_DbTable_Address();
		$this->_soaddressModel = new Default_Model_DbTable_OrderAddress();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
	}
	/*
	 * 一个用户最多添加20个记录
	 */
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM address WHERE uid=:uidtmp AND status=1";
		$all = $this->_addressModel->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/**
	 * 获取用户收货地址
	 */
	public function getAddress()
	{
		$sqlstr ="SELECT a.id,a.uid,a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.default,
    	p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area
    	FROM address as a LEFT JOIN province as p
        ON a.province=p.provinceid
        LEFT JOIN city as c
        ON a.city=c.cityid
        LEFT JOIN area as e
        ON a.area = e.areaid
    	WHERE a.uid=:uidtmp AND a.status=1
    	ORDER BY `default` DESC";
		return $this->_addressModel->getBySql($sqlstr, $this->sqlarr);
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
    	WHERE a.uid=:uidtmp AND a.id={$addressid}";
		return $this->_soaddressModel->getByOneSql($sqlstr, $this->sqlarr);
	}
	/**
	 * 获取已经存储的用户提供快递账号
	 */
	public function getExpressAddress()
	{
		$sqlstr ="SELECT a.*
		FROM order_address as a
		WHERE a.uid=:uidtmp AND express_account IS NOT NULL AND express_account!='' ORDER BY created DESC LIMIT 0 , 1";
		return $this->_soaddressModel->getByOneSql($sqlstr, $this->sqlarr);
	}
}