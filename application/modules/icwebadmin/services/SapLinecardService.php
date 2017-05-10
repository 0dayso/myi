<?php
class Icwebadmin_Service_SapLinecardService
{
	private $_model;
	public function __construct()
	{
		$this->_model= new Icwebadmin_Model_DbTable_SapLinecard();
	}
	
	
	/*
	 * 获取总数
	*/
	public function getNum($where=''){
		$allver = $this->_model->getByOneSql("SELECT count(id) as num FROM oa_line WHERE id!='' ".$where);
		return $allver['num'];
	}
	
	public function getById($id)
	{
		 $where   = ' id='.$id;
		return $this->_model->getRowByWhere($where);
	}
	
	/*
	 * 获取记录bysql
	*/
	public function getAllBySql($offset='',$perpage='',$where=''){
		$limit = '';
		if($offset && $perpage) $limit = "LIMIT {$offset},{$perpage}";
		$sqlstr ="SELECT * FROM oa_line WHERE id!='' {$where} {$limit}";
		return	$this->_model->getBySql($sqlstr);
	}
	/*
	 * 更新by id
	*/
	public function updatebyid($data,$id){
		return $this->_model->updateById($data,$id);
	}
	/*
	 * 更新All
	*/
	public function updateall(){
		return $this->_model->updateBySql("UPDATE product_ats SET status=0");
	}

}