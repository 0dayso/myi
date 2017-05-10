<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpppglController extends Zend_Controller_Action
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
    	$this->view->openpicurl   = "/icwebadmin/common/openpic";
    	$this->view->uplodimgurl   = "/icwebadmin/common/uplodimg";
    	
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
    	$this->brand = new Icwebadmin_Model_DbTable_Brand();
    }
    public function indexAction(){
    	$perpage=40;
    	$total_query = $this->brand->getBySql("SELECT count(id) as num FROM brand",array());
    	$total = $total_query[0]['num'];
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->brandlist = $this->brand->getBySql("SELECT * FROM brand ORDER BY displayorder ASC LIMIT $offset,$perpage",array());
    }
    //添加
    public function addAction(){
    	//$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$status        = (int)($formData['status']);
    		$type          = (int)($formData['type']);
    		$show          = (int)($formData['show']);
    		$name         = trim($this->filter->pregHtmlSql($formData['name']));
    		$name_en = trim($this->filter->pregHtmlSql($formData['name_en']));
    		$introduction        = $formData['introduction'];
    		$displayorder        = $formData['displayorder'];
    		$logo         = $this->filter->pregHtmlSql($formData['logo']);
    		$content      = $formData['content'];
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写名称。<br/>';
    			$error++;
    		}else{
    			$redep1 = $this->brand->getRowByWhere("name='{$name}'");
    			if(!empty($redep1)){
    				$message .='名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if(empty($name_en)){
    			$message .='请填写英文名称 。<br/>';
    			$error++;
    		}else{
    			$redep2 = $this->brand->getRowByWhere("name_en='{$name_en}'");
    			if(!empty($redep2)){
    				$message .='英文名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->brand->addDate(array('type'=>$type,
    					'show'=>$show,
    					'name'=>$name,
    					'name_en'=>$name_en,
    					'introduction'=>$introduction,
    					'logo'=>$logo,
    					'content'=>$content,
    					'displayorder'=>$displayorder,
    					'status'=>$status,
    					'created'=>time(),
    					'modified'=>time()));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    //编辑
    public function editAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
        if($this->getRequest()->isPost()){
        $formData      = $this->getRequest()->getPost();
    		$status          = (int)($formData['status']);
    		$id           = (int)$formData['id'];
    		$type         = (int)$formData['type'];
    		$show         = (int)$formData['show'];
    		$name         = trim($this->filter->pregHtmlSql($formData['name']));
    		$name_en      = trim($this->filter->pregHtmlSql($formData['name_en']));
    		$oa_name      = $this->filter->pregHtmlSql($formData['oa_name']);
    		$introduction = $formData['introduction'];
    		$displayorder = $formData['displayorder'];
    		$apporder     = $formData['apporder'];
    		$ad_image     = $formData['ad_image'];
    		$logo         = '';
    		if(!empty($formData['image'])){
    			foreach($formData['image'] as $v){
    				$logo .=$v.'|';
    			}
    		}
    		$content      = str_replace('\\','',$formData['content']); //替换 为了处理图片
    		$error = 0;$message='';
    		if(empty($name)){
    			$message ='请填写名称。<br/>';
    			$error++;
    		}else{
    			$redep1 = $this->brand->getRowByWhere("name='{$name}' AND id!='{$id}'");
    			if(!empty($redep1)){
    				$message .='名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if(empty($name_en)){
    			$message .='请填写英文名称 。<br/>';
    			$error++;
    		}else{
    			$redep2 = $this->brand->getRowByWhere("name_en='{$name_en}' AND id!='{$id}'");
    			if(!empty($redep2)){
    				$message .='英文名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			$_SESSION['messages'] = $message;
    			//echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			//exit;
    		}else{
    			$data = array('type'=>$type,
    					'show'=>$show,
    					'name'=>$name,
    					'name_en'=>$name_en,
    					'oa_name'=>$oa_name,
    					'introduction'=>$introduction,
    					'logo'=>$logo,
    					'ad_image'=>$ad_image,
    					'content'=>$content,
    					'displayorder'=>$displayorder,
    					'apporder'=>$apporder,
    					'status'=>$status,
    					'modified'=>time());
    			$this->brand->updateById($data, $id);	
    			$_SESSION['messages'] = '编辑成功。';
    			//echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			//exit;
    		}
    	}
    	$ID   =  $_GET['ID'];
    	$this->view->reArray =$this->brand->getRowByWhere("id='{$ID}'");
    }
}