<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_CpcplxController extends Zend_Controller_Action
{
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
    	$this->view->selectparenturl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/selectparent";
    	$this->view->cleancacheurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/cleancache";
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
    	$this->producttype = new Icwebadmin_Model_DbTable_ProdCategory();
    	
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    }
    public function indexAction(){
    	//产品目录
		
		$prodCategory = $this->_prodService->getProdCategory();
		$this->view->first  = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
    }
    /**
     * 清除缓存
     */
    public function cleancacheAction(){
    	$re = false;
    	$myfile1 = CACHE_PATH.'zend_cache---pcall_index_cache';
    	$myfile2 = CACHE_PATH.'zend_cache---internal-metadatas---pcall_index_cache';
    	if (file_exists($myfile1)) {
    	    if (unlink($myfile1)) {
    	    	$re = true;
            } 
    	}
    	if (file_exists($myfile2)) {
    		if (unlink($myfile2)) {
    			$re = true;
    		}
    	}
    	if($re){
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'清除缓存成功'));
    		exit;
    	}else{
    	    echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'清除缓存失败'));
    	    exit;
    	}
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
    		$status   = (int)($formData['status']);
    		$show_home = (int)($formData['show_home']);
    		$level    = (int)($formData['level']);
    		if($level==1) $parentid = 0;
    		else $parentid = (int)($formData['parentid']);
    		$ptname   = $this->filter->pregSql($formData['ptname']);
    		$name_en  = $this->filter->pregSql($formData['name_en']);
    		$ptorder  = (int)($formData['ptorder']);
    		$ptorder  =$ptorder==''?0:$ptorder;
    		$error = 0;$message='';
    		if(empty($ptname)){
    			$message ='请填名称。<br/>';
    			$error++;
    		}else{
    			$rearray = $this->producttype->getRowByWhere("name ='{$ptname}' AND level='{$level}'");
    			if(!empty($rearray)){
    				$message .='名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($level!=1 && $parentid){
    			$where = '';
    			if($level == 2) $where = '  AND level = 1 ';
    			elseif($level == 3) $where = '  AND level = 2 ';
    			$re = $this->producttype->getRowByWhere("id ='{$parentid}' $where");
    			if(empty($re)){
    				$message .='父级分类id不存在<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->producttype->addData(array('name'=>$ptname,
    					'name_en'=>$name_en,
    					'level'=>$level,
    					'parent_id'=>$parentid,
    					'show_home'=>$show_home,
    					'displayorder'=>$ptorder,
    					'status'=>$status));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功。'));
    			exit;
    		}
    	}
    }
    //更新
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$id       = (int)($formData['id']);
    		$status   = (int)($formData['status']);
    		$show_home = (int)($formData['show_home']);
    		$level    = (int)($formData['level']);
    		if($level==1) $parentid = 0;
    		else $parentid = (int)($formData['parentid']);
    		$ptname   = $this->filter->pregSql($formData['ptname']);
    		$name_en  = $this->filter->pregSql($formData['name_en']);
    		$ptorder  = (int)($formData['ptorder']);
    		$ptorder  =$ptorder==''?0:$ptorder;
    		$error = 0;$message='';
    		if(empty($ptname)){
    			$message ='请填名称。<br/>';
    			$error++;
    		}else{
    			$rearray = $this->producttype->getRowByWhere("id!={$id} AND name ='{$ptname}' AND level='{$level}'");
    			if(!empty($rearray)){
    				$message .='名称已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($level!=1 && $parentid){
    			$where = '';
    			if($level == 2) $where = '  AND level = 1 ';
    			elseif($level == 3) $where = '  AND level = 2 ';
    			$re = $this->producttype->getRowByWhere("id ='{$parentid}' $where");
    			if(empty($re)){
    				$message .='父级分类id不存在<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->producttype->updateById(array('name'=>$ptname,
    					'name_en'=>$name_en,
    					'level'=>$level,
    					'parent_id'=>$parentid,
    					'show_home'=>$show_home,
    					'displayorder'=>$ptorder,
    					'status'=>$status),$id);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    			exit;
    		}
    	}else{
    		$this->view->category = $this->_prodService->getProdCategoryById((int)$_GET['id']);
    	}
    }
    public function selectparentAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	$this->view->treedata = $this->producttype->getAllByWhere("status='1'");
    }
}