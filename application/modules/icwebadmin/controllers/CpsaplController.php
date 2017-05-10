<?php 
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_CpSaplController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_service;
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
    	$this->_service = new Icwebadmin_Service_SapLinecardService();
    }
    public function indexAction(){

    	$brand_id = $this->_request->getQuery('brand_id');
    	if(isset($_GET['brand_id']) && strlen($brand_id)>0){
    		$this->view->brand_id = $brand_id = $_GET['brand_id']  ;
    		$where .= "  and brand_id  = '$brand_id'";
    	}    	
    	$where .= " and is_ec='Y'  ORDER BY  oa_name  ";
    	$this->view->total  = $this->_service->getNum($where);
    	$this->view->product = $product =  $this->_service->getAllBySql('','',$where);
		$brand = new Icwebadmin_Model_DbTable_Brand();
		$brand_arr = $brand->fetchAll(null,'name asc')->toArray();
		foreach($brand_arr as $k=>$v)
		{
			$json_arr[$k]['value'] = $v['id'];
			$json_arr[$k]['text'] = $v['name'];
		}
		$brand_json = json_encode($json_arr);
		$this->view->brand_json = $brand_json;    	
    }
    public function editAction(){
    	
		$request = $this->getRequest()->getPost();
		$id 		   = $request['pk'];
		$data['brand_id'] = $request['value'];
		$brand = new Icwebadmin_Model_DbTable_Brand();
		$tmp = $brand->fetchRow("id=".$data['brand_id'])->toArray();
		$data['ic_brand'] = $tmp['name'];
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$this->_service->updatebyid($data, $id);
         echo "updated!"; 
    }
}