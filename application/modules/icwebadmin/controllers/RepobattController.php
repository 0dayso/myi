<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_RepoBattController extends Zend_Controller_Action
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
    	
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    	$this->_repoService = new Icwebadmin_Service_RepoService();
    	$this->fun = new MyFun();
    	$this->view->USDTOCNY = $this->fun->getUSDToRMB();
    }
    public function indexAction(){
    	
    	$this->view->sdata = $sdata = $_POST['sdata']?$_POST['sdata']:date("Y-m-d 17:00:00",strtotime("-1 day"));
    	$this->view->etmie = $etmie = $_POST['etmie']?$_POST['etmie']:date("Y-m-d 17:00:00");
    	$stmie = strtotime($sdata);
    	$etmie = strtotime($etmie);
    	$sql = " AND so.created BETWEEN '$stmie' AND '$etmie'";
    	$this->view->ordertype = $_POST['ordertype']?$_POST['ordertype']:'all';
    	$back_order = 0;
    	if($this->view->ordertype=='online') $back_order = 1;
    	elseif($this->view->ordertype=='outline'){
    		$back_order = 2;
    	}
    	$this->view->staffchose = $_POST['staff']?$_POST['staff']:array();
    	if($this->view->staffchose){
    		$st = implode("','",$this->view->staffchose);
    		$sql .= " AND st.staff_id IN ('{$st}')";
    	}
    	$this->view->xiaoshou = $this->_staffservice->getDepXs($this->view->staffchose);
    	$this->view->countarray = $this->_repoService->countByStaff($sql,$back_order);
    	
    	//非代理线
    	$this->_eventservice = new Default_Service_EventService();
    	$event = $this->_eventservice->getEvent("eventnumber='201402135'");
    	if($event['data']){
    		eval($event['data']);
    	}
    	$part_str = " sp.id IN ('".implode("','",$partarray)."')";
    	 
   
    	$part_str_2 = " AND sp.prod_id IN ('".implode("','",$partarray)."')";
    	$sql = $part_str_2." AND so.created BETWEEN '$stmie' AND '$etmie'" ;
    	$soprod = $this->_repoService->orderRepo($sql,$back_order);
    	$bmp = $bnt = array();
    	foreach($soprod as $sparr){
    		if($sparr['department']=='BMP'){
    			$bmp[$sparr['currency']] += $sparr['buynum']*$sparr['buyprice'];
    			$bmp['order'][$sparr['salesnumber']]= $sparr['salesnumber'];
    		}elseif($sparr['department']=='B&T'){
    			$bnt[$sparr['currency']] += $sparr['buynum']*$sparr['buyprice'];
    			$bnt['order'][$sparr['salesnumber']]= $sparr['salesnumber'];
    		}
    	}
    	$bmp['total'] = $bmp['RMB']+($bmp['USD']*$this->view->USDTOCNY);
    	$bmp['ordernum'] = count($bmp['order']);
    	$bnt['total'] = $bnt['RMB']+($bnt['USD']*$this->view->USDTOCNY);
    	$bnt['ordernum'] = count($bnt['order']);
    	
    	$this->view->bmp = $bmp;
    	$this->view->bnt = $bnt;
    	//获取销售
    	$staffservice = new Icwebadmin_Service_StaffService();
    	$this->view->staff = $staffservice->getXiaoShou();
    	
    	
    }
    /**
     * 订单走势
     */
    public function trendAction(){
    	$this->_eventservice = new Default_Service_EventService();
    	$event = $this->_eventservice->getEvent("eventnumber='201402135'");
    	if($event['data']){
    		eval($event['data']);
    	}
    	
    	$this->view->sdata = $_GET['sdata'];
    	$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    	$stmie = strtotime($_GET['sdata']." 00:00:00");
    	$etmie = strtotime($_GET['edata']." 23:59:59");
    	$sql = " AND so.created BETWEEN '$stmie' AND '$etmie'";
    	//线上，线下
    	$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'all';
    	$back_order = 0;
    	if($this->view->ordertype=='online') $back_order = 1;
    	elseif($this->view->ordertype=='outline'){
    		$back_order = 2;
    	}
    	//类型
    	$this->view->type = $_GET['type'];
    	$this->view->orderstrend =array();
    	if($_GET['sdata']){
    	  $this->view->orderstrend = $this->_repoService->orderstrend($sql,$back_order,$stmie,$etmie,$this->view->type,$partarray);
    	}
    }
    /**
     * qtylist
     */
    public function qtylistAction(){
    	$this->_eventservice = new Default_Service_EventService();
    	$event = $this->_eventservice->getEvent("eventnumber='201402135'");
    	if($event['data']){
    		eval($event['data']);
    	}
    	$part_str = " sp.id IN ('".implode("','",$partarray)."')";
    	
    	$this->_prodModer = new Icwebadmin_Model_DbTable_Product();
    	$sqlstr ="SELECT sp.id,sp.part_no,sp.hk_stock,sp.sz_stock,sp.hk_cover,sp.sz_cover,br.name as bname
    	FROM product as sp
    	LEFT JOIN brand as br ON sp.manufacturer=br.id
    	WHERE {$part_str} ";
    	$this->view->arr = $this->_prodModer->getBySql($sqlstr);

    	//销售统计
    	$this->view->sdata = $sdata = $_GET['sdata']?$_GET['sdata']:date("Y-m-d 17:00:00",strtotime("-1 day"));
    	$this->view->etmie = $etmie = $_GET['etmie']?$_GET['etmie']:date("Y-m-d 17:00:00");
    	$stmie = strtotime($sdata);
    	$etmie = strtotime($etmie);
		
    	$part_str_2 = " AND sp.prod_id IN ('".implode("','",$partarray)."')";
    	$sql = $part_str_2." AND so.created BETWEEN '$stmie' AND '$etmie'" ;
    	
    	//线上，线下
    	$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'all';
    	$back_order = 0;
    	if($this->view->ordertype=='online') $back_order = 1;
    	elseif($this->view->ordertype=='outline'){
    		$back_order = 2;
    	}
    	$soprod = $this->_repoService->orderRepo($sql,$back_order);
    	$soprod_tmp = array();
    	foreach($soprod as $sparr){
    		$soprod_tmp[$sparr['prod_id']][$sparr['currency']] +=$sparr['buynum']*$sparr['buyprice'];
    		$soprod_tmp[$sparr['prod_id']]['num'] +=$sparr['buynum'];
    	}
    	$this->view->soprod = $soprod_tmp;
    }
    public function changeorderAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$staff_id = $_POST['staff_id'];
    	$disroder = $_POST['disroder'];
    	$staffmonder = new Icwebadmin_Model_DbTable_Model('admin_staff');
    	$re = $staffmonder->update(array('disroder'=>$disroder), "`staff_id` ='$staff_id'");
    }
}