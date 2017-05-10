<?php
class Default_Service_EventService
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
		$sqlstr = "SELECT e.* FROM event as e WHERE e.eventnumber!='' {$where} LIMIT $offset,$perpage";
		return $this->_model->getBySql($sqlstr);
	}
	/*
	 * 获取总数
	 */
	public function getTotal($where=''){
		$allver = $this->_model->getRowByWhere("SELECT count(e.id) as num FROM event as e WHERE e.eventnumber!='' {$where}");
		return $allver['num'];
	}
	/*
	 * 获取一条记录
	*/
	public function getEvent($where='')
	{
		return $this->_model->getRowByWhere($where);
	}
}