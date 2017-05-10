<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_AjaximService
{
	private $_servicelistsModel;
	public function __construct() {
		$this->_servicelistsModel = new Icwebadmin_Model_DbTable_Model("ajaxim_servicelists");
	}
	/**
	 * 获取在线支持客服
	 */
	public function getServicelists(){
		$sqlstr = "SELECT sl.*,ag.groupname,st.lastname,st.firstname
	    FROM ajaxim_servicelists as sl
		LEFT JOIN ajaxim_group AS ag ON sl.group_id = ag.id
		LEFT JOIN admin_staff AS st ON st.staff_id  = sl.staffs";
		return $this->_servicelistsModel->Query($sqlstr);
	}
	/**
	 * 获取在线客户by id
	 */
	public function getServiceByid($id){
		$sqlstr = "SELECT sl.*,ag.groupname,st.lastname,st.firstname
	    FROM ajaxim_servicelists as sl
		LEFT JOIN ajaxim_group AS ag ON sl.group_id = ag.id
		LEFT JOIN admin_staff AS st ON st.staff_id  = sl.staffs
	    WHERE sl.id='{$id}'";
		return $this->_servicelistsModel->QueryRow($sqlstr);
	}
}