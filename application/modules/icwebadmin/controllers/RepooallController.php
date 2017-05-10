<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_RepoOallController extends Zend_Controller_Action
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
    	
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	
    	$this->_repoService = new Icwebadmin_Service_RepoService();
    	
    	$this->fun = new MyFun();
    	$this->view->USDTOCNY = $this->fun->getUSDToRMB();
    }
    public function indexAction(){
    	$this->view->sdata = $_GET['sdata']?$_GET['sdata']:date("Y-m-d",strtotime("-1 month"));
    	$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    	$stmie = strtotime($this->view->sdata." 00:00:00");
    	$etmie = strtotime($this->view->edata." 23:59:59");
		echo strtotime("2014-1-1 00:00:00");echo '<br/>';
		echo strtotime("2014-4-1 00:00:00");echo '<br/>';
    	//类型
    	$this->view->type = $_GET['type']?$_GET['type']:'week';
    	$this->view->orderstrend =array();
    	if($this->view->sdata){
    		$sql = " AND so.created <= '$etmie' ";
    	   // $this->view->orderstrend = $this->_repoService->orderTrend($sql,$this->view->USDTOCNY,$stmie,$etmie,$this->view->type);
    	}
    }
}