<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_SaLogsController extends Zend_Controller_Action
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
    	if(isset($_GET['type'])) $this->view->type = $_GET['type'];
    	else $this->view->type = 'default';
    	if($this->view->type == 'default'){
    	    $total = $this->_logservice->getDefaultRowNum();
    	}elseif($this->view->type == 'admin'){
    		$total = $this->_logservice->getAdminRowNum();
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	if($this->view->type == 'default'){
    		$this->view->alllogs = $this->_logservice->getDefaultAll($offset, $perpage);
    	}elseif($this->view->type == 'admin'){
    		$this->view->alllogs = $this->_logservice->getAdminAll($offset, $perpage);
    	}
    }
    /**
     * 追踪
     */
    public function trackAction(){
    	$this->view->type      = $this->_getParam('type');
    	$this->view->valuetype = $valuetype  = $this->_getParam('valuetype');
    	$this->view->value     =  $value = $this->_getParam('value');
    	$this->view->valuetype_array = explode('||', $valuetype);
    	$this->view->value_array = explode('||', $value);
    	$sql = '';
    	if($this->view->type=='default'){
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
    			if($vtarr=='controller'){
    				$sql .= " AND dl.controller='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='action'){
    				$sql .= " AND dl.action='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='log_id'){
    				$sql .= " AND dl.log_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='temp1'){
    				if($this->view->value_array[$key]) $sql .= " AND dl.temp1='".$this->view->value_array[$key]."'";
    				else $sql .= " AND dl.temp1 IS NULL";
    			}
    			if($vtarr=='temp2'){
    				$sql .= " AND dl.temp2 LIKE '%".$this->view->value_array[$key]."%'";
    			}
    			if($vtarr=='temp3'){
    				$sql .= " AND dl.temp3 = '".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='temp5'){
    				$sql .= " AND dl.temp5 = '".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='created'){
    				$sql .= " AND dl.created BETWEEN ".(strtotime(date(('Y-m-d'),$this->view->value_array[$key])))." AND ".(strtotime(date(('Y-m-d 23:59:59'),$this->view->value_array[$key])))."";
    			}
    			if($vtarr=='temp4'){
    				
    				$sql .= " AND dl.temp4='".$this->view->value_array[$key]."'";
    			}
    			
    		}
    		$total = $this->_logservice->getDefaultRowNum($sql);
    		//分页
    		$perpage=20;
    		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    		$offset  = $page_ob->offset();
    		$this->view->page_bar= $page_ob->show(6);
    		$this->view->alllogs = $this->_logservice->getDefaultAll($offset, $perpage,$sql);
    	}elseif($this->view->type=='admin'){
    		foreach($this->view->valuetype_array as $key=>$vtarr){
    			if($vtarr=='session_id'){
    				$sql .= " AND al.session_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='staffid'){
    				$sql .= " AND al.staffid='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='ip'){
    				$sql .= " AND al.ip='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='controller'){
    				$sql .= " AND al.controller='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='action'){
    				$sql .= " AND al.action='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='log_id'){
    				$sql .= " AND al.log_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='temp1'){
    				if($this->view->value_array[$key]) $sql .= " AND al.temp1='".$this->view->value_array[$key]."'";
    				else $sql .= " AND al.temp1 IS NULL";
    			}
    			if($vtarr=='temp2'){
    				$sql .= " AND al.temp2 LIKE '%".$this->view->value_array[$key]."%'";
    			}
    			if($vtarr=='created'){
    				$sql .= " AND al.created BETWEEN ".(strtotime(date(('Y-m-d'),$this->view->value_array[$key])))." AND ".(strtotime(date(('Y-m-d 23:59:59'),$this->view->value_array[$key])))."";
    			}
    			if($vtarr=='temp4'){
    		
    				$sql .= " AND al.temp4='".$this->view->value_array[$key]."'";
    			}
    		}
    		$total = $this->_logservice->getAdminRowNum($sql);
    		//分页
    		$perpage=20;
    		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    		$offset  = $page_ob->offset();
    		$this->view->page_bar= $page_ob->show(6);
    		$this->view->alllogs = $this->_logservice->getAdminAll($offset, $perpage,$sql);
    	}
    }
}