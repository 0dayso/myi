<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_AdminlogService
{
	private $_adminlogModer;
	private $_fun;
	public function __construct()
	{
		$this->_adminlogModer = new Icwebadmin_Model_DbTable_AdminLog();
		$this->_fun  = new MyFun();
	}
	/**
	 * 添加日志记录
	 */
	public function addLog($data=array()){
	    try {
            $front = Zend_Controller_Front::getInstance();
		    $add_data = array('session_id'=>session_id(),
				'staffid'=>$_SESSION['staff_sess']['staff_id'],
				'ip'=>$this->_fun->getIp(),
				'controller'=>$front->getRequest()->getControllerName(),
				'action'=>$front->getRequest()->getActionName(),
				'created'=>time(),
				'log_id'=>$data['log_id'],
				'temp1'=>$data['temp1'],
				'temp2'=>$data['temp2'],
				'temp3'=>$data['temp3'],
				'temp4'=>$data['temp4'],
				'description'=>$data['description']);
		    return $this->_adminlogModer->addData($add_data);
	   }catch (Exception $e) {
			return false;
	   }
	}
	/**
	 * 添加日志记录
	 */
	public function getLogBySql($sql){
		return $this->_adminlogModer->getBySql($sql);
	}
}