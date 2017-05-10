<?php
class Icwebadmin_Model_DbTable_Sectionarea extends Zend_Db_Table_Abstract
{
	protected $_name = 'admin_sectionarea';
	
    public function getRowByWhere($where,$order='',$Status=1)
	{
		if($Status==1)
		   $where .=" AND status='{$Status}'";
		$row = $this->fetchRow($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
	}
	public function getAllByWhere($where,$order='',$Status=1)
	{
		if($Status==1)
		   $where .=" AND status='{$Status}'";
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
	public function addArea($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
	public function updateById($data,$id)
	{
		$this->update($data, "staff_area_id='{$id}'");
	}
	public function deleteById($id)
	{
		$this->delete("staff_area_id='{$id}'");
	}
}