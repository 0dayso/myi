<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_SaVlogController extends Zend_Controller_Action
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
    	
    	$this->_logservice = new Icwebadmin_Service_LogService();
    	$this->fun = $this->view->fun = new MyFun();
    }
    public function indexAction(){
    	
    	$total = $this->_logservice->getDefaultViewRowNum();
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$this->view->alllogs = $this->_logservice->getDefaultViewAll($offset, $perpage);
    }
    /**
     * 追踪
     */
    public function trackAction(){
    	$this->view->valuetype = $valuetype  = $this->_getParam('valuetype');
    	$this->view->value     =  $value = $this->_getParam('value');
    	$this->view->valuetype_array = explode('||', $valuetype);
    	$this->view->value_array = explode('||', $value);
    	$sql = '';
    		foreach($this->view->valuetype_array as $key=>$vtarr){
    			if($vtarr=='session_id'){
    				$sql .= " AND dl.session_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='uid'){
    				$sql .= " AND dl.uid='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='ip'){
    				$sql .= " AND dl.ip='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='r'){
    				$sql .= " AND dl.r='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='page'){
    				$sql .= " AND dl.page='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='href'){
    				$sql .= " AND dl.href='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='agent'){
    				$sql .= " AND dl.agent='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='text'){
    				$sql .= " AND dl.text='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='title'){
    			
    				$sql .= " AND dl.title='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='rev'){
    				 
    				$sql .= " AND dl.rev='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='rel'){
    				 
    				$sql .= " AND dl.rel='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='created'){
    				$sql .= " AND dl.created BETWEEN ".(strtotime(date(('Y-m-d'),$this->view->value_array[$key])))." AND ".(strtotime(date(('Y-m-d 23:59:59'),$this->view->value_array[$key])))."";
    			}
    		}
    		$total = $this->_logservice->getDefaultViewRowNum($sql);
    		//分页
    		$perpage=20;
    		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    		$offset  = $page_ob->offset();
    		$this->view->page_bar= $page_ob->show(6);
    		$this->view->alllogs = $this->_logservice->getDefaultViewAll($offset, $perpage,$sql);
    }
}