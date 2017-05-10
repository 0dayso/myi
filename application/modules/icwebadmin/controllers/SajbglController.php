<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_SajbglController extends Zend_Controller_Action
{
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
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	//加载类
    	$this->level        = new Icwebadmin_Model_DbTable_Level();
    }
    public function indexAction(){
    	$this->view->Level=$this->level->getAllByWhere("Level_ID!=''");
    }
    //添加
    public function addAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Level_ID = $this->filter->pregHtmlSql($formData['levelid']);
    		$Level    = $this->filter->pregHtmlSql($formData['level']);
    		$error = 0;$message='';
    		if(empty($Level_ID)){
    			$message ='请填写级别编号。<br/>';
    			$error++;
    		}
    		if(empty($Level)){
    			$message .='请填写级别名称。<br/>';
    			$error++;
    		}
    		if(!$this->filter->checkUpper($Level_ID)){
    			$message .='级别编号必须为大写字母。<br/>';
    			$error++;
    		}
    		if(!$this->filter->checkLength($Level_ID,2,4)){
    			$message .='级别编号长度必须为2-4。<br/>';
    			$error++;
    		}
    		$redep1 = $this->level->getRowByWhere("level_id='{$Level_ID}'");
    		$redep2 = $this->level->getRowByWhere("level='{$Level}'");
    		if(!empty($redep1)){
    			$message .='级别编号已经存在。<br/>';
    			$error++;
    		}
    		if(!empty($redep2)){
    			$message .='级别名称已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->level->add(array('level_id'=>$Level_ID,'level'=>$Level,'updatedate'=>date('Y-m-d H:i:s')));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    //编辑
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	$Level_ID   =  $_GET['ID'];
    	$reArray =$this->level->getRowByWhere("level_id='{$Level_ID}'");
    	if($reArray){
    		$this->view->Level_ID = $reArray['level_id'];
    		$this->view->Level    = $reArray['level'];
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Level_ID = $this->filter->pregHtmlSql($formData['levelid']);
    		$Level    = $this->filter->pregHtmlSql($formData['level']);
    		$error = 0;$message='';
    		if(empty($Level_ID)){
    			$message .='请填写部门名称。<br/>';
    			$error++;
    		}
    		$redep = $this->level->getRowByWhere("level_id!='{$Level_ID}' AND level='{$Level}'");
    		if(!empty($redep)){
    			$message .='部门名称已经存在。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->level->updateById(array('level'=>$Level,'updatedate'=>date("Y-m-d H:i:s")),$Level_ID);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    }
    //删除
    public function deleteAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Level_ID = $formData['ID'];
    		$staff = new Icwebadmin_Model_DbTable_Staff();
    		$flag=$staff->getRowByWhere("level_id='{$Level_ID}' AND status = '1'");
    		if(!empty($flag)) {
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>"该级别有用户在使用，不能删除。"));
    			exit;
    		}else
    		{
    			$this->level->deleteById($Level_ID);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"删除成功。"));
    			exit;
    		}
    	}
    }
}