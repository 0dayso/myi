<?php
class Default_Model_DbTable_User extends Zend_Db_Table_Abstract
{
	protected $_name = 'user';
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
	public function getRowBySql($sql,$prepare)
	{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows[0];
	}
	public function getByName($uname)
	{
		$row = $this->fetchRow("uname = '{$uname}'");
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function getByEmail($email)
	{
		$row = $this->fetchRow("email = '{$email}'");
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function addUser($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
	public function updateByUid($data,$uid)
	{
		$this->update($data, 'uid = '.(int)$uid);
	} 
	public function updateByUname($data,$uname)
	{
		$this->update($data, "uname = '{$uname}'");
	}
	public function deleteAlbum($id)
	{
		$this->delete('id='.(int)$id);
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