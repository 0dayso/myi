<?php
class Default_Service_FriendshipService
{

	private $_ftypeModel;
	private $_flinkModel;
	public function __construct() {

		$this->_ftypeModel = new Default_Model_DbTable_Model("friendship_type");
		$this->_flinkModel = new Default_Model_DbTable_Model("friendship_link");
	}
	/**
	 * 获取推荐到首页的友情链接
	 */
	public function getHomeLink()
	{
		$sqlstr = "SELECT fl.* FROM friendship_link as fl 
		LEFT JOIN friendship_type as ft ON ft.id=fl.type 
		WHERE fl.status=102 AND fl.home=1 AND ft.status=1 ORDER BY fl.displayorder ASC,fl.id ASC";
		return $this->_flinkModel->getBySql($sqlstr);
	}
}