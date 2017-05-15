<?php
class Default_Model_DbTable_SupplierGrab extends Zend_Db_Table_Abstract
{
	protected $_name = 'sx_supplier_grab';
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
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows;
	}
	public function getByOneSql($sql,$prepare=array())
	{
		$db = $this->getAdapter();
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows[0];
	}
	public function addData($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
}