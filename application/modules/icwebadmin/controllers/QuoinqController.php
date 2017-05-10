<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_QuoinqController extends Zend_Controller_Action
{
	private $_model;
	private $_inqservice;
	private $_staffservice;
	private $_adminlogService;
	private $_usService;
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
    	$this->ajaxurl= $this->view->ajaxurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/ajax/";
    	$this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
    	$this->oainqurl  = $this->view->oainqurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/oainq";
    	
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->sectionarea = new  Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	$this->_model = new  Icwebadmin_Model_DbTable_Inquiry();
    	$this->_inqservice = new Icwebadmin_Service_InquiryService();
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    	$this->_usService  = new Icwebadmin_Service_UserService();
    	
    	$this->view->fun = $this->fun = new MyFun();
    	
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    }
    public function indexAction(){
    	$xswhere = " AND iq.back_inquiry=0 ";
    	//排序
    	$orderbystr = ' ORDER BY iq.created DESC';
    	$orderbyarr = array('ASC','DESC');
    	$orderarray = array('created');
    	if(isset($_GET['ordertype']))
    	$this->view->ordertype = $ordertype = $_GET['ordertype'];
    	else $this->view->ordertype = $ordertype = '';
    	if(in_array($ordertype,$orderarray)){
    		$this->view->orderby = $orderby = $_GET['orderby'];
    		if($ordertype=='created' && in_array($orderby,$orderbyarr)){
    			$orderbystr = " ORDER BY iq.created ".$orderby;
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
    		$xswhere .=" AND iq.created BETWEEN ".strtotime($sdata)." AND ".$edata;
    	}
    	//产品线
    	$this->view->selectbrand = $selectbrand = $_GET['brand'];
    	if($selectbrand){
    		$xswhere .=" AND iq.id IN (SELECT DISTINCT (inq_id) FROM `inquiry_detailed` AS inqd
    				LEFT JOIN product AS p ON p.id = inqd.part_id WHERE p.manufacturer = '{$selectbrand}') ";
    	}
    	//交货地
    	$this->view->delivery = $delivery = $_GET['delivery'];
    	if(in_array($delivery,array('HK','SZ'))){
    		$xswhere .=" AND iq.delivery='{$delivery}' ";
    	}
    	//OA报价
    	$this->view->oa_status = $_GET['oa_status'];
    	if(in_array($this->view->oa_status,array('100','101','102'))){
    		$xswhere .=" AND iq.oa_status='{$this->view->oa_status}' ";
    	}
    	//选择不同的类型 
    	$typetmp =$this->view->type = $_GET['type']==''?'oainq':$_GET['type'];
    	
    	//如果销售只能看到自己负责的询价
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	
    	if($staffinfo['level_id']=='XS'){
    		if($staffinfo['control_staff']){
    			$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    			$control_staff_str = implode("','",$control_staff_arr);
    			$xswhere .= " AND up.staffid IN ('".$control_staff_str."')";
    		}else{
    			$xswhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    		}
    	}
    	else{
    		//根据应用领域分配跟进销售
    		$this->view->xs_staff = $this->_staffservice->getXiaoShou();
    		$this->view->xsname = $_GET['xsname'];
    		if($_GET['xsname']){
    			$xswhere .= " AND up.staffid = '".$_GET['xsname']."'";
    		}
    	}
    	if($_GET['keyword']){
    	  $this->view->type = 'select';
    	  $this->view->keyword = $keyword = $_GET['keyword'];
    	  $xswhere .="  AND iq.inq_number LIKE '%".$keyword. "%'";
    	  $this->view->selectnum = $this->_inqservice->getSelectNum($xswhere,$keyword);
    	}
    	$this->view->oainqnum    = $this->_inqservice->getOainqNum($xswhere);
    	$this->view->waitnum     = $this->_inqservice->getWaitNum($xswhere);
    	$this->view->allnum      = $this->_inqservice->getAlreadyNum($xswhere);
    	$this->view->ordernum = $this->_inqservice->getOrderNum($xswhere);
    	$this->view->nonum = $this->_inqservice->getNoNum($xswhere);

    	if($_GET['keyword']) $total=$this->view->selectnum;
    	elseif($typetmp=='oainq') $total=$this->view->oainqnum;
    	elseif($typetmp=='wait') $total=$this->view->waitnum;
    	elseif($typetmp=='already') $total=$this->view->allnum;
    	elseif($typetmp=='order')   $total=$this->view->ordernum;
    	elseif($typetmp=='no')      $total=$this->view->nonum;
    	
    	if($_GET['keyword'])  $total=$this->view->selectnum;
    	
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$xswhere .=$orderbystr;

    	if($typetmp=='oainq') $inquires =$this->_inqservice->getOainqInquiry($offset,$perpage,$xswhere);
    	elseif($typetmp=='wait') $inquires =$this->_inqservice->getWaitInquiry($offset,$perpage,$xswhere);
    	elseif($typetmp=='already') $inquires =$this->_inqservice->getAlreadyInquiry($offset,$perpage,$xswhere);
    	elseif($typetmp=='order')  $inquires =$this->_inqservice->getOrderInquiry($offset,$perpage,$xswhere);
    	elseif($typetmp=='no') $inquires =$this->_inqservice->getNoInquiry($offset,$perpage,$xswhere);
    	if($_GET['keyword']){
    		$inquires = $this->_inqservice->getSelectInquiry($offset,$perpage,$xswhere);
    	}
    	//pcn，pdn
    	foreach($inquires as $k=>$v){
    		$detaile = $v['detaile'];
    		foreach($detaile as $n=>$d){
    		    $inquires[$k]['detaile'][$n]['pdnpcn'] = $this->_prodService->checkpdnpcn($d['part_id']);
    		}
    	}
    	$this->view->inquiry = $inquires;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    	
    	$paymenttypeser = new Icwebadmin_Service_PaymenttypeService();
    	$this->view->paytypearr = $paymenttypeser->getPaytypeName();
    	
    	//获取品牌
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!='' AND status=1" ," name ASC");
    	//通用器件
    	$eventservice = new Icwebadmin_Service_EventService();
    	$this->view->tongyong = $eventservice->getTongYong();
    }
    
    public function addAction(){
    	$this->_helper->layout->disableLayout();

    }
    
    public function viewAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $id = $this->_getParam('id');
    	$this->view->data = $this->_inqservice->getUserById($id);
    	//获取用户询价记录
    	$this->view->inqre = $this->_inqservice->userInqInfo($this->view->data['uid']);
    	//获取用户订单记录
    	$this->_inqsoservice = new Icwebadmin_Service_InqOrderService();
    	$this->view->inqsore = $this->_inqsoservice->inqSoInfo($this->view->data['uid']);
    	$this->_soservice = new Icwebadmin_Service_OrderService();
    	$this->view->sore = $this->_soservice->soInfo($this->view->data['uid']);
    }
    /*
     * 查看询价历史
    */
    public function historyAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $id = $this->_getParam('id');;
    	$inq = $this->_inqservice->getInquiryHistory($id);
    	$this->view->inquiry = $inq;
    }
    /**
      *查询是否存在OA
    */
    public function checkoauserAction()
    {
    	if($this->getRequest()->isPost()){
    	    $this->_helper->layout->disableLayout();
    	    $this->_helper->viewRenderer->setNoRender();
    	    $data = $this->getRequest()->getPost();
    	    $OainquiryService = new Icwebadmin_Service_OainquiryService();
    	    $oauser_arr = $OainquiryService->FindByConditionResult($data['client_cname']);
    		if(is_array($oauser_arr) && $oauser_arr['ClientListID']){
    			$ClientListID = $oauser_arr['ClientListID'];
    			$this->_usService->updateOaCode($oauser_arr['ClientListID'],$data['uid']);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$data['uid'],'temp4'=>'检查客户存在OA成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'此客户存在OA中'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'存在此客户不存在OA中，请向OA注册'));
    			exit;
    		}
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'参数错误'));
    		exit;
    	}
    }
    /**
     * 向OA提交询价
     */
    public function oainqAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$data = $this->getRequest()->getPost();
    		$id                 = $data['id'];
    		$rdtype             = $data['rdtype'];
    		$uid                = $data['uid'];
    		$oa_sales           = $data['oa_sales'];
    		$client_cname       = $data['client_cname'];
    		$client_ename       = $data['client_ename'];
    		$project_name       = $data['project_name'];
    		$project_status     = $data['project_status'];
    		$ic_remark          = $data['ic_remark'];
    		$oa_productlineArr  = $data['oa_productline'];
    		$oa_forecastArr     = $data['oa_forecast'];
    		$oa_target_priceArr = $data['oa_target_price'];
    		$oa_price_sourceArr = $data['oa_price_source'];
    		$ic_inqd_remarkArr  = $data['ic_inqd_remark'];
    		$detidArr           = $data['det_id'];
    		$pmscinqArr         = $data['pmscinq'];
    		
    		$error = 0;$message='';
    		$upData = array();
    		if(empty($client_cname)){
    			$message .='客户中文名不能为空';
    			$error++;
    		}
    		if(empty($id)){
    			$message .='id为空';
    			$error++;
    		}
    		
    		$upData = array();
    		$cansend = false;
    		foreach($oa_forecastArr as $k=>$oa_forecast){
    		  if($pmscinqArr[$k]){
    		  	$cansend = true;
    			  if($oa_forecast=='') {
    			   $message .='请填入预测量'.$oa_forecast;
    		       $error++;
    			   break;
    			 }else{
    			   $upData[$k]['oa_forecast'] = $oa_forecast;
    			 }
    			 if(!$oa_productlineArr[$k] && $rdtype!=5) {
    				$message .='请选择产品线';
    				$error++;
    				break;
    			 }else{
    				$upData[$k]['oa_productline'] = $oa_productlineArr[$k];
    			 }
    			 if($oa_forecast=='') {
    				$message .='请填入目标价';
    				$error++;
    				break;
    			 }else{
    				$upData[$k]['oa_target_price'] = $oa_target_priceArr[$k];
    			 }
    			 if(!$oa_price_sourceArr[$k]) {
    				$message .='请选择价格来源';
    				$error++;
    				break;
    			 }else{
    				$upData[$k]['oa_price_source'] = $oa_price_sourceArr[$k];
    			 }
    			 if(!$ic_inqd_remarkArr[$k]) {
    				$message .='请填入说明描述';
    				$error++;
    				break;
    			 }elseif(!$this->filter->checkLength($ic_inqd_remarkArr[$k], 1, 500)){
    			 	$message .='说明描述已经超过500个字';
    			 	$error++;
    			 	break;
    			 }else{
    				$upData[$k]['ic_inqd_remark'] = $ic_inqd_remarkArr[$k];
    			 }
    			 //如果RFQ询价不允许BPPRFQ询价
    			 if($rdtype==1){
    				$oaproductline = explode('<>',$upData[$k]['oa_productline']);
    				if(!$oaproductline[0]) {
    					$message .='RFQ询价产品线不允许选择BPPRFQ';
    					$error++;
    					break;
    				}
    			 }
    		  }
    		}
    		
    		if(!$cansend){
    			$message .='请选择型号向PMSC询价！';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//更新用户ＯＡ销售
    			if($oa_sales){
    				$this->upmodel = new Icwebadmin_Model_DbTable_Model('user_profile');
    				$this->upmodel->update(array('oa_sales'=>$oa_sales), "uid='{$uid}'");
    			}
    			$this->uoa_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply');
    			$uoaarr = $this->uoa_model->getRowByWhere("uid='{$uid}'");
    			//添加
    			if(!$uoaarr){
    				$apply_id = $this->uoa_model->addData(array('uid'=>$uid,
    						'client_cname'=>$client_cname,
    						'client_ename'=>$client_ename,
    						'created'     => time()));
    			}else{ //编辑
    				$this->uoa_model->update(array('client_cname'=>$client_cname,
    						'client_ename'=>$client_ename), "id=".$uoaarr['id']);
    			}
    			
    			foreach($detidArr as $k=>$did){
    				$oa_productline = explode('<>',$upData[$k]['oa_productline']);
    				$ddate = array('brand_name'=>$oa_productline[1],
    						'oa_rdtype'=>$rdtype,
    						'oa_productline'=>$oa_productline[0],
    						'oa_forecast'=>$upData[$k]['oa_forecast'],
    						'oa_target_price'=>$upData[$k]['oa_target_price'],
    						'oa_price_source'=>$upData[$k]['oa_price_source'],
    						'ic_inqd_remark'=>$upData[$k]['ic_inqd_remark']);
    				$this->_inqservice->updateDetailed($did,$ddate);
    			}
    			
    			$inqDate = array('oa_rdtype'=>$rdtype,
    					'oa_project_name'=>$project_name,
    					'oa_project_status'=>$project_status,
    					'ic_remark'=>$ic_remark,
    					'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$this->_inqservice->updateInquiry($id,$inqDate);
    			//提交给OA
    			
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'编辑向OA询价'));
    		    echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提示：此操作只是在IC易站保存成功，你还需要进行下步操作：“向OA提交询价”，才是真的向PMSC提交询价。'));
    		    exit;
    		}

    	}
    	$this->view->id = $id = $this->_getParam('id');

    	//询价者信息
    	$this->view->user = $user = $this->_inqservice->getUserById($id);
    	//OA信息
    	$this->view->oauser = $this->_staffservice->getApplyUser($this->view->user['uid']);

    	//如果没有申请注册OA客户，临时客户询价需要销售
    	if(!$this->view->oauser['oa_sales']){
    		//OA销售
    	    $oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline'," oa_name ASC");
    	    $this->view->oa_employee = $oa_sellline_model->getAllByWhere("type='sell'");
    	}
    	$inqhistory_tmp = array();
    	$inqhistory = $this->_inqservice->getInquiryHistory($id);
    	foreach($inqhistory as $key=>$inqtmp){
    		if($id==$inqtmp['id']){
    			$inqhistory_tmp[0]=$inqtmp;
    	
    		}else{
    			$inqhistory_tmp[$key+1]=$inqtmp;
    		}
    	}
    	$this->view->inqhistory = $inqhistory_tmp;
    	ksort($this->view->inqhistory);
    	//OA产品线
    	$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    	$this->view->oaproductline = $oa_sellline_model->getAllByWhere("type='line'"," oa_name ASC");
    }
    /**
     * 查看OA询价历史
     */
    public function oainqviewAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $id = $this->_getParam('id');
    	//询价者信息
    	$this->view->user = $user = $this->_inqservice->getUserById($id);
    	//OA信息
    	$this->view->oauser = $this->_staffservice->getApplyUser($this->view->user['uid']);
    	//如果没有申请注册OA客户，临时客户询价需要销售
    	if(!$this->view->oauser['oa_sales']){
    		//OA销售
    		$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline'," oa_name ASC");
    		$this->view->oa_employee = $oa_sellline_model->getAllByWhere("type='sell'");
    	}
    	//OA产品线
    	$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    	$this->view->oaproductline = $oa_sellline_model->getAllByWhere("type='line'"," oa_name ASC");
    	//询价历史
    	$this->view->inqhistory = $this->_inqservice->getHistoryById($id);
    }
    /**
     * 填写OA用户资料
     */
    public function oauserinfoAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData = $this->processData();
    		if(!$formData['error']){
    			$this->uoa_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply');
    			$this->uoac_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply_contact');
    			//添加
    			if(!$formData['id']){
    				$apply_id = $this->uoa_model->addData($formData['oa_apply']);
    				$formData['oa_apply_contact']['apply_id'] = $apply_id;
    				$this->uoac_model->addData($formData['oa_apply_contact']);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$apply_id,'temp4'=>'提交成功'));
            	    //echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'保存客户资料成功，你可以进行2)操作填写向OA询价信息。'));
            	    //exit;
    				
    			}elseif($formData['id']){ //编辑
    				$this->uoa_model->update($formData['oa_apply'], "id=".$formData['id']);
    				$this->uoac_model->update($formData['oa_apply_contact'], "apply_id=".$formData['id']);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$formData['id'],'temp4'=>'重新提交申请成功'));
    				//echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改客户资料成功，你可以进行2)操作填写向OA询价信息。'));
    				//exit;
    			}
    			//向OA注册客户
    			//OAwebservice 注册用户
    			$OainquiryService = new Icwebadmin_Service_OainquiryService();
    			//查询是否存在OA
    			$userapply = $this->_staffservice->getApplyUser($formData['oa_apply']['uid']);
    			$oauser_arr = $OainquiryService->FindByConditionResult($userapply['client_cname']);
    			 
    			if(is_array($oauser_arr) && $oauser_arr['ClientListID']){
    				$ClientListID = $oauser_arr['ClientListID'];
    			}else{
    				$usercontact = $this->_staffservice->getApplyContact($userapply['id']);
    				$CustomerInfo=array('CAddress'=>$userapply['caddress'],
    						'ClassID'=>$userapply['category'],
    						'IndustryID'=>$userapply['industry'],
    						'SegmentID'=>$userapply['field'],
    						'CEmployeeID'=>$userapply['oa_sales'],
    						'SalesID'=>$userapply['oa_sales'],
    						'ClientCName'=>$userapply['client_cname'],
    						'ClientEName'=>$userapply['client_ename'],
    						'ClientIntroduce'=>$userapply['customer_profile'],
    						'Country'=>$userapply['cname'],
    						'EAddress'=>$userapply['eaddress'],
    						'Email'=>$userapply['email'],
    						'EndCustomerName'=>$userapply['end_customers'],
    						'EnrolFund'=>$userapply['registered_capital'],
    						'Fax'=>$userapply['fax'],
    						'IndustryID'=>$userapply['industry'],
    						'InvestmentRatio1'=>$userapply['investment_ratio_1']?$userapply['investment_ratio_1']:0,
    						'InvestmentRatio2'=>$userapply['investment_ratio_2']?$userapply['investment_ratio_2']:0,
    						'JuridicalPerson'=>$userapply['legal'],
    						'NetAsset'=>$userapply['net_assets'],
    						'Place'=>$userapply['ciname'],
    						'Region'=>$userapply['pname'],
    						'RelationCompany'=>$userapply['affiliates'],
    						'SName'=>$userapply['abbreviation'],
    						'SaleAmount'=>$userapply['annual_sales'],
    						'SizeID'=>$userapply['employees'],
    						'StockHolder1'=>$userapply['shareholder_1'],
    						'StockHolder2'=>$userapply['shareholder_2'],
    						'Tel'=>$userapply['telephone'],
    						'TotalCapital'=>$userapply['total_assets'],
    						'TypeID'=>$userapply['nature'],
    						'VendorName1'=>$userapply['suppliers_1'],
    						'VendorName2'=>$userapply['suppliers_2'],
    						'VendorName3'=>$userapply['suppliers_3'],
    						'WebSite'=>$userapply['website'],
    						'Workingground'=>$userapply['area_operations'],
    						'ZipCode'=>$userapply['zipcode'],
    						'SourceSys'=>'IC');
    				$ClientContactPersonInfo=array('BirthDay'=>$usercontact['birthday'],
    						'Email'=>$usercontact['lxr_email'],
    						'Fax'=>$usercontact['lxr_fax'],
    						'HomeAddress'=>$usercontact['home_address'],
    						'JobDesc'=>$usercontact['position'],
    						'LikeItem'=>$usercontact['hobby'],
    						'Marriage'=>$usercontact['marriage'],
    						'Mobile'=>$usercontact['lxr_phone'],
    						'Name'=>$usercontact['contact_name'],
    						'OfficeAddress'=>$usercontact['office_location'],
    						'OfficeDesc'=>$usercontact['department'],
    						'ContactPersonClassID'=>$usercontact['relationship'],
    						'RelationLevelID'=>$usercontact['relationship_degree'],
    						'Sex'=>$usercontact['sex'],
    						'Spouse'=>$usercontact['spouse'],
    						'Tel'=>$usercontact['lxr_telephone'],
    						'Title'=>$usercontact['appellation']);
    				$ClientBusinessCopeList  = array("ClientBusinessCopeEntity"=>array('BusinessCopeCode'=>$userapply['territory'],'EmployeeID'=>$userapply['oa_sales']));
    				$ClientResult= $OainquiryService->SubmitClientListInfo($CustomerInfo,$ClientContactPersonInfo,$ClientBusinessCopeList);
    			
    				$ClientListID = $ClientResult['SubmitClientListInfoResult'];
    			}
    			//将OA客户ID更新到IC易站
    			if($ClientListID){
    				$this->_usService->updateOaCode($ClientListID,$formData['oa_apply']['uid']);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$formData['oa_apply']['uid'],'temp4'=>'注册OA客户成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'注册OA客户成功。'));
    				exit;
    			}else{
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$formData['oa_apply']['uid'],'temp4'=>'注册OA客户失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>110, "message"=>'注册OA客户失败'));
    				exit;
    			}
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$formData['message']));
    			exit;
    		}
    		$this->view->processData = $formData;
    			
    	}
    	$uid = $this->_getParam('uid');

    	$this->view->userinfo = $this->_usService->getUserProfile($uid);
    	
    	//修改
    	if($this->_getParam('uoaid')){
    		$uoaid = $this->_getParam('uoaid');
    		$userapply = $this->_staffservice->getApplyUser($uid);
    		$usercontact = $this->_staffservice->getApplyContact($userapply['id']);
    		$userapply['oa_apply_id'] = $userapply['id'];
    		if($userapply && $usercontact)
    		   $this->view->processData = array_merge($userapply,$usercontact);
    		else  $this->view->processData = $userapply;
    	}
    	//oa数据字典
    	$oa_dictionary_model = new Icwebadmin_Model_DbTable_Model('oa_dictionary');
    	$this->view->dictionary = $oa_dictionary_model->getAllByWhere("status=1");
    	//OA销售
    	$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    	$this->view->oa_employee = $oa_sellline_model->getAllByWhere("type='sell'"," oa_name ASC");
    }
    /**
     * 检查part no是否存在sap系统
     */
    public function checkpartinsqpAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->processData();
    		$inqid = (int)$formData['inqid'];
    		$detailed = $this->_inqservice->getDetailedByID($inqid);
    		if(empty($detailed)){
    			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'询价ID为空'));
    			exit;
    		}else{
    			$prodService = new Icwebadmin_Service_ProductService();
    			$OainquiryService = new Icwebadmin_Service_OainquiryService();
    			$nopart = '';
    			foreach($detailed as $darr){
    			   $sapok = $prodService->checkSap($darr['part_no']);
    			   if(!$sapok){
    			   	  $prefix = substr($darr['part_no'],0,3);
    			   	  if($prefix=='IDT'){
    			   	      $sappartno = substr($darr['part_no'],3);
    			   	  }else{
    			   	  	  $sappartno = $darr['part_no'];
    			   	  }
    			      $re = $OainquiryService->CheckPartInSAP($sappartno);
    			      if(!$re) $nopart .=$darr['part_no'].'；';
    			      else{
    			      	//更新
    			      	$prodService->update(array('sap'=>1)," part_no = '".$darr['part_no']."'");
    			      }
    			   }
    			}
    			if(!empty($nopart)){
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>$prefix."1、SAP系统不存在的型号：".$nopart." \n2、请走现有添加新型号流程，再来选择RFQ询价。\n3、你也可以选择Budgetary或BPPRFQ询价。"));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'所有型号都存在于SAP系统中，你可以提交RFQ询价。'));
    				exit;
    			}
    		}
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请提交'));
    		exit;
    	}
    }
    /**
     * 向OA提交询价
     */
    public function oasendinqAction(){
    	$this->view->inqid = $this->_getParam('inqid');
    	$this->view->uid = $this->_getParam('uid');
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$data    = $this->getRequest()->getPost();
    		$inqid   = $data['inqid'];
    		$uid     = $data['uid'];
    		$type    = $data['type'];
    		//OAwebservice 注册用户
    		$OainquiryService = new Icwebadmin_Service_OainquiryService();
    		$userapply = $this->_staffservice->getApplyUser($uid);
    		//用户资料
    		$userinfo = $this->_usService->getUserProfile($uid);
    		//询价信息
    		$inqHistory =  $this->_inqservice->getInquiryHistory($inqid);
    		if(empty($inqHistory)){
    			echo Zend_Json_Encoder::encode(array("code"=>111, "message"=>'向OA询价失败，询价信息为空'));
    			exit;
    		}else $inq = $inqHistory[0];
    		
    	$model = new Icwebadmin_Model_DbTable_Model('inquiry');
    	$model->beginTransaction();
    	try {

    		//OAwebservice 查找已经存在的oacode
    		$oacode     =  $userapply['oa_code'];
    		if($type == 'oauser')
    		{
    			//OA客户已经关联
    			if($oacode){
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'检查客户资料成功。'));
    				exit;
    			}
    			//查询是否存在OA
    			$oauser_arr = $OainquiryService->FindByConditionResult($userapply['client_cname']);
    			
    			if(is_array($oauser_arr) && $oauser_arr['ClientListID']){
    				$ClientListID = $oauser_arr['ClientListID'];
    			}else{
    				//Budgetary BPPRFQ 不需要注册用户
    				if(!$userapply['oa_sales'] && ($inq['oa_rdtype']==2 || $inq['oa_rdtype']==5) && ($userapply['client_cname'] || $userinfo['companyname'])){
    					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'检查客户资料成功。'));
    					exit;
    				}elseif(!$userapply['oa_sales'] && $inq['oa_rdtype']==1){
    					echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'注册OA客户失败，需要提交RFQ询价请注册OA客户。<div style="margin-top:2px"><a class="gbqfb" href="javascript:;" onclick="$.closePopupLayer(\'box\');showbox(\'/icwebadmin/QuoInq/oauserinfo/uid/'.$uid.'\')">注册OA客户</a></div>'));
    					exit;
    				}
    				if(!$userapply['client_cname']){
    					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'需要向OA注册客户，请完善客户资料。'));
    					exit;
    				}
    				$usercontact = $this->_staffservice->getApplyContact($userapply['id']);
    				$CustomerInfo=array('CAddress'=>$userapply['caddress'],
    						'ClassID'=>$userapply['category'],
    						'IndustryID'=>$userapply['industry'],
    						'SegmentID'=>$userapply['field'],
    						'CEmployeeID'=>$userapply['oa_sales'],
    						'SalesID'=>$userapply['oa_sales'],
    						'ClientCName'=>$userapply['client_cname'],
    						'ClientEName'=>$userapply['client_ename'],
    						'ClientIntroduce'=>$userapply['customer_profile'],
    						'Country'=>$userapply['cname'],
    						'EAddress'=>$userapply['eaddress'],
    						'Email'=>$userapply['email'],
    						'EndCustomerName'=>$userapply['end_customers'],
    						'EnrolFund'=>$userapply['registered_capital'],
    						'Fax'=>$userapply['fax'],
    						'IndustryID'=>$userapply['industry'],
    						'InvestmentRatio1'=>$userapply['investment_ratio_1']?$userapply['investment_ratio_1']:0,
    						'InvestmentRatio2'=>$userapply['investment_ratio_2']?$userapply['investment_ratio_2']:0,
    						'JuridicalPerson'=>$userapply['legal'],
    						'NetAsset'=>$userapply['net_assets'],
    						'Place'=>$userapply['ciname'],
    				        'Region'=>$userapply['pname'],
    						'RelationCompany'=>$userapply['affiliates'],
    						'SName'=>$userapply['abbreviation'],
    						'SaleAmount'=>$userapply['annual_sales'],
    						'SizeID'=>$userapply['employees'],
    						'StockHolder1'=>$userapply['shareholder_1'],
    						'StockHolder2'=>$userapply['shareholder_2'],
    						'Tel'=>$userapply['telephone'],
    						'TotalCapital'=>$userapply['total_assets'],
    						'TypeID'=>$userapply['nature'],
    						'VendorName1'=>$userapply['suppliers_1'],
    						'VendorName2'=>$userapply['suppliers_2'],
    						'VendorName3'=>$userapply['suppliers_3'],
    						'WebSite'=>$userapply['website'],
    						'Workingground'=>$userapply['area_operations'],
    						'ZipCode'=>$userapply['zipcode'],
    						'SourceSys'=>'IC');
    				$ClientContactPersonInfo=array('BirthDay'=>$usercontact['birthday'],
    						'Email'=>$usercontact['lxr_email'],
    						'Fax'=>$usercontact['lxr_fax'],
    						'HomeAddress'=>$usercontact['home_address'],
    						'JobDesc'=>$usercontact['position'],
    						'LikeItem'=>$usercontact['hobby'],
    						'Marriage'=>$usercontact['marriage'],
    						'Mobile'=>$usercontact['lxr_phone'],
    						'Name'=>$usercontact['contact_name'],
    						'OfficeAddress'=>$usercontact['office_location'],
    						'OfficeDesc'=>$usercontact['department'],
    						'ContactPersonClassID'=>$usercontact['relationship'],
    						'RelationLevelID'=>$usercontact['relationship_degree'],
    						'Sex'=>$usercontact['sex'],
    						'Spouse'=>$usercontact['spouse'],
    						'Tel'=>$usercontact['lxr_telephone'],
    						'Title'=>$usercontact['appellation']);
    				$ClientBusinessCopeList  = array("ClientBusinessCopeEntity"=>array('BusinessCopeCode'=>$userapply['territory'],'EmployeeID'=>$userapply['oa_sales']));
    				$ClientResult= $OainquiryService->SubmitClientListInfo($CustomerInfo,$ClientContactPersonInfo,$ClientBusinessCopeList);
    				
    				$ClientListID = $ClientResult['SubmitClientListInfoResult'];
    	
    			}
    			//将OA客户ID更新到IC易站
    			if($ClientListID){
    			    $this->_usService->updateOaCode($ClientListID,$uid);
    			    $model->commit();
    			   //日志
    			   $this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$uid,'temp4'=>'注册OA客户成功'));
    			   echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'注册OA客户成功。'));
    			   exit;
    			}else{
    				$model->rollBack();
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$uid,'temp4'=>'注册OA客户失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>110, "message"=>'注册OA客户失败'));
    				exit;
    			}
    		}elseif($type == 'oainq'){
    			$oa_rfq_id = 0;
    			$oa_currency = array('SZ'=>1,'HK'=>2);
    			
    			//RFQ Budgetary
    			if($inq['oa_rdtype']==1 || $inq['oa_rdtype']==2)
    			{
    			  $TypeID=($inq['oa_rdtype']==5?5:($inq['oa_rdtype']-1));
    			  $Budgetary_ProductRFQInfo = array('ClientListID'=>$userapply['oa_code']?$userapply['oa_code']:0,
    					'CustomerCName'=>$userapply['client_cname']?$userapply['client_cname']:$userinfo['companyname'],
    					'CustomerEName'=>$userapply['client_ename'],
    					'TypeID'=>$TypeID,
    					'IsEndCustomer'=>0,
    					'IsHasOpporunity'=>0,
    					'IsNewRFQ'=>$inq['oa_rdtype'],
    					'WorkStatusID'=>1,
    					'EmployeeID'=>$userapply['oa_sales']?$userapply['oa_sales']:$userapply['up_oa_sales'],
    					'SourceSys'=>'IC');
    			  
    			  $BPPRFQ_ProductRFQInfo = array('CLientCname'=>$userapply['client_cname'],
    			  		'EmployeeID'=>$userapply['oa_sales']?$userapply['oa_sales']:$userapply['up_oa_sales'],
    			  		'WorkStatusID'=>1,
    			  		'SourceSys'=>'IC');
    			  
    			  $Budgetary_List = $BPPRFQ_List = array();
    			  foreach($inq['detaile'] as $detaile){
    			  	if($detaile['oa_price_source']){
    			  	  if($detaile['oa_productline']){
    				    $Budgetary_List[] = array('MPNCode'=>$detaile['part_no'],
    					'ProductLineID'=>$detaile['oa_productline'],
    					'CurrencyID'=>$oa_currency[$inq['delivery']],
    					'Qty'=>$detaile['number'],
    					'EstimatedUsage'=>$detaile['oa_forecast'],
    					'TargetPrice'=>$detaile['oa_target_price'],
    					'Comments'=>$detaile['ic_inqd_remark'],
    					'WorkStatusID'=>$detaile['oa_price_source']);
    			  	  }else{
    			  	    $BPPRFQ_List[] = array('ProductLine'=>$detaile['brand']?$detaile['brand']:$detaile['brand_name'],
    			  		'PartNumber'=>$detaile['part_no'],
    			  		'RequestQty'=>$detaile['number'],
    			  		'TargetPrice'=>$detaile['oa_target_price'],
    			  		'Currency'=>$inq['currency'],
    			  		'EAU'=>$detaile['oa_forecast'],
    			  		'RFQRemark'=>$detaile['ic_inqd_remark']);
    			  	  }
    			  	}
    			  }
    			  $Budgetary_ClientResult = $BPPRFQ_ClientResult = array();
    			  //RFQ  Budgetary 
    			  if($Budgetary_List){
    			     $Budgetary_ClientResult= $OainquiryService->SubmitProductRFQInfo($Budgetary_ProductRFQInfo,$Budgetary_List);
    			     $oa_rfq_id = $Budgetary_ClientResult[0]['ProductRFQID'];
    			  }
    			  //BPP
    			  if($BPPRFQ_List){
    			  	  $BPPRFQ_ClientResult= $OainquiryService->SubmitProductBPPRFQInfo($BPPRFQ_ProductRFQInfo,$BPPRFQ_List);
    			  	  $oa_rfq_bpp_id = $BPPRFQ_ClientResult[0]['ProductRFQID'];
    			  }
    			  //合并
    			  $ClientResult = array_merge($Budgetary_ClientResult,$BPPRFQ_ClientResult);
				  
    			}elseif($inq['oa_rdtype']==5){// BPPRFQ
	
    				$ProductRFQInfo = array('CLientCname'=>$userapply['client_cname'],
    						'EmployeeID'=>$userapply['oa_sales']?$userapply['oa_sales']:$userapply['up_oa_sales'],
    						'WorkStatusID'=>1,
    						'SourceSys'=>'IC');
    				 
    				foreach($inq['detaile'] as $detaile){
    					$List[] = array('ProductLine'=>$detaile['brand']?$detaile['brand']:$detaile['brand_name'],
    							'PartNumber'=>$detaile['part_no'],
    							'RequestQty'=>$detaile['number'],
    							'TargetPrice'=>$detaile['oa_target_price'],
    							'Currency'=>$inq['currency'],
    							'EAU'=>$detaile['oa_forecast'],
    							'RFQRemark'=>$detaile['ic_inqd_remark']);
    				}
    				$ClientResult= $OainquiryService->SubmitProductBPPRFQInfo($ProductRFQInfo,$List);
    				if($ClientResult){
    					$oa_rfq_id = $ClientResult[0]['ProductRFQID'];
    				}
    			}
    			//将OA询价ID更新到IC易站
    			if($oa_rfq_id){
    				//更新
    				$this->_inqservice->upInqByid(array('oa_rfq'=>$oa_rfq_id,
    						'oa_rfq_bpp'=>$oa_rfq_bpp_id,'oa_status'=>101),$inqid);
    				foreach($ClientResult as $key=>$v){
    					$inqdid = '';
    					foreach($inq['detaile'] as $detaile){
    						if($detaile['part_no']==$v['MPNCode'] || $detaile['part_no']==$v['PartNumber']){
    							$inqdid = $detaile['id'];break;
    						}
    					}
    					if($inqdid && $v['ProductRFQID'] && $v['ProductRFQDetailID']){
    					   $this->_inqservice->updateDetWhere(array('oa_rfq_id'=>$v['ProductRFQID'],
    							'oa_inqd_id'=>$v['ProductRFQDetailID']),
    							"id=".$inqdid);
    					}else{
    						$model->rollBack();
    						//日志
    						$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$inqid,'temp4'=>'向OA提交询价失败','description'=>"数据为空：inqdid,".$inqdid.";ProductRFQID,".$v['ProductRFQID'].";ProductRFQDetailID,".$v['ProductRFQDetailID']));
    						echo Zend_Json_Encoder::encode(array("code"=>110, "message"=>'向OA提交询价失败'));
    						exit;
    					}
    				}
    				$model->commit();
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$inqid,'temp4'=>'向OA提交询价成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'向OA提交询价成功。'));
    				exit;
    			}else{
    				$model->rollBack();
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$inqid,'temp4'=>'向OA提交询价失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>110, "message"=>'向OA提交询价失败'));
    				exit;
    			}
    		}
    		} catch (Exception $e) {
    		    $model->rollBack();
    		    echo Zend_Json_Encoder::encode(array("code"=>110, "message"=>'参数出错，提交失败。'));
    		    exit;
    		}
    	}
    }
    
    /*
     * 报价
     */
    public function editAction(){
    	$this->view->id = $id = $this->_getParam('id');
    	$this->_helper->layout->disableLayout();
    	$this->view->type = $this->_getParam('type');
    	//询价者信息
    	$this->view->user = $user = $this->_inqservice->getUserById($id);
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$id                = $data['id'];
    		$currency          = $data['currency'];
    		$status            = $data['status'];
    		$reasons           = $data['reasons'];
    		$down_payment      = $data['down_payment'];
    		$paytypearray      = $data['paytype'];
    		$percentage_arr    = explode('_',$data['percentage']);
    		if($percentage_arr[1]){
    			$paytypearray[] = $percentage_arr[1];
    		}
    		//去重复
    		$paytypearray = array_unique($paytypearray);
    		$percentage   = $percentage_arr[0];
    		$paper_contract = $data['paper_contract'];
    		$paytype = '';
    		foreach($paytypearray as $pay_str){
    			if($pay_str){
    				if(empty($paytype)) $paytype = $pay_str;
    				else $paytype .= '|'.$pay_str;
    			}
    		}
    		$son_id             = $data['son_id'];
    		$pmpqArr            = $data['pmpq'];
    		$detidArr           = $data['det_id'];
    		$partidArr          = $data['part_id'];
    		$priceArr           = $data['price'];
    		$result_price_oaArr = $data['result_price_oa'];
    		$expiration_timeArr = $data['expiration_time'];
    		$detailed_remark    = $data['detailed_remark'];
    		$result_remark      = $data['result_remark'];
    		$error = 0;$message='';
    		$upData = array();
    		if(empty($id)){
    			$message .='id为空';
    			$error++;
    		}
    		if(!$status){
    			$message .='请选择审核结果';
    			$error++;
    		}
    		if($status ==2 && empty($reasons))
    		{
    			if(empty($percentage) && $down_payment<0){
    			    $message .='请选择支付金额';
    			    $error++;
    		    }
    		    if(empty($paytype)){
    			    $message .='请选择支付方式';
    			    $error++;
    		    }
    		    if(empty($pmpqArr)){
    			   $message .='请填入最低购买数';
    			   $error++;
    		    }else{
    			  foreach($pmpqArr as $k=>$pmpq){
    				if(!$pmpq) {
    					$message .='请填入最低购买数';
    					$error++;
    					break;
    				}else{
    					$upData[$k]['pmpq'] = $pmpq;
    				}
    			  }
    		    }
    		    if(empty($priceArr)){
    			   $message .='请填入报价';
    			   $error++;
    		   }else{
    		      foreach($priceArr as $k=>$price){
    				 $upData[$k]['result_price'] = $price;
    		      }
    		   }
    	      if(empty($expiration_timeArr)){
    			  $message .='请填入报价过期时间';
    			  $error++;
    		  }else{
    			foreach($expiration_timeArr as $k=>$time){
    				if(empty($time)) {
    					$message .='请填入报价过期时间';
    					$error++;
    					break;
    				}elseif((strtotime($time)+86399)<time()){
    					$message .='报价过期时间不能小于今天';
    					$error++;
    					break;
    			    }else{
    					//多加23:59:59
    					$upData[$k]['expiration_time'] = strtotime($time)+86399;
    				}
    			 }
    		  }
    		  /*
    		   * 报价规则
    		   * 1：如果没阶梯价必须通过PMSC询价
    		   * 2：不通过pmsc询价，在报价价时不需符合阶梯价
    		   */
    		  $this->_prodService = new Icwebadmin_Service_ProductService();
    		  foreach($detidArr as $k=>$did){
    		  	 $pmpq         = $upData[$k]['pmpq'];
    		  	 $result_price = $upData[$k]['result_price'];
    		  	 
    		  	 /*
    		  	 $rearray = $this->_inqservice->getPriceHistory($partidArr[$k],$pmpq,$currency);
    		  	 //是否符合历史报价
    		  	 $pricehistory = false;
    		  	 if($rearray){
    		  	 	foreach($rearray as $array){
    		  	 	   if($result_price >= $array['result_price'] && $upData[$k]['expiration_time']<=$array['expiration_time']){
    		  	 	   	  $pricehistory = true;break;
    		  	 	   }
    		  	 	}
    		  	 }
    		  	 if(!$result_price_oaArr[$k] && $result_price && !$pricehistory){
    		  	 	//首先判断书本价
    		  	 	$bookprice = $this->_prodService->getbookprice($partidArr[$k]);
    		  	 	if($currency=='USD'){
    		  	 		if($bookprice['book_price_usd']){
    		  	 			$minprice = $this->fun->getBookPrice($bookprice['book_price_usd'],$pmpq);
    		  	 			if($minprice && $result_price < $minprice){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的报价必须大于等于书本价 ：".$currency.$minprice."。\n2、可以选择历史有效价格进行报价";
    		  	 				$error++;
    		  	 			}elseif($minprice<=0){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的最少起订量不在书本价范围内，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			    $error++;
    		  	 			}
    		  	 		}else{
    		  	 			$message .= "1、产品：".$bookprice['part_no']."没有{$currency}书本价，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			$error++;
    		  	 		}
    		  	 	}elseif($currency=='RMB'){
    		  	 		$break_price_rmb = $bookprice['book_price_rmb']?$bookprice['book_price_rmb']:$this->fun->breakpriceUsdtormb($bookprice['book_price_usd']);
    		  	 		if($break_price_rmb){
    		  	 			$minprice = $this->fun->getBookPrice($break_price_rmb,$pmpq);
    		  	 			if($minprice && $result_price < $minprice){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的报价必须大于等于书本价：".$currency.$minprice."。\n2、可以选择历史有效价格进行报价";
    		  	 				$error++;
    		  	 			}elseif($minprice<=0){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的最少起订量不在书本价范围内，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			    $error++;
    		  	 			}
    		  	 		}else{
    		  	 			$message .= "1、产品：".$bookprice['part_no']."没有{$currency}书本价，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			$error++;
    		  	 		}
    		  	 	}elseif($currency=='HKD'){
    		  	 		$break_price_hkd = $this->fun->breakpriceUsdtohkd($bookprice['book_price_usd']);
    		  	 		if($break_price_hkd){
    		  	 			$minprice = $this->fun->getBookPrice($break_price_hkd,$pmpq);
    		  	 			if($minprice && $result_price < $minprice){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的报价必须大于等于书本价：".$currency.$minprice."。\n2、可以选择历史有效价格进行报价";
    		  	 				$error++;
    		  	 			}elseif($minprice<=0){
    		  	 				$message .= "1、产品：".$bookprice['part_no']."的最少起订量不在书本价范围内，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			    $error++;
    		  	 			}
    		  	 		}else{
    		  	 			$message .= "1、产品：".$bookprice['part_no']."没有{$currency}书本价，请向PMSC询价后才能向客户报价。\n2、可以选择历史有效价格进行报价";
    		  	 			$error++;
    		  	 		}
    		  	 	}
    		  	  }else{//end if(!$result_price_oaArr[$k] && $result_price)
    		  	  	if($result_price < $result_price_oaArr[$k]){
    		  	  		$bookprice = $this->_prodService->getbookprice($partidArr[$k]);
    		  	  		$message .= "1、产品：".$bookprice['part_no']."的报价必须大于等于PMSC报价：".$currency.$result_price_oaArr[$k]."。";
    		  	  		$error++;
    		  	  	}
    		  	  }*/
    		  }
    		  
    		}elseif($status ==4){
    			if(empty($result_remark)){
    				$message .='请输入报价说明';
    				$error++;
    			}
    		}
    		
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$model = new Icwebadmin_Model_DbTable_Model('inquiry');
    			$model->beginTransaction();
    			try {
    			//不是审核不通过，更新
    			if($status==2 && !$reasons)
    			{
    			  foreach($detidArr as $k=>$did){
    			  	if($upData[$k]['result_price'])
    			  	{
    				   $ddate = array('expiration_time'=>$upData[$k]['expiration_time'],
    						'result_remark'=>$detailed_remark[$k],
    						'pmpq'=>$upData[$k]['pmpq'],
    						'result_price'=>$upData[$k]['result_price']);
    				  
    			  	}else{
    			  		$ddate = array('expiration_time'=>'',
    			  				'result_remark'=>'',
    			  				'pmpq'=>'',
    			  				'result_price'=>'');
    			  	}
    			  	$this->_inqservice->updateDetailed($did,$ddate);
    			  }
    			  //添加积分
    			  $this->_scoreService = new Default_Service_ScoreService();
    			  $this->_scoreService->addScore('inquiry',1,'报价添加积分',$data['uid']);
    			}elseif($reasons){
    				$ddate = array('expiration_time'=>'',
    						'result_remark'=>'',
    						'pmpq'=>'',
    						'result_price'=>'');
    				$this->_inqservice->updateDetWhere($ddate, "inq_id='{$id}'");
    			}else{
    				foreach($detidArr as $k=>$did){
    					$ddate = array('expiration_time'=>'',
    					'result_remark'=>'',
    					'pmpq'=>'',
    					'result_price'=>'');
    					$this->_inqservice->updateDetailed($did,$ddate);
    				}
    			}
    			$inqDate = array('status'=>$status,
    					'percentage'=>$percentage,
    					'paper_contract'=>$paper_contract,
    					'down_payment'=>$down_payment,
    					'paytype'=>$paytype,
    					'result_remark'=>$result_remark,
    					'reasons'=>$reasons,
    					'modified'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$this->_inqservice->updateInquiry($id,$inqDate);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'报价成功'));
    			$model->commit();
    			//异步请求开始
    			$this->fun->asynchronousStarts();
    			//发送邮件
    			$inqinfo = $this->_inqservice->getInquiryByID($id);
    			$emailreturn = $this->_inqservice->sendReInqEmail($user,$inqinfo);
    			//邮件日志
    			if($emailreturn){
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$id,'temp4'=>'发送报价邮件成功'));
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$id,'temp4'=>'发送报价邮件失败'));
    			}
    		    echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'报价成功'));
    		    //异步请求结束
    		    $this->fun->asynchronousEnd();
    		    exit;
    		    } catch (Exception $e) {
    		    	$model->rollBack();
    		    	echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'参数出错，报价失败。'));
    		    	exit;
    		    }
    		}
    	}
    	
    	//询价详细
    	$inqhistory_tmp = array();
    	$inqhistory = $this->_inqservice->getInquiryHistory($id);
    	foreach($inqhistory as $key=>$inqtmp){
    		if($id==$inqtmp['id']){
    			$inqhistory_tmp[0]=$inqtmp;
    
    		}else{
    			$inqhistory_tmp[$key+1]=$inqtmp;
    		}
    	}
    	$this->view->inqhistory = $inqhistory_tmp;
    	ksort($this->view->inqhistory);

    	//支付方式
    	$paymentser = new Icwebadmin_Service_PaymentService();
    	$paymenttypeser = new Icwebadmin_Service_PaymenttypeService();
    	$this->view->payment = $paymentser->getAll();
    	$this->view->paymenttype = $paymenttypeser->getAll();
    }
    /*
     * ajax获取询价编号
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	//如果销售只能看到自己负责的询价
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	$xswhere = " AND back_inquiry=0 ";
    	if($staffinfo['level_id']=='XS') $xswhere = " AND staffid='".$_SESSION['staff_sess']['staff_id']."'";
    	
    	$keyword = $_GET['q'];
    	$where="`inq_number` LIKE '%".$keyword. "%'".$xswhere;
    	$sqlstr ="SELECT `inq_number`  FROM `inquiry` WHERE {$where}";
    	$soArr = $this->_model->getBySql($sqlstr,array());
    
    	for($i=0;$i<count($soArr);$i++)
    	{
    	echo $keyword = $soArr[$i]['inq_number'] . "\n";
    	}
	}
    public function deleteAction($id){
    	$id = (int) $this->_getParam($id);
    	$this->_model->deleteInquiry($id);
    }
    
    public function ajaxAction(){
    	$this->_helper->layout->disableLayout();
    	$post = $this->getRequest()->getPost();
    	$id = (int) $this->_getParam('id');
    	if($post['expiration_time']){
    		$time = strtotime($post['expiration_time']);
    		$this->_inqservice->updateDetailed($id,array('expiration_time'=>$time));
    		echo $post['expiration_time']; die();
    	}
    	if($post['result']){
    		$this->_inqservice->updateDetailed($id,array('result_price'=>$post['result']));
    		echo $post['result']; die();
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
    	if(!$post['client_cname']){
    		$error++;
    		$message .="请输入中文名称.<br/>";
    	}
    	/*if(!$post['client_ename']){
    		$error++;
    		$message .="请输入 英文名称.<br/>";
    	}*/
    	$this->uoa_model = new Icwebadmin_Model_DbTable_Model('user_oa_apply');
    	if($post['id']){
    		$re = $this->uoa_model->getAllByWhere("uid !='".$post['uid']."' AND client_cname='".$post['client_cname']."'");
    		if($re){
    			$error++;
    			$message .="中文名称已经存在.<br/>";
    		}
    		if($post['client_ename']){
    		$re = $this->uoa_model->getAllByWhere("uid !='".$post['uid']."' AND client_ename='".$post['client_ename']."'");
    		if($re){
    			$error++;
    			$message .="英文名称已经存在.<br/>";
    		}
    		}
    	}else{
    		$re = $this->uoa_model->getAllByWhere("client_cname='".$post['client_cname']."'");
    		if($re){
    			$error++;
    			$message .="中文名称已经存在.<br/>";
    		}
    		if($post['client_ename']){
    		$re = $this->uoa_model->getAllByWhere("client_ename='".$post['client_ename']."'");
    		if($re){
    			$error++;
    			$message .="英文名称已经存在.<br/>";
    		}
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
    	/*if(!$post['eaddress']){
    		$error++;
    		$message .="请输入英文地址.<br/>";
    	}*/
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
    	/*if(!$post['website']){
    		$error++;
    		$message .="请输入 网站.<br/>";
    	}*/
    	 
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
     * 联系不上客户发邮件
     */
    public function sendmailtouserAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$data    = $this->getRequest()->getPost();
    		$inqid   = $data['id'];
    		//用户信息
    		$user = $this->_inqservice->getUserById($inqid);
    		//发送邮件
    		$inqinfo = $this->_inqservice->getInquiryByID($inqid);
    		$emailreturn = $this->_inqservice->emailAlertToUser($user,$inqinfo);
    		//邮件日志
    		if($emailreturn){
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$inqid,'temp4'=>'发送报价邮件成功'));
    			$this->_inqservice->updateInquiry($inqid,array('mail_alert'=>1));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提醒邮件发送成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$inqid,'temp4'=>'发送报价邮件失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提醒邮件发送失败'));
    			exit;
    		}
    	}
    }
    /**
     * 修改标准交期
     */
    public function changeleadtimeAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_inqModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
    	$prodService = new Icwebadmin_Service_ProductService();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$data    = $this->getRequest()->getPost();
    		if(!$data['lead_time']){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请填写标准交期'));
    			exit;
    		}
    		
    		$re = $this->_inqModer->updateInquiry($data['inqdid'],array('inq_lead_time'=>$data['lead_time']));
    		if($re){
    		   $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'更新标准交期成功','description'=>$data['old_lead_time'].'更新为'.$data['lead_time']));
    		   echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新标准交期成功'));
    		   exit;
    		}else{
    		  $this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['id'],'temp4'=>'更新标准交期失败','description'=>$data['old_lead_time'].'更新为'.$data['lead_time']));
    		  echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'更新标准交期失败'));
    		  exit;
    		}
    	}
    	$this->view->prodinfo = $prodService->getInqProd($this->_getParam('pratid'));
    	$this->view->inqdinfo   = $this->_inqModer->getRowByWhere("id='".$this->_getParam('inqdid')."'");
    }
    /**
     * 发邮件
     */
    public function sendmailAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $this->_getParam('id');
    }
    /**
     * 查看询价详细
     */
    public function viewinqAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->_getParam('inq_number')){
    		$this->view->id = $id = $this->_inqservice->getIdByNumber($this->_getParam('inq_number'));
    	}else{
    	   $this->view->id = $id = $this->_getParam('id');
    	}
    	$this->_helper->layout->disableLayout();
    	//询价者信息
    	$this->view->user = $user = $this->_inqservice->getUserById($id); 
    	//询价详细
    	$inqhistory_tmp = array();
    	$inqhistory = $this->_inqservice->getInquiryHistory($id);
    	foreach($inqhistory as $key=>$inqtmp){
    		if($id==$inqtmp['id']){
    			$inqhistory_tmp[0]=$inqtmp;
    	
    		}else{
    			$inqhistory_tmp[$key+1]=$inqtmp;
    		}
    		//pcn，pdn
    		$detaile = $inqtmp['detaile'];
    		foreach($detaile as $n=>$d){
    			$inqhistory_tmp[$key]['detaile'][$n]['pdnpcn'] = $this->_prodService->checkpdnpcn($d['part_id']);
    		}
    	}
    	$this->view->inqhistory = $inqhistory_tmp;
    	ksort($this->view->inqhistory);
    	//查找订单号
    	$this->view->inqorder = $this->_inqservice->getInqOrder($id);
    }
    /**
     * 不能支持报价
     */
    public function notsupportAction(){
    	$this->_helper->layout->disableLayout();
    	$inqreasonsModer = new Icwebadmin_Model_DbTable_Model('inquiry_reasons');
    	$this->view->inqreason = $inqreasonsModer->getAllByWhere("status=1");
    	$this->view->id = $this->_getParam('id');
    	$this->view->inqinfo = $this->_inqservice->getInquiryByID($this->view->id);
    }
    /**
     * 更新特殊价格
     */
    public function updatepmscpriceAction(){
    	$this->_inqModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$data    = $this->getRequest()->getPost();
    		if($data['det_id'] && $data['special_result_price']){
    		foreach($data['det_id'] as $k=>$inqdid){
    		  $re = $this->_inqModer->updateInquiry($inqdid,array('special_result_price'=>$data['special_result_price'][$k]));
    		  if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['inq_number'],'temp4'=>'更新特殊价格成功','description'=>'详细ID：'.$inqdid.';'.$data['special_result_price_old'][$k].'更新为'.$data['special_result_price'][$k]));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新特殊价格成功'));
    			exit;
    		  }
    		}
    		}
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->inqinfo = $this->_inqservice->getInquiryByID($this->_getParam('id'));
    }
    /**
     * 获取有效期内的报价历史
     */
    public function pricehistoryAction(){
    	$this->_helper->layout->disableLayout();
    	$part_id  = $this->_getParam('partid');
    	$pmpq     = $this->_getParam('pmpq');
    	$this->view->currency = $currency = $this->_getParam('currency');
    	$this->view->key = $this->_getParam('key');
    	$this->view->rearray = $this->_inqservice->getPriceHistory($part_id, $pmpq,$currency);
    }
    /**
     * 填写原因
     */
    public function reasonAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$data    = $this->getRequest()->getPost();
    		$inqid   = $data['id'];
    		$reason   = $data['reason'];
    		$re = $this->_inqservice->updateInquiry($inqid, array('reason'=>$reason));
    		//邮件日志
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$inqid,'temp4'=>'更新报价跟进成功','description'=>$reason));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$inqid,'temp4'=>'更新报价跟进失败','description'=>$reason));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新失败'));
    			exit;
    		}
    	}
    	$this->view->id  = $this->_getParam('id');
    	$this->view->inqinfo = $this->_inqservice->getInquiryByID($this->view->id);
    	//获取历史记录
    	$sql = "SELECT `created`,`description` FROM `admin_log` 
    			WHERE `controller` = 'QuoInq' 
    			AND `action` = 'reason' 
    			AND `temp2`= '{$this->view->id}'
    			AND `temp1` IS NULL";
    	$this->view->reasonlog = $this->_adminlogService->getLogBySql($sql);
    }
}