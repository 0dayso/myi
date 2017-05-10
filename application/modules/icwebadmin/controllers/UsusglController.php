<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/common.php';
class Icwebadmin_UsusglController extends Zend_Controller_Action
{
	private $_userModel;
	private $_userprofileModel;
	private $_usService;
	private $_stService;
	private $_inqservice;
	private $_inqsoservice;
	private $_soservice;
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
    	
    	$this->view->comapprove= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/comapprove";
    	
    	$this->view->shieldurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/shield";
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
    	
    	$this->_userModel = new Icwebadmin_Model_DbTable_User();
    	$this->_userprofileModel = new Icwebadmin_Model_DbTable_UserProfile();
    	$this->_usService  = new Icwebadmin_Service_UserService();
    	$this->_stService = new Icwebadmin_Service_StaffService();
    	$this->_inqservice = new Icwebadmin_Service_InquiryService();
    	$this->_inqsoservice = new Icwebadmin_Service_InqOrderService();
    	$this->_soservice = new Icwebadmin_Service_OrderService();
    	$this->view->fun = $this->fun = new MyFun();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$wherestr = "";
    	//排序
    	$orderbystr = ' ORDER BY u.created DESC';
    	$orderbyarr = array('ASC','DESC');
    	$orderarray = array('created','score');
    	$this->view->ordertype = $ordertype = $_GET['ordertype'];
    	if(in_array($ordertype,$orderarray)){
    		$this->view->orderby = $orderby = $_GET['orderby'];
    		if($ordertype=='created' && in_array($orderby,$orderbyarr)){
    			$orderbystr = " ORDER BY u.created ".$orderby;
    		}
    		if($ordertype=='score' && in_array($orderby,$orderbyarr)){
    			$orderbystr = " ORDER BY up.score ".$orderby;
    		}
    	}else{
    		$this->view->ordertype = 'created';
    		$this->view->orderby = 'DESC';
    	}
    	//开始和结束时间
    	$this->view->sdata = $sdata = $_GET['sdata'];
    	$this->view->edata = $edata = $_GET['edata'];
    	if($sdata){
    		$edata = $edata?strtotime($edata):time();
    		$wherestr .=" AND u.created BETWEEN ".strtotime($sdata)." AND ".$edata;
    	}
    	//用户注册性质
    	$this->view->backstage = $_GET['backstage'];
    	if($this->view->backstage){
    		$wherestr .=" AND u.backstage = ".($this->view->backstage=='back'?'1':'0');
    	}
    	//邮箱认证
    	$this->view->emailapprove = $_GET['emailapprove'];
    	if($this->view->emailapprove){
    		$wherestr .=" AND u.emailapprove = ".($this->view->emailapprove=='pass'?'1':'0');
    	}
    	//如果销售只显示他们的客户
    	$xssql = "";
    	//如果销售只能看到自己负责的询价
    	$staffinfo = $this->_stService->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	
    	if($staffinfo['level_id']=='XS'){
    		if($staffinfo['control_staff']){
    			$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    			$control_staff_str = implode("','",$control_staff_arr);
    			$wherestr .= " AND up.staffid IN ('".$control_staff_str."')";
    		}else{
    			$wherestr .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    		}
    	}else{
    		//根据应用领域分配跟进销售
    		$admin_staffService = new Icwebadmin_Service_StaffService();
    		$this->view->xs_staff = $admin_staffService->getXiaoShou();
    		$this->view->xsname = $_GET['xsname'];
    		if($_GET['xsname']){
    			$wherestr .= " AND up.staffid = '".$_GET['xsname']."'";
    		}
    	}
    	
    	$this->view->type = $_GET['type'];
    	$this->view->keyword = $keyword =$this->filter->pregHtmlSql($_GET['keyword']);
    	
    	if(!empty($keyword)){
    		$wherestr .= " AND (up.`companyname` LIKE '%".$keyword."%' OR u.uname LIKE '%".$keyword."%')";
    	}
    	$sqlstr = "SELECT count(u.uid) as allnum FROM user as u
    			LEFT JOIN user_profile as up ON up.uid=u.uid
    			WHERE u.enable=1 {$wherestr}";
    	
    	$allnumarr = $this->_userModel->getBySql($sqlstr,array());
    	
    	$this->view->selectnum = $total = $allnumarr[0]['allnum'];
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$sqlstr = "SELECT u.*,up.*,uoa.id as uoaid,uoa.status as uoastatus,st.staff_id,st.lastname,st.firstname,dp.department,
    	ac.name as appname,u.created as ucreated,p.province,c.city,e.area
    	FROM user as u
    	LEFT JOIN user_profile as up ON up.uid=u.uid
    	LEFT JOIN user_oa_apply as uoa ON uoa.uid=u.uid
    	LEFT JOIN admin_staff as st ON up.staffid = st.staff_id
    	LEFT JOIN admin_department as dp ON st.department_id = dp.department_id
    	LEFT JOIN app_category as ac ON ac.id = up.industry
    	LEFT JOIN province as p ON up.province=p.provinceid
    	LEFT JOIN city as c ON up.city=c.cityid
    	LEFT JOIN area as e ON up.area = e.areaid
    	WHERE u.uid=up.uid AND u.enable=1 {$wherestr}
    	{$orderbystr}  LIMIT $offset,$perpage ";
    	$inqArr = $this->_userModel->getBySql($sqlstr,array());
    	$this->view->user = $inqArr;
    	
    	//查询是否有待审核的用户
    	$this->view->apptotal =$this->_stService->getAppTotal();
    	
    	//查询被屏蔽用户数量
    	$sqlstr = "SELECT count(u.uid) as num FROM user as u
    	LEFT JOIN user_profile as up ON up.uid=u.uid
    	WHERE u.enable=0 {$xssql}";
    	$shieldArr = $this->_userModel->getBySql($sqlstr,array());
    	$this->view->shieldtotal = $shieldArr[0]['num'];
    }
    
    /*
     * 企业审核列表
     */
    public function comapproveAction(){

    	//查询是否有待审核的用户
    	$total = $this->view->apptotal = $this->_stService->getAppNum();
    	
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->user = $this->_stService->getApply($offset,$perpage);	
    }
    /*
     * 企业审核
     */
    public function appviewAction(){	
    	
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id=(int)$formData['id'];
    		$uid=(int)$formData['uid'];
    		$status=(int)$formData['status'];
    		$result_remark=$this->filter->pregHtmlSql($formData['result_remark']);
    		$message = '';$error = 0;
    	    if(!$status){
    		   $message = '请选择审核结果';
    		   $error++;
    		}
    		if($status==102){
    		   if(empty($result_remark)){
    		      $message = '请填写审核说明';
    		      $error++;
    		   }
    		}
    		$userapply = $this->_stService->getApplyUser($uid);
    		if(!$userapply){
    		    $message = '参数错误';
    		    $error++;
    		}
    		if(!$error){
    			//不通过
    			if($status==102){
    			   //更新审批状态
    			   $this->_stService->updateApply(array('status'=>$status,'oa_sales'=>$formData['oa_sales'],'modified'=>time(),'result_remark'=>$result_remark),$id);
    			   //邮件通知
    			   $this->_stService->emailOaclientFeedback($userapply,$status);
    			   //日志
    			   $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp3'=>$status,'temp4'=>'审批成功','description'=>$result_remark));	
    			   $_SESSION['messages'] = '审核成功';
    			   $this->_redirect($this->indexurl.'/comapprove');
    			}elseif($status==101){
    			    //OAwebservice
					
    			   //更新用户OA编号
    			   $this->_stService->updateUserprofile(array('oa_code'=>'oa1233'),$userapply['uid']);
    			   //更新审批状态
    			   $this->_stService->updateApply(array('status'=>$status,'oa_sales'=>$formData['oa_sales'],'modified'=>time(),'result_remark'=>$result_remark),$id);
    			   //邮件通知
    			   $this->_stService->emailOaclientFeedback($userapply,$status);
    			   //日志
    			   $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp3'=>$status,'temp4'=>'审批成功','description'=>$result_remark));	
    			   $_SESSION['messages'] = '审核成功';
    			   $this->_redirect($this->indexurl.'/comapprove');
  
    			}
    			
    		}else{
    			$this->view->processData = $formData;
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'审批失败','description'=>$message));
    		    $_SESSION['messages'] = $message;
    		}
    	 }
    	 if(!$this->_getParam('uid')) $this->_redirect($this->indexurl);
    	 $this->view->userapply = $this->_stService->getApplyUser($this->_getParam('uid'));
    	 //联系人
    	 $usercontact = $this->_stService->getApplyContact($this->view->userapply['id']);
    	 if(!$usercontact) $this->_redirect($this->indexurl);
    	 $this->view->usercontact = $usercontact[0];
    	 //OA销售
    	 $oa_employee_model = new Icwebadmin_Model_DbTable_Model('oa_employee');
    	 $this->view->oa_employee = $oa_employee_model->getAllByWhere("status=1");
    	 //OA数据字典
    	 $this->view->oadictionary = $this->_stService->getOadictionary();
    }
    //屏蔽或开启或取消企业认证
    public function shieldAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$this->_helper->layout->disableLayout();
    	    $this->_helper->viewRenderer->setNoRender();
    		$filter = new MyFilter();
    		$formData      = $this->getRequest()->getPost();
    		$uid  = (int)$formData['uid'];
    		$type = $formData['type'];
            if($type=='close'){
            	$this->_userModel->updateByUid(array('enable'=>0),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }elseif($type=='open'){
            	$this->_userModel->updateByUid(array('enable'=>1),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }elseif($type=='cancel_com'){
            	$this->_userprofileModel->updateByUid(array('companyapprove'=>3),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }elseif($type=='pass'){
            	$this->_userprofileModel->updateByUid(array('cod'=>1),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }elseif($type=='nopass'){
            	$this->_userprofileModel->updateByUid(array('cod'=>0),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }elseif($type=='detailed'){
            	$this->_userprofileModel->updateByUid(array('detailed'=>0),$uid);
            	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
            	exit;
            }else{
            	echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'操作失败。'));
    		    exit;
            }
    	}
    	//如果销售只显示他们的客户
    	$wherestr = "";
    	if($_SESSION['staff_sess']['level_id']=='XS'){
    		$wherestr = " AND staffid = '".$_SESSION['staff_sess']['staff_id']."'";
    	}
       //查询用户
    	$sqlstr = "SELECT count(u.uid) as allnum FROM user as u
    	LEFT JOIN user_profile as up ON up.uid=u.uid
    	WHERE u.enable=0 {$wherestr}";
    	$appnumarr = $this->_userModel->getBySql($sqlstr,array());
    	
    	$total = $appnumarr[0]['appnum'];
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	 
    	$sqlstr = "SELECT u.*,u.created as ucreated,up.*,p.province,c.city,e.area
    	FROM user as u,user_profile as up
    	LEFT JOIN province as p ON up.province=p.provinceid
    	LEFT JOIN city as c ON up.city=c.cityid
    	LEFT JOIN area as e ON up.area = e.areaid
    	WHERE u.uid=up.uid AND u.enable=0 {$wherestr}
    	ORDER BY u.created DESC  LIMIT $offset,$perpage ";
    	$inqArr = $this->_userModel->getBySql($sqlstr,array());
    	$this->view->user = $inqArr;
    }
    /**
     * 获取用户名或公司名
     */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$keyword = $this->fun->phpEscape($_GET['q']);
    	$where=" AND up.`companyname` LIKE '%".$keyword."%' OR u.uname LIKE '%".$keyword."%'";
    	$sqlstr ="SELECT u.uname,up.`companyname` FROM `user_profile` as up
    	LEFT JOIN user as u ON u.uid=up.uid  WHERE u.enable=1 {$where}";
    	$Arr = $this->_userModel->getBySql($sqlstr);

    	for($i=0;$i<count($Arr);$i++)
    	{
    		echo $keyword = $Arr[$i]['uname'] . "\n".$Arr[$i]['companyname'] . "\n";
    	}
    }
    /**
     * 重新分配销售
     */
    public function resetxsAction(){
    	$this->_helper->layout->disableLayout();
    	if($_GET['uid']){
    		$this->view->userinfo = $this->_usService->getUserProfile($_GET['uid']);
    	}
    	//销售
    	$this->view->xiaoshou = $this->_stService->getXiaoShou();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData = $this->getRequest()->getPost();
    		$uid      = (int)$formData['uid'];
    		$staffid  = $formData['staffid'];
    		$follow_up = $formData['follow_up'];
    		$re = $this->_userprofileModel->update(array("staffid"=>$staffid,'follow_up'=>$follow_up), "uid = '{$uid}'");
    		if($re){
    		  //发送邮件通知销售
    		  $email = '';
    		  foreach($this->view->xiaoshou as $value){
    			 if($staffid == $value['staff_id']){
    				$email = $value['email'];
    			 }
    		  }
    		  $userinfo = $this->_usService->getUserProfile($uid);
    		  if($email && $userinfo){
    		     $emailre = $this->_stService->emailResetxs($email,$staffid,$userinfo);
    		  }
    		  if($emailre){
    		  	 //日志
    		  	 $this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$uid,'temp4'=>'重新分配销售发邮件成功：'.$staffid,'description'=>$follow_up));
    		  	 echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'重新分配销售并发邮件成功'));
    		     exit;
    		  }else{
    		  	//日志
    		  	$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>'400','temp2'=>$uid,'temp4'=>'重新分配销售发邮件失败：'.$staffid,'description'=>$follow_up));
    		  	echo Zend_Json_Encoder::encode(array("code"=>1,"message"=>'重新分配销售发邮件失败'));
    		  	exit;
    		  }
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>'400','temp2'=>$uid,'temp4'=>'重新分配销售失败：'.$staffid,'description'=>$follow_up));
    			echo Zend_Json_Encoder::encode(array("code"=>1,"message"=>'重新分配销售失败'));
    			exit;
    		}
    	}
    }
    public function viewAction(){
    	$this->_helper->layout->disableLayout();
    	$uid = $this->_getParam('uid');
    	$this->view->user = $this->_usService->getUserProfile($uid);
    	//获取用户询价记录
    	$this->view->inqre = $this->_inqservice->userInqInfo($uid);
    	//获取用户订单记录
    	$this->view->inqsore = $this->_inqsoservice->inqSoInfo($uid);
    	$this->view->sore = $this->_soservice->soInfo($uid);
    }
    /**
     * 申请更改企业资料
     */
    public function applicationAction(){
    	$uid = $this->_getParam('uid');
    	$this->view->userinfo = $this->_usService->getUserProfile($uid);
    
    	//获取审批人
    	$this->view->superior = $this->_stService->getSuperior();

    	//查询是否已经申请过
    	$userapply = $this->_stService->getApplyUser($uid);
    	if($userapply){
    		$this->view->processData = $userapply;
    		//联系人
    	    $usercontact = $this->_stService->getApplyContact($userapply['id']);
    	    $userapply['oa_apply_id'] = $userapply['id'];
    	    $this->view->processData = array_merge($userapply,$usercontact[0]);
    	}
    	if($this->getRequest()->isPost()){
    		$formData = $this->processData();
    		if(!$formData['error']){
    			$this->uoa_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply');
    			$this->uoac_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply_contact');
    			//添加
    			if(!$formData['id']){
    			  $apply_id = $this->uoa_model->addData($formData['oa_apply']);
    			  if($apply_id){
    				$formData['oa_apply_contact']['apply_id'] = $apply_id;
    				$this->uoac_model->addData($formData['oa_apply_contact']);
    				$_SESSION['messages'] = '提交成功';
    				//邮件通知
    				$this->_stService->emailOaclient($this->view->superior,$_SESSION['staff_sess'],$formData);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$apply_id,'temp4'=>'提交成功'));
    				$this->_redirect($this->indexurl.'/comapprove');
    			  }else{
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$apply_id,'temp4'=>'提交失败'));
    				$_SESSION['messages'] = '提交失败';
    			  }
    		    }elseif($formData['id']){ //编辑
    		    	$this->uoa_model->update($formData['oa_apply'], "id=".$formData['id']);
    		    	$this->uoac_model->update($formData['oa_apply_contact'], "apply_id=".$formData['id']);
    		    	$_SESSION['messages'] = '提交成功';
    		    	//邮件通知
    		    	$this->_stService->emailOaclient($this->view->superior,$_SESSION['staff_sess'],$formData);
    		    	//日志
    		    	$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$formData['id'],'temp4'=>'重新提交申请成功'));
    		    	$this->_redirect($this->indexurl.'/comapprove');
    		    	
    		    }
    		}else{
				$_SESSION['messages'] = $formData['message'];
			}
			$formData['applastname'] = $userapply['applastname'];
			$formData['appfirstname'] = $userapply['appfirstname'];
			$formData['modified'] = $userapply['modified'];
			$formData['status'] = $userapply['status'];
			$formData['result_remark'] = $userapply['result_remark'];
			$this->view->processData = $formData;
			
    	}
    	//oa数据字典
    	$oa_dictionary_model = new Icwebadmin_Model_DbTable_Model('oa_dictionary');
    	$this->view->dictionary = $oa_dictionary_model->getAllByWhere("status=1");
    	//OA销售
    	$oa_employee_model = new Icwebadmin_Model_DbTable_Model('oa_employee');
    	$this->view->oa_employee = $oa_employee_model->getAllByWhere("status=1");

    }
    /*
     * 修改用户资料
     */
    public function updateuserAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData  = $this->getRequest()->getPost();
    		$user = $this->_usService->getUserProfile($formData['uid']);
    		$data = array('companyname'=>$formData['companyname'],
    					'currency'=>$formData['currency'],
    					'truename'=>$formData['contact'],
    				    'department_id'=>$formData['department_id'],
    				    'industry'=>$formData['industry'],
    					'mobile'=>$formData['mobile'],
    					'tel'=>$formData['tel'],
    					'fax'=>$formData['fax'],
    					'province'=>$formData['province'],
    					'city'=>$formData['city'],
    					'area'=>$formData['area'],
    					'address'=>$formData['address'],
    					'property'=>$formData['property'],
    				    'personaldesc'=>$formData['industry_other']);
    		$re = $this->_userprofileModel->updateByUid($data,$formData['uid']);
    		//邮件日志
    		if($re){
    			$description  = "修改信息：".$user['companyname'].' -> '.$data['companyname'].' ; ';
    			$description .= $user['currency'].' -> '.$data['currency'].' ; ';
    			$description .= $user['industry'].' -> '.$data['industry'].' ; ';
    			$description .= $user['truename'].' -> '.$data['truename'].' ; ';
    			$description .= $user['mobile'].' -> '.$data['mobile'].' ; ';
    			$description .= $user['tel'].' -> '.$data['tel'].' ; ';
    			$description .= $user['fax'].' -> '.$data['fax'].' ; ';
    			$description .= $user['provinceid'].' -> '.$data['province'].' ; ';
    			$description .= $user['cityid'].' -> '.$data['city'].' ; ';
    			$description .= $user['areaid'].' -> '.$data['area'].' ; ';
    			$description .= $user['address'].' -> '.$data['address'].' ; ';
    			$description .= $user['property'].' -> '.$data['property'].' ; ';
    			$description .= $user['personaldesc'].' -> '.$data['personaldesc'].' ; ';
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$formData['uid'],'temp4'=>'修改客户信息成功','description'=>$description));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改客户信息成功'));
    		    exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$formData['uid'],'temp4'=>'修改客户信息失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改客户信息失败'));
    		    exit;
    		}
    	}
    	$uid = $this->_getParam('uid');
    	$this->view->user = $this->_usService->getUserProfile($uid);
    	//查询应用
    	$appcModel = new Default_Model_DbTable_AppCategory();
    	$this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
    	//部门
    	$officeModel = new Icwebadmin_Model_DbTable_Model('user_department');
    	$this->view->office = $officeModel->getAllByWhere("status=1","displayorder ASC");
    }
    /**
     * 上传营业执照和税务登记证
     * @return string
     */
    public function uploadannexAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->user = $this->_usService->getUserProfile($this->_getParam('uid'));
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$uid        = $formData['uid'];
    		$annexname  = $formData['annexname'];
    		$type       = $formData['type'];
    		if($uid && $annexname){
    			if($type=='annex1'){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$uid,'temp4'=>'上传营业执照成功'));
    				$this->_userprofileModel->updateByUid(array('annex1'=>$annexname), $uid);
    			}elseif($type=='annex2'){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$uid,'temp4'=>'上传税务登记证成功'));
    				$this->_userprofileModel->updateByUid(array('annex2'=>$annexname), $uid);
    			}
    		}
    	}
    }
    public function processData(){
    	$post  = $this->getRequest()->getPost();
    	//Zend_Debug::dump($post); exit;
    	$error = 0;$message = '';
    	if(!$post['uid']){
    		$error++;
    		$message .= "客户编号为空.<br/>";
    	}
    	if(!$post['approval_staffid']){
    		$error++;
    		$message .="审核人为空.<br/>";
    	}
    	if(!$post['client_cname']){
    		$error++;
    		$message .="请输入中文名称.<br/>";
    	}
    	if(!$post['client_ename']){
    		$error++;
    		$message .="请输入 英文名称.<br/>";
    	}
    	if(!$post['id']){
    		$this->uoa_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply');
    		$re = $this->uoa_model->getAllByWhere("uid='".$post['uid']."' OR client_cname='".$post['client_cname']."' OR client_ename='".$post['client_ename']."'");
    		if($re){
    			$error++;
    			$message .="此客户已经存在.<br/>";
    		}
    	}
    	if(!$post['registered_capital']){
    		$error++;
    		$message .="请输入注册资金.<br/>";
    	}
    	if(!$post['net_assets']){
    		$error++;
    		$message .="请输入 净资产.<br/>";
    	}
    	if(!$post['total_assets']){
    		$error++;
    		$message .="请输入 总资产.<br/>";
    	}
    	if(!$post['area_operations']){
    		$error++;
    		$message .="请输入经营面积.<br/>";
    	}
    	if(!$post['annual_sales']){
    		$error++;
    		$message .="请输入年销售额.<br/>";
    	}
    	if(!$post['country']){
    		$error++;
    		$message .="请选择国家.<br/>";
    	}
    	if(!$post['region']){
    		$error++;
    		$message .="请选择地区.<br/>";
    	}
    	if(!$post['city']){
    		$error++;
    		$message .="请选择城市.<br/>";
    	}
    	if(!$post['zipcode']){
    		$error++;
    		$message .="请输入邮编.<br/>";
    	}
    	if(!$post['caddress']){
    		$error++;
    		$message .="请输入中文地址.<br/>";
    	}
    	if(!$post['eaddress']){
    		$error++;
    		$message .="请输入英文地址.<br/>";
    	}
    	if(!$post['telephone']){
    		$error++;
    		$message .="请输入 电话.<br/>";
    	}
    	if(!$post['fax']){
    		$error++;
    		$message .="请输入传真.<br/>";
    	}
    	if(!$post['email']){
    		$error++;
    		$message .="请输入  Email.<br/>";
    	}
    	if(!$post['website']){
    		$error++;
    		$message .="请输入 网站.<br/>";
    	}
    	
    	//联系人
    	if(!$post['contact_name']){
    		$error++;
    		$message .="请输入联系人姓名.<br/>";
    	}
    	if(!$post['lxr_telephone']){
    		$error++;
    		$message .="请输入联系人电话.<br/>";
    	}
    	if(!$post['lxr_phone']){
    		$error++;
    		$message .="请输入联系人手机.<br/>";
    	}
    	if(!$post['lxr_email']){
    		$error++;
    		$message .="请输入联系人 Email.<br/>";
    	}
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		$re_data = $oa_apply = $oa_apply_contact = array();
    		$oa_apply['uid']              = $post['uid'];
    		$oa_apply['apply_staffid']    = $_SESSION['staff_sess']['staff_id'];
    		$oa_apply['approval_staffid'] = $post['approval_staffid'];
    		$oa_apply['territory']     = $post['territory'];
    		$oa_apply['oa_sales']     = $post['oa_sales'];
    		$oa_apply['client_cname']     = $post['client_cname'];
    		$oa_apply['client_ename']     = $post['client_ename'];
    		$oa_apply['abbreviation']     = $post['abbreviation'];
    		$oa_apply['category']     = $post['category'];
    		$oa_apply['industry']     = $post['industry'];
    		$oa_apply['field']     = $post['field'];
    		$oa_apply['nature']     = $post['nature'];
    		$oa_apply['top_flag']     = $post['top_flag'];
    		$oa_apply['freeze_flag']     = $post['freeze_flag'];
    		$oa_apply['legal']     = $post['legal'];
    		$oa_apply['creation_date']     = $post['creation_date'];
    		$oa_apply['registered_capital']     = $post['registered_capital'];
    		$oa_apply['net_assets']     = $post['net_assets'];
    		$oa_apply['total_assets']     = $post['total_assets'];
    		$oa_apply['suppliers_1']     = $post['suppliers_1'];
    		$oa_apply['suppliers_2']     = $post['suppliers_2'];
    		$oa_apply['suppliers_3']     = $post['suppliers_3'];
    		$oa_apply['employees']     = $post['employees'];
    		$oa_apply['area_operations']     = $post['area_operations'];
    		$oa_apply['annual_sales']     = $post['annual_sales'];
    		$oa_apply['shareholder_1']     = $post['shareholder_1'];
    		$oa_apply['investment_ratio_1']     = $post['investment_ratio_1'];
    		$oa_apply['shareholder_2']     = $post['shareholder_2'];
    		$oa_apply['investment_ratio_2']     = $post['investment_ratio_2'];
    		$oa_apply['affiliates']     = $post['affiliates'];
    		$oa_apply['end_customers']     = $post['end_customers'];
    		$oa_apply['country']     = $post['country'];
    		$oa_apply['region']     = $post['region'];
    		$oa_apply['city']     = $post['city'];
    		$oa_apply['zipcode']     = $post['zipcode'];
    		$oa_apply['caddress']     = $post['caddress'];
    		$oa_apply['eaddress']     = $post['eaddress'];
    		$oa_apply['telephone']     = $post['telephone'];
    		$oa_apply['fax']     = $post['fax'];
    		$oa_apply['email']     = $post['email'];
    		$oa_apply['website']     = $post['website'];
    		$oa_apply['customer_profile']     = $post['customer_profile'];
    		$oa_apply['remark']     = $post['remark'];
    		$oa_apply['created']     = time();
    		if($post['id']){
    			$oa_apply['status'] = 100;
    		}
    		$re_data['oa_apply'] = $oa_apply;
    		
    		//联系人
    		$oa_apply_contact['contact_name'] = $post['contact_name'];
    		$oa_apply_contact['sex'] = $post['sex'];
    		$oa_apply_contact['relationship'] = $post['relationship'];
    		$oa_apply_contact['relationship_degree'] = $post['relationship_degree'];
    		$oa_apply_contact['department'] = $post['department'];
    		$oa_apply_contact['position'] = $post['position'];
    		$oa_apply_contact['lxr_telephone'] = $post['lxr_telephone'];
    		$oa_apply_contact['lxr_phone'] = $post['lxr_phone'];
    		$oa_apply_contact['lxr_email'] = $post['lxr_email'];
    		$oa_apply_contact['lxr_fax'] = $post['lxr_fax'];
    		$oa_apply_contact['office_location'] = $post['office_location'];
    		$oa_apply_contact['home_address'] = $post['home_address'];
    		$oa_apply_contact['hobby'] = $post['hobby'];
    		$oa_apply_contact['appellation'] = $post['appellation'];
    		$oa_apply_contact['marriage'] = $post['marriage'];
    		$oa_apply_contact['spouse'] = $post['spouse'];
    		$oa_apply_contact['birthday'] = $post['birthday'];
    		$oa_apply_contact['created'] = time();
    		$re_data['oa_apply_contact'] = $oa_apply_contact;
    		
    		$re_data['id'] = $post['id'];
    		return $re_data;
    	}
    }
    /**
     * 复制客户，为客户增添新账号
     */
    public function copyuserAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";exit;
    	}
    	if($this->getRequest()->isPost()){
    		$userModel = new Default_Model_DbTable_User();
    		$userprofile = new Default_Model_DbTable_UserProfile();
    		$formData = $this->getRequest()->getPost();
    		$father_uid  = $formData['father_uid'];
    		$uname       = trim($formData['uname']);
    		$email       = trim($formData['email']);
    		$father_user = $this->_usService->getUserProfile($father_uid);
    		$error = 0;$message='';
    		if(!$father_user){
    			$message = "用户信息为空<br/>";
    			$error++;
    		}
    		if(!$formData['contact']){
    			$message = "联系人为空<br/>";
    			$error++;
    		}
    		if(!$uname)
    		{
    			$message .= '用户名不能为空<br/>';
    			$error ++;
    		}else{
    			$reUser = $userModel->getByName($uname);
    			if($reUser){
    				$message .= '用户名已经存在<br/>';
    				$error ++;
    			}
    		}
    		if($email)
    		{
    			if(!$this->filter->checkEmail($email)){
    				$message .= '请输入正确的邮箱地址<br/>';
    				$error ++;
    			}else{
    				$reUser = $userModel->getByEmail($email);
    				if($reUser){
    					$message .= '邮箱已经存在<br/>';
    					$error ++;
    				}
    			}
    		}
    		if(!$error){
    			//开始事务
    			$userModel->beginTransaction();
    			try{
    			   //添加新客户信息
    			   $pass = rand(111111,999999);
    			   $password = md5(md5($pass));
    			   $ip = $this->fun->getIp();
    			   $userarr = array('uname'=>$uname,
    			   		'pass'=>$password,
    			   		'pass_back'=>$pass,
    			   		'email'=>$email,
    			   		'emailapprove'=>1,
    			   		'created'=>time(),
    	           		'lasttime'=>time(),
    			   		'ip'=>$ip,
    	           		'lastip'=>$ip);
    			   $uid = $userModel->addUser($userarr);
    			   if($uid){
    			   $uparr = array('uid'=>$uid,
    			   		'companyapprove'=>$father_user['companyapprove'],
    			   		'detailed'=>$father_user['detailed'],
    			   		'cod'=>$father_user['cod'],
    			   		'currency'=>$father_user['currency'],
    			   		'truename'=>$formData['contact'],
    			   		'department_id'=>$formData['department_id'],
    			   		'companyname'=>$father_user['companyname'],
    			   		'province'=>$father_user['provinceid'],
    			   		'city'=>$father_user['cityid'],
    			   		'area'=>$father_user['areaid'],
    			   		'address'=>$father_user['address'],
    			   		'zipcode'=>$father_user['zipcode'],
    			   		'tel'=>$formData['tel'],
    			   		'mobile'=>$formData['mobile'],
    			   		'fax'=>$formData['fax'],
    			   		'personaldesc'=>$father_user['personaldesc'],
    			   		'annex1'=>$father_user['annex1'],
    			   		'annex2'=>$father_user['annex2'],
    			   		'annex3'=>$father_user['annex3'],
    			   		'created'=>time(),
    			   		'modified'=>time(),
    			   		'industry'=>$father_user['industry'],
    			   		'property'=>$father_user['property'],
    			   		'staffid'=>$father_user['staffid'],
    			   		'sap_code'=>$father_user['sap_code'],
    			   		'oa_code'=>$father_user['oa_code'],
    			   		'oa_sales'=>$father_user['oa_sales'],
    			   		'father_uid'=>$father_uid);
    			    $userprofile->addUser($uparr);
    			    //建立文件夹
    			    $this->_MyCommon = new MyCommon();
    			    $this->_MyCommon->createUserFolder($uid);
    			    //复制文件
    			    $annexur_part = COM_ANNEX.$father_uid.'/';
    			    $annexurl = $annexur_part.$father_user['annex1'];
    			    $annexurl2= $annexur_part.$father_user['annex2'];
    			    $newannexur_part = COM_ANNEX.$uid.'/';
    			    if(file_exists($annexurl) && $father_user['annex1']){
    			   	  @copy($annexurl,$newannexur_part.$father_user['annex1']);
    			    }
    			    if(file_exists($annexurl2) && $father_user['annex2']){
    			      @copy($annexurl2,$newannexur_part.$father_user['annex2']);
    			    }

    			    $_SESSION['message'] = '添加新账号成功';
    				$this->view->processData = array();
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$uid,'temp4'=>'添加新账号成功','description'=>$father_uid));
    				$userModel->commit();
    				 //发送邮件
    				 $emailreturn = $this->_stService->emailToUser($userarr,$uparr);
    				 //邮件日志
    				 if($emailreturn){
    				 	$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$uid,'temp4'=>'添加新账号发送邮件通知客户成功'));
    				 }else{
    					$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$uid,'temp4'=>'添加新账号发送邮件通知客户失败'));
    				 }
    			   }else{
    			       $userModel->rollBack();
    			   	   $_SESSION['message'] = '添加新账号失败';
    			   	   $this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$father_uid,'temp4'=>'添加新账号失败','description'=>'添加新uid失败'));
    			   }
    		} catch (Exception $e) {
    				$userModel->rollBack();
    				$_SESSION['message'] = '添加新账号失败';
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$father_uid,'temp4'=>'添加新账号失败','description'=>$e->getMessage()));
    		}
    		}else{
    			$this->view->processData = $formData;
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$father_uid,'temp4'=>'添加新账号失败','description'=>$message));
    			$_SESSION['message'] = '添加失败：<br/>'.$message;
    		}
    	}
    	if(!$this->_getParam('key')){
    		$this->_redirect($this->indexurl);
    	}
    	$uid = $this->fun->decryptVerification($this->_getParam('key'));
    	$this->view->user = $this->_usService->getUserProfile($uid);
    	if(!$this->view->user){
    		$this->_redirect($this->indexurl);
    	}
    	//部门
    	$officeModel = new Icwebadmin_Model_DbTable_Model('user_department');
    	$this->view->office = $officeModel->getAllByWhere("status=1","displayorder ASC");
    	$this->view->messages = $_SESSION['message'];
    	unset($_SESSION['message']);
    }
}