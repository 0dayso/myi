<?php
class Icwebadmin_Model_DbTable_ReplyLog extends Zend_Db_Table_Abstract
{
	protected $_name = 'reply_log';
	
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
		return $rows->toArray();
	}
	public function add($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
	}
}