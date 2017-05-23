<?php
class Icwebadmin_Model_DbTable_News extends Zend_Db_Table_Abstract
{
	protected $_name = 'news';
	
	
    public function getAll($offset=0,$perpage=10,$where=array())
    {
    	$select = $this->_db->select();
		$select->from(array('a' => $this->_name),              			   '*')
					->joinLeft(array('b'=>'news_type'),'b.id = a.news_type_id','b.news_type')
					->joinLeft(array('c'=>'app_category'),'c.id = a.app_level1','c.name');
		if(!empty($where)){
			 
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
    	$select->order('home DESC')
    	->order('status DESC')
    	->order('published DESC')
    	->order('modified DESC')
    	->limit($perpage,$offset);
    	$sql= $select->__toString();
    	return $this->_db->fetchAll($select);
    }
    
    public function getOneById($id)
    {
    	$select = $this->_db->select();
    	$select->from(array('a' => $this->_name),              			   '*')
    	->joinLeft(array('c'=>'product'),'c.id = a.part_id','c.part_no')
    	->where('a.id ='.$id);
    	return $this->_db->fetchRow($select);    	
    }
    
    public function getTotal($where){
        $select = $this->_db->select();
        $select->from(array('a' => $this->_name),'COUNT(*) AS num');
        if(!empty($where)){
            foreach($where as $w)
            {
                $select->where($w);
            }
        }
        $re = $this->_db->fetchRow($select);
        return $re['num'];
    }    
	public function add($data)
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
	
	public  function deleteById($id)
	{
		$where['id = ?'] = $id;
		return $this->_db->delete($this->_name,$where);
	}
	public function activeById($id,$bind)
	{
		$where['id = ?'] = $id;
		return $this->_db->update($this->_name, $bind,$where);
	}	
}