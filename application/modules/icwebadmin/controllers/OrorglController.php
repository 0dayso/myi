<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/fpdf/pdfclass.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_OrorglController extends Zend_Controller_Action
{
	private $_soService;
	private $_emailService;
	private $_adminlogService;
	private $_fun;
	private $_prodService;
    public function init(){
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
    	//var_dump($controllerArray); die();
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
    	
    	$this->view->deliveryurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delivery";
    	$this->view->release = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/release";
    	$this->view->viewsourl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/viewso";
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
    	
    	$this->_soService = new Icwebadmin_Service_OrderService();
    	$this->_emailService = new Default_Service_EmailtypeService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->_fun = $this->view->fun = new MyFun();
    	
    	$this->config = Zend_Registry::get('config');
    	$this->view->reviewer = $this->config->order->reviewer;
    	$this->view->tel = $this->config->order->tel;
    	$this->view->email = $this->config->order->email;
    	$this->_prodService= new Icwebadmin_Service_ProductService();
    }
    public function indexAction(){
    	$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    	$spModel = new Icwebadmin_Model_DbTable_SalesProduct();
    	$sqlarr = array();
    	$typestr = '';
    	$selectstr = '';
    	$orderbystr = '';
    	//负责销售
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	if($staffinfo['level_id']=='XS'){
    		$sellsql = '';
    		//可以兼顾其他销售
    		if($staffinfo['control_staff']){
    			$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    			$control_staff_str = implode("','",$control_staff_arr);
    			$sellsql = "up.staffid IN ('".$control_staff_str."')";
    		}else{
    			$sellsql = "up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    		}
    		//发放优惠券的订单
    		$couponService = new Icwebadmin_Service_CouponService();
    		$coupsalenum = $couponService->getCouponSalesnumber($_SESSION['staff_sess']['staff_id']);
    		if(!empty($coupsalenum)){
    			$cstr = '(';
    			foreach($coupsalenum as $k=>$v){
    				if($k==0) $cstr .="'".$v['salesnumber']."'";
    				else $cstr .=",'".$v['salesnumber']."'";
    			}
    			$cstr .= ')';
    			$sellsql .= " OR so.salesnumber IN ".$cstr;
    		}
    		$selectstr .=" AND (".$sellsql.")";
    	}else{
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
    	//支付类型
    	$this->view->paytype = $paytype = $_GET['paytype'];
    	if(in_array($paytype,array('transfer','online','coupon'))){
    		$selectstr .=" AND so.paytype='{$paytype}' ";
    	}
    	//货物类型
    	$this->view->shipments = $shipments = $_GET['shipments'];
    	if(in_array($shipments,array('spot','order'))){
    		$selectstr .=" AND so.shipments='{$shipments}' ";
    	}
    	//交货地
    	$this->view->delivery = $delivery = $_GET['delivery'];
    	if(in_array($delivery,array('SZ','HK'))){
    		$selectstr .=" AND so.delivery_place='{$delivery}' ";
    	}
    	//选择不同的类型  (待释放，待付款，待发货)
    	$typeArr =array('rel','not','wpay','ver','ema','rec','shou','eva','can','back','select');
    	$typetmp = $_GET['type']==''?'rel':$_GET['type'];
    	$relsql  = " AND so.status IN ('101','201') AND so.back_status='101'".$selectstr;
    	$versql  = " AND so.status='101' AND paytype='transfer' AND receipt!='' AND so.back_status='201'".$selectstr;
    	$notsql  = " AND so.back_status='102'".$selectstr;
    	$wpaysql = " AND so.status='101' AND so.back_status='201'".$selectstr;
    	$emasql  = " AND so.status='201' AND so.back_status='201'".$selectstr;
    	$recsql  = " AND so.status='201' AND so.back_status='202'".$selectstr;
    	$shousql = " AND so.status='202' AND so.back_status='202'".$selectstr;
    	$evasql  = " AND so.status='301' AND so.back_status='202'".$selectstr;
    	$cansql  = " AND so.status='401'".$selectstr;
    	$backsql = " AND so.status IN ('501','502')".$selectstr;
    	
    	//搜索
    	if($_GET['salesnumber']) {
    		$salesnumber = $_GET['salesnumber'];
    		if($salesnumber){
    		  $sqrstr="AND so.salesnumber LIKE '%".$salesnumber. "%'".$selectstr;
    		  //搜索订单数
    		  $this->view->selnum = $total =  $this->_soService->getRowNum($sqrstr);
    		  $this->view->salesnumber = $salesnumber;
    		}else {
    			$this->_redirect ( $this->indexurl );
    		}
    	}	
    	//待释放订单数
    	$this->view->relnum = $total =  $this->_soService->getRowNum($relsql);
    	
    	//待审核回执单
    	$this->view->vernum = $total = $this->_soService->getRowNum($versql);
    	//审核不通过
    	$this->view->notnum = $total = $this->_soService->getRowNum($notsql);
    	//全部订单数
    	$this->view->allnum = $total = $this->_soService->getRowNum($selectstr);
		//待付款订单数
    	$this->view->wpaynum = $total = $this->_soService->getRowNum($wpaysql);
    	//待确认可发货
    	$this->view->emanum = $total = $this->_soService->getRowNum($emasql);
    	//待确认发货
    	$this->view->recnum = $total = $this->_soService->getRowNum($recsql);
    	//待确认收货
    	$this->view->shounum = $total = $this->_soService->getRowNum($shousql);
    	//待评价
    	$this->view->evanum = $total = $this->_soService->getRowNum($evasql);
    	//已取消
    	$this->view->cannum = $total =  $this->_soService->getRowNum($cansql);
    	//退款退货
    	$this->view->backnum = $total = $this->_soService->getRowNum($backsql);
    	
    	
    		$this->view->type =$typetmp;
    		if($typetmp == 'rel') {
    			$typestr = $relsql;
    			$total = $this->view->relnum;
    		}elseif($typetmp == 'ver') {
    			$typestr = $versql;
    			$total = $this->view->vernum;
    		}elseif($typetmp == 'not') {
    			$typestr = $notsql;
    			$total = $this->view->notnum;
    		}elseif($typetmp == 'wpay') {
    			$typestr = $wpaysql;
    			$total = $this->view->wpaynum;
    		}elseif($typetmp == 'ema') {
    			$typestr = $emasql;
    			$total = $this->view->emanum;
    		}
    		elseif($typetmp == 'rec') {
    			$typestr = $recsql;
    			$total = $this->view->recnum;
    		}
    		elseif($typetmp == 'shou') {
    			$typestr = $shousql;
    			$total = $this->view->shounum;
    		}
    		elseif($typetmp == 'eva') {
    			$typestr = $evasql;
    			$total = $this->view->evanum;
    		}
    		elseif($typetmp == 'can') {
    			$typestr = $cansql;
    			$total = $this->view->cannum;
    		}elseif($typetmp == 'back') {
    			$typestr = $backsql;
    			$total = $this->view->backnum;
    		}else {
    		    $total = $this->view->allnum;
    		    $typestr .=' AND so.back_status!=100'.$selectstr;
    	    }
        if($_GET['salesnumber']){
    		$typestr = $sqrstr;
    		$total = $this->view->selnum;
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);

    	$soArr = $this->_soService->getAllSo($offset,$perpage,$typestr,$orderbystr);
    	 
    	$this->view->salesorder = $sotmp = array();
    	$numAll = $num1 = $num2 = $num3 = $num4 = 0;
    	$numAll = count($soArr);
    	$prodService = new Icwebadmin_Service_ProductService();
    	for($i=0;$i<$numAll;$i++){
    	$data = $soArr[$i];
    	$product = $ptmp = array();
    		$product = $spModel->getAllByWhere("salesnumber='".$data['salesnumber']."'");
    		for($j=0;$j<count($product);$j++){
    			$data2=$product[$j];
    			$partinfo = $prodService->getProductByID($data2['prod_id']);
    			//pcn，pdn
    			$partinfo['pdnpcn'] = $this->_prodService->checkpdnpcn($data2['prod_id']);
    			$ptmp[] = array_merge($partinfo,$data2);
    		}
    		$data['prodarr']=$ptmp;
    	    $sotmp[] = $data;
    	}
        $this->view->salesorder = $sotmp;
        //通用器件
        $eventservice = new Icwebadmin_Service_EventService();
        $this->view->tongyong = $eventservice->getTongYong();
    }
    /*
     * 查看订单
     */
    public function viewsoAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    }
    /*
     * 确认发货
     */
    public function deliveryAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->sonum = $this->filter->pregHtmlSql($_GET['sonum']);
    	$this->view->sonid = $_GET['sonid'];
    	//快递公司
    	$couModel = new Icwebadmin_Model_DbTable_Courier();
    	$this->view->courier = $couModel->getAllByWhere("id!=''");

    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$soid        = $this->filter->pregHtmlSql($formData['soid']);
    		$courier     = $this->filter->pregHtmlSql($formData['courier']);
    		$cou_number  = $this->filter->pregHtmlSql($formData['cou_number']);
    		$error = 0;$message='';
    		if(empty($soid)){
    			$message .='订单id为空。<br/>';
    			$error++;
    		}
    		if(empty($courier)){
    			$message .='快递公司为空。<br/>';
    			$error++;
    		}
    		if(empty($cou_number)){
    			$message .='快递号为空。<br/>';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$couhModel = new Icwebadmin_Model_DbTable_CourierHistory();
    			$newid = $couhModel->add(array('cou_id'=>$courier,
    					'cou_number'=>$cou_number,
    					'so_id'=>$soid,
    					'created'=>time(),
    					'created_by'=>$_SESSION['staff_sess']['staff_id']));
    			if($newid)
    			{
    				$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    				$soModel->update(array('courierid'=>$newid,'status'=>202,'modified'=>time()), "id='{$soid}'");
    			    echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'确认发货成功。'));
    			    exit;
    			}else {
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'插入快递信息失败。'));
    				exit;
    			}
    		}
    	}
    }
    /*
     * 审核订单
     */
    public function releaseAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$status        = $formData['status'];
    		$notpass        = $formData['notpass'];
    		if($status=='201') $back_status = 201;
    		elseif($status=='102'){
    			$back_status = 102;
    			//审核不通过，恢复产品数量
    			$this->dfproductService = new Default_Service_ProductService();
    			$this->dfproductService->reinstate($salesnumber);
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'参数错误。'));
    			exit;
    		}
    		$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    		$soModel->update(array('back_status'=>$back_status,'admin_notes'=>$notpass,'modified'=>time()), "salesnumber='{$salesnumber}'");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp3'=>$back_status,'temp4'=>'审核订单成功','description'=>$notpass));
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'审核订单成功'));
    		exit;
    	}
    	$this->view->salesnumber  = $_GET['sonum'];
    	$this->view->sonid  = $_GET['sonid'];
    	$this->view->soinfo = $this->_soService->getSoInfo($this->view->salesnumber);
    	
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
    		
    		$re = $this->_soService->updateByNum(array('status'=>$status,'admin_notes'=>$admin_notes,'modified'=>time()), $salesnumber);
    		if($re){
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'操作失败。'));
    			exit;
    		}
    		
    	}
    }
    /*
     * 查看快递信息
    */
    public function courierAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber = $salesnumber = $_GET['sonum'];
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
    		$sonid       = $formData['sonid'];
    		$salesnumber = $formData['salesnumber'];
    		if(!empty($cou_number)){
    			$chModel = new Default_Model_DbTable_CourierHistory();
    			$rearray = $chModel->getRowByWhere("cou_number='{$cou_number}' AND salesnumber='{$salesnumber}'");
    			//$soModel   = new Default_Model_DbTable_SalesOrder();
    			//$re = $soModel->getRowByWhere("salesnumber='".$salesnumber."'");
    			//if(empty($re)) $courier='此物流信息不存在。';
    			//else{
    			   $courier = $rearray['track'];
    			//}
    			echo Zend_Json_Encoder::encode(array("code"=>0, "courier"=>$courier));
    			exit;
    		}
    	}
    }
    /*
     * 确认银行汇款已收款
    */
    public function receivablesAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$soid        = (int)($formData['id']);
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    		$soModel->update(array('status'=>201,'pay_time'=>time(),'modified'=>time()), "id='{$soid} AND status=101'");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp3'=>201,'temp4'=>'确认收款成功'));
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'确认成功。订单号：'.$salesnumber));
    		exit;
    	}
    }
    
    /*
     * ajax获取订单号
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$soArr = $this->_soService->ajaxtag($_GET['q']);
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
		if( $this->_getParam('key') != md5(session_id())) $this->_redirect('/icwebadmin/OrOrgl');
		//订单详情
		$salesnumber = $this->_getParam('salesnumber');
		
		$orderarr = $this->_soService->getSoInfo($salesnumber);
		
		//产品详细
		$orderarr['pordarr'] = $this->_soService->getSoPart($salesnumber);
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($orderarr['uid']);
		if(empty($userinfo)) {echo '用户为空';exit;};
		//国内合同
		$pdf = new PDF ();
		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		$definqorderService = new Default_Service_InqOrderService();
		if($orderarr['delivery_place'] == 'SZ') $definqorderService->szContract($pdf,$orderarr,$userinfo,$currencyArr,$unit);
		elseif($orderarr['delivery_place'] == 'HK') $definqorderService->hkContract($pdf,$orderarr,$userinfo,$currencyArr,$unit);
	}
	/**
	 * 后台查看生成数字合同
	 */
	public function digitalcontractAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if( $this->_getParam('key') != md5(session_id())) $this->_redirect('/icwebadmin/OrOrgl');
		//订单详情
		$salesnumber = $this->_getParam('salesnumber');
		
		$orderarr = $this->_soService->getSoInfo($salesnumber);
		
		//产品详细
		$orderarr['pordarr'] = $this->_soService->getSoPart($salesnumber);
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($orderarr['uid']);
		$pdfpart = ORDER_ELECTRONIC.md5('order'.$orderarr['salesnumber']).'.pdf';
		if(file_exists($pdfpart) && $this->_getParam('type')!='up'){
			$this->_redirect($pdfpart);
		}else{
			$definqorderService = new Default_Service_InqOrderService();
			if($orderarr['delivery_place'] == 'SZ') $return=$definqorderService->szDigitalContract($orderarr,$userinfo);
			elseif($orderarr['delivery_place'] == 'HK') $return=$definqorderService->hkDigitalContract($orderarr,$userinfo);
			if($return) $this->_redirect($pdfpart);
		}
	}
	/*
	 * 释放订单时发邮件
	 */
	public function mailsendAction(){
	$this->_helper->layout->disableLayout();
	if($this->getRequest()->isPost()){
		$this->_helper->viewRenderer->setNoRender();
		$salesnumber= $this->filter->pregHtmlSql($_POST['salesnumber']);
		$mailtype  = $_POST['mailtype'];
		//订单详细
		$this->orderarr = $this->_soService->getSoInfo($salesnumber);
		if(empty($this->orderarr)) {
			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'此订单已经释放'.$salesnumber));
			exit;
		}
		//增值税发票
		$filearray = array('0'=>'','1'=>'','2'=>'','3'=>'');
		if($this->orderarr['itype']==2){
			//附件
			$annexur_part = COM_ANNEX.$this->orderarr['uid'].'/';
			$annexurl = $annexur_part.$this->orderarr['annex1'];
			$annexurl2= $annexur_part.$this->orderarr['annex2'];
			if(file_exists($annexurl) && $this->orderarr['annex1']){
				$filearray[0] = $annexurl;
			}else {
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少营业执照'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少营业执照'.$salesnumber));
				exit;
			}
			if(file_exists($annexurl2) && $this->orderarr['annex2']){
				$filearray[1] = $annexurl2;
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少税务登记证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少税务登记证'.$salesnumber));
				exit;
			}
		}
		//转账凭证
		if($this->orderarr['paytype']=='transfer' && $this->orderarr['total']>0){
			if(!empty($this->orderarr['receipt']) && file_exists(UP_RECEIPT.$this->orderarr['receipt'])){
				$filearray[2] = UP_RECEIPT.$this->orderarr['receipt'];
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少转账凭证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少转账凭证'.$salesnumber));
				exit;
			}
		}
		//产品详细
		$this->pordarr = $this->_soService->getSoPart($salesnumber);
		$dforderService = new Default_Service_OrderService();
		
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($this->orderarr['uid']);
		$pdfpart = ORDER_ELECTRONIC.md5('order'.$salesnumber).'.pdf';
		if(file_exists($pdfpart)){
			$filearray[4] = $pdfpart;
		}else{
			$this->orderarr['pordarr'] = $this->pordarr;
			$definqorderService = new Default_Service_InqOrderService();
			if($this->orderarr['delivery_place'] == 'SZ') $return=$definqorderService->szDigitalContract($this->orderarr,$userinfo);
			elseif($this->orderarr['delivery_place'] == 'HK') $return=$definqorderService->hkDigitalContract($this->orderarr,$userinfo);
			if($return) $filearray[4] = $pdfpart;
		}
		
		//负责销售情况
		$this->_staffService=new Icwebadmin_Service_StaffService();
		$sellinfo = $this->_staffService->sellbyuid($this->orderarr['uid']);
		
		$fromname = '盛芯电子';
		$prompt_invoice = '';
		$imp_item = 1;
		//如果需要开发票的 或者 银行转账的
		if($this->orderarr['invoiceid']>0){
			$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_invoice',$this->orderarr['uid']);
		}else{
			if($this->orderarr['delivery_place']=='SZ' && $this->orderarr['total']>0){
			    $prompt_invoice = '<div style="padding:3px 0;font-size:14px; font-weight:bold; color:#ff6600;font-family:\'微软雅黑\';">重要提示：'.$imp_item.'、此订单客户没有选择开发票，请与负责销售确认。</div>';
			    $imp_item++;
			}
			$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_noinvoice',$this->orderarr['uid']);
		}
		//样片邮件
		if($mailtype==2){
			$title = '#内部订单# - 订单号:'.$salesnumber.'，请处理';
			$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的Jill Cen,</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">盛芯电子内部订单，请使用
                                                <b style="color:#fd2323; font-size:15px;"> SQS Customer Code </b>处理。
       											</div>'.$prompt_invoice.'
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
               </table>';
		}else{//正常订单
			$title    = '#在线订单# - 订单号:'.$salesnumber.'，请处理';
         	$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的Jill Cen,</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">盛芯电子在线订单，请使用
                                                <b style="color:#fd2323; font-size:15px;"> SQS Customer Code </b>处理。
       											</div>'.$prompt_invoice.'
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
               </table>';
		}	
	    $mess = $dforderService->getOrderTable($this->orderarr,$this->pordarr,$hi_mess,$sellinfo);
  		
	    //恢复bpp库存
	    $this->_prodService = new Default_Service_ProductService();
	    $this->_prodService->reinstateBpp($salesnumber);
	    
        //日志
        $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'释放订单成功'));
        
        $emailto = $emailcc = $emailbcc = array();
        if(!empty($emailarr['to'])){
        	$emailto = $emailarr['to'];
        }
        if(!empty($emailarr['cc'])){
        	$emailcc = $emailarr['cc'];
        }
        if(!empty($emailarr['bcc'])){
        	$emailbcc = $emailarr['bcc'];
        }
        //中电品牌抄送给研发
        $staffservice = new Icwebadmin_Service_StaffService();
        $emailcc = $staffservice->ccToYafa($this->pordarr,$emailcc);
        $myfun =  new MyFun();
		$emailreturn = $myfun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,$filearray,$sellinfo,0);
		//邮件日志
		if($emailreturn){
			//更新状态
			$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
			$soModel->update(array('back_status'=>202,'modified'=>time()), "salesnumber='{$salesnumber}' AND status=201");
			
			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件成功'));
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'释放订单成功。订单号：'.$salesnumber));
		}else{
			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件失败'));
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'释放订单失败。订单号：'.$salesnumber));
		}
		exit;
	}else{
		$this->view->id = $_GET['id'];
		$this->view->salesnumber = $_GET['salesnumber'];
		$this->view->soinfo = $this->_soService->getSoInfo($this->view->salesnumber);
	}
	}
	
	/**
	 * nxp抢购订单释放邮件
	 */
	public function mailsendnxpAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$salesnumber= $this->filter->pregHtmlSql($_POST['salesnumber']);
		//订单详细
		$this->orderarr = $this->_soService->getSoInfo($salesnumber);
		if(empty($this->orderarr)) {
			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'此订单已经释放'.$salesnumber));
			exit;
		}
		//产品详细
		$this->pordarr = $this->_soService->getSoPart($salesnumber);
		$dforderService = new Default_Service_OrderService();
		
		//用户资料
		$userService = new Icwebadmin_Service_UserService();
		$userinfo = $userService->getUserProfile($this->orderarr['uid']);
		//转账凭证
		if($this->orderarr['paytype']=='transfer' && $this->orderarr['total']>0){
			if(!empty($this->orderarr['receipt']) && file_exists(UP_RECEIPT.$this->orderarr['receipt'])){
				$filearray[2] = UP_RECEIPT.$this->orderarr['receipt'];
			}else{
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'释放订单失败',' 	description'=>'缺少转账凭证'));
				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单释放失败，缺少转账凭证'.$salesnumber));
				exit;
			}
		}
		//订单pdf
		$pdfpart = ORDER_ELECTRONIC.md5('order'.$salesnumber).'.pdf';
		if(file_exists($pdfpart)){
			$filearray[4] = $pdfpart;
		}else{
			$this->orderarr['pordarr'] = $this->pordarr;
			$definqorderService = new Default_Service_InqOrderService();
			if($this->orderarr['delivery_place'] == 'SZ') $return=$definqorderService->szDigitalContract($this->orderarr,$userinfo);
			elseif($this->orderarr['delivery_place'] == 'HK') $return=$definqorderService->hkDigitalContract($this->orderarr,$userinfo);
			if($return) $filearray[4] = $pdfpart;
		}
		//负责销售情况
		$this->_staffService=new Icwebadmin_Service_StaffService();
		$sellinfo = $this->_staffService->sellbyuid($this->orderarr['uid']);
		
		$fromname = '盛芯电子';
		$prompt_invoice = '';
		$imp_item = 1;

		
		$title    = '#NXP通用料5折秒杀订单# - 订单号:'.$salesnumber.'，请处理';
		$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的Shirley Yang,</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">NXP通用料5折秒杀订单，请处理。
       											</div>'.$prompt_invoice.'
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
               </table>';
		
		$mess = $dforderService->getOrderTable($this->orderarr,$this->pordarr,$hi_mess,$sellinfo);
		
		//恢复bpp库存
		$this->_prodService = new Default_Service_ProductService();
		$this->_prodService->reinstateBpp($salesnumber);
		 
		//日志
		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'释放订单成功'));
		
		$emailarr = $this->_emailService->getEmailAddress('nxp_order_release',$this->orderarr['uid']);
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = $emailarr['to'];
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		$myfun =  new MyFun();
		$emailreturn = $myfun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,$filearray,$sellinfo,0);
			
		//邮件日志
		if($emailreturn){
			//更新状态
			$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
			$soModel->update(array('back_status'=>202,'modified'=>time()), "salesnumber='{$salesnumber}' AND status=201");
				
			$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件成功'));
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'释放订单成功。订单号：'.$salesnumber));
		}else{
			$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送释放订单通知邮件失败'));
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'释放订单失败。订单号：'.$salesnumber));
		}
		exit;
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
		$this->view->invoice = $this->_soService->getInvoice($invoiceid);
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
			$salesorderModel   = new Icwebadmin_Model_DbTable_SalesOrder();
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
	 * 反馈交期
	 */
	public function deliverytimeAction(){
		if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
		{
			echo "权限不够。";
			exit;
		}
		$this->_helper->layout->disableLayout();
		$this->view->salesnumber  = $_GET['salesnumber'];
		$this->view->deliverytime_old  = $_GET['deliverytime_old']?date("Y-m-d",$_GET['deliverytime_old']):'';
		if($this->getRequest()->isPost()){
			$this->_helper->viewRenderer->setNoRender();
			$formData      = $this->getRequest()->getPost();
			$salesnumber   = $formData['salesnumber'];
			$delivery_time = $formData['delivery_time'];
			if(empty($salesnumber) || empty($delivery_time)){
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'交期反馈失败','description'=>'参数错误'));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'参数错误'));
				exit;
			}else{
				$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
				$soModel->update(array('delivery_time'=>strtotime($delivery_time),'modified'=>time()), "salesnumber='{$salesnumber}'");

				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'交期反馈成功','description'=>strtotime($delivery_time)));
				 
				//异步请求开始
				$this->_fun->asynchronousStarts();

				$orderarr = $this->_soService->getSoInfo($salesnumber);
				//产品详细
				$pordarr = $this->_soService->getSoPart($salesnumber);
				
				$emailreturn = $this->_soService->leadtimeEmail($orderarr,$pordarr);
				
				//邮件日志
				if($emailreturn){
					$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumber,'temp4'=>'发送交期反馈邮件成功'));
				}else{
					$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'发送交期反馈邮件失败'));
				}
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单交期反馈并发邮件成功'));
				//异步请求结束
				$this->_fun->asynchronousEnd();
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
		
		$salesorderModel   = new Icwebadmin_Model_DbTable_SalesOrder();
		
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
			$salesorderModel   = new Icwebadmin_Model_DbTable_SalesOrder();
			$orderService = new Default_Service_OrderService();
			$orderarr = $orderService->geSoinfo($salesnumber,1);
			if($orderarr['status']==101){
				$updata = array('receipt'=>$receipt,'pay_time'=>time(),'status'=>201);
			}else{
				$updata = array('receipt'=>$receipt,'pay_time'=>time());
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
		$this->view->orderarr = $this->_soService->getSoInfo($salesnumber);
		//优化券
		$couponService = new Icwebadmin_Service_CouponService();
		$this->view->coupon_code = $couponService->getOrderCoupon($this->view->orderarr['coupon_code']);
		
		//产品详细
		$pordarr = $this->_soService->getSoPart($salesnumber);
		foreach($pordarr as $k=>$por){
			$pordarr[$k]['pdnpcn'] = $this->_prodService->checkpdnpcn($por['prod_id']);
		}
		$this->view->pordarr = $pordarr;
		
		$this->view->salesnumber = $salesnumber;
		//所有者
		$this->view->owner = $this->_soService->getOwnerByUid($this->view->orderarr['uid']);
	}
	/**
	 * 添加发票
	 */
	public function addinvoiceAction(){
		$this->_helper->layout->disableLayout();
		$this->view->soinfo = $this->_soService->getSoInfo($this->_getParam('salesnumber'));
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
					$soModer = new Icwebadmin_Model_DbTable_SalesOrder();
					$soModer->update(array('invoiceid'=>$invoice_id,'invoiceaddress'=>$this->view->soinfo['addressid']), "salesnumber='".$this->view->soinfo['salesnumber']."'");
					$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$this->view->soinfo['salesnumber'],'temp4'=>'添加订单发票成功','description'=>implode(',', $data)));
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
		$this->view->salesnumber = $this->_fun->decryptVerification($this->_getParam('key'));
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$salesnumber = $formData['salesnumber'];
			$freight     = $formData['freight'];
			$directions  = $formData['directions'];
			
			$orderarr = $this->_soService->getSoInfo($salesnumber);
			if($orderarr){
				$soModel       = new Icwebadmin_Model_DbTable_SalesOrder();
				$newtotal      = $orderarr['total']-$orderarr['freight']+$freight;
				$newtotal_back = $orderarr['total_back']-$orderarr['freight']+$freight;
				$soModel->update(array('freight'=>$freight,
						        'total'=>$newtotal,
						        'total_back'=>$newtotal_back), 
						        "salesnumber='{$salesnumber}'");
				//日志
				$old = $orderarr['freight'].'->'.$freight.';'.$orderarr['total'].'->'.$newtotal.';'.$orderarr['total_back'].'->'.$newtotal_back;
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
}