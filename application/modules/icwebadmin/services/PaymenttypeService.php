<?php
class Icwebadmin_Service_PaymenttypeService
{
	private $_moder;
	public function __construct()
	{
		$this->_model = new Icwebadmin_Model_DbTable_Model('payment_type');
	}
	/*
	 * 获取所有
	 */
	public function getAll()
	{
		return $this->_model->getAllByWhere("status='1'");
	}
	/*
	 * 获取支付方式名称
	 */
	public function getPaytypeName()
	{
		$re = array();
		$arr = $this->_model->getAllByWhere("status='1'");
		for($i=0;$i<count($arr);$i++)
		{
			$re[$arr[$i]['type']] = $arr[$i]['name'];
		}
		return $re;
	}
}