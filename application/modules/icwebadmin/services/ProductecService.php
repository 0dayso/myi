<?php
class Icwebadmin_Service_ProductecService
{
	private $_prodatsModer;
	public function __construct()
	{
		$this->_prodatsModer = new Icwebadmin_Model_DbTable_ProductEc();
	}
	/*
	 * 获取总数
	*/
	public function getNum($where=''){
		$allver = $this->_prodatsModer->getByOneSql("SELECT count(id) as num FROM ec_inventory WHERE id!='' ".$where);
		return $allver['num'];
	}
	
	public function getById($id)
	{
		 $where   = ' id='.$id;
		return $this->_prodatsModer->getRowByWhere($where);
	}
	
	/*
	 * 获取记录bysql
	*/
	public function getAllBySql($offset='',$perpage='',$where=''){
		$limit = '';
		if($offset && $perpage) $limit = "LIMIT {$offset},{$perpage}";
		$sqlstr ="SELECT * FROM ec_inventory WHERE id!='' {$where} {$limit}";
		return	$this->_prodatsModer->getBySql($sqlstr);
	}
	/*
	 * 更新by id
	*/
	public function updatebyid($data,$id){
		return $this->_prodatsModer->updateById($data,$id);
	}
	/*
	 * 更新All
	*/
	public function updateall(){
		return $this->_prodatsModer->updateBySql("UPDATE product_ats SET status=0");
	}

}