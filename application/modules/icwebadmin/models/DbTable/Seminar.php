<?php 
class Icwebadmin_Model_DbTable_Seminar extends Zend_Db_Table_Abstract{
	
	protected $_name = "seminar";
	
	public function getAllSeminars($offset=0,$perpage=10,$where=array()){
		
		$select  = $this->_db->select();
		$select->from($this->_name,'*')
					->join('app_category','seminar.app_level1 = app_category.id','app_category.name as category');
		if(!empty($where)){
				
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
		$select		
		->order('home DESC')
		->order('type DESC')
		->order('created DESC')
		->limit($perpage,$offset);
		$sql = $select->__toString();
		return $this->_db->fetchAll($select);
	}
	
	public function getTotal(){
		
		return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
	}
	
	// get detail row, join seminar and app_category
	public function getSeminarDetailById($id){
		
		$select  = $this->_db->select();
		$select->from($this->_name,'*')
		->join('app_category','seminar.app_level1 = app_category.id','app_category.name as category')
		->where("seminar.id=$id");
		$sql = $select->__toString();
		return $this->_db->fetchAll($select);
	}
	public function addDate($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
   public function getSeminarById($id){
   	
	   	$id = (int)$id;
	   	$row = $this->fetchRow('id='.$id);
	   	return $row->toArray();   	
	   	
   }	
	public function updateSeminar($id,$data){
		return $this->update($data, 'id='.$id);
		
	}
	
	public function deleteSeminar($id){
		return $this->delete('id='.(int)$id);
	}
	
	public function getBySql($sql,$prepare)
	{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows;
	}
	
	//public function getBySql();
	
}