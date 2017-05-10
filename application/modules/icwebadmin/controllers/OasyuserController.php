<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_OasyUserController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_oainquiryService;
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
    	
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	
    	$this->_oainquiryService = new Icwebadmin_Service_OainquiryService();
    }
    public function indexAction(){
    	$this->view->oaClient = array();
    	$clientcname = trim($this->_getParam('clientcname'));
    	if($clientcname && $clientcname!='公司'){
    		$this->view->clientcname = $clientcname;
    		$this->view->oaClient    = $this->_oainquiryService->Find($clientcname);
    		//OA销售
    		$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    		$oa_employee = $oa_sellline_model->getAllByWhere("type='sell'"," oa_name ASC");
    		$this->view->oa_employee = array();
    		foreach($oa_employee as $arr){
    			$this->view->oa_employee[$arr['oa_id']] = $arr['oa_name'];
    		}
    	}
    }
    /**
     * 跳转到OA
     */
    public function oaviewuserAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();

    	/*$loginname = $_SESSION['staff_sess']['staff_id'];
    	if($_SESSION['staff_sess']['staff_id']=='andyxian') $loginname = 'andy.xian';
    	$loginname ='rose.zhao';
    	$login = $this->_oainquiryService->GetLoginID($loginname);*/
    	
    	if($this->_getParam('oaurl')){
    		$this->_redirect($this->_getParam('oaurl'));
    	}else{
    		echo 'Log in OA system fails!';
    	}
    }
}