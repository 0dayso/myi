<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_OrInqoController extends Zend_Controller_Action
{
	private $_inqsoService;
	private $_adminlogService;
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
    	
    	$this->view->changestatusurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/changestatus";
    	$this->view->release = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/release";
    	$this->view->emancipateurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/emancipate";
    	$this->view->viewsourl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/viewso";
    	$this->view->processingurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/processing";
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
    	$this->_inqsoService = new Icwebadmin_Service_InqOrderService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	
    	$this->view->fun =$this->fun= new MyFun();
    	
    	$this->config = Zend_Registry::get('config');
    	$this->view->reviewer = $this->config->order->reviewer;
    	$this->view->tel = $this->config->order->tel;
    	$this->view->email = $this->config->order->email;
    	$this->_prodService= new Icwebadmin_Service_ProductService();
    }
    public function indexAction(){
    	$inqsoModel = new Icwebadmin_Model_DbTable_InqSalesOrder;
    	$spModel = new Icwebadmin_Model_DbTable_SalesProduct();

    	$sqlarr = array();
    	$selectstr = '';
    	$orderbystr = '';
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
    	}
    	else{
    		//根据应用领域分配跟进销售
    		$this->view->xs_staff = $this->_staffservice->getXiaoShou();
    		$this->view->xsname = $_GET['xsname'];
    		if($_GET['xsname']){
    			$selectstr .= " AND up.staffid = '".$_GET['xsname']."'";
    		}
    	}
    	//排序
    	$orderbyarr = array('ASC','DESC');
    	$orderarray = array('total','created');
    	$this->view->ordertype = $ordertype = $_GET['ordertype'];
    	if(in_array($ordertype,$orderarray)){
    		$this->view->orderby = $orderby = ($_GET['orderby']==''?'ASC':$_GET['orderby']);
    		if($ordertype=='total' && in_array($orderby,$orderbyarr)){
    			$orderbystr = " ORDER BY so.total ".$orderby;
    		}
    		if($ordertype=='created' && in_array($orderby,$orderbyarr)){
    			$orderbystr = " ORDER BY so.created ".$orderby;
    		}
    	}
    	//开始和结束时间
    	$this->view->sdata = $sdata = $_GET['sdata'];
    	$this->view->edata = $edata = $_GET['edata'];
    	if($sdata){
    		$edata = $edata?strtotime($edata):time();
    		$selectstr .=" AND so.created BETWEEN ".strtotime($sdata)." AND ".$edata;
    	}
    	//订单性质
    	
    	$this->view->back_order = $_GET['back_order']?$_GET['back_order']:'before';
    	if($this->view->back_order=='all'){
    		$selectstr .="";
    	}elseif($this->view->back_order=='back'){
    		$selectstr .=" AND so.back_order = 1";
    	}else{
    		$selectstr .=" AND so.back_order = 0";
    	}
    	//支付类型
    	$this->view->paytype = $paytype = $_GET['paytype'];
    	if(in_array($paytype,array('transfer','online','cod'))){
    		$selectstr .=" AND so.paytype='{$paytype}' ";
    	}
    	//交货地
    	$this->view->delivery_place = $delivery_place = $_GET['delivery_place'];
    	if(in_array($delivery_place,array('HK','SZ'))){
    		$selectstr .=" AND so.delivery_place='{$delivery_place}' ";
    	}
    	//货物方式
    	$this->view->shipments = $shipments = $_GET['shipments'];
    	if(in_array($shipments,array('spot','order'))){
    		$selectstr .=" AND so.shipments='{$shipments}' ";
    	}
    	//订单流程
    	$this->view->sqs_code = $sqs_code = $_GET['sqs_code'];
    	if(in_array($sqs_code,array('1','2'))){
    		$selectstr .=" AND so.sqs_code='".($sqs_code-1)."'";
    	}
    	
    	//选择不同的类型  (待释放，待付款，待发货)
    	$typeArr =array('rel','wpay','proc','change','over','ema','rec','shou','eva','can','all','not','select','back');
    	$this->view->type = $typetmp = $_GET['type']==''?'rel':$_GET['type'];
    	$typestr = $selectstr;
    	$relsql  = " AND so.status!='401' AND so.back_status='101'".$selectstr;
    	$wpaysql = " AND so.status='101' AND so.paytype!='cod' AND so.back_status='201'".$selectstr;
    	$emasql  = " AND so.status='102' AND so.back_status=201".$selectstr;
    	$procsql = " AND so.status='102' AND so.back_status=202".$selectstr;
    	$changesql = " AND so.delivery_status='101' AND so.back_status!=102".$selectstr;
    	$oversql = " AND (so.status='103' OR so.status='201') AND so.back_status!=202".$selectstr;
    	$recsql  = " AND so.status='201' AND so.back_status=202".$selectstr;
    	$shousql = " AND so.status='202' AND so.back_status=202".$selectstr;
    	$evasql  = " AND so.status='301' AND so.back_status=202".$selectstr;
    	$cansql  = " AND so.status='401'".$selectstr;
    	$allsql  = " AND so.back_status!=102".$selectstr;
    	$notsql  = " AND so.back_status=102".$selectstr;
    	$backsql = " AND so.status IN ('501','502')".$selectstr;
    	//待释放订单数
    	$this->view->relnum = $this->_inqsoService->getRowNum($relsql);
    	//待付款
    	$this->view->wpaynum = $this->_inqsoService->getRowNum($wpaysql);
    	//处理中订单数
    	$this->view->procnum = $this->_inqsoService->getRowNum($procsql);
    	//交货期更改申请
    	$this->view->changenum = $this->_inqsoService->getRowNum($changesql);
    	//待支付余款
    	$this->view->overnum = $this->_inqsoService->getRowNum($oversql);
    	//待释放订单数
    	$this->view->emanum = $this->_inqsoService->getRowNum($emasql);
    	//已释放待发货订单数
    	$this->view->recnum = $this->_inqsoService->getRowNum($recsql);
    	//已发货待收货
    	$this->view->shounum = $this->_inqsoService->getRowNum($shousql);
    	//已完成
    	$this->view->evanum = $this->_inqsoService->getRowNum($evasql);
    	//已取消
    	$this->view->cannum = $this->_inqsoService->getRowNum($cansql);
    	//全部有效
    	$this->view->allnum = $this->_inqsoService->getRowNum($allsql);
    	//不通过
    	$this->view->notnum = $this->_inqsoService->getRowNum($notsql);
    	//退款退货
    	$this->view->backnum = $this->_inqsoService->getRowNum($backsql);
    	if($typetmp == 'rel') {
    		$typestr = $relsql;
    		$total = $this->view->relnum;
    	}elseif($typetmp == 'wpay'){
    		$typestr = $wpaysql;
    		$total = $this->view->wpaynum;
    	}elseif($typetmp == 'proc'){
    		$typestr = $procsql;
    		$total = $this->view->procnum;
    	}elseif($typetmp == 'change'){
    		$typestr = $changesql;
    		$total = $this->view->changenum;
    	}elseif($typetmp == 'over'){
    		$typestr = $oversql;
    		$total = $this->view->overnum;
    	}elseif($typetmp == 'ema'){
    		$typestr = $emasql;
    		$total = $this->view->emanum;
    	}elseif($typetmp == 'rec'){
    		$typestr = $recsql;
    		$total = $this->view->recnum;
    	}elseif($typetmp == 'shou'){
    		$typestr = $shousql;
    		$total = $this->view->shounum;
    	}elseif($typetmp == 'eva'){
    		$typestr = $evasql;
    		$total = $this->view->evanum;
    	}elseif($typetmp == 'can'){
    		$typestr = $cansql;
    		$total = $this->view->cannum;
    	}elseif($typetmp == 'all'){
    		$typestr = $allsql;
    		$total = $this->view->allnum;
    	}elseif($typetmp == 'not'){
    		$typestr = $notsql;
    		$total = $this->view->notnum;
    	}elseif($typetmp == 'back'){
    		$typestr = $backsql;
    		$total = $this->view->backnum;
    	}else {
    		$total = $this->view->allnum;
    		$typestr .= $selectstr;
    	}
    	if($_GET['salesnumber']){
    		$this->view->salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    		$typestr = $selectsql=" AND so.salesnumber LIKE '%".$this->view->salesnumber. "%'".$selectstr;
    		$this->view->selectnum = $total = $this->_inqsoService->getRowNum($selectsql);
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$soArr = $this->_inqsoService->getAllSo($offset, $perpage, $typestr,$orderbystr);
    	$prodService = new Icwebadmin_Service_ProductService();
    	$numAll = count($soArr);
    	for($i=0;$i<$numAll;$i++){
    	$data = $soArr[$i];
    	$product = $ptmp = array();
    		$product = $spModel->getAllByWhere("salesnumber='".$data['salesnumber']."'");
    		for($j=0;$j<count($product);$j++){
    			$data2=$product[$j];
    			$partinfo = $prodService->getProductByID($data2['prod_id']);
    			//pcn，pdn
    			$partinfo['pdnpcn'] = $this->_prodService->checkpdnpcn($data2['prod_id']);
    			
    			$ptmp[$data2['prod_id']]=$partinfo;
    		}
    		$data['prodarr']=$ptmp;
    	    $sotmp[] = $data;
    	}
        $this->view->salesorder = $sotmp;
        //通用器件
        $eventservice = new Icwebadmin_Service_EventService();
        $this->view->tongyong = $eventservice->getTongYong();
    }
    /**
     * 查看订单
    */
    public function viewsoAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    }
    /**
     * 审核订单
    */
    public function releaseAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$status     = $formData['status'];
    		$notpass     = $formData['notpass'];
    		$sqscode     = (int)($formData['sqscode']?$formData['sqscode']:0);
    		if($status==201) $back_status = 201;
    		elseif($status==102) $back_status = 102;
    		else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'参数错误。'));
    			exit;
    		}
    		
    		$uparr = array('sqs_code'=>$sqscode,'back_status'=>$back_status,'admin_notes'=>$notpass,'modified'=>time());
    		//货到付款并且有现货，
    		if($this->_inqsoService->checkCodShipments($salesnumber)) $uparr['status'] = 102;
    		elseif($this->_inqsoService->checkCod($salesnumber)) $uparr['status']=102;
    		//预付全款是否为0
    		if(!$this->_inqsoService->checkDownPayment($salesnumber)) $uparr['status']=102;

    		$re = $this->_inqsoService->updateStatus($salesnumber,$uparr);
    		if($re)
    		{
    			//审核不通过，恢复产品数量
    			if($back_status == 102){
    				$this->_prodService = new Default_Service_ProductService();
    				$this->_prodService->reinstate($salesnumber);
    			}
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp3'=>$back_status,'temp4'=>'审核订单成功','description'=>$notpass));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'审核订单成功'));
    			exit;
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp3'=>$back_status,'temp4'=>'审核订单失败','description'=>$notpass));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'审核订单失败'));
    			exit;
    		}
    	}
    	if($_GET['sonum']){
    	    $this->view->salesnumber = $_GET['sonum'];
    	    //是否允许走sqs code
    	    $this->view->cansqs = $this->_inqsoService->canSqsCode($this->view->salesnumber);
    	}else{
    		$this->_redirect('/icwebadmin');
    	}
    }
    /**
     * 更改订单状态
    */
    public function changestatusAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$type        = $formData['type'];
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$status = 0;
    		if($type == 'pay')
    		{
    			//检查是否支付全额
    			//if($this->_inqsoService->checkAllpay($salesnumber)) $status = 201;
    			$status = 102;
    			$uparr = array('status'=>$status,'pay_time'=>time(),'modified'=>time());
    			$re = $this->_inqsoService->updateStatus($salesnumber,$uparr);
    			$message = '确认付款成功';
    		}elseif($type == 'payover')
    		{
    			$status = 201;
    			$re = $this->_inqsoService->updateStatus($salesnumber,array('status'=>$status,'pay_2_time'=>time(),'modified'=>time()));
    			$message = '确认收取剩余货款成功';
    		}elseif($type == 'noneed')
    		{
    			//不需要反馈交期
    			if($this->_inqsoService->checkCod($salesnumber) || $this->_inqsoService->checkAllpay($salesnumber)){
    				$uparr = array('status'=>201,'modified'=>time());
    			}else{
    				$uparr = array('status'=>103,'back_status'=>301,'modified'=>time());
    			}
    			$re = $this->_inqsoService->updateStatus($salesnumber,$uparr);
    			$message = '操作成功成功';
    		}
    	    if($re)
    	    {
    	    	//日志
    	    	$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp3'=>$status,'temp4'=>'确认收款成功','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>$message));
    			exit;
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>400,'temp2'=>$salesnumber,'temp3'=>$status,'temp4'=>'确认收款失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'操作失败。'));
    			exit;
    		}
    	}
    }
    /*
     * 退款退货
    */
    public function backorderAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber  = $_GET['sonum'];
    	$this->view->sonid        = $_GET['sonid'];
    	$this->view->type         = $_GET['type'];
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$type        = $formData['type'];
    		$admin_notes = $formData['admin_notes'];
    		if($type =='reimburse') $status = 501;
    		elseif($type=='returns') $status = 502;
    		else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'参数错误。'));
    			exit;
    		}
    
    		$re = $this->_inqsoService->updateByNum(array('status'=>$status,'admin_notes'=>$admin_notes,'modified'=>time()), $salesnumber);
    		if($re){
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'操作失败。'));
    			exit;
    		}
    
    	}
    }
    /**
     * 处理订单页面，需要订货的订单
     */
    public function processingAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber  = $_GET['sonum'];
    	$this->view->type  = $_GET['type'];
    	$this->view->deliverytime_old  = $_GET['deliverytime_old']?date("Y-m-d",$_GET['deliverytime_old']):'';
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber   = $formData['salesnumber'];
    		$delivery_time = $formData['delivery_time'];
    		$type = $formData['type'];
    		if(empty($salesnumber) || empty($delivery_time)){
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'交期反馈失败','description'=>'参数错误'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'参数错误'));
    			exit;
    		}else{
    			//if($this->_inqsoService->checkAllpay($salesnumber)) $status = 201;
    			if($type=='edit'){
    				$uparr = array('delivery_time'=>strtotime($delivery_time),'modified'=>time());
    			}else{
    			   $uparr = array('delivery_time'=>strtotime($delivery_time),'feedback_time'=>time(),'modified'=>time());
    			   if($this->_inqsoService->checkCod($salesnumber) || $this->_inqsoService->checkAllpay($salesnumber)){ 
    				   $uparr['status'] = 201;
    			   }else{
    				   $uparr['status'] = 103;
    				   $uparr['back_status']=301;
    			   }
    			}
    			$re = $this->_inqsoService->updateStatus($salesnumber,$uparr);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'交期反馈成功','description'=>strtotime($delivery_time)));
    			
    			//异步请求开始
    			$this->fun->asynchronousStarts();
    			//发送邮件
    			$orderarr = $this->_inqsoService->getSoInfo($salesnumber);
    			$pordarr = $this->_inqsoService->getSoPart($salesnumber);
    			$emailreturn = $this->_inqsoService->leadtimeEmail($orderarr,$pordarr);
    			//邮件日志
    			if($emailreturn){
    		       $this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送交期反馈邮件成功'));
    			}else{
    			   $this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送交期反馈邮件失败'));
    			 }
    			 echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单已经处理成功'));
    			  //异步请求结束
    			  $this->fun->asynchronousEnd();
    			  exit;
    			
    		}
    	}
    }
    public function mailsendAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber = $this->_getParam('salesnumber');
    	$this->view->type = $this->_getParam('type');
    	$this->view->soinfo = $this->_inqsoService->getSoInfo($this->view->salesnumber);
    	//是否允许走sqs code
    	$this->view->cansqs = $this->_inqsoService->canSqsCode($this->view->salesnumber);
    }
    /**
     * 释放订单发邮件通知
     */
     public function emancipateAction(){
     	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
     	{
     		echo "权限不够。";
     		exit;
     	}
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$salesnumber= $this->filter->pregHtmlSql($_POST['salesnumber']);
		//更新sqs code
		$sqscode = ($_POST['sqscode']?1:0);
		$uparr = array('sqs_code'=>$sqscode,'modified'=>time());
		$this->_inqsoService->updateStatus($salesnumber,$uparr);

		$orderarr = $this->_inqsoService->getSoInfo($salesnumber,' AND so.status=102 AND so.back_status=201');
		$orderarr['line_process'] = $line_process = $_POST['line_process'];
		if(empty($orderarr)) {
			//日志
			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'订单查询结果为空'));
			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败'.$salesnumber));
			exit;
		}
		
		//增值税发票
		$filearray = array('0'=>'','1'=>'','2'=>'','3'=>'','4'=>'');
		if($orderarr['itype']==2){
			//附件
			$annexur_part = COM_ANNEX.$orderarr['uid'].'/';
			$annexurl = $annexur_part.$orderarr['annex1'];
			$annexurl2= $annexur_part.$orderarr['annex2'];
			if(file_exists($annexurl) && $orderarr['annex1']){
				$filearray[0] = $annexurl;
			}else {
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少营业执照'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少营业执照'.$salesnumber));
				exit;
			}
			if(file_exists($annexurl2) && $orderarr['annex2']){
				$filearray[1] = $annexurl2;
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少税务登记证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少税务登记证'.$salesnumber));
				exit;
			}
		}
		//转账凭证
		if($orderarr['paytype']=='transfer'){
			if(!empty($orderarr['receipt']) && file_exists(UP_RECEIPT.$orderarr['receipt'])){
				$filearray[2] = UP_RECEIPT.$orderarr['receipt'];
			}elseif($orderarr['down_payment']>0){
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少转账凭证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少转账凭证'.$salesnumber));
				exit;
			}
		}
		//如果需要盖章合同
		//if($orderarr['paper_contract']){
		if(!$line_process){
			//如果存在
			$pdfpart = ORDER_PAPER.md5('order'.$orderarr['salesnumber']).'.pdf';
			if(file_exists($pdfpart)){
				$filearray[4] = $pdfpart;
			}else{
				//国内合同
				$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
				$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
				$definqorderService = new Default_Service_InqOrderService();
				//产品详细
				$orderarr['pordarr'] = $this->_inqsoService->getSoPart($orderarr['salesnumber']);
				//用户资料
				$userService = new Icwebadmin_Service_UserService();
				$userinfo = $userService->getUserProfile($orderarr['uid']);
				if($orderarr['delivery_place'] == 'SZ') $return=$definqorderService->szContract($orderarr,$userinfo,$currencyArr,$unit);
				elseif($orderarr['delivery_place'] == 'HK') $return=$definqorderService->hkContract($orderarr,$userinfo,$currencyArr,$unit);
				if($return) $filearray[4] = $pdfpart;
			}
			if(!$filearray[4]){
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少合同'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少合同'.$salesnumber));
				exit;
			}
		}
		//}
		//产品详细
		$pordarr = $this->_inqsoService->getSoPart($salesnumber);
		
		//异步请求开始
		$this->fun->asynchronousStarts();
		//发送邮件
		$emailreturn = $this->_inqsoService->emancipateSendEmail($orderarr,$pordarr,$filearray);
		//邮件日志
		if($emailreturn){
			//更改订单状态
			$re = $this->_inqsoService->updateStatus($salesnumber,array('line_process'=>$line_process,'back_status'=>202,'modified'=>time()));
			if($re)
			{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'释放订单成功'));
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败'));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单释放失败。'));
				exit;
			}
			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件成功'));
		}else{
			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件失败'));
		}
		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单释放成功。订单号：'.$salesnumber));
		//异步请求结束
		$this->fun->asynchronousEnd();
		exit;
		
	}
	/**
	 * 支付余款通知发货邮件
	 */
	public function deliverymailAction(){
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo "权限不够。";
			exit;
		}
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$salesnumber= $this->filter->pregHtmlSql($_POST['salesnumber']);
		$orderarr = $this->_inqsoService->getSoInfo($salesnumber,' AND so.status=201 AND so.back_status=301');
		if(empty($orderarr)) {
			//日志
			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'通知发货失败',' 	description'=>'订单查询结果为空'));
			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'通知发货失败'.$salesnumber));
			exit;
		}
		//增值税发票
		$filearray = array('0'=>'','1'=>'','2'=>'','3'=>'');
		if($orderarr['itype']==2){
			//附件
			$annexur_part = COM_ANNEX.$orderarr['uid'].'/';
			$annexurl = $annexur_part.$orderarr['annex1'];
			$annexurl2= $annexur_part.$orderarr['annex2'];
			if(file_exists($annexurl) && $orderarr['annex1']){
				$filearray[0] = $annexurl;
			}else {
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少营业执照'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少营业执照'.$salesnumber));
				exit;
			}
			if(file_exists($annexurl2) && $orderarr['annex2']){
				$filearray[1] = $annexurl2;
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少税务登记证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少税务登记证'.$salesnumber));
				exit;
			}
		}
		//转账凭证
		if($orderarr['paytype']=='transfer'){
			if(!empty($orderarr['receipt_2']) && file_exists(UP_RECEIPT.$orderarr['receipt_2'])){
				$filearray[3] = UP_RECEIPT.$orderarr['receipt_2'];
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少余额转账凭证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少余额转账凭证'.$salesnumber));
				exit;
			}
		}
		//产品详细
		$pordarr = $this->_inqsoService->getSoPart($salesnumber);
	
		//异步请求开始
		$this->fun->asynchronousStarts();
		//发送邮件
		$emailreturn = $this->_inqsoService->deliverySendEmail($orderarr,$pordarr,$filearray);
		//邮件日志
		if($emailreturn){
			//更改订单状态
			$re = $this->_inqsoService->updateStatus($salesnumber,array('back_status'=>202,'modified'=>time()));
			if($re)
			{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'通知发货成功'));
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'通知发货失败'));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'通知发货失败。'));
				exit;
			}
			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送通知发货邮件成功'));
		}else{
			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送通知发货邮件失败'));
		}
		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'通知发货。订单号：'.$salesnumber));
		//异步请求结束
		$this->fun->asynchronousEnd();
		exit;
	
	}
	/**
	 * 处理交货期更改申请
	 */
	public function deliverychangeAction(){
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo "权限不够。";
			exit;
		}
		$this->_helper->layout->disableLayout();
		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$formData    = $this->getRequest()->getPost();
			$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
			$delivery_time = $this->filter->pregHtmlSql($formData['delivery_time']);
			$delivery_change_date = $this->filter->pregHtmlSql($formData['delivery_change_date']);
			$status = (int)$formData['status'];
			if(empty($status)){
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请审批情况'));
				exit;
			}else{
				$salesorderModel   = new Icwebadmin_Model_DbTable_InqSalesOrder();
				$orderarr = $this->_inqsoService->getSoInfo($salesnumber);
				if($status == 201){
					$this->_inqsoService->deliverychangeEmail($orderarr,'yes');
				    $update = array('delivery_status'=>201,'delivery_time'=>$delivery_change_date,'delivery_time_back'=>$delivery_time);
				}else{
					$this->_inqsoService->deliverychangeEmail($orderarr,'no');
					$update = array('delivery_status'=>301);
				}
				$re = $salesorderModel->update($update, "salesnumber='{$salesnumber}'");
				if($re){
					//jamie
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交成功'));
					exit;
				}else{
					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'提交失败'));
					exit;
				}
			}
		}else{
			$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
			$this->view->so = $this->_inqsoService->getSoInfo($salesnumber);
			
		}
	}
	/**
	 * 查看快递信息
	*/
	public function courierAction(){
		$this->_helper->layout->disableLayout();
		$this->view->salesnumber  = $salesnumber = $_GET['sonum'];
		$chModel = new Icwebadmin_Model_DbTable_CourierHistory();
		$sqlstr ="SELECT ch.*,c.name,st.lastname,st.firstname
    	          FROM courier_history as ch
                  LEFT JOIN courier as c ON ch.cou_id=c.id
    			  LEFT JOIN admin_staff as st ON st.staff_id = ch.created_by
    	          WHERE salesnumber=:salesnumbertmp";
		$rearraytmp = $chModel->getBySql($sqlstr, array('salesnumbertmp'=>$salesnumber));
		$this->view->rearray = $rearraytmp[0];  
	}
	public function ajaxcourierAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$cou_number  = $formData['cou_number'];
			$salesnumber = $formData['salesnumber'];
			if(!empty($cou_number)){
				$chModel = new Default_Model_DbTable_CourierHistory();
				$rearray = $chModel->getRowByWhere("cou_number='{$cou_number}' AND salesnumber='{$salesnumber}'");
				$soModel   = new Default_Model_DbTable_SalesOrder();
				$re = $this->_inqsoService->getSoBase("salesnumber='".$salesnumber."'");
				if(empty($re)) $courier='此物流信息不存在。';
				else{
					$courier = $rearray['track'];
				}
				echo Zend_Json_Encoder::encode(array("code"=>0, "courier"=>$courier));
				exit;
			}else {
				echo Zend_Json_Encoder::encode(array("code"=>100, "courier"=>'物流参数错误'));
				exit;
			}
		}
	}
	/*
	 * ajax获取订单号
	*/
	public function getajaxtagAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$soArr = $soArr = $this->_inqsoService->ajaxtag($_GET['q']);
		for($i=0;$i<count($soArr);$i++)
		{
		echo $keyword = $soArr[$i]['salesnumber'] . "\n";
		}
	}
	/**
	 * 后台查看生成合同
	 */
	public function iccontractAction()
	{
		$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
		if( $this->_getParam('key') != md5(session_id())) $this->_redirect('/icwebadmin/OrInqo');
		//订单详情
		$salesnumber = $this->_getParam('salesnumber');
		//订单详细
		$soarray = $this->_inqsoService->getSoInfo($salesnumber);
		//产品详细
		$soarray['pordarr'] = $this->_inqsoService->getSoPart($salesnumber);
		if(empty($soarray)) {echo '订单信息为空';exit;};
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($soarray['uid']);
		if(empty($userinfo)) {echo '用户为空';exit;};
		//如果存在
		$pdfpart = ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf';
		if(file_exists($pdfpart) && $this->_getParam('type')!='up'){
			$this->_redirect($pdfpart);
		}else{
			//国内合同
			$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
			$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
			$definqorderService = new Default_Service_InqOrderService();
			if($soarray['delivery_place'] == 'SZ'){
				if($soarray['back_order']==1){
				    $return=$definqorderService->szContract_Line($soarray,$userinfo,$currencyArr,$unit);
				}else{
					$return=$definqorderService->szContract($soarray,$userinfo,$currencyArr,$unit);
				}
			}elseif($soarray['delivery_place'] == 'HK'){
				if($soarray['back_order']==1){
				    $return=$definqorderService->hkContract_Line($soarray,$userinfo,$currencyArr,$unit);
				}else{
					$return=$definqorderService->hkContract($soarray,$userinfo,$currencyArr,$unit);
				}
			}
			if($return) $this->_redirect($pdfpart);
		}
	}
	/**
	 * 后台查看生成数字合同
	 */
	public function digitalcontractAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if( $this->_getParam('key') != md5(session_id())) $this->_redirect('/icwebadmin/OrInqo');
		//订单详情
		$salesnumber = $this->_getParam('salesnumber');
		//订单详细
		$soarray = $this->_inqsoService->getSoInfo($salesnumber);
		//产品详细
		$soarray['pordarr'] = $this->_inqsoService->getSoPart($salesnumber);
		if(empty($soarray)) {echo '订单信息为空';exit;};
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($soarray['uid']);
		if(empty($userinfo)) {echo '用户为空';exit;};
		//如果存在
		$pdfpart = ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf';
		if(file_exists($pdfpart) && $this->_getParam('type')!='up'){
			$this->_redirect($pdfpart);
		}else{
			//国内合同
			$definqorderService = new Default_Service_InqOrderService();
			if($soarray['delivery_place'] == 'SZ') $return=$definqorderService->szDigitalContract($soarray,$userinfo);
			elseif($soarray['delivery_place'] == 'HK') $return=$definqorderService->hkDigitalContract($soarray,$userinfo);
			if($return) $this->_redirect($pdfpart);
		}
	}
	/**
	 * 修改发票信息
	 */
	public function editinvoiceAction()
	{
		$this->_helper->layout->disableLayout();
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$invoiceid   = $formData['invoiceid'];
			$type        = $formData['type'];
			$taitouname  = $formData['taitouname'];
			$contype     = $formData['contype'];
			$dwname      = $formData['dwname'];
			$identifier  = $formData['identifier'];
			$regaddress  = $formData['regaddress'];
			$regphone    = $formData['regphone'];
			$bank        = $formData['bank'];
			$account     = $formData['account'];
			$error = 0;$message='';$data = array();
			if($type == 1){
				if(empty($taitouname) || empty($contype)){
					$message ='请填写完整的发票信息。<br/>';
					$error++;
				}else{
					$data['name']    = $taitouname;
					$data['contype'] = $contype;
				}
			}elseif($type==2){
				if(empty($dwname) || empty($identifier)|| empty($regaddress)|| empty($regphone)|| empty($bank)|| empty($account)){
					$message ='请填写完整的发票信息。<br/>';
					$error++;
				}else{
					$data['name']       = $dwname;
					$data['identifier'] = $identifier;
					$data['regaddress'] = $regaddress;
					$data['regphone']   = $regphone;
					$data['bank']       = $bank;
					$data['account']    = $account;
				}
			}else{
				$message ='发票信息错误。<br/>';
				$error++;
			}
			if($error){
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				$invoiceModer = new Icwebadmin_Model_DbTable_Invoice();
				$re = $invoiceModer->updateById($data, $invoiceid);
				if($re){
					$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$invoiceid,'temp4'=>'修改发票信息成功'));
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
					exit;
				}else{
					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'修改失败。'));
					exit;
				}
			}
		}
		$invoiceid = $this->_getParam('invoiceid');
		$soService = new Icwebadmin_Service_OrderService();
		$this->view->invoice = $soService->getInvoice($invoiceid);
	}
	/**
	 * 取消订单
	 */
	public function cancelAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$salesnumber  = ($formData['salesnumber']);
			$salesorderModel   = new Icwebadmin_Model_DbTable_InqSalesOrder();
			$re = $salesorderModel->update(array('status'=>401), "salesnumber='{$salesnumber}'");
			if($re){
				//恢复产品数量
				$this->_prodService = new Default_Service_ProductService();
				$this->_prodService->reinstate($salesnumber);
				//记录日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'订单取消成功'));
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单取消成功'));
				exit;
			}else{
				//记录日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'订单取消失败'));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单取消失败'));
				exit;
			}
		}
	}
	/**
	 * 转账成功后，上传凭证
	 */
	public function transferAction(){
		$this->_helper->layout->disableLayout();
		$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
		$this->view->ordertype = $_GET['ordertype'];
		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
		$this->view->so = $salesorderModel->getRowByWhere("salesnumber='{$salesnumber}'");
	}
	/**
	 * 余款转账成功后，上传凭证
	 */
	public function transfer2Action(){
		$this->_helper->layout->disableLayout();
		$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
		$this->view->ordertype = $_GET['ordertype'];
		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
		$this->view->so = $salesorderModel->getRowByWhere("salesnumber='{$salesnumber}'");
	}
	//更新转账凭证并发邮件
	public function updatereceiptAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData = $this->getRequest()->getPost();
			$salesnumber = $formData['salesnumber'];
			$receipt     = $formData['receipt'];
			$ordertype   = $formData['ordertype'];
			$updata=array();
			
			$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
			$orderService = new Default_Service_InqOrderService();
			$orderarr     = $orderService->geSoinfo($salesnumber,1);
			if($orderarr['status']==101){
					$updata = array('receipt'=>$receipt,'pay_time'=>time(),'status'=>102);
			}elseif($orderarr['status']==103){
					$updata = array('receipt_2'=>$receipt,'pay_2_time'=>time(),'status'=>201,'back_status'=>301);
			}else{
				if($formData['tablerow']==2){
						$updata = array('receipt_2'=>$receipt,'pay_2_time'=>time());
				}else{
						$updata = array('receipt'=>$receipt,'pay_time'=>time());
				}
			}
			$re = $salesorderModel->update($updata, "salesnumber='{$salesnumber}'");
			if($re){
				//记录日志
				$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'上传了转账凭证成功'));
			}else{
				//记录日志
				$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'上传了转账凭证失败'));
			}
		}
	}
	/**
     * ajax查看订单
    */
    public function ajaxorderinfoAction(){
    	$this->config = Zend_Registry::get('config');
    	//取货地址
    	$this->view->delivery_add_sz = $this->config->order->delivery_add_sz;
    	$this->view->delivery_tel_sz = $this->config->order->delivery_tel_sz;
    	$this->view->delivery_workdate_sz = $this->config->order->delivery_workdate_sz;
    	$this->view->delivery_des_sz = $this->config->order->delivery_des_sz;
    	
    	$this->view->delivery_add_hk = $this->config->order->delivery_add_hk;
    	$this->view->delivery_tel_hk = $this->config->order->delivery_tel_hk;
    	$this->view->delivery_workdate_hk = $this->config->order->delivery_workdate_hk;
    	$this->view->delivery_des_hk = $this->config->order->delivery_des_hk;
    	
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	//订单详细
    	$this->view->orderarr = $this->_inqsoService->getSoInfo($salesnumber);
    	//产品详细
    	$pordarr = $this->_inqsoService->getSoPart($salesnumber);
    	foreach($pordarr as $k=>$por){
    		$pordarr[$k]['pdnpcn'] = $this->_prodService->checkpdnpcn($por['prod_id']);
    	}
    	$this->view->pordarr = $pordarr;
    	$this->view->salesnumber = $salesnumber;
    	//询价信息
    	$inqServer = new Icwebadmin_Service_InquiryService();
    	$this->view->inqinfo = $inqServer->getInquiryByID($this->view->orderarr['inquiry_id']);
    	//所有者
    	$this->view->owner = $this->_inqsoService->getOwnerByUid($this->view->orderarr['uid']);
    }
    /*
     * 填写订单pi
     */
    public function orderpiAction(){
    	$this->_helper->layout->disableLayout();
    	$userService = new Icwebadmin_Service_UserService();
    	$this->_staffService=new Icwebadmin_Service_StaffService();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$this->_salesproductModel = new Icwebadmin_Model_DbTable_SalesProduct();
    		$this->_inqsalesorderModel= new Icwebadmin_Model_DbTable_InqSalesOrder();
    		$update = array('ufname'=>$formData['ufname'],
    					'ufaddress'=>$formData['ufaddress'],
    					'ufcontact'=>$formData['ufcontact'],
    					'uftel'=>$formData['uftel']);
    		if($formData['ufid']){
    			$this->_inqsoService->updateUserForwarder($formData['ufid'],$update);
    		}else{
    			$formData['ufid'] = $this->_inqsoService->addUserForwarder($update);
    		}
    		$orderupdate = array('invoice_no'=>$formData['invoice_no'],
    				'ufid'=>$formData['ufid'],
    				'admin_notes'=>$formData['admin_notes'],
    				'customer_id'=>$formData['customer_id'],
    				'delivery_terms'=>$formData['delivery_terms'],
    				'payment_terms'=>$formData['payment_terms'],
    				'down_handling_charge'=>$formData['down_handling_charge'],
    				'surplus_handling_charge'=>$formData['surplus_handling_charge'],
    				'usdtohkd'=>$formData['usdtohkd']);
    		$this->_inqsalesorderModel->update($orderupdate, "salesnumber='".$formData['salesnumber']."'");
    		foreach($formData['partd_id'] as $k=>$id){
    			$update_d = array('item_no'=>$formData['item_no'][$k],
    					'cpn'=>$formData['cpn'][$k],
    					'po_no'=>$formData['po_no'][$k]);
    			$this->_salesproductModel->update($update_d, "id='$id'");
    		}
    		$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$formData['salesnumber'],'temp4'=>'生成订单PI成功'));
    		//生成pi pdf
    		//订单详细
    		$soarray = $this->_inqsoService->getSoInfo($formData['salesnumber']);
    		//产品详细
    		$soarray['pordarr'] = $this->_inqsoService->getSoPart($formData['salesnumber']);
    		//客户货代公司
    		if($soarray['ufid']){
    			$userforwarder = $this->_inqsoService->getUserForwarder($soarray['ufid']);
    		}
    		//用户资料
    		$userinfo = $userService->getUserProfile($soarray['uid']);
    		//负责销售情况
    		$sellinfo = $this->_staffService->sellbyuid($soarray['uid']);
    		if($soarray['total']!=$soarray['down_payment']){
    			//预付款
    			$this->_inqsoService->createPi($soarray,$userforwarder,$userinfo,$sellinfo,'down');
    			//剩余货款
    			$this->_inqsoService->createPi($soarray,$userforwarder,$userinfo,$sellinfo,'surplus');
    		}else{
    			//预付款
    			$this->_inqsoService->createPi($soarray,$userforwarder,$userinfo,$sellinfo,'down');
    		}
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'生成订单PI成功'));
    		exit;
    	}
    	$rateModel = new Default_Model_DbTable_Rate();
    	$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='HKD' AND status='1'");
    	$this->view->usdtohkd = $arr['rate_value'];
    	$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
    	$this->view->usdtormb = $arr['rate_value'];
    	$salesnumber = $this->_getParam('salesnumber');
    	//订单详细
    	$this->view->orderarr = $this->_inqsoService->getSoInfo($salesnumber);
    	//产品详细
    	$this->view->pordarr = $this->_inqsoService->getSoPart($salesnumber);
    	//用户资料
		$this->view->userinfo = $userService->getUserProfile($this->view->orderarr['uid']);
		//收货地址
		$this->view->addressArr = $userService->getUserAddress($this->view->orderarr['uid']);
		//负责销售情况
		$this->view->sellinfo = $this->_staffService->sellbyuid($this->view->orderarr['uid']);
        //客户货代公司
    	$this->view->userforwarder = array();
    	if($this->view->orderarr['ufid']){
    		$this->view->userforwarder = $this->_inqsoService->getUserForwarder($this->view->orderarr['ufid']);
    	}
    }
    /**
     * 修改合同
     */
    public function modifycontractAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$this->_salesproductModel = new Icwebadmin_Model_DbTable_SalesProduct();
    		foreach($formData['sales_product_id'] as $k=>$id){
    			$update_d = array('lead_time'=>$formData['lead_time'][$k],
    					    'remark'=>$formData['remark'][$k]);
    			$this->_salesproductModel->update($update_d, "id='$id'");
    		}
    		$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$formData['salesnumber'],'temp4'=>'修改合同成功'.$formData['type']));
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改合同成功'));
    		exit;
    	}
    	$salesnumber = $this->_getParam('salesnumber');
    	//合同类型
    	$this->view->type    = $this->_getParam('type');
    	//订单详细
    	$this->view->orderarr = $this->_inqsoService->getSoInfo($salesnumber);
    	//产品详细
    	$this->view->pordarr = $this->_inqsoService->getSoPart($salesnumber);echo $salesnumber;
    	
    }
    /**
     * 添加发票
     */
    public function addinvoiceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->soinfo = $this->_inqsoService->getSoInfo($this->_getParam('salesnumber'));
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$invoiceid   = $formData['invoiceid'];
    		$type        = $formData['type'];
    		$taitouname  = $formData['taitouname'];
    		$contype     = $formData['contype'];
    		$dwname      = $formData['dwname'];
    		$identifier  = $formData['identifier'];
    		$regaddress  = $formData['regaddress'];
    		$regphone    = $formData['regphone'];
    		$bank        = $formData['bank'];
    		$account     = $formData['account'];
    		$error = 0;$message='';$data = array();
    		if($type == 1){
    			if(empty($taitouname) || empty($contype)){
    				$message ='请填写完整的发票信息。';
    				$error++;
    			}else{
    				$data = array('uid'=>$this->view->soinfo['uid'],
    						'type'=>$type,
    						'name'=>$taitouname,
    						'contype'=>$contype,
    						'created'=>time(),
    						'modified'=>time());
    			}
    		}elseif($type==2){
    			$annexur_part = COM_ANNEX.$this->view->soinfo['uid'].'/';
    			$annexurl = $annexur_part.$this->view->soinfo['annex1'];
    			$annexurl2= $annexur_part.$this->view->soinfo['annex2'];
    			if(empty($dwname) || empty($identifier)|| empty($regaddress)|| empty($regphone)|| empty($bank)|| empty($account)){
    				$message ='请填写完整的发票信息。';
    				$error++;
    			}elseif(!file_exists($annexurl) || !$this->view->soinfo['annex1']){
    				$message ='请上传营业执照。';
    				$error++;
    			}elseif(!file_exists($annexurl2) || !$this->view->soinfo['annex2']){
    				$message ='请上传税务登记证。';
    				$error++;
    			}else{
    				$data = array('uid'=>$this->view->soinfo['uid'],
    						'type'=>$type,
    						'name'=>$dwname,
    						'identifier'=>$identifier,
    						'regaddress'=>$regaddress,
    						'regphone'=>$regphone,
    						'bank'=>$bank,
    						'account'=>$account,
    						'created'=>time(),
    						'modified'=>time());
    			}
    		}else{
    			$message ='发票信息错误。';
    			$error++;
    		}
    		if($error){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$this->view->soinfo['salesnumber'],'temp4'=>'添加订单发票失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$invoiceModer = new Icwebadmin_Model_DbTable_Invoice();
    			$invoice_id = $invoiceModer->addData($data);
    			if($invoice_id){
    				$soModer = new Icwebadmin_Model_DbTable_InqSalesOrder();
    				$soModer->update(array('invoiceid'=>$invoice_id,'invoiceaddress'=>$this->view->soinfo['addressid']), "salesnumber='".$this->view->soinfo['salesnumber']."'");
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$this->view->soinfo['salesnumber'],'temp4'=>'添加订单发票成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加订单发票成功'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$this->view->soinfo['salesnumber'],'temp4'=>'添加订单发票失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'添加订单发票失败'));
    				exit;
    			}
    		}
    	}
    }
    //修改运费
    public function modifyshippingAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber = $this->fun->decryptVerification($this->_getParam('key'));
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$freight     = $formData['freight'];
    		$directions  = $formData['directions'];
    			
    		$orderarr = $this->_inqsoService->getSoInfo($salesnumber);
    		if($orderarr){
    			$soModel       = new Icwebadmin_Model_DbTable_InqSalesOrder();
    			if($orderarr['down_payment'] == $orderarr['total'])
    			$down_payment = $orderarr['down_payment']-$orderarr['freight']+$freight;
    			else $down_payment = $orderarr['down_payment'];
    			$newtotal      = $orderarr['total']-$orderarr['freight']+$freight;
    			$soModel->update(array('freight'=>$freight,
    					'down_payment'=>$down_payment,
    					'total'=>$newtotal),
    					"salesnumber='{$salesnumber}'");
    					//日志
    			$old = $orderarr['freight'].'->'.$freight.';'.$orderarr['down_payment'].'->'.$down_payment.';'.$orderarr['total'].'->'.$newtotal;
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'修改订单运费成功:'.$old,'description'=>$directions));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改订单运费成功'));
    			exit;
    		}else{
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'修改运费失败','description'=>'订单信息为空'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'修改运费失败，系统错误'));
    			exit;
    		}
    	}
    }
    public function changeliuchengAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber  = $formData['salesnumber'];
    		$type = $formData['type'];
    		if(!empty($salesnumber)){
    			$inqsoModel = new Icwebadmin_Model_DbTable_InqSalesOrder;
    			$inqsoModel->update(array("sqs_code"=>$type), "salesnumber='$salesnumber'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"更新成功"));
    			exit;
    		}else {
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单号不能为空'));
    			exit;
    		}
    	}
    }
}