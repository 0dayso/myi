<?php 
class Icwebadmin_Model_DbTable_Bom extends Zend_Db_Table_Abstract{
	
	protected $_name = "bom";
	
	public function getAllBoms($offset=0,$perpage=10){
		
		$select  = $this->_db->select();
		$select->from($this->_name,'*')
					//->join('user','user.uid = bom.uid', 'user.uname as user')
					->limit($perpage,$offset);
		$sql = $select->__toString();
		return $this->_db->fetchAll($select);
	}
	
	
	public  function getBomDetailById($id){
		
		$select  = $this->_db->select()->from('bom_detailed')->where("bom_id=$id");
		return $this->_db->fetchAll($select);
		
	}
	public function getTotal(){
		
		return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
	}
	


   public function getBomById($id){
   	
	   	$id = (int)$id;
	   	$row = $this->fetchRow('id='.$id);
	   	return $row->toArray();   	
	   	
   }	
	public function updateBom($id,$data){
		
		return $this->update($data, 'id='.$id);
		
	}

	public function updateBomDetail($id,$data){
	
		return $this->getAdapter()->update('bom_detailed', $data,"bomid =$id");
	
	}	
	
	public function insertBomDetail($id,$data){
	
		return $this->_db->insert('bom_detailed', $data);
	
	}	
	
	public function getUiqBomDetail($id,$part_no){
		
		
		$select  = $this->_db->select()->from('bom_detailed')->where(" bomid=$id and part_no = '$part_no' ");
		return $this->_db->fetchRow($select);
	}
	
	public function deleteBom($id){
		
		return $this->delete('id='.(int)$id);
		
	}
	
	public function deleteBomDetail($id){
	
		return $this->_db->delete('bom_detailed',"bomid = $id");
	
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
	
}