<?php
class Icwebadmin_Service_LogService
{
	private $_defaultmoder;
	private $_defaultviewmoder;
	private $_adminmoder;
	public function __construct()
	{
		$this->_defaultmoder     = new Icwebadmin_Model_DbTable_Model('default_log');
		$this->_defaultviewmoder = new Icwebadmin_Model_DbTable_Model('default_view_log');
		$this->_adminmoder       = new Icwebadmin_Model_DbTable_Model('admin_log');
	}
	/**
	 * 获取count()行数
	 */
	public function getDefaultRowNum($str='')
	{
		$sqlstr = "SELECT count(id) as num FROM default_log as dl 
		WHERE dl.id!='' {$str} ";
		return $this->_defaultmoder->QueryItem($sqlstr);
	}
	/**
	 * 获取用户操作记录
	 */
	public function getDefaultAll($offset,$perpage,$typestr='',$orderbystr='')
	{
		if(!$orderbystr) $orderbystr = "ORDER BY dl.id DESC";
		$sqlstr = "SELECT dl.*,u.uname,up.companyname FROM default_log as dl
		LEFT JOIN user as u ON u.uid = dl.uid
		LEFT JOIN user_profile as up ON up.uid = dl.uid
		WHERE dl.id!='' {$typestr} {$orderbystr} LIMIT $offset,$perpage";
		return $this->_defaultmoder->Query($sqlstr);
	}
	/**
	 * 获取count()行数
	 */
	public function getAdminRowNum($str='')
	{
		$sqlstr = "SELECT count(id) as num FROM admin_log as al
		WHERE al.id!='' {$str} ";
		return $this->_adminmoder->QueryItem($sqlstr);
	}
	/**
	 * 获取用户操作记录
	 */
	public function getAdminAll($offset,$perpage,$typestr='',$orderbystr='')
	{
		if(!$orderbystr) $orderbystr = "ORDER BY al.id DESC";
		$sqlstr = "SELECT al.*,st.* FROM admin_log as al
		LEFT JOIN admin_staff as st ON st.staff_id = al.staffid
		WHERE al.id!='' {$typestr} {$orderbystr} LIMIT $offset,$perpage";
		return $this->_adminmoder->Query($sqlstr);
	}
	/**
	 * 获取count()行数
	 */
	public function getDefaultViewRowNum($str='')
	{
		$sqlstr = "SELECT count(id) as num FROM default_view_log as dl
		WHERE dl.id!='' {$str} ";
		return $this->_defaultviewmoder->QueryItem($sqlstr);
	}
	/**
	* 获取用户操作记录
	 */
	 public function getDefaultViewAll($offset,$perpage,$typestr='',$orderbystr='')
	 {
	 		if(!$orderbystr) $orderbystr = "ORDER BY dl.id DESC";
		$sqlstr = "SELECT dl.*,u.uname,up.companyname FROM default_view_log as dl
			LEFT JOIN user as u ON u.uid = dl.uid
			LEFT JOIN user_profile as up ON up.uid = dl.uid
			WHERE dl.id!='' {$typestr} {$orderbystr} LIMIT $offset,$perpage";
			return $this->_defaultviewmoder->Query($sqlstr);
	 }
}