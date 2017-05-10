<?php
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_SaareaController extends Zend_Controller_Action
{
    public function init(){ 
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$this->controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $this->controller));
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
    	$this->view->areaaddurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/areaadd";
    	$this->view->areaediturl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/areaedit";
    	$this->view->areadeleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/areadelete";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	//加载部门Service类
    	$this->sectionservice = new Icwebadmin_Service_SectionService();
    }
    public function indexAction(){
    	$this->view->Section=$this->sectionservice->getSection();
    }
    //添加section
    public function addAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$filter = new MyFilter();
    		$formData      = $this->getRequest()->getPost();
    		$Section_Area_ID  = $filter->pregHtmlSql($formData['sectionid']);
    		$Section_Area_Des = $filter->pregHtmlSql($formData['sectiondesc']);
    		$Order_ID         = (int)($formData['orderid']==''?0:$formData['orderid']);
    		$error = 0;$message='';
    		if(empty($Section_Area_ID)){
    			$message ='请填写Section Area ID。<br/>';
    			$error++;
    		}
    		if(empty($Section_Area_Des)){
    			$message .='请填写标题。<br/>';
    			$error++;
    		}
    		if(!$filter->checkStartUpper($Section_Area_ID)){
    			$message .='Section Area ID必须以大写字母开头加小写字母。<br/>';
    			$error++;
    		}
    		if(!$filter->checkLength($Section_Area_ID,2,4)){
    			$message .='Section Area ID长度必须为2-4。<br/>';
    			$error++;
    		}
    		$exist = $this->sectionservice->checkDectionid($Section_Area_ID);
    		if($exist){
    			$message .='Section Area ID已经存在。<br/>';
    			$error++;
    		}
    		$existdes = $this->sectionservice->checkTitle($Section_Area_Des);
    		if($existdes){
    			$message .='标题已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    		    echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    		    exit;
    		}else{
    			$section = new Icwebadmin_Model_DbTable_Section();
    			$section->addSection(array('section_area_id'=>$Section_Area_ID,'section_area_des'=>$Section_Area_Des,'order_id'=>$Order_ID));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    //编辑section
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	$ID   =  $_GET['ID'];
    	$section = new Icwebadmin_Model_DbTable_Section();
    	$reArray =$section->getRowByWhere("section_area_id='{$ID}'");
    	if($reArray){
    		$this->view->Section_Area_ID = $reArray['section_area_id'];
    		$this->view->Order_ID        = $reArray['order_id'];
    		$this->view->Section_Area_Des= $reArray['section_area_des'];
    	}
    	if($this->getRequest()->isPost()){
    	    $filter = new MyFilter();
    		$formData         = $this->getRequest()->getPost();
    		$Section_Area_ID  = $filter->pregHtmlSql($formData['sectionid']);
    		$Section_Area_Des = $filter->pregHtmlSql($formData['sectiondesc']);
    		$Order_ID         = (int)($formData['orderid']==''?0:$formData['orderid']);
    		$error = 0;$message='';
    		if(empty($Section_Area_Des)){
    			$message .='请填写标题。<br/>';
    			$error++;
    		}
    		$existdes = $this->sectionservice->checkTitle($Section_Area_Des,$Section_Area_ID);
    		if($existdes){
    			$message .='标题已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    		    echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    		    exit;
    		}else{
    			$section->updateById(array('section_area_des'=>$Section_Area_Des,'order_id'=>$Order_ID),$Section_Area_ID);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    }
    //删除section
	public function deleteAction(){
		$this->_helper->layout->disableLayout();
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
			exit;
		}
		if($this->getRequest()->isPost()){
			$formData        = $this->getRequest()->getPost();
			$Section_Area_ID = $formData['ID'];
			$area = new Icwebadmin_Model_DbTable_Sectionarea();
	        $flag=$area->getRowByWhere("section_area_id='{$Section_Area_ID}'");
			if(!empty($flag)) {
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>"该区域具有area使用，请先删除所有area。"));
				exit;
			}else 
			{
				$section = new Icwebadmin_Model_DbTable_Section();
				$section->deleteById($Section_Area_ID);
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"删除成功。"));
			    exit;
			}
		}
	}
	//进入area
	public function areaAction(){
		$filter = new MyFilter();
		$sectionid   =  $filter->pregHtmlSql($_GET['sectionid']);
		if(!empty($sectionid))
		{
			$section = new Icwebadmin_Model_DbTable_Section();
    	    $reArray =$section->getRowByWhere("section_area_id='{$sectionid}'");
			if(!empty($reArray))
			{
				$this->view->Section_Area_ID = $reArray['section_area_id'];
				$this->view->Section_Area_Des= $reArray['section_area_des'];
				$area = new Icwebadmin_Model_DbTable_Sectionarea();
			    $this->view->Area = $area->getAllByWhere("section_area_id = '{$sectionid}'","CAST(`order_id` AS SIGNED) ASC ",0);
			}else $this->_redirect($this->indexurl);
		}else $this->_redirect($this->indexurl);
	}
	//添加area
	public function areaaddAction(){
		$this->_helper->layout->disableLayout();
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
			exit;
		}
		$filter = new MyFilter();
		if($this->getRequest()->isPost()){
			$formData      = $this->getRequest()->getPost();
			$sectionid     = $filter->pregHtmlSql($formData['sectionid']);
		}else $sectionid   =  $filter->pregHtmlSql($_GET['sectionid']);
		if(!empty($sectionid))
		{
			$section = new Icwebadmin_Model_DbTable_Section();
			$reArray =$section->getRowByWhere("section_area_id='{$sectionid}'");
			if(!empty($reArray)){
				$this->view->Section_Area_ID = $reArray['section_area_id'];
			}else $this->_redirect($this->indexurl);
		}else $this->_redirect($this->indexurl);
		//处理提交
		if($this->getRequest()->isPost()){
			$filter = new MyFilter();
			$Section_Area_ID  = $filter->pregHtmlSql($formData['sectionid']);
			$Staff_Area_ID    = $filter->pregHtmlSql($formData['areaid']);
			$Staff_Area_Des   = $filter->pregHtmlSql($formData['areadesc']);
			$Order_ID         = (int)($formData['orderid']==''?0:$formData['orderid']);
			$Status           = (int)($formData['status']);
			$error = 0;$message='';
			
			if(empty($Staff_Area_ID)){
				$message ='请填写Staff Area ID。<br/>';
				$error++;
			}
			if(empty($Staff_Area_Des)){
				$message .='请填写标题。<br/>';
				$error++;
			}
			if(!$filter->checkStartUpper($Staff_Area_ID)){
				$message .='Staff Area ID必须以大写字母开头加小写字母。<br/>';
				$error++;
			}
			if(!$filter->checkLength($Staff_Area_ID,2,4)){
				$message .='Staff Area ID长度必须为2-4。<br/>';
				$error++;
			}
			$this->areaservice = new Icwebadmin_Service_AreaService();
			$exist = $this->areaservice->checkDectionid($Staff_Area_ID);
			if($exist){
				$message .='Staff Area ID已经存在。<br/>';
				$error++;
			}
			$existdes = $this->areaservice->checkTitle($Staff_Area_Des);
			if($existdes){
				$message .='标题已经存在。<br/>';
				$error++;
			}
			if($error){
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				$area = new Icwebadmin_Model_DbTable_Sectionarea();
				$newid = $area->addArea(array('section_area_id'=>$Section_Area_ID,
						'staff_area_id'=>$Staff_Area_ID,
						'staff_area_des'=>$Staff_Area_Des,
						'url'  => '/icwebadmin/'.$Section_Area_ID.$Staff_Area_ID,
						'order_id' => $Order_ID,
						'status' => $Status));
				//建立controllers和views
				if($newid)
				{
					//创建controllers
					$insert_txt = $this->mycommon->getControllersTxt($Section_Area_ID,$Staff_Area_ID);
					$this->mycommon->createFile('/modules/icwebadmin/controllers/',$Section_Area_ID.strtolower($Staff_Area_ID).'Controller.php',$insert_txt);
					//创建view文件夹
					$viewFolder = '/modules/icwebadmin/views/scripts/'.strtolower($Section_Area_ID.'-'.$Staff_Area_ID).'/';
					$folder=$this->mycommon->createFolder($viewFolder);
					if($folder)
					{
						//添加文件
						$view_txt=$this->mycommon->getViewsTxt();
						$this->mycommon->createFile($viewFolder,'index.phtml',$view_txt);
					}
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
				    exit;
				}
				else {
					echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'添加失败。'));
					exit;
				}
			}
		}
	}
	//编辑area
	public function areaeditAction(){
		$this->_helper->layout->disableLayout();
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
			exit;
		}
		$ID   =  $_GET['ID'];
		$area = new Icwebadmin_Model_DbTable_Sectionarea();
		$reArray =$area->getRowByWhere("Staff_Area_ID='{$ID}'",'',0);
		if($reArray){
			$this->view->Staff_Area_ID   = $reArray['staff_area_id'];
			$this->view->URL             = $reArray['url'];
			$this->view->Staff_Area_Des  = $reArray['staff_area_des'];
			$this->view->Order_ID        = $reArray['order_id'];
			$this->view->Status          = $reArray['status'];
		}
		if($this->getRequest()->isPost()){
			$filter = new MyFilter();
			$formData         = $this->getRequest()->getPost();
			$Staff_Area_ID    = $filter->pregHtmlSql($formData['areaid']);
			$Staff_Area_Des   = $filter->pregHtmlSql($formData['areadesc']);
			$Order_ID         = (int)($formData['orderid']==''?0:$formData['orderid']);
			$Status           = (int)($formData['status']);
			$error = 0;$message='';
			if(empty($Staff_Area_Des)){
				$message .='请填写标题。<br/>';
				$error++;
			}
			$this->areaservice = new Icwebadmin_Service_AreaService();
			$existdes = $this->areaservice->checkTitle($Staff_Area_Des,$Staff_Area_ID);
			if($existdes){
				$message .='标题已经存在。<br/>';
				$error++;
			}
			if($error){
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				$area->updateById(array('staff_area_des'=>$Staff_Area_Des,'order_id'=>$Order_ID,'status'=>$Status),$Staff_Area_ID);
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
				exit;
			}
		}
	}
	//删除area
	public function areadeleteAction(){
		$this->_helper->layout->disableLayout();
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
			exit;
		}
		if($this->getRequest()->isPost()){
			$formData        = $this->getRequest()->getPost();
			$Staff_Area_ID = $formData['ID'];
			$area = new Icwebadmin_Model_DbTable_Sectionarea();
			$area->deleteById($Staff_Area_ID);
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"删除成功。"));
			exit;
		}
	}
}