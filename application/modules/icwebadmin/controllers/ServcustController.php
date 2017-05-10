<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_ServCustController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_ajaximService;
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
    	$this->areaService = new Icwebadmin_Service_AreaService();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	$this->_ajaximService = new Icwebadmin_Service_AjaximService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    }
    public function indexAction(){
    	$this->view->servicelist = $this->_ajaximService->getServicelists();
    }
    //修改运费
    public function editAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$id = $this->_getParam('id');
    	$this->view->service = $this->_ajaximService->getServiceByid($id);
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$id        = $formData['id'];
    		$status    = $formData['status'];
    		$username  = $formData['username'];
    		$stype     = $formData['stype'];
    		$staffs    = $formData['staffs'];
    		$error = 0;$message = '';
    		if(!$id){
    			$error++;
    			$message .= "编号为空.";
    		}
    		if(!$username){
    			$error++;
    			$message .="请填写名称.";
    		}
    		if(!in_array($stype, array('100','200'))){
    			$error++;
    			$message .="请选择类型.";
    		}
    		if($stype=='200'){
    			if(!$staffs){
    			    $error++;
    			    $message .="请填写指定人员.";
    			}else{
    				$staffservice = new Icwebadmin_Service_StaffService();
    				if(!$staffservice->getStaffInfo($staffs)){
    					$error++;
    					$message .="指定人员不存在.";
    				}
    			}
    		}
    		if($error){
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'编辑客服失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			$sModel = new Icwebadmin_Model_DbTable_Model("ajaxim_servicelists");
    			$sModel->update(array('status'=>$status,
    					'username'=>$username,
    					'stype'=>$stype,
    					'staffs'=>$staffs),
    					"id='{$id}'");
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'编辑客服成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑客服成功'));
    			exit;
    		}
    	}
    }
}