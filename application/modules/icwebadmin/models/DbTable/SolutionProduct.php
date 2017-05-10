<?php
class Icwebadmin_Model_DbTable_SolutionProduct extends Zend_Db_Table_Abstract
{
	protected $_name = 'solution_product';
	
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
	public function doSql($sql){
		$db = $this->getAdapter();
		return $db->query($sql);
	}
	public function addDate($data)
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
		$this->update($data, "id='{$id}'");
	}
}