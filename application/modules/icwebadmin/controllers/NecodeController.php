<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_NeCodeController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_model;
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
    	$this->fun = $this->view->fun = new MyFun();
    	$this->_model = new Default_Model_DbTable_Model("app_code");
    	
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$perpage=20;
    	$where = '';
    	if($_GET['app_level1']){
    		$where .= ' AND ac.app_level1='.(int) $_GET['app_level1'];
    	}
    	if(isset($_GET['status']) && $_GET['status'] !='' ){
    		$where .= ' AND ac.status='.(int) $_GET['status'];
    	}
    	if($_GET['q'])
    	{
    		$q = $_GET['q'];
    		$where .= " AND (ac.title like'%$q%' OR ac.keyword  like '%$q%')";
    	}
    	$total = $this->_model->QueryItem("SELECT count(ac.id) FROM `app_code` as ac WHERE ac.id!='' {$where}");
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$data = $this->_model->Query("SELECT ac.*,ap.name as appname FROM `app_code` as ac 
    			LEFT JOIN app_category as ap ON ap.id = ac.app_level1
    			WHERE ac.id!='' {$where}
    			ORDER BY ac.`push` DESC,ac.`status` DESC,ac.id DESC");
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
    		$file = $_FILES['annexpath'];
    		//上传
    		$folder = 'upload/default/code/';
    		if(!is_dir($folder)) mkdir($folder,0777);
    		$newname = 'code'.time().'.'.$this->_filter->extend($file["name"]);
    		@move_uploaded_file($file["tmp_name"],$folder.$newname);
    		@unlink($file);
    		$data['annexpath'] = $folder.$newname;
    		$data['annexname'] = $file["name"];
    		$id = $this->_model->addData($data);
    		if($id){
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'添加应用代码成功','description'=>implode(';', $data)));
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'添加应用代码失败','description'=>implode(';', $data)));
    		}
    		$message = "Records added! ";
    		$this->_helper->flashMessenger->addMessage($message);
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
    		$data = $this->_process($request->getPost(),'edit');
    		//上传
    		$file = $_FILES['annexpath'];
    		if($file["tmp_name"]){
    		   $folder = 'upload/default/code/';
    		   if(!is_dir($folder)) mkdir($folder,0777);
    		     $newname = 'code'.time().'.'.$this->_filter->extend($file["name"]);
    		     @move_uploaded_file($file["tmp_name"],$folder.$newname);
    		     @unlink($file);
    		    $data['annexpath'] = $folder.$newname;
    		    $data['annexname'] = $file["name"];
    		}
    		$re = $this->_model->update($data,"id='".$data['id']."'");
    		if($re){
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'更新应用代码成功','description'=>implode(';', $data)));
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['id'],'temp4'=>'更新应用代码失败','description'=>implode(';', $data)));
    		}
    		$message = "Records #{$id} updated! ";
    		$this->_helper->flashMessenger->addMessage($message);
    		$this->_redirect($this->view->url());
    	}
    	$this->view->data = $this->_model->getRowByWhere("id='{$id}'");
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    public function activeAction(){
    	$data = array(
    			'modified'=>time(),
    			'modified_by'  => $_SESSION['staff_sess']['staff_id']
    	);
    	$id =(int) $this->getRequest()->getParam('id');
    	if($this->getRequest()->getParam('flag')!=null)
    	{
    		$data['status'] = $this->getRequest()->getParam('flag');
    	}
    	if($this->getRequest()->getParam('push')!=null)
    	{
    		$data['push'] = $this->getRequest()->getParam('push');
    	}
    	if(!$id) $this->_redirect($this->indexurl);
    	$this->_model->update($data, "id='{$id}'");
    	$message = "Records updated.";
    	$this->_helper->flashMessenger->addMessage($message);
    	$this->_redirect($this->indexurl);
    }
    private function _process($post,$type='add')
    {
    	$error = 0;$message = '';
    	if(!$post['title']){
    		$error++;
    		$message .= "请输入标题.<br/>";
    	}
        
    	if(!$post['dep_environment']){
    		$error++;
    		$message .= "请输入开发环境.<br/>";
    	}
    	if(!$post['keyword']){
    		$error++;
    		$message .= "请输入关键字.<br/>";
    	}
    	if(!$post['app_level1']){
    		$error++;
    		$message .= "请选择应用分类.<br/>";
    	}
    	if(!$post['description']){
    		$error++;
    		$message .= "请输入简介.<br/>";
    	}
    	if(!$post['content']){
    		$error++;
    		$message .= "请输入详细描述.<br/>";
    	}
    	if(!isset($post['status'])) $post['status']=0;
    	if(!isset($post['push']))   $post['push']=0;
    	if(!isset($post['need_pass'])) $post['need_pass']=0;
    	
    	$file = $_FILES['annexpath'];
    	$zip = $this->_filter->extend($file["name"]);
    	if($type=='add'){
    	   if($zip!='zip' && $zip!='ZIP' && $zip!='rar' && $zip!='RAR'){
    		$error++;
    		$message .= "只允许上传zip和rar文件.<br/>";
    	  }
    	}elseif($file["name"]){
    		if($zip!='zip' && $zip!='ZIP' && $zip!='rar' && $zip!='RAR'){
    			$error++;
    			$message .= "只允许上传zip和rar文件.<br/>";
    		}
    	}
    	if($error){
    		$this->_helper->flashMessenger->addMessage($message);
    		$_SESSION['post'] = $post;
    		$this->_redirect($this->view->url());
    	}else{
    		$post['published'] = ($post['published']?strtotime($post['published']):time());
    		//替换 为了处理图片
    		$post['content'] = str_replace('\\','',$post['content']);
    		if($type=='add'){
    		   $post['created']  = time();
    		   $post['created_by']  = $_SESSION['staff_sess']['staff_id'];
    		}elseif($type=='edit'){
    		   $post['modified']  = time();
    		   $post['modified_by']  = $_SESSION['staff_sess']['staff_id'];
    		}
    		
    		return $post;
    	}
    	 
    }
    public function downloadAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$docid= $this->fun->decryptVerification($_GET['key']);
    	$docarr =  $this->_model->getRowByWhere("id='{$docid}'");
    	if(!empty($docarr['annexpath']) && file_exists($docarr['annexpath'])){
    		$docre = explode("/", $docarr['annexpath']);
    		$newname = $docarr['annexname']?$docarr['annexname']:$docre[(count($docre)-1)];
    		$this->fun->filedownloadpage($docarr['annexpath'],$newname);
    			
    	}else $this->_redirect ($this->indexurl);
    }
}