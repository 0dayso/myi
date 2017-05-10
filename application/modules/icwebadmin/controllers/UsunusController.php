<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_UsUnusController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_unuerModer;
	private $_unuerService;
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
    	
    	$this->_unuerModer = new Icwebadmin_Model_DbTable_Unuser();
    	$this->_unuerService = new Icwebadmin_Service_UnuserService();
    }
    public function indexAction(){
    	$this->view->keyword = $keyword = $_GET['keyword'];
    	$where = "u.companyname!=''";
    	if($keyword) $where = " (u.companyname like '%{$keyword}%' OR u.companyname_en like '%{$keyword}%')";
		$total = $this->_unuerService->getNum($where);
		$perpage=20;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show(6);
    	$this->view->allcom = $this->_unuerService->getAll($offset, $perpage,$where);
    }
    public function addAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->processData();
    		//Zend_Debug::dump($data); die();
    		if(!$data['error']){
    			$newid = $this->_unuerModer->addDate($data);
    			if($newid){
    				$_SESSION['messages'] = "添加成功.";
    				$this->_redirect($this->addurl);
    			}else{
    				$_SESSION['messages'] = "添加失败.";
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}
    }
    
    public function editAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->processData('edit');
    		if(!$data['error']){
    			$re = $this->_unuerModer->update($data, "un_uid = ".$data['un_uid']);
    			if($re){
    				$_SESSION['messages'] = "更新成功.";
    				$this->_redirect($this->editurl.'/un_uid/'.$data['un_uid']);
    			}else{
    				$_SESSION['messages'] = "更新失败.";
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}else{
    		$this->view->un_uid = $un_uid =(int) $this->getRequest()->getParam('un_uid');
    		if(!$un_uid) $this->_redirect($this->indexurl);
    		$this->view->processData = $this->_unuerModer->getRowByWhere("un_uid = '{$un_uid}'");
    	}
    }
    public function changeAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$un_uid =(int) $this->getRequest()->getParam('un_uid');
    	$status =(int) $this->getRequest()->getParam('status');
    	$this->_unuerModer->update(array("status" =>$status), "un_uid = ".$un_uid);
    	$_SESSION['messages'] = "更新成功.";
    	$this->_redirect($this->indexurl);
    }
    public function processData($pottype=''){
    	$post  = $this->getRequest()->getPost();
    	//Zend_Debug::dump($post); exit;
    	$error = 0;$message = '';
    	if(!$post['companyname']){
    		$error++;
    		$message .= "请输入公司名称.<br/>";
    	}elseif($pottype != 'edit'){
    		$re = $this->_unuerModer->getRowByWhere("status = 1 AND companyname = '".$post['companyname']."'");
    		if(!empty($re)){
    			$error++;
    			$message .= "该公司已经存在.<br/>";
    		}
    	}
    	if(!$post['truename']){
    		$error++;
    		$message .="请输入联系人.<br/>";
    	}
    	if(!$post['tel']){
    		$error++;
    		$message .="请输入固定电话.<br/>";
    	}
    	if(!$post['address']){
    		$error++;
    		$message .="请输入详细地址.<br/>";
    	}
    	
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		if($pottype == 'edit') $post['modified'] = time();
    		else $post['created'] = time();
    		return $post;
    	}
    }
}