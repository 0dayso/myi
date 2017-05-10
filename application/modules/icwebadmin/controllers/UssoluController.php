<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_UsSoluController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_ruleservice;
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
    	$this->view->fun = $this->fun = new MyFun();
    	$this->_ruleservice = new Icwebadmin_Service_UserruleService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$sql='';
        $this->view->type = $_GET['type']?$_GET['type']:'wait';
    	//待审批
    	$waitsql   = " AND urd.status='1' AND urd.apply ='1'";
    	//通过   
    	$passsql   = " AND urd.status='1' AND urd.apply ='2'";
    	//不通过
    	$nopasssql = " AND urd.status='1' AND urd.apply= '3'";

    	$this->view->waitnum = $this->_ruleservice->getRuleDetailedNum($waitsql);
    	$this->view->passsnum = $this->_ruleservice->getRuleDetailedNum($passsql);
    	$this->view->nopasssnum = $this->_ruleservice->getRuleDetailedNum($nopasssql);
    	if($this->view->type=='wait'){
    		$total = $this->view->waitnum;
    		$sql = $waitsql;
    	}elseif($this->view->type=='passs'){
    		$total = $this->view->passsnum;
    		$sql = $passsql;
    	}elseif($this->view->type=='nopass'){
    		$total = $this->view->nopasssnum;
    		$sql = $nopasssql;
    	}else $this->_redirect ( '/icwebadmin' );
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->rulearr = $this->_ruleservice->getRuleDetailed($offset,$perpage,$sql);
    }
    public function applyAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id = $this->fun->decryptVerification($formData['key']);
    		$solarr = $this->_ruleservice->getRuleDetailedByid($id);
    		if($solarr){
    			$areas = $rights = '';
    			foreach($formData['Right_Rule'] as $area=>$rule){
    				if($rule!='B'){
    					$areas  .= ($areas?','.$area:$area);
    					$rights .= ($rights?','.$rule:$rule);
    				}
    			}
    			$re = $this->_ruleservice->updateRuleDetailed(array('areas'=>$areas,
    					'rights'=>$rights,
    					'apply'=>$formData['apply'],
    					'remark'=>$formData['remark'],
    					'modified'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id'])," id = ".$solarr['id']);
    			if($re){
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'审核方案申请成功','description'=>$formData['apply'].'||'.$solarr['areas'].';'.$solarr['rights'].'||'.$areas.';'.$rights.'||'.$formData['remark']));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'审核成功'));
    				exit;
    			}else{
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'审核方案申请失败','description'=>'更新失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'审核失败，系统错误'));
    				exit;
    			}
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'审核方案申请失败','description'=>'查询结果为空'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'审核失败，系统错误'));
    			exit;
    		}	
    	}
    	$id = $this->fun->decryptVerification($this->_getParam('key'));
    	$this->view->solarr = $this->_ruleservice->getRuleDetailedByid($id);
    	
    	$uid = $this->_getParam('uid');
    	$this->_usService  = new Icwebadmin_Service_UserService();
    	$this->view->user = $this->_usService->getUserProfile($uid);
    }
}