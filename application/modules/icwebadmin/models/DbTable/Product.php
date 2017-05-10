<?php
class Icwebadmin_Model_DbTable_Product extends Zend_Db_Table_Abstract
{
	protected $_name = 'product';
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
	/**
	 * @param int $offset
	 * @param int $perpage
	 * @param mixed $where
	 * @return  mixed
	 */
	public function getByWhere($offset=0,$perpage=10,$where=array())
	{
		$select = $this->_db->select();
		$select->from(array('a' => $this->_name),              			   '*')
		->joinLeft(array('b'=>'brand'),'b.id = a.manufacturer','b.name as brand')
		->joinLeft(array('c'=>'prod_category'),'c.id = a.part_level3','c.name as cat3');;
		if(!empty($where)){
			foreach($where as $w)
			{
				$select->where($w);
			}
		}
		$select
		->order('status DESC')
		->order('modified DESC')
		->limit($perpage,$offset);
		$sql= $select->__toString();
		return $this->_db->fetchAll($select);
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
	public function updateBySql($sql,$prepare)
	{
		$db = $this->getAdapter();
		$db->query($sql,$prepare);
		return true;
	}
	public function addData($data)
	{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
	}
	/**
	 * 添加多条记录
	 */
	public function addDatas($datas)
	{
		if(!empty($datas))
		{
			$fieldstr='(';
			$valuestr='';$i=0;
			foreach($datas as $data)
			{
				if(is_array($data))
				{
					$j=0;
					if($i==0) $valuestr .="(";
					else $valuestr .=",(";
					foreach($data as $key=>$value)
					{
						if($i==0){
							if($j==0) $fieldstr .='`'.$key.'`';
							else $fieldstr .=',`'.$key.'`';
						}
						if($j==0) $valuestr .="'".$value."'";
						else $valuestr .=",'".$value."'";
						$j++;
					}
					$valuestr .=")";
					$i++;
				}else return false;
			}
			$fieldstr .=')';
			$db = $this->getAdapter();
			$db->query("INSERT INTO {$this->_name} {$fieldstr} VALUES {$valuestr};",array());
			return true;
		}else return false;
	}
	public function updateById($data,$id)
	{
		return $this->update($data, 'id = '.(int)$id);
	} 
	public function deleteAlbum($id)
	{
		$this->delete('id='.(int)$id);
	}
}