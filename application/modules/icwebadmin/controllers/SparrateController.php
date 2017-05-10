<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_SparrateController extends Zend_Controller_Action
{
	private  $_rateModel;
	private $_adminlogService;
    public function init(){
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
    	$this->Section_Area_ID = $this->view->Section_Area_ID = $controllerArray[1];
    	$this->Staff_Area_ID   = $this->view->Staff_Area_ID = $controllerArray[2];
    	
    	/*************************************************************
    	 ***		创建一些通用url             ***
    	**************************************************************/
    	$this->indexurl = $this->view->indexurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}";
    	$this->addurl   = $this->view->addurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/add";
    	$this->editurl  = $this->view->editurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/edit";
    	$this->deleteurl= $this->view->deleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delete";
    	$this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	
    	$this->_rateModel = new Default_Model_DbTable_Rate();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
       $this->view->rateArr = $this->_rateModel->getAllByWhere("status='1'");
    }
    /*
     * 添加汇率
    */
    public function addAction(){
    $this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$currency    = $this->filter->pregHtmlSql($formData['currency']);
    		$to_currency = $this->filter->pregHtmlSql($formData['to_currency']);
    		$rate_value = $this->filter->pregHtmlSql($formData['rate_value']);
    		$error = 0;$message='';
    		if(empty($currency) || empty($to_currency) || empty($rate_value)){
    			$message ='信息不能为空。<br/>';
    			$error++;
    		}
    		if(!$this->filter->checkUpper($currency) || !$this->filter->checkUpper($to_currency)){
    			$message .='汇率标识必须为大写字母。<br/>';
    			$error++;
    		}
    		if(!is_numeric($rate_value)){
    			$message .='汇率必须为数字。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$data = array('currency'=>$currency,
    					'to_currency'=>$to_currency,
    					'rate_value'=>$rate_value,
    					'created_by'=>$_SESSION['staff_sess']['staff_id'],
    					'created'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id'],
    					'modified'=>time());
    			$newid = $this->_rateModel->addData($data);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'添加汇率成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    /*
     * 更新汇率
     */
    public function editAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    	$post = $this->getRequest()->getPost();
    	$id = (int) $this->_getParam('id');
    	if(is_numeric($post['result'])){
    		$data = array('rate_value'=>$post['result'],
    				'modified_by'=>$_SESSION['staff_sess']['staff_id'],
    				'modified'=>time());
    		$re = $this->_rateModel->update($data, "id = '$id'");
    		if($re){
    		  //日志
    		  $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更新汇率成功'));
    		   echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'更新成功。'));
    		   exit;
    		}
    	}
        echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'更新失败。'));
    	exit;
    }
}