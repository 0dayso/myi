<?php
class Icwebadmin_Service_EventService
{
	private $_moder;
	public function __construct()
	{
		$this->_model = new Icwebadmin_Model_DbTable_Model('event');
	}
	/*
	 * 获取记录
	*/
	public function getAll($offset,$perpage,$where='')
	{
		$sqlstr = "SELECT e.* FROM event as e WHERE e.eventnumber!='' {$where} ORDER BY e.eventnumber DESC  LIMIT $offset,$perpage";
		return $this->_model->getBySql($sqlstr);
	}
	/*
	 * 获取总数
	 */
	public function getTotal($where=''){
		$allver = $this->_model->getRowBySql("SELECT count(e.id) as num FROM event as e WHERE e.eventnumber!='' {$where}");
		return $allver['num'];
	}
	/*
	 * 获取一条记录
	*/
	public function getEvent($where='')
	{
		return $this->_model->getRowByWhere($where);
	}
	/*
	 * 添加
	 */
	public function addEvent($data)
	{
		return $this->_model->addData($data);
	}
	/*
	 * 编辑
	*/
	public function updateByWhere($data,$where)
	{
		return $this->_model->updateByWhere($data,$where);
	}
	/**
	 * 获取通用器件列表
	 */
	public function getTongYong(){
		$str = $this->_model->QueryItem("SELECT e.data FROM event as e WHERE e.id='3' LIMIT 1");
		preg_match_all('/([\'"])([^\'"\.]*?)\1/',$str,$match);
		if($match[2][0]) return explode(',',$match[2][0]);
		elseif($str) return explode(',',$str);
		else return array();
	}
}