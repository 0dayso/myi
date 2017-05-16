<?php
class Icwebadmin_Model_DbTable_SupplierGrab extends Zend_Db_Table_Abstract
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
	public function getOneById($id)
	{
		$select = $this->_db->select();
		$select->from(array('a' => $this->_name),'*')
		->where('a.id ='.$id);
		return $this->_db->fetchRow($select);
	}
	public function addData($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
	public  function  updateById($data,$id)
	{
		$where['id = ?'] = $id;
		return  $this->_db->update($this->_name, $data,$where);
	}
	public function getTotal(){
	
		return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
	}
	public function getAll($offset=0,$perpage=10,$where=array(),$orderBy)
	{
		$select = $this->_db->select();
		$select->from(array('a' => $this->_name),'*');
		if(!empty($where)){
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
		$select->order(" state ASC")
		->order($orderBy)
		->limit($perpage,$offset);
		$sql= $select->__toString();
		return $this->_db->fetchAll($select);
	}
}