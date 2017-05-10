<?php
class Icwebadmin_Model_DbTable_Inquiry extends Zend_Db_Table_Abstract
{
	protected $_name = 'inquiry';
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
	public function deleteAlbum($id)
	{
		$this->delete('id='.(int)$id);
	}

	//update by micheal
	public function getAllInquiries($offset=0,$perpage=10){
	
		$q = "select b.part_no as product, a.*,c.uname as user  from inquiry a
		left join product b on b.id = a.part_id
		left join user c on c.uid = a.uid LIMIT $offset,$perpage
		";
		$db = $this->getAdapter();
		$res = $db->query($q);
		return $res->fetchAll();
	}
	
	public function getTotal(){
	    return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
	}
	
	public function getInquiryById($id){

		$row = $this->fetchRow("id='$id'");
		return $row->toArray();
	
	}
	
	public function updateInquiry($id,$data){
	
	return $this->update($data, "id='$id'");
	
	}	
}