<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_SampSxsqController extends Zend_Controller_Action
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
    	$this->_dataservice = new Icwebadmin_Service_DataService();
    	
    	$this->view->fun = new MyFun();
    }
    public function indexAction(){
    	$typestr = $selectstr = '';
    	$this->view->type = $_GET['type']?$_GET['type']:'wait';

    	//待处理
    	$waitsql   = " AND spa.status='100' ".$selectstr;
    	//处理
    	$procsql   = " AND spa.status='101' ".$selectstr;
    	//已处理
    	$alreadysql= " AND spa.status='201' ".$selectstr;
    	 
    	$this->view->waitnum = $this->_dataservice->getApplyNum($waitsql);
    	$this->view->procnum = $this->_dataservice->getApplyNum($procsql);
    	$this->view->alreadynum = $this->_dataservice->getApplyNum($alreadysql);
    	if($this->view->type=='wait'){
    		$total = $this->view->waitnum;
    		$typestr = $waitsql;
    	}elseif($this->view->type=='proc'){
    		$total = $this->view->procnum;
    		$typestr = $procsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
    	}else $this->_redirect ( '/icwebadmin' );
    	 
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->applyall = $this->_dataservice->getApply($offset, $perpage, $typestr);
    }
    /**
     * 审核
     */
    public function releaseAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id   = (int)$formData['id'];
    	    $type = (int)$formData['type'];
    	    
    		$this->_dataModel = new Icwebadmin_Model_DbTable_Model("data_apply");
    		$re = $this->_dataModel->update(array('status'=>$type), "id='{$id}'");
    		if($re){
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'操作失败'));
    			exit;
    		}
    	}
    }
}