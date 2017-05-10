<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_ReadLinkController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_linkservice;
	private $_ftypeModel;
	private $_flinkModel;
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
    	
    	$this->_linkservice = new Icwebadmin_Service_FriendshipService();
    	
    	$this->_ftypeModel = new Default_Model_DbTable_Model("friendship_type");
    	$this->_flinkModel = new Default_Model_DbTable_Model("friendship_link");
    }
    public function indexAction(){
    	//类型
    	$this->view->type = (int)$_GET['type'];
    	//获取友情链接分类
    	$this->view->linktype = $this->_linkservice->getAllType();
    	if(!$this->view->type) $this->view->type = $this->view->linktype[0]['id'];
    	//条件
    	$where = " AND fl.type='{$this->view->type}'";
    	
    	$perpage=20;
    	$total = $this->_linkservice->getTotal($where);
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->data = $this->_linkservice->getAllLinks($offset,$perpage,$where);
    }
    /**
     * 添加分类
     */
    public function addtypeAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$status   = (int)($formData['status']);
    		$name    = $formData['name'];
    		$displayorder  = (int)($formData['displayorder']);
    	
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写名称。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_ftypeModel->addData(array('name'=>$name,
    					'displayorder'=>$displayorder,
    					'status'=>$status,
    					'created'=>time(),
    					'created_by'=>$_SESSION['staff_sess']['staff_id']));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    /**
     * 编辑分类
     */
    public function edittypeAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$status   = (int)($formData['status']);
    		$id       = (int)$formData['tid'];
    		$name     = $formData['name'];
    		$displayorder  = (int)($formData['displayorder']);
    		 
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写名称。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_ftypeModel->update(array('name'=>$name,
    					'displayorder'=>$displayorder,
    					'status'=>$status,
    					'modified'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id']), "id='{$id}'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    			exit;
    		}
    	}
    	$tid = (int)$this->_getParam("tid");
    	$this->view->typedata = $this->_ftypeModel->getRowByWhere("id='{$tid}'");
    }
    /**
     * 添加 链接
     */
    public function addlinkAction(){
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$link_type      = (int)($formData['link_type']);
    		$type      = (int)($formData['type']);
    		$status   = (int)($formData['status']);
    		$home = (int)($formData['home']);
    		$name      = $formData['name'];
    		$url       = $formData['url'];
    		$email       = $formData['email'];
    		$introduction       = $formData['introduction'];
    		$displayorder  = (int)($formData['displayorder']);
    		$icon_url       = $formData['icon_url'];
    		 
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写网站名称。<br/>';
    			$error++;
    		}
    		if(empty($url) || $url=='http://'){
    			$message ='请填写网站地址。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_flinkModel->addData(array(
    					'type'=>$type,
    					'name'=>$name,
    					'link_type'=>$link_type,
    					'url'=>$url,
    					'icon'=>$icon_url,
    					'home'=>$home,
    					'email'=>$email,
    					'introduction'=>$introduction,
    					'displayorder'=>$displayorder,
    					'status'=>$status,
    					'created'=>time(),
    					'created_by'=>$_SESSION['staff_sess']['staff_id']));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    	//分类
    	$this->view->linktype = $this->_linkservice->getAllType();
    	$tid = (int)$this->_getParam("tid");
    	$this->view->typedata = $this->_ftypeModel->getRowByWhere("id='{$tid}'");
    }
    /**
     * 编辑 链接
     */
    public function editlinkAction(){
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$id      = (int)($formData['id']);
    		$link_type      = (int)($formData['link_type']);
    		$type      = (int)($formData['type']);
    		$status   = (int)($formData['status']);
    		$home = (int)($formData['home']);
    		$name      = $formData['name'];
    		$url       = $formData['url'];
    		$email       = $formData['email'];
    		$introduction       = $formData['introduction'];
    		$displayorder  = (int)($formData['displayorder']);
    		$icon_url       = $formData['icon_url'];
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写网站名称。<br/>';
    			$error++;
    		}
    		if(empty($url) || $url=='http://'){
    			$message ='请填写网站地址。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_flinkModel->update(array(
    					'type'=>$type,
    					'name'=>$name,
    					'link_type'=>$link_type,
    					'url'=>$url,
    					'icon'=>$icon_url,
    					'home'=>$home,
    					'email'=>$email,
    					'introduction'=>$introduction,
    					'displayorder'=>$displayorder,
    					'status'=>$status,
    					'modified'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id']),"id='{$id}'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    	$id = (int)$this->_getParam("id");
    	$this->view->linkdata = $this->_linkservice->getLink($id);
    	//分类
    	$this->view->linktype = $this->_linkservice->getAllType();
    }
}