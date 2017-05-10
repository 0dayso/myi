<?php
class Default_Model_DbTable_News extends Zend_Db_Table_Abstract
{
	protected $_name = 'news';
	
	
    public function getAll($offset=0,$perpage=10,$where=array())
    {
    	$select = $this->_db->select();
		$select->from(array('a' => $this->_name),              			   '*')
					->joinLeft(array('b'=>'news_type'),'b.id = a.news_type_id','b.news_type')
					->joinLeft(array('c'=>'app_category'),'c.id = a.app_level1','c.name')
					->where('a.status=1');
		if(!empty($where)){
			 
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
		$sql= $select->__toString();
    	$select->order('published DESC')->limit($perpage,$offset);
    	$sql= $select->__toString();
    	return $this->_db->fetchAll($select);
    }
    
    public function getOneById($id,$status=0)
    {
    	$select = $this->_db->select();
    	$select->from(array('a' => $this->_name),              			   '*')
    	->joinLeft(array('b'=>'app_category'),'b.id = a.app_level1','b.name')
    	->joinLeft(array('c'=>'product'),'c.id = a.part_id','c.part_no')
    	->where('a.id ='.$id);
    	if($status){
    		$select->where('a.status=1');
    	}
    	return $this->_db->fetchRow($select);    	
    }
    
    public function getRelatedNews($id)
    {
    	$select = $this->_db->select();
    	$select->from(array('a' => $this->_name), '*')
    	->where('a.status=1')
    	->where('a.id !='.$id);
    	$select->order('published DESC')->limit(4,0);
    	return $this->_db->fetchAll($select);    	
    }
    public function getTotal(){

    	return  $this->getAdapter()->select()->from($this->_name,'COUNT(*)')->query()->fetchColumn();
    }    
    public function getTotalWhere($where){
    
    	return  $this->getAdapter()->select()->where($where)->from($this->_name,'COUNT(*)')->query()->fetchColumn();
    }
}