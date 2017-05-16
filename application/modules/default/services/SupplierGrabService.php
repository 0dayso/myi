<?php
class Default_Service_SupplierGrabService
{
	private $_supplierGrabServiceModer;

	public function __construct()
	{
		$this->_supplierGrabServiceModer = new Default_Model_DbTable_SupplierGrab();
		
	}
	
	/*
	 * 获取可用的供应商
	*/
	public function getSupplierGrab()
	{
		$sqlstr = "SELECT * FROM sx_supplier_grab WHERE state='1' ORDER BY displayorder DESC";
		$arr = $this->_supplierGrabServiceModer->getBySql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $arr;
	}
	/*
	 * 获取供应商
	 */
	public function getOneSupplierGrab($id)
	{
		$sqlstr = "SELECT * FROM sx_supplier_grab WHERE id = '$id' AND state='1'";
		$arr = $this->_supplierGrabServiceModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $arr;
	}
}