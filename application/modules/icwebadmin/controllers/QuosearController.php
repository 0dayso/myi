<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_QuoSearController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_serchinqModel;
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
    	
    	$this->_serchinqModel = new Icwebadmin_Model_DbTable_SearchInquiry();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$sqlstr = "SELECT count(uid) as allnum FROM search_inquiry WHERE status=0";
    	$allnumarr = $this->_serchinqModel->getByOneSql($sqlstr);
    	$total = $allnumarr['allnum'];
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	 
    	$sqlstr = "SELECT si.*,u.uname,up.companyname FROM search_inquiry as si 
    	LEFT JOIN user as u ON u.uid=si.uid
    	LEFT JOIN user_profile as up ON up.uid=si.uid
    	ORDER BY si.status ASC,si.created DESC  LIMIT $offset,$perpage ";
    	$this->view->serchinq = $this->_serchinqModel->getBySql($sqlstr);
    }
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->id  = $_GET['id'];
    	if($this->getRequest()->isPost()){
    		$formData   = $this->getRequest()->getPost();
    		$id         = $formData['id'];
    		$part_id    = $formData['part_id'];
    		$result = $formData['result'];
    		if(!$part_id)
    		{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'请填新上线产品ID。'));
    			exit;
    		}
    		$prodService = new Icwebadmin_Service_ProductService();

    		//发送通知邮件
    		
    		$prodService = new Icwebadmin_Service_ProductService();
    		$prodinfo = $prodService->getInqProd($part_id);
    		$serchinfo = $this->_serchinqModel->getByOneSql("SELECT sinq.*,u.uname,u.email from search_inquiry as sinq
    				LEFT JOIN user as u on sinq.uid = u.uid
    				WHERE sinq.id='{$id}'");
    		$emailre = $prodService->newprodEmail($prodinfo,$serchinfo);

    		if($emailre){
    			$re = $this->_serchinqModel->updateById(array('result'=>$result,'status'=>101), $id);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$id,'temp4'=>'发送新品上线邮件通知成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'发送邮件给客户成功。'));
    			exit;
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$id,'temp4'=>'发送新品上线邮件通知失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'发送邮件给客户失败。'));
    			exit;
    		}
    	
    	}
    }
}