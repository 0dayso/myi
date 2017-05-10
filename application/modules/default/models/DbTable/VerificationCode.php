<?php
class Default_Model_DbTable_VerificationCode extends Zend_Db_Table_Abstract
{
	protected $_name = 'verification_code';

	public function addCode($data)
	{
		$this->insert($data);
	}
	public function getByName($uname)
	{
		$row = $this->fetchRow("uname = '{$uname}'");
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
    public function updateById($id,$data)
	{
		$this->update($data, 'id = '.(int)$id);
	} 
	public function getRowByWhere($where,$order='')
	{
		$row = $this->fetchRow($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function getBySql($Sql,$order='')
	{
		$row = $this->fetchRow($Sql,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function QueryItem($sql,$prepare=array())
	{
		try{
			$db = $this->getAdapter();
			$result = $db->query($sql,$prepare);
			return $result->fetchColumn();
		} catch (Exception $e) {
			return false;
		}
	}
}