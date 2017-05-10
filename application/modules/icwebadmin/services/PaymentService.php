<?php
class Icwebadmin_Service_PaymentService
{
	private $_moder;
	public function __construct()
	{
		$this->_model = new Icwebadmin_Model_DbTable_Model('payment');
	}
	/*
	 * 获取所有
	 */
	public function getAll()
	{
		return $this->_model->getAllByWhere("status='1'");
	}
}