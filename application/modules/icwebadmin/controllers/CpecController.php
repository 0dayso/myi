<?php 
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_CpEcController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_prodatsService;
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
    	$this->_prodatsService = new Icwebadmin_Service_ProductecService();
    }
    public function indexAction(){
    	if($_GET['brand']){
    		$this->view->brand = $brand = $_GET['brand']  ;
    		$where = "  and  oa_mfr = '$brand'";
    	}
    	if(!empty($_GET['part_id'])){
    		$this->view->part_id = $part_id = $_GET['part_id']  ;
    		$where .= "  and part_id  = '$part_id'";
    	}    	
    	$where .= " ORDER BY  oa_mfr  ";
    	$this->view->total  = $this->_prodatsService->getNum($where);
    	$this->view->product = $this->_prodatsService->getAllBySql('','',$where); 
    }
    public function editAction(){
    	
		$request = $this->getRequest()->getPost();
		$id 		   = $request['pk'];
		$data['ic_part_no'] = $request['value'];
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$this->_prodatsService->updatebyid($data, $id);
         echo "updated!"; 
    }
}