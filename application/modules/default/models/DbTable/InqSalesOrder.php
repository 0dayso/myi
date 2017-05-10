<?php
class Default_Model_DbTable_InqSalesOrder extends Zend_Db_Table_Abstract
{
	protected $_name = 'inq_sales_order';
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
	public function updateBySql($sql,$prepare=array())
	{
		$db = $this->getAdapter();
		return $db->query($sql,$prepare);
	}
	/**
	 * 事务开始
	 */
	public function beginTransaction()
	{
		$db = $this->getAdapter();
		$db->beginTransaction();
	}
	/**
	 * 事务完成
	 */
	public function commit()
	{
		$db = $this->getAdapter();
		$db->commit();
	}
	/**
	 * 事务回滚
	 */
	public function rollBack()
	{
		$db = $this->getAdapter();
		$db->rollBack();
	}
}