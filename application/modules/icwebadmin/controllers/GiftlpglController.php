<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_GiftLpglController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_giftservice;
	private $_adminlogService;
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
    	$this->_giftservice = new Icwebadmin_Service_GiftService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$typestr = '';
    	$total = $this->_giftservice->getGiftNum($typestr); 
    	//分页
    	$perpage=40;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->giftall = $this->_giftservice->getGift($offset, $perpage, $typestr);
    }
    public function addAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	//礼品类别
    	$this->view->category = $this->_giftservice->getGiftCategory();
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$data = $this->processData($post);
    		if(!$data['error']){
    			$this->_giftModel = new Icwebadmin_Model_DbTable_Model("gift");
    			$newid = $this->_giftModel->addData($data);
    			if($newid){
    				$_SESSION['messages'] = "添加成功.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'礼品添加成功'));
    				$this->_redirect($this->addurl);
    			}else{
    				$_SESSION['messages'] = "添加失败.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$newid,'temp4'=>'礼品添加失败'));
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}
    }
    function editAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_giftModel = new Icwebadmin_Model_DbTable_Model("gift");
    	//礼品类别
    	$this->view->category = $this->_giftservice->getGiftCategory();
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$data = $this->processData($post,'edit');
    		if(!$data['error']){
    			
    			$re = $this->_giftModel->update($data, "id=".$data['id']);
    			
    			$_SESSION['messages'] = "更新成功.";
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'礼品更新成功'));
    			$this->_redirect($this->editurl.'/id/'.$data['id']);
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}else{
    		$id =(int) $this->getRequest()->getParam('id');
    		$this->view->id = $id;
    		if(!$id) $this->_redirect($this->indexurl);
    		$this->view->processData = $this->_giftModel->getRowByWhere("id=$id");
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
    		$this->_model = new Icwebadmin_Model_DbTable_Model("gift");
    		$this->_model->update(array('status'=>$status),"id = {$id}");
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
    /*
     * 推荐首页
    */
    public function changehomeAction(){
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
    		$home = (int)$formData['homevalue'];
    		$this->_model = new Icwebadmin_Model_DbTable_Model("gift");
    		$this->_model->update(array('home'=>$home),"id = {$id}");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改推荐到首页成功，改为:'.$home));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'更改推荐到首页失败，改为:'.$home));
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
    private function processData($post,$pottype=''){
    	$error = 0;$message = '';
    	//替换
    	$post['status'] = (isset($post['status'])) ?  1 : 0;
    	$post['content'] = str_replace('\\','',$post['content']);
    	 
    	if(!$post['name']){
    		$error++;
    		$message .= "请输入名称！<br>";
    		 
    	}
    	if(!$post['score']){
    		$error++;
    		$message .= "请输入消费积分！<br>";
    		 
    	}
    	if(!$post['images']){
    	 $error++;
    	 $message .= "请上传图片！<br>";
    	}
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		$data = array(
    				'name'=>$post['name'],
    				'brand_name'=>$post['brand_name'],
    				'type'=>$post['type'],
    				'category'=>$post['category'],
    				'score'=>$post['score'],
    				'market_price'=>$post['market_price'],
    				'stock'=>$post['stock'],
    				'images'=>$post['images'],
    				'description'=>$post['description'],
    				'content'=>$post['content'],
    				'status'=>(int)$post['status']);
    		if($pottype=='edit'){
    			$data['id']=$post['id'];
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