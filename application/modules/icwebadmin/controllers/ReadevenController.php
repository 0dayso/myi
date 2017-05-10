<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
include_once APPLICATION_PATH.'/../public/js/filemanager/class/FileManager.php';
class Icwebadmin_ReadEvenController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_eventservice;
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
    	$this->filemanagerurl= $this->view->filemanagerurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/filemanager";
    	
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
    	$this->view->fun = new MyFun();
    	$this->_eventservice = new Icwebadmin_Service_EventService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$perpage=20;
    	$total = $this->_eventservice->getTotal();
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->data = $this->_eventservice->getAll($offset,$perpage);
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
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
    			$newid = $this->_eventservice->addEvent($data);
    			if($newid){
    				$eventnumber = date("Ymd").$newid;
    				$this->_eventservice->updateByWhere(array('eventnumber'=>$eventnumber),"id={$newid}");
    				//创建文件夹
    				$folder = 'upload/event/'.$eventnumber;
    			    if(!is_dir($folder)) //判断是否存在
			        {
				      mkdir($folder,0777);//创建
			        }$_SESSION['messages'] = "添加成功.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'活动添加成功'));
    				$this->_redirect($this->addurl);
    			}else{
    				$_SESSION['messages'] = "添加失败.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$newid,'temp4'=>'活动添加失败'));
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
    		//Zend_Debug::dump($data); die();
    		if(!$data['error']){
    			$data['status'] = $data['status']?$data['status']:0;
    			$re = $this->_eventservice->updateByWhere($data,"eventnumber='".$data['eventnumber']."'");
    			if($re){
    				$_SESSION['messages'] = "更新成功.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['eventnumber'],'temp4'=>'活动更新成功'));
    				$this->_redirect($this->editurl.'/eventnumber/'.$data['eventnumber']);
    			}else{
    				$_SESSION['messages'] = "更新失败.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['eventnumber'],'temp4'=>'活动更新失败'));
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    
    	}else{
    		$eventnumber =(int) $this->getRequest()->getParam('eventnumber');
    		if(!$eventnumber){
    			$this->_redirect($this->indexurl);
    		}else{
    			$this->view->processData = $this->_eventservice->getEvent("eventnumber = {$eventnumber}");
    	    }
    	}
    }
    public function editdataAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		
    		$re = $this->_eventservice->updateByWhere(array('data'=>str_replace('\\', '',$data['data'])),"id='".$data['id']."'");
    		//日志
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'活动数据更新成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'活动数据更新成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['id'],'temp4'=>'活动数据更新失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'活动数据更新失败'));
    			exit;
    		}
    	}else{
    		$id =(int) $this->getRequest()->getParam('id');
    		$this->view->even = $this->_eventservice->getEvent("id = {$id}");
    	}
    }
    public function editcodeAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    
    		$re = $this->_eventservice->updateByWhere(array('code'=>str_replace('\\', '',$data['code'])),"id='".$data['id']."'");
    		//日志
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'活动代码更新成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'活动代码更新成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['id'],'temp4'=>'活动代码更新失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'活动代码更新失败'));
    			exit;
    		}
    	}else{
    		$id =(int) $this->getRequest()->getParam('id');
    		$this->view->even = $this->_eventservice->getEvent("id = {$id}");
    	}
    }
 public function filemanagerAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->view->eventnumber = $eventnumber =(int) $this->getRequest()->getParam('eventnumber');
    	if(!$eventnumber){
    		$this->_redirect($this->indexurl);
         }else{
    		$this->view->processData = $this->_eventservice->getEvent("eventnumber = {$eventnumber}");
    		//创建文件夹
    		$folder = 'upload/event/'.$eventnumber;
    		if(is_dir($folder)) //判断是否存在
    		{
    			$FileManager = new FileManager($folder);
    			$this->view->filemanager = $FileManager->create();
    		}else{
    			$this->_redirect($this->indexurl);
    		}
         }
         if($this->getRequest()->isPost()){
         	$postdata = $this->getRequest()->getPost();
         	$eventnumber = $postdata['eventnumber'];
         	$file = $_FILES['file_zip'];
         	$zip = $this->_filter->extend($file["name"]);
         	if($zip!='zip'){
         		$_SESSION['messages'] = "只允许上传zip文件.";
         		$this->_redirect("/icwebadmin/ReadEven/filemanager/eventnumber/$eventnumber");
         	}
         	//上传解压
         	$folder = 'upload/event/'.$eventnumber.'/';
         	if(!is_dir($folder))
         	{
         		mkdir($folder,0777);
         	}
         	 @move_uploaded_file($file["tmp_name"],$folder.$file["name"]);
             @unlink($file);
         	//解压
         	$zip =new ZipArchive();
         	if ($zip->open($folder.$file["name"]) == TRUE)
         	{
         		$zip->extractTo($folder);
         		$zip->close();
         		   @unlink($folder.$file["name"]);
         	}
		 $_SESSION['messages'] = "批量上传成功.";
         $this->_redirect("/icwebadmin/ReadEven/filemanager/eventnumber/$eventnumber");
      }
    }
    /*
     * 改变状态
    */
    public function changestatusAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id     = (int)$formData['id'];
    		$status = (int)$formData['status'];
    		$this->_eventservice->updateByWhere(array('status'=>$status),"id = {$id}");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改状态成功，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'更改状态失败，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
    public function processData($pottype=''){
    	$post  = $this->getRequest()->getPost();
    	//Zend_Debug::dump($post); exit;
    	$error = 0;$message = '';
    	if(!$post['title']){
    		$error++;
    		$message .="请输入标题.<br/>";
    	}
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		$data = array(
    				'status'=>(int) $post['status'],
    				'title'=>$post['title'],
    				'logbar'=>($post['logbar']?1:0),
    				'menu'=>($post['menu']?1:0),
    				'headtitle'=>$post['headtitle'],
    				'description'=>$post['description'],
    				'keywords'=>$post['keywords']
    				);
    		if($pottype=='edit'){
    			$data['eventnumber']=$post['eventnumber'];
    			$data['modified']=time();
    			$data['modified_by']=$_SESSION['staff_sess']['staff_id'];
    		}else{
    			$data['created']=time();
    			$data['created_by']=$_SESSION['staff_sess']['staff_id'];
    		}
    		return $data;
    	}
    }
}