<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_SampSqglController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_samplesservice;
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
    	$this->_samplesservice = new Icwebadmin_Service_SamplesService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	
    	$this->view->fun = new MyFun();
 	
    }
    public function indexAction(){
    	$typestr = $selectstr = '';
    	$this->view->select = $_GET['select'];
    	if($this->view->select) $this->view->type = $_GET['type']?$_GET['type']:'already';
    	else $this->view->type = $_GET['type']?$_GET['type']:'wait';
    	
    	//负责销售
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	if($staffinfo['level_id']=='XS'){
    		if($staffinfo['control_staff']){
    			$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    			$control_staff_str = implode("','",$control_staff_arr);
    			$selectstr .= " AND up.staffid IN ('".$control_staff_str."')";
    		}else{
    			$selectstr .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    		}
    	}else{
    		//根据应用领域分配跟进销售
    		$this->view->xs_staff = $this->_staffservice->getXiaoShou();
    		$this->view->xsname = $_GET['xsname'];
    		if($_GET['xsname']){
    			$selectstr .= " AND up.staffid = '".$_GET['xsname']."'";
    		}
    	}
    	//搜索
    	if($_GET['salesnumber']) {
    		$this->view->salesnumber = $salesnumber = $_GET['salesnumber'];
    		if($salesnumber){
    			$selectstr .="AND spa.salesnumber LIKE '%".$salesnumber. "%'";
    		}
    	}
    	//待处理
    	$waitsql   = " AND spa.status='100' ".$selectstr;
    	//处理中
    	$procsql   = " AND spa.status='200' ".$selectstr;
    	//已处理
    	$alreadysql= " AND spa.status='300' ".$selectstr;
    	//已发货
    	$sendsql= " AND spa.status='301' ".$selectstr;
    	//被取消
    	$cancelsql = " AND spa.status='400' ".$selectstr;
    	
    	$this->view->waitnum = $this->_samplesservice->getApplyNum($waitsql);
    	$this->view->procnum = $this->_samplesservice->getApplyNum($procsql);
    	$this->view->alreadynum = $this->_samplesservice->getApplyNum($alreadysql);
    	$this->view->sendnum = $this->_samplesservice->getApplyNum($sendsql);
    	$this->view->cancelnum = $this->_samplesservice->getApplyNum($cancelsql);
    	if($this->view->type=='wait'){
    		$total = $this->view->waitnum;
    		$typestr = $waitsql;
    	}elseif($this->view->type=='proc'){
    		$total = $this->view->procnum;
    		$typestr = $procsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
    	}elseif($this->view->type=='send'){
    		$total = $this->view->sendnum;
    		$typestr = $sendsql;
    	}elseif($this->view->type=='cancel'){
    		$total = $this->view->cancelnum;
    		$typestr = $cancelsql;
    	}else $this->_redirect ( '/icwebadmin' );
    	
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->applyall = $this->_samplesservice->getApply($offset, $perpage, $typestr);
    }
    /**
     * 审核
     */
    public function releaseAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->applyid = (int)$_GET['id'];
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id = (int)$formData['id'];
    		$detailedid = $formData['detailedid'];
    		$approvenum = $formData['approvenum'];
    	
    		$this->_samplesapplyModel = new Icwebadmin_Model_DbTable_Model("samples_apply");
    		$re = $this->_samplesapplyModel->update(array('status'=>$formData['status'],
    				'remark'=>$formData['remark'],
    				'modified'=>time(),
    				'modified_by'=>$_SESSION['staff_sess']['staff_id']), "id='{$id}'");
    		if($re){
    			//批准数量
    			if($formData['status'] == 200){
    				$this->_samplesdetailedModel = new Icwebadmin_Model_DbTable_Model("samples_detailed");
    				foreach($detailedid as $k=>$id){
    					$this->_samplesdetailedModel->update(array('approvenum'=>(int)$approvenum[$k]), "id='{$id}'");
    				}
    			}
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'审核成功。'.implode(';',$formData)));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'审核成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'审核失败','description'=>'更新数据库失败'.implode(';',$formData)));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'处理失败'));
    			exit;
    		}
    	}
    	$this->view->apply = $this->_samplesservice->getApplyById($this->view->applyid);
    }
    /**
     * 释放
     */
    public function processAction(){
    	$this->_helper->layout->disableLayout();
    	$couModel = new Icwebadmin_Model_DbTable_Courier();
    	$applyid = (int)$_GET['id'];
    	$this->view->apply = $this->_samplesservice->getApplyById($applyid);
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id = (int)$formData['id'];
    		$this->_samplesapplyModel = new Icwebadmin_Model_DbTable_Model("samples_apply");
    		$salesnumber = '0'.(intval(date('Y'))%10).date('m').date('d').substr(microtime(),2,4).'-'.substr(time(),-5);
    		$re = $this->_samplesapplyModel->update(array('salesnumber'=>$salesnumber,'status'=>300),"id = {$id}");
    		if($re){
    			//邮件通知cse
    			$apply = $this->_samplesservice->getApplyById($id);
    			if($apply){
    			    $re = $this->_samplesservice->emailcse($apply);
    			    if($re){
    			    	$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'释放样片订单邮件发送成功'));
    			    }else{
    			    	$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'释放样片订单邮件发送失败'));
    			    }
    			    //通知客户
    			    $this->_samplesservice->emailuser($apply);
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'释放样片订单查记录为空'));
    			}
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'释放样片订单成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'释放样片订单成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'释放样片订单失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'释放样片订单失败'));
    			exit;
    		}
    	}
    }
    public function getdwAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$num = 0;
    	$part_no = $_POST['part_no'];
    	if($part_no){
    	   $num = $this->_samplesservice->getAtsSzByPartno($part_no);
    	}
    	echo Zend_Json_Encoder::encode(array("num"=>$num));
    	exit;
    }
}