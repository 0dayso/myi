<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpBppController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_bppService;
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
    	$this->_bppService = new Icwebadmin_Service_BppService();
    }
    public function indexAction(){
    	$where = "";
    	//合作伙伴
    	$this->view->selectvendor = $_GET['vendor'];
    	if($this->view->selectvendor){
    		$where .=" AND bs.vendor_id='{$this->view->selectvendor}'";
    	}
    	//品牌
    	$this->view->selectbrand = $_GET['brand'];
    	if($this->view->selectbrand){
    		$where .=" AND b.id='{$this->view->selectbrand}'";
    	}
    	//型号
    	$this->view->partno = trim($_GET['partno']);
    	if($this->view->partno){
    		$where .=" AND p.part_no LIKE '%{$this->view->partno}%'";
    	}
    	//库存
        $this->view->stock = $_GET['stock'];
    	if($this->view->stock){
    		$where .=" AND bs.stock > 0";
    	}
    	//占用库存
    	$this->view->stockcover = $_GET['stockcover'];
    	if($this->view->stockcover){
    		$where .=" AND bs.bpp_stock_cover > 0";
    	}
    	
    	$perpage=20;
    	$total = $this->_bppService->getRowNum($where);
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->data = $this->_bppService->getBppList($offset, $perpage,$where);
    	
    	//获取品牌
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!=''"," name ASC");
    	
    	$this->view->vendor = $this->_brandMod->getBySql("SELECT * FROM `vendor`");
    }
}