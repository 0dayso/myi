<?php
class Icwebadmin_Service_ScorelogService
{
	private $_defaultmoder;

	public function __construct()
	{
		$this->_defaultmoder     = new Icwebadmin_Model_DbTable_Model('score_log');
	}
	/**
	 * 获取count()行数
	 */
	public function getDefaultRowNum($str='')
	{
		$sqlstr = "SELECT count(id) as num FROM score_log as dl 
		WHERE dl.id!='' {$str} ";
		return $this->_defaultmoder->QueryItem($sqlstr);
	}
	/**
	 * 获取用户操作记录
	 */
	public function getDefaultAll($offset,$perpage,$typestr='',$orderbystr='')
	{
		if(!$orderbystr) $orderbystr = "ORDER BY dl.id DESC";
		$sqlstr = "SELECT dl.*,sap.order_no,u.uname,up.companyname FROM score_log as dl
		LEFT JOIN user as u ON u.uid = dl.uid
		LEFT JOIN user_profile as up ON up.uid = dl.uid
		LEFT JOIN sap_order as sap ON dl.temp5 = sap.salesnumber
		WHERE dl.id!='' {$typestr} {$orderbystr} LIMIT $offset,$perpage";
		return $this->_defaultmoder->Query($sqlstr);
	}
	
}