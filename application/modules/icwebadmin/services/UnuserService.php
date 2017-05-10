<?php
class Icwebadmin_Service_UnuserService
{
	private $_unuerModer;
	public function __construct()
	{
		$this->_unuerModer = new Icwebadmin_Model_DbTable_Unuser();
	}
	/**
	 * 获取数量
	 */
	public function getNum($where="un_uid!=''")
	{
		$sqlstr = "SELECT count(un_uid) as allnum FROM un_user as u WHERE u.companyname!='' AND {$where}";
		$allnumarr = $this->_unuerModer->getBySql($sqlstr,$this->sqlarr);
		return $allnumarr[0]['allnum'];
	}
	/*
	 * 获取记录
	*/
	public function getAll($offset,$perpage,$where=" u.un_uid!=''")
	{
		$sqlstr ="SELECT u.*
    	FROM  un_user as u 
        WHERE u.companyname!='' AND  $where
    	ORDER BY u.status DESC LIMIT $offset,$perpage";
		return $this->_unuerModer->getBySql($sqlstr);
	}
	/**
	 * 获取用户资料
	 */
	public function getUserByid($unuid)
	{
		return $this->_unuerModer->getRowByWhere("un_uid = '{$unuid}'");
	}
}