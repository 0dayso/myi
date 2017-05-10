<?php
class Icwebadmin_Model_DbTable_Solution extends Zend_Db_Table_Abstract
{
	protected $_name = 'solution';
	
    public function getRowByWhere($where,$order='')
	{
		$row = $this->fetchRow($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function getAllByWhere($where,$order='')
	{
		$row = $this->fetchAll($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function getBySql($sql,$prepare=array())
	{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows;
	}
	public function getByOneSql($sql,$prepare=array())
	{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows[0];
	}
	public function getTotal(){
	
		return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
	}
	public function getAllSolutions($offset=0,$perpage=10){
	
		$select  = $this->_db->select();
		$select->from($this->_name,'solution.*')
		->join('app_category as app1','solution.app_level1 = app1.id','app1.name as appname1')
		->order('home DESC')
		->order('status DESC')
		->order('created DESC')
		->limit($perpage,$offset);
		$sql = $select->__toString();
		return $this->_db->fetchAll($select);
	}
	public function getPartsBySolutionId($id){
	
		$id = (int)$id;
		$row = $this->fetchRow('id='.$id);
		return $row->toArray();
		 
	}
	public function updateSeminar($id,$data){
	
		return $this->update($data, 'id='.$id);
	
	}
	public function addDate($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
}