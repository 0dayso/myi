<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpSeleController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_propertyservice;
	private $_prodService;
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
    	$this->propertyurl= $this->view->propertyurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/property";
    	$this->propertyvalueurl= $this->view->propertyvalueurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/propertyvalue";
    	$this->selectionurl= $this->view->selectionurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/selection";
    	
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
    	
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    	$this->_propertyservice = new Icwebadmin_Service_PropertyService();
    }
    public function indexAction(){
    	//产品目录
    	$prodCategory = $this->_prodService->getProdCategory();
    	$this->view->first  = $prodCategory['first'];
    	$this->view->second = $prodCategory['second'];
    	$this->view->third  = $prodCategory['third'];
    }
    /**
     * 选型页面
     */
    public function selectionAction(){
    	$cid = (int)$this->_getParam('cid');
    	$this->view->pc_name = $_GET['pc'];
    	$this->view->category = $this->_prodService->getProdCategoryById($cid);
    	if(!$this->view->category) $this->_redirect($this->indexurl);
    	//与属性关系
    	$this->view->propertycategory = $this->_propertyservice->getPropertyCategoryByCid($cid,"AND pc.status=1");

    	//查询产品分类级别
    	$clevel = $this->_prodService->getProdCategoryLevel($cid);
    	//查询产品
    	$total = $this->_propertyservice->getProdCount($cid,$clevel,$_GET['pc']);
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->product = $this->_propertyservice->getProd($cid,$clevel,$_GET['pc'],$offset,$perpage);
    }
    /**
     * 属性管理页
     */
    public function propertyAction(){
    	$this->view->property = $this->_propertyservice->getProperty();
    }
    /**
     * 属性值页
     */
    public function propertyvalueAction(){
    	$pid = (int)$this->_getParam('pid');
    	$re = $this->_propertyservice->getProperty(" AND id = '$pid'");
    	if(!$re) $this->_redirect($this->indexurl);
    	else $this->view->property = $re[0];
    	$this->view->propertyvalue = $this->_propertyservice->getPropertyValue($pid);
    }
    /**
     * 添加属性
     */
    public function addpropertyAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$status   = (int)($formData['status']);
    		$cname    = $formData['cname'];
    		$ename    = $formData['ename'];
    		$displayorder  = (int)($formData['displayorder']);
    
    		$error = 0;$message='';
    		if(empty($cname)){
    			$message ='请填写名称。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_propertymoder = new Icwebadmin_Model_DbTable_Model('property');
    			$this->_propertymoder->addData(array('cname'=>$cname,
    					'ename'=>$ename,
    					'displayorder'=>$displayorder,
    					'status'=>$status));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    /**
     * 编辑属性
     */
    public function editpropertyAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$pid      = (int)($formData['pid']);
    		$status   = (int)($formData['status']);
    		$cname    = $formData['cname'];
    		$ename   = $formData['ename'];
    		$displayorder  = (int)($formData['displayorder']);
    
    		$error = 0;$message='';
    		if(empty($pid)){
    			$message ='属性为空。<br/>';
    			$error++;
    		}
    		if(empty($cname)){
    			$message ='属性名称为空。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_propertymoder = new Icwebadmin_Model_DbTable_Model('property');
    			$this->_propertymoder->update(array('cname'=>$cname,
    					'ename'=>$ename,
    					'displayorder'=>$displayorder,
    					'status'=>$status),"id = '{$pid}'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    	$pid = (int)$this->_getParam('pid');
    	$re = $this->_propertyservice->getProperty(" AND id = '$pid'");
    	$this->view->property = $re[0];
    }
    /**
     * 添加属性值
     */
    public function addvalueAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$pid      = (int)($formData['pid']);
    		$status   = (int)($formData['status']);
    		$value    = $formData['value'];
    		$displayorder  = (int)($formData['displayorder']);
    		
    		$error = 0;$message='';
    		if(empty($pid)){
    			$message ='属性为空。<br/>';
    			$error++;
    		}
    		if(empty($value)){
    			$message ='属性值为空。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_propertyvaluemoder = new Icwebadmin_Model_DbTable_Model('property_value');
    			$this->_propertyvaluemoder->addData(array('property_id'=>$pid,
    					'value'=>$value,
    					'displayorder'=>$displayorder,
    					'status'=>$status));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    	$pid = (int)$this->_getParam('pid');
    	$re = $this->_propertyservice->getProperty(" AND id = '$pid'");
    	if(!$re) $this->_redirect($this->indexurl);
    	else $this->view->property = $re[0];
    }
    /**
     * 编辑属性值
     */
    public function editvalueAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$pvid      = (int)($formData['pvid']);
    		$status   = (int)($formData['status']);
    		$value    = $formData['value'];
    		$displayorder  = (int)($formData['displayorder']);
    
    		$error = 0;$message='';
    		if(empty($pvid)){
    			$message ='属性为空。<br/>';
    			$error++;
    		}
    		if(empty($value)){
    			$message ='属性值为空。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_propertyvaluemoder = new Icwebadmin_Model_DbTable_Model('property_value');
    			$this->_propertyvaluemoder->update(array('value'=>$value,
    					'displayorder'=>$displayorder,
    					'status'=>$status),"id = '{$pvid}'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    	$pid = (int)$this->_getParam('pid');
    	$re = $this->_propertyservice->getProperty(" AND id = '$pid'");
    	$this->view->property = $re[0];
    	
    	$pvid = (int)$this->_getParam('pvid');
    	$this->view->propertyvalue = $this->_propertyservice->getPropertyValueByid($pvid);
    }
    /**
     * 编辑产品分类与属性
     */
    public function editcategorypropertyAction(){
    	$cid = (int)$this->_getParam('cid');
    	$this->view->category = $this->_prodService->getProdCategoryById($cid);
    	if(!$this->view->category) $this->_redirect($this->indexurl);
    	//与属性关系
    	$this->view->propertycategory = $this->_propertyservice->getPropertyCategoryByCid($cid);
    }
    /**
     * 编辑产品属性与分类关系
     */
    public function editcpvalueAction(){
        $this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData      = $this->getRequest()->getPost();
    		$pcid     = (int)($formData['pcid']);
    		$property_id = (int)($formData['property_id']);
    		$status     = (int)($formData['status']);
    		$displayorder     = (int)($formData['displayorder']);
    		$this->pcmoder = new Icwebadmin_Model_DbTable_Model('property_category');
    		$re = $this->pcmoder->update(array('property_id'=>$property_id,'status'=>$status,'displayorder'=>$displayorder), "id='{$pcid}'");
    		if($re){
    		   echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    		   exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'更新失败。'));
    			exit;
    		}
    	}
    	$pcid = (int)$this->_getParam('pcid');
    	$this->view->propertycategory = $this->_propertyservice->getPropertyCategoryByPcid($pcid);
    	if(!$this->view->propertycategory) $this->_redirect($this->indexurl);
    	$this->view->property = $this->_propertyservice->getProperty();
    }
    /**
     * 添加产品属性与分类关系
     */
    public function addcpvalueAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData      = $this->getRequest()->getPost();
    		$pcid        = (int)($formData['pcid']);
    		$cid        = (int)($formData['cid']);
    		$property_id = (int)($formData['property_id']);
    		$status      = (int)($formData['status']);
    		$displayorder     = (int)($formData['displayorder']);
    		$this->pcmoder = new Icwebadmin_Model_DbTable_Model('property_category');
    		$old = $this->pcmoder->getRowByWhere("property_id='{$property_id}' AND category_id='{$cid}'");
    		if($old){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'此属性已经存在。'));
    			exit;
    		}
    		$re = $this->pcmoder->addData(array('category_id'=>$cid,'property_id'=>$property_id,'status'=>$status,'displayorder'=>$displayorder));
    		if($re){
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'添加失败。'));
    			exit;
    		}
    	}
    	$this->view->cid = (int)$this->_getParam('cid');
    	$this->view->property = $this->_propertyservice->getProperty();
    }
}