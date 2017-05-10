<?php
class Icwebadmin_Model_DbTable_Section extends Zend_Db_Table_Abstract
{
	protected $_name = 'admin_section';
	
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
	public function addSection($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
	}
	public function updateById($data,$id)
	{
		$this->update($data, "section_area_id='{$id}'");
	}
	public function deleteById($id)
	{
		$this->delete("section_area_id='{$id}'");
	}
}