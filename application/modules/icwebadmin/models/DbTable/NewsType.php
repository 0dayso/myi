<?php
class Icwebadmin_Model_DbTable_NewsType extends Zend_Db_Table_Abstract
{
	protected $_name = 'news_type';
	
    public function getAll($offset=0,$perpage=10,$where=array())
    {
    	$select = $this->_db->select();
		$select->from(array('a' => $this->_name),
                 			   '*');
		if(!empty($where)){
			 
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
		$sql= $select->__toString();
    	$select->order('sort_order DESC')->limit($perpage,$offset);
    	$sql= $select->__toString();
    	return $this->_db->fetchAll($select);
    }
	

}