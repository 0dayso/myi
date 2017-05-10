<?php
class Icwebadmin_Service_FriendshipService
{

	private $_ftypeModel;
	private $_flinkModel;
	public function __construct() {

		$this->_ftypeModel = new Default_Model_DbTable_Model("friendship_type");
		$this->_flinkModel = new Default_Model_DbTable_Model("friendship_link");
	}
	/**
	 * 获取友情链接分类
	 */
	public function getAllType(){
		return $this->_ftypeModel->getAllByWhere("id!=''","displayorder ASC");
	}
	/**
	 * 获取友情链接总数
	 */
	public function getTotal($where='')
	{
		return $this->_flinkModel->QueryItem("SELECT count(fl.id) as num FROM friendship_link as fl WHERE fl.id!='' {$where}");
	}
	/**
	 * 获取友情链接记录
	*/
	public function getAllLinks($offset,$perpage,$where='')
	{
		$sqlstr = "SELECT fl.* FROM friendship_link as fl WHERE fl.name!='' {$where}
		ORDER BY fl.displayorder ASC LIMIT $offset,$perpage";
		return $this->_flinkModel->getBySql($sqlstr);
	}
	/**
	 * 获取友情链接记录
	 */
	public function getLink($id)
	{
		$sqlstr = "SELECT fl.*,ft.name as typename FROM friendship_link as fl 
		LEFT JOIN friendship_type as ft ON ft.id=fl.type
		WHERE fl.name!='' AND fl.id='$id'";
		return $this->_flinkModel->QueryRow($sqlstr);
	}
}