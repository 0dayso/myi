<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_OasyFindController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_findService;
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
    	
    	$this->_findService = new Icwebadmin_Service_FindService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    }
    public function indexAction(){
    	//首页查找
    	if($this->getRequest()->isPost()){
    		$this->view->inqarray = $this->view->soarray = $this->view->inqsoarray = array();
    		$formData = $this->getRequest()->getPost();
    		$formData['find_key'] = trim($formData['find_key']);
    		$this->view->find_key = $formData['find_key'];
    		$this->view->find_type = $formData['find_type'];
    	    $this->view->inqarray = array();
    	    $this->view->samplesarray = array();
    		if($formData['find_type']=='order'){
    			
    			//2.订单记录 
    			$this->view->soarray = $this->_findService->getSoByOrder($formData['find_key']);

    			$this->view->inqsoarray = $this->_findService->getInqSoByOrder($formData['find_key']);
    			//1.询价记录
    			$this->view->inqarray = array();
    			foreach($this->view->inqsoarray as $inqso){
    				$this->view->inqarray = array_merge($this->view->inqarray,$this->_findService->getInqByOrder($inqso['inquiry_id']));
    			}
    			
    			//样片订单
    			$sqrstr="AND spa.salesnumber LIKE '%".$formData['find_key']. "%'";
    			$this->view->samplesarray =  $this->_findService->getSamples($sqrstr);
    			
    		}elseif($formData['find_type']=='inq'){
    			//1.询价记录
    			$this->view->inqarray = $this->_findService->getInqByInq($formData['find_key']);
    			//2.订单记录
    			$this->view->inqsoarray = array();
    			foreach($this->view->inqarray as $inq){
    				$this->view->inqsoarray = array_merge($this->view->inqsoarray,$this->_findService->getInqSoByInq($inq['id']));
    			}
    		}elseif($formData['find_type']=='product'){
    		    //1.询价记录
    			$this->view->inqarray = $this->_findService->getInqByProd($formData['find_key']);
    			//2.订单记录 
    			$this->view->soarray = $this->_findService->getSoByProd($formData['find_key']);
    			$this->view->inqsoarray = $this->_findService->getInqSoByProd($formData['find_key']);
    			//样片订单
    			$sqrstr="AND sd.part_no LIKE '%".$formData['find_key']. "%'";
    			$this->view->samplesarray =  $this->_findService->getSamples($sqrstr);
    		}elseif($formData['find_type']=='companyname'){
    		    //1.询价记录
    			$this->view->inqarray = $this->_findService->getInqByCompanyname($formData['find_key']);
    			//2.订单记录 
    			$this->view->soarray = $this->_findService->getSoByCompanyname($formData['find_key']);
    			$this->view->inqsoarray = $this->_findService->getInqSoByCompanyname($formData['find_key']);
    			
    			//样片订单
    			$sqrstr="AND up.companyname LIKE '%".$formData['find_key']. "%'";
    			$this->view->samplesarray =  $this->_findService->getSamples($sqrstr);
    		}
    	}
    }
}