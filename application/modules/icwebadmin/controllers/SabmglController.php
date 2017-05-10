<?php
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_SabmglController extends Zend_Controller_Action
{
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
    	//加载类
    	$this->department = new Icwebadmin_Model_DbTable_Department();
    }
    public function indexAction(){
    	$this->view->Department=$this->department->getAllByWhere("department_id!=''");
    }
    //添加
    public function addAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$filter = new MyFilter();
    		$formData      = $this->getRequest()->getPost();
    		$Department_ID = $filter->pregHtmlSql($formData['Department_ID']);
    		$Department    = $filter->pregHtmlSql($formData['Department']);
    		$error = 0;$message='';
    		if(empty($Department_ID)){
    			$message ='请填写部门编号。<br/>';
    			$error++;
    		}
    		if(empty($Department)){
    			$message .='请填写部门名称。<br/>';
    			$error++;
    		}
    		if(!$filter->checkUpper($Department_ID)){
    			$message .='部门编号必须为大写字母。<br/>';
    			$error++;
    		}
    		if(!$filter->checkLength($Department_ID,2,4)){
    			$message .='部门编号长度必须为2-4。<br/>';
    			$error++;
    		}
    		$department = new Icwebadmin_Model_DbTable_Department();
    		$redep1 = $department->getRowByWhere(" department_id='{$Department_ID}'");
    		$redep2 = $department->getRowByWhere(" department='{$Department}'");
    		if(!empty($redep1)){
    			$message .='部门编号已经存在。<br/>';
    			$error++;
    		}
    		if(!empty($redep2)){
    			$message .='部门名称已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    		    echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    		    exit;
    		}else{
    			$department->addDepartment(array('department_id'=>$Department_ID,'department'=>$Department,'updatedate'=>date('Y-m-d H:i:s')));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    //编辑
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	$DepID   =  $_GET['DepID'];
    	$department   = new Icwebadmin_Model_DbTable_Department();
    	$reArray =$department->getRowByWhere("department_id='{$DepID}'");
    	if($reArray){
    		$this->view->Department_ID = $reArray['department_id'];
    		$this->view->Department    = $reArray['department'];
    	}
    	if($this->getRequest()->isPost()){
    		$filter = new MyFilter();
    		$formData      = $this->getRequest()->getPost();
    		$Department_ID = $filter->pregHtmlSql($formData['Department_ID']);
    		$Department    = $filter->pregHtmlSql($formData['Department']);
    		$error = 0;$message='';
    		
    		if(empty($Department)){
    			$message .='请填写部门名称。<br/>';
    			$error++;
    		}
    		$redep = $department->getRowByWhere(" department_id!='{$Department_ID}' AND department='{$Department}'");
    		if(!empty($redep)){
    			$message .='部门名称已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$department->updateByDep(array('department'=>$Department,'updatedate'=>date("Y-m-d H:i:s")),$Department_ID);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    }
    //删除
	public function deleteAction(){
		$this->_helper->layout->disableLayout();
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
			exit;
		}
		if($this->getRequest()->isPost()){
			$formData      = $this->getRequest()->getPost();
			$Department_ID = $formData['ID'];
			$staff = new Icwebadmin_Model_DbTable_Staff();
	        $flag=$staff->getRowByWhere("department_id='{$Department_ID}' AND status = '1'");
			if(!empty($flag)) {
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>"该部门有用户在使用，不能删除。"));
				exit;
			}else 
			{
				$department = new Icwebadmin_Model_DbTable_Department();
				$department->deleteByDep($Department_ID);
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"删除成功。"));
			    exit;
			}
		}
	}
}