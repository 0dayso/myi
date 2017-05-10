<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_SampSpglController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
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
    	$this->productservice = new Icwebadmin_Service_ProductService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	$this->samplesService = new Icwebadmin_Service_SamplesService();
    }
    public function indexAction(){
    	$typestr = '';
    	$this->view->type = $_GET['type']?$_GET['type']:'on';
    	//在线
    	$onsql   = " AND sp.status='1'";
    	//下线
    	$unsql   = " AND sp.status='0'";
    	
    	$this->view->onnum = $this->samplesService->getSamplesNum($onsql);
    	$this->view->unnum = $this->samplesService->getSamplesNum($unsql);
    	

    	if($this->view->type=='on'){
    		$total = $this->view->onnum;
    		$typestr = $onsql;
    	}elseif($this->view->type=='un'){
    		$total = $this->view->unnum;
    		$typestr = $unsql;
    	}else $this->_redirect ( '/icwebadmin' );
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->samplesall = $this->samplesService->getSamples($offset, $perpage, $typestr);
    }
    /**
     * 添加
     */
    public function addAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$this->view->sparr = $data = $this->getRequest()->getPost();
    		$part_id = $data['part_id'];
    		if(!$part_id){
    			$_SESSION['messages'] = "请填入正确的产品ID";
    		}else{
    			$arr = $this->productservice->getProductByID($part_id);
    			if(!$arr){
    				$_SESSION['messages'] = "此产品id不存在 或 已经下线";
    			}else{
    				$this->samplesService->addsample(array('part_id'=>$part_id,'hot_top'=>$data['hot_top']));
    				$_SESSION['messages'] = "添加成功";
    			}
    		}
    	}
    }
    /**
     * 编辑
     */
    public function editAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$spid = $_GET['id'];
    	$this->view->sparr = $this->samplesService->getSamplesById($spid);
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$id = $data['id'];
    		$part_id = $data['part_id'];
    		if(!$part_id){
    			$_SESSION['messages'] = "请填入正确的产品ID";
    		}else{
    		    $arr = $this->productservice->getProductByID($part_id);
    		    if(!$arr){
    		    	$_SESSION['messages'] = "此产品id不存在 或 已经下线";
    		    }else{
    		    	$this->samplesService->upsample($id,array('part_id'=>$part_id,'hot_top'=>$data['hot_top']));
    		    	$_SESSION['messages'] = "更新成功";
    		    }
    		}
    		$this->view->sparr['part_id'] = $part_id;
    		$this->view->sparr['hot_top'] = $data['hot_top'];
    	}
    }
    /**
     * 删除
     */
    public function deletlAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id = $_POST['id'];
    	$this->samplesService->deletesample($id);
    }
}