<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_OlabApplController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
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
    	$this->areaService = new Icwebadmin_Service_AreaService();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    }
    public function indexAction(){
    	$labService = new Icwebadmin_Service_LabService();
    	//选择不同的类型
    	$typeArr =array('wait','pass','com','notpass');
    	$typetmp = '';
    	if(isset($_GET['type'])) $typetmp = $_GET['type'];
    	if(!in_array($typetmp, $typeArr)){
    		$this->view->type = 'wait';
    	}else{
    		$this->view->type = $typetmp;
    	}
    	$waitsql     = " AND la.status='100' ";
    	$passsql     = " AND la.status='201'";
    	$comsql      = " AND la.status='202'";
    	$notpasssql  = " AND la.status='401'";
    	
    	 
    	$this->view->waittotal    = $labService->getNum($waitsql);
    	$this->view->passtotal    = $labService->getNum($passsql);
    	$this->view->comtotal     = $labService->getNum($comsql);
    	$this->view->notpasstotal = $labService->getNum($notpasssql);
    	$sql = '';
    	if($this->view->type == 'wait') {
    		$total = $this->view->waittotal;
    		$sql   = $waitsql;
    	}elseif($this->view->type == 'pass') {
    		$total = $this->view->passtotal;
    		$sql   = $passsql;
    	}elseif($this->view->type == 'com') {
    		$total = $this->view->comtotal;
    		$sql   = $comsql;
    	}elseif($this->view->type == 'notpass') {
    		$total = $this->view->notpasstotal;
    		$sql   = $notpasssql;
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->applyall = $labService->getRecord($offset,$perpage,$sql);
    	 
    	//实验器材
    	$this->view->instrument = $labService->getInst();
    	//
    	$this->view->room = array();
    	$room = $labService->getRoom();
    	foreach($room as $rv){
    		$this->view->room[$rv['id']] = $rv['name'];
    	}
    }
    /**
     * 审批
     */
    public function approvalAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$data = array();
    		$id           = $formData['id'];
    		$status       = $formData['status'];
    		$help_name    = $this->_filter->pregHtmlSql($formData['help_name']);
    		$help_dep     = $this->_filter->pregHtmlSql($formData['help_dep']);
    		$help_tel     = $this->_filter->pregHtmlSql($formData['help_tel']);
    		$help_email   = $this->_filter->pregHtmlSql($formData['help_email']);
    		$remark       = $this->_filter->pregHtmlSql($formData['remark']);
    		$this->labModel = new Icwebadmin_Model_DbTable_Model('lab_apply');
    	
    		$re = $this->labModel->update(array('help_name'=>$help_name,
    				'help_dep'=>$help_dep,
    				'help_tel'=>$help_tel,
    				'help_email'=>$help_email,
    				'remark'=>$remark,
    				'status'=>$status,
    				'modify'=>time()), "id='$id'");
    		if($re){	//记录日志
    			if($status==201 && !empty($help_email)){
    				//邮件通知
    				$labService = new Icwebadmin_Service_LabService();
    				$labService->mailalert($id,$help_name,$help_email);
    				//客户
    				$labService->mailalertToUser($id,'pass');
    			}else{
    				//邮件通知
    				$labService = new Icwebadmin_Service_LabService();
    				//客户
    				$labService->mailalertToUser($id,'nopass');
    			}
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$id,'temp3'=>$status,'temp4'=>'审批实验室申请成功。'.($status==201?'通过':'不通过')));
    			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'提交成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$id,'temp4'=>'审批实验室申请失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'提交失败'));
    			exit;
    		}
    	}
    	$labService = new Icwebadmin_Service_LabService();
    	$this->view->id = $_GET['id'];
    	$this->view->record = $labService->getRecord(0,1," AND la.id = ".$this->view->id);
    }
    /**
     * 审批
     */
    public function reportAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$data = array();
    		$id           = $formData['id'];
    		$oa_project           = $formData['oa_project'];
    		$oa_project_name           = $formData['oa_project_name'];
    		$project_des           = $formData['project_des'];
    		$expected_time          = $formData['expected_time'];
    		$test_case       = $this->_filter->pregHtmlSql($formData['test_case']);
    		$followup       = $this->_filter->pregHtmlSql($formData['followup']);
    		$wish       = $this->_filter->pregHtmlSql($formData['wish']);
    		$this->labModel = new Icwebadmin_Model_DbTable_Model('lab_apply');
    		 
    		$re = $this->labModel->update(array('test_case'=>$test_case,
    				'followup'=>$followup,
    				'wish'=>$wish,
    				'oa_project'=>$oa_project,
    				'oa_project_name'=>$oa_project_name,
    				'project_des'=>$project_des,
    				'expected_time'=>$expected_time,
    				'status'=>202), "id='$id'");
    		if($re){	//记录日志
    			
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$id,'temp4'=>'填写报告成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'提交成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$id,'temp4'=>'填写报告失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'提交失败'));
    			exit;
    		}
    	}
    	$labService = new Icwebadmin_Service_LabService();
    	$this->view->id = $_GET['id'];
    	$this->view->record = $labService->getRecord(0,1," AND la.id = ".$this->view->id);
    }
}