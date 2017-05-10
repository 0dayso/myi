<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_DefaultlogService
{
	private $_defaultlogModer;
	private $_defaultviewlogModer;
	private $_fun;
	public function __construct()
	{
		$this->_defaultlogModer = new Default_Model_DbTable_DefaultLog();
		$this->_defaultviewlogModer = new Default_Model_DbTable_Model('default_view_log');
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->_fun  = new MyFun();
	}
	/**
	 * 添加日志记录
	 */
	public function addLog($data=array()){
	    try {
            $front = Zend_Controller_Front::getInstance();
		    $add_data = array('session_id'=>session_id(),
				'uid'=>$_SESSION['userInfo']['uidSession'],
				'ip'=>$this->_fun->getIp(),
				'controller'=>$front->getRequest()->getControllerName(),
				'action'=>$front->getRequest()->getActionName(),
				'created'=>time(),
				'log_id'=>$data['log_id'],
				'temp1'=>$data['temp1'],
				'temp2'=>$data['temp2'],
				'temp3'=>$data['temp3'],
				'temp4'=>$data['temp4'],
		    	'temp5'=>$data['temp5'],
				'description'=>$data['description']);
		    return $this->_defaultlogModer->addData($add_data);
	   }catch (Exception $e) {
			return false;
	   }
	}
	/**
	 * 添加点击日志记录
	 */
	public function addViewLog($data=array()){
		try {
			$front = Zend_Controller_Front::getInstance();
			$pagearray = explode('/',$data['page']);
			$add_data = array('session_id'=>session_id(),
					'uid'=>$_SESSION['userInfo']['uidSession'],
					'ip'=>$this->_fun->getIp(),
					'controller'=>$front->getRequest()->getControllerName(),
					'action'=>$front->getRequest()->getActionName(),
					'created'=>time(),
					'siteid'=>$data['siteid'],
					'status'=>$data['status'],
					'name'=>$data['name'],
					'msg'=>$data['msg'],
					'r'=>$data['r'],
					'page'=>$data['page'],
					'page1'=>($pagearray[2]?$pagearray[2]:''),
					'page2'=>($pagearray[3]?$pagearray[3]:''),
					'page3'=>($pagearray[4]?$pagearray[4]:''),
					'agent'=>$data['agent'],
					'ex'=>$data['ex'],
					'text'=>$data['text'],
					'title'=>$data['title'],
					'href'=>$data['href'],
					'target'=>$data['target'],
					'mid'=>$data['mid'],
					'class'=>$data['class'],
					'alt'=>$data['alt'],
					'src'=>$data['src'],
					'rev'=>$data['rev'],
					'rel'=>$data['rel']);
			return $this->_defaultviewlogModer->addData($add_data);
		}catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 获取应用方案历史访问时间
	 */
	public function getVappTime($solid,$nowtype){
		$re = $this->_defaultlogModer->getAllByWhere("temp1='{$solid}' AND temp3='{$nowtype}' AND session_id='".session_id()."'");
		if($re){
			$total = 0;
			foreach($re as $v){
			   $arr    = explode(',',$v['temp4']);
			   if($arr[1] && $arr[0]) $total += $arr[1]-$arr[0];
			}
			return $total;
		}else return 0;
	}
	/**
	 * 获取应用方案历史访问总时间
	 */
	public function getVappTotalTime($solid){
		$re = $this->_defaultlogModer->getAllByWhere("temp1='{$solid}' AND session_id='".session_id()."'");
		if($re){
			$total = 0;
			foreach($re as $v){
				$arr    = explode(',',$v['temp4']);
				if($arr[1] && $arr[0]) $total += $arr[1]-$arr[0];
			}
			return $total;
		}else return 0;
	}
}