<?php
class Default_Model_DbTable_Favorites extends Zend_Db_Table_Abstract
{
	protected $_name = 'favorites';
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
	public function addData($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
	public function updateById($data,$id)
	{
		$this->update($data, 'id = '.(int)$id);
	} 
	public function updateBySql($sql,$prepare)
	{
		$db = $this->getAdapter();
		$db->query($sql,$prepare);
		return true;
	}
}