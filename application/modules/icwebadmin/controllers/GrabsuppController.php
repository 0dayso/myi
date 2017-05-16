<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_GrabSuppController extends Zend_Controller_Action
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
    	
    	$this->_model = new Icwebadmin_Model_DbTable_SupplierGrab();
    }
    public function indexAction(){
    	$perpage=10;
    	$total = $this->_model->getTotal();
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$data = $this->_model->getAll($offset,$perpage,$where," displayorder DESC");
    	$this->view->data = $data;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function addAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$request = $this->getRequest();
    	if($request->isPost()){
    		$data = $this->_process($request->getPost());
    		$id = $this->_model->addData($data);
    		if($id){
    			$message = "添加成功! ";
    			$this->_helper->flashMessenger->addMessage($message);
    		}
    		$this->_redirect($this->indexurl);
    	}
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    
    public function editAction(){
    	 
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id =(int) $this->getRequest()->getParam('id');
    	if(!$id) $this->_redirect($this->indexurl);
    	$request = $this->getRequest();
    	if($request->isPost()){
    		$data = $this->_process($request->getPost());
    		
    		$this->_model->updateById($data,$id);
    
    		
    		$message = "更新成功! ";
    		$this->_helper->flashMessenger->addMessage($message);
    		$this->_redirect($this->view->url());
    	}
    	$data = $this->_model->getOneById($id);
    	$this->view->data = $data;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function _process($post)
    {
    	$error = 0;$message = '';
    	if(!$post['name']){
    		$error++;
    		$message .= "请输入供应商名称.<br/>";
    	}
    	if(!$post['img']){
    		$error++;
    		$message .= "请上传供应商logo.<br/>";
    	}
    	if(!$post['delivery_cn']){
    		$error++;
    		$message .= "请输入国内交期.<br/>";
    	}
    	if(!$post['delivery_hk']){
    		$error++;
    		$message .= "请输入香港交期.<br/>";
    	}
    	if($error){
    		$this->_helper->flashMessenger->addMessage($message);
    		$_SESSION['post'] = $post;
    		$this->_redirect($this->view->url());
    	}else{
    		return $post;
    	}
    	 
    }
}