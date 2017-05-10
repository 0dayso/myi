<?php
class Default_Model_DbTable_Model extends Zend_Db_Table_Abstract
{
	protected $_name;
	
	/**
	 * 构造函数
	 */
	public function __construct($tablename) {
		parent::__construct();
		$this->_name = $tablename;
	}
    public function updateBySql($sql,$prepare=array())
	{
		$db = $this->getAdapter();
		return $db->query($sql,$prepare);
	}
	public function Query($sql,$prepare=array())
	{
		try{
		  $db = $this->getAdapter();
		  $result = $db->query($sql,$prepare);
		  $rows = $result->fetchAll();
		  return $rows;
		} catch (Exception $e) {
			return false;
		}
	}
	public function QueryRow($sql,$prepare=array())
	{
		try{
		$db = $this->getAdapter();
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows[0];
		} catch (Exception $e) {
			return false;
		}
	}
	public function QueryItem($sql,$prepare=array())
	{
		try{
		$db = $this->getAdapter();
		$result = $db->query($sql,$prepare);
		return $result->fetchColumn();
		} catch (Exception $e) {
			return false;
		}
	}
    public function getRowByWhere($where,$order='')
	{
		try{
		$row = $this->fetchRow($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
		} catch (Exception $e) {
			return false;
		}
	}
	public function getRowBySql($sql,$prepare=array())
	{
		try{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows[0];
		} catch (Exception $e) {
			return false;
		}
	}
	public function getAllByWhere($where,$order='')
	{
		try{
		$row = $this->fetchAll($where,$order);
		if(!$row){
			return false;
		}
		return $row->toArray();
		} catch (Exception $e) {
			return false;
		}
	}
	public function getBySql($sql,$prepare=array())
	{
		try{
		$db = $this->getAdapter();
		//'SELECT * FROM example WHERE date > :placeholder', array('placeholder' => '2006-01-01'
		$result = $db->query($sql,$prepare);
		$rows = $result->fetchAll();
		return $rows;
		} catch (Exception $e) {
			return false;
		}
	}
	public function addData($data)
	{
		try{
		$db = $this->getAdapter();
		$db->insert($this->_name , $data);
		return $db->lastInsertId();
		} catch (Exception $e) {
			return false;
		}
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
	public function updateByWhere($data,$where)
	{
		try{
		return $this->update($data, $where);
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 事务开始
	 */
	public function beginTransaction()
	{
		try{
		$db = $this->getAdapter();
		$db->beginTransaction();
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 事务完成
	 */
	public function commit()
	{
		try{
		$db = $this->getAdapter();
		$db->commit();
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 事务回滚
	 */
	public function rollBack()
	{
		try{
		$db = $this->getAdapter();
		$db->rollBack();
		} catch (Exception $e) {
			return false;
		}
	}
}