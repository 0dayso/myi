<?php
class Icwebadmin_Service_EmailtypeService
{
	private $_emailtypeModel;
	public function __construct() {
		$this->_emailtypeModel = new Icwebadmin_Model_DbTable_EmailType();
	}
	/**
	 * 获取所有
	 */
	public function getEmailType()
	{
		return $this->_emailtypeModel->getAllByWhere("status=1");
	}
	/**
	 * 更加id获取
	 */
	public function getEmailTypeById($id)
	{
		return $this->_emailtypeModel->getRowByWhere("id= '{$id}' AND status=1");
	}
	/**
	 * 更新byid
	 */
	public function updateById($data,$id)
	{
		return $this->_emailtypeModel->updateById($data, $id);
	}
}