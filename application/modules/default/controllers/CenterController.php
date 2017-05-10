<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class CenterController extends Zend_Controller_Action
{
	private $_userService;
	private $_defaultlogService;
    public function init()
    {
        //登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	
    	//获取购物车寄存
    	$cartService = new Default_Service_CartService();
    	$cartService->getCartDeposit();
    	
    	$this->filter = new MyFilter();
    	$this->user   = new Default_Model_DbTable_User();
    	$this->userprofile = new Default_Model_DbTable_UserProfile();
    	
    	//菜单选择
    	$_SESSION['menu'] = 'center';
    	
    	$this->fun =$this->view->fun= new MyFun();
    	
    	//产品目录
    	$prodService = new Default_Service_ProductService();
    	$prodCategory = $prodService->getProdCategory();
    	$this->view->first = $prodCategory['first'];
    	$this->view->second = $prodCategory['second'];
    	$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		
		$this->_userService = new Default_Service_UserService();
		$this->_defaultlogService = new Default_Service_DefaultlogService();

		$this->commonconfig = Zend_Registry::get('commonconfig');
    }

    public function indexAction()
    {       
    	$_SESSION['leftmenu_select'] ='index';
    	//资料和客户经理
    	$this->view->usrinfo = $this->_userService->getCenterUserInfo();
    	//订单相关
    	$this->view->onlinesoinfo = $this->_userService->getOnlineSoInfo();
    	$this->view->inqsoinfo = $this->_userService->getInqSoInfo();
    	//询价相关
    	$this->view->inqinfo = $this->_userService->getInqInfo();
    	//热推产品
    	$this->view->hotpord = $this->_userService->getHotPord();
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    }
    /**
     * 在线订单
     */
    public function orderAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] = 'order';
    	$soModel = new Default_Model_DbTable_SalesOrder();
    	$spModel = new Default_Model_DbTable_SalesProduct();
    	$soService = new Default_Service_OrderService();
    	$inqsoService = new Default_Service_InqOrderService();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$uploadtext = $_FILES['fileToUpload'];
    		$filetype = @getimagesize($uploadtext['tmp_name']);
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$error=0;$message='';
    		if(!empty($uploadtext['tmp_name']))
    		{
    			if(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    		    {
    				$message ='附件格式不正确';
    				$error++;
    			}
    			if(($uploadtext["size"]/(1024*1024))>8) //大于8M
    			{
    				$message ='附件超过8M';
    				$error++;
    			}
    		}else{
    			$message ='附件不能为空';
    			$error++;
    		}
    		if($error){
    			$_SESSION['postsess']['code'] = 101;
    			$_SESSION['postsess']['message'] = $message;
    		}else{
    			//银行转账订单附件路径
    			$path = 'upload/default/order_annex/';
    			if(!is_dir($path)) //判断是否存在文件夹
    			{
    				mkdir($path,0777);
    			}
    			$time = time();
    			//附件命名
    			if(!empty($uploadtext['tmp_name'])){
    				$annex="{$salesnumber}.".$this->filter->extend($uploadtext['name']);
    				@move_uploaded_file($uploadtext["tmp_name"],$path.$annex);
    				@unlink($uploadtext);
    				if (file_exists($path.$annex))
    				{
    					$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    						
    					$re = $salesorderModel->update(array('receipt'=>$annex), "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    					$_SESSION['postsess']['code'] = 0;
    					$_SESSION['postsess']['message'] = '上传回执单成功';
    				}else {
    					$_SESSION['postsess']['code'] = 102;
    					$_SESSION['postsess']['message'] ='上传回执单失败';
    				}
    			}
    		}
    	}
    	$sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
    	//选择不同的类型
    	$typeArr =array('wpay','send','rec','eva','can','not');
    	$typetmp = '';
    	if(isset($_GET['type'])){
    		$typetmp = $_GET['type'];
    	}
    	$typestr ='';
    	$allsql = " AND available=1";
    	$wpaysql = " AND status='101' AND back_status!=102 AND available=1";
    	$sendsql = " AND status='201' AND back_status!=102 AND available=1";
    	$recsql  = " AND status='202' AND back_status!=102 AND available=1";
    	$evasql  = " AND status='301' AND back_status!=102 AND available=1";
    	$cansql  = " AND status='401' AND back_status!=102 AND available=1";
    	$notsql  = " AND back_status=102 AND available=1";
    	//全部订单数
    	$sqlstr = "SELECT count(id) as allnum FROM sales_order WHERE uid=:uidtmp {$allsql}";
    	$allnumarr = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->allnum = $allnumarr[0]['allnum'];
		//待付款订单数
    	$sqlstr = "SELECT count(id) as wpaynum FROM sales_order WHERE uid=:uidtmp {$wpaysql}";
    	$allwpay = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->wpaynum = $allwpay[0]['wpaynum'];
    	//待发货
    	$sqlstr = "SELECT count(id) as sendnum FROM sales_order WHERE uid=:uidtmp {$sendsql}";
    	$allsend = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->sendnum = $allsend[0]['sendnum'];
    	//待确认收货
    	$sqlstr = "SELECT count(id) as recnum FROM sales_order WHERE uid=:uidtmp {$recsql}";
    	$allrec = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->recnum = $allrec[0]['recnum'];
    	//待评价
    	$sqlstr = "SELECT count(id) as evanum FROM sales_order WHERE uid=:uidtmp {$evasql}";
    	$alleva = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->evanum = $alleva[0]['evanum'];
    	//已取消
    	$sqlstr = "SELECT count(id) as cannum FROM sales_order WHERE uid=:uidtmp {$cansql}";
    	$allcan = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->cannum = $allcan[0]['cannum'];
    	//审核不通过
    	$sqlstr = "SELECT count(id) as notnum FROM sales_order as so WHERE uid=:uidtmp {$notsql}";
    	$allnot = $soModel->getBySql($sqlstr,$sqlarr);
    	$this->view->notnum =  $allnot[0]['notnum'];
    if(in_array($typetmp,$typeArr))
    	{
    		$this->view->type =$typetmp;
    		if($typetmp == 'wpay') {
    			$typestr = $wpaysql;
    			$total = $this->view->wpaynum;
    		}
    		elseif($typetmp == 'send') {
    			$typestr = $sendsql;
    			$total = $this->view->sendnum;
    		}
    		elseif($typetmp == 'rec') {
    			$typestr = $recsql;
    			$total = $this->view->recnum;
    		}
    		elseif($typetmp == 'eva') {
    			$typestr = $evasql;
    			$total = $this->view->evanum;
    		}
    		elseif($typetmp == 'can') {
    			$typestr = $cansql;
    			$total = $this->view->cannum;
    		}elseif($typetmp == 'not') {
    			$typestr = $notsql;
    			$total = $this->view->notnum;
    		}
    	}else {
    		$this->view->type='';
    		$total = $this->view->allnum;
    		$typestr = $allsql;
    	}
    	//分页
    	$perpage= $this->commonconfig->page->orderlist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->orderlisttype);
    	$sqlstr = "SELECT *
    	           FROM sales_order WHERE uid=:uidtmp {$typestr} ORDER BY created DESC,status ASC LIMIT $offset,$perpage";
    	$this->view->salesorder = $soModel->getBySql($sqlstr,$sqlarr);
    	//在线订单总数
    	$this->view->onlineSoNum = $soService->onlineSoNum();
    	//询价订单总数
    	$this->view->inqSoNum = $inqsoService->inqSoNum();
    }
    /**
     * 询价订单
     */
    public function inqorderAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] = 'inqorder';
    	$inqsoService = new Default_Service_InqOrderService();
    	$soModel = new Default_Model_DbTable_InqSalesOrder();
    	$spModel = new Default_Model_DbTable_SalesProduct();
    	$soService = new Default_Service_OrderService();
    	$inqsoService = new Default_Service_InqOrderService();
    	
    	$sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
    	//选择不同的类型
    	$typeArr =array('wpay','proc','over','send','rec','eva','can','not');
        $typetmp = '';
    	if(isset($_GET['type'])){
    		$typetmp = $_GET['type'];
    	}
    	$typestr ='';
    	$allsql  = " AND available=1";
    	$wpaysql = " AND status='101' AND paytype!='cod' AND back_status!=102 AND available=1";
    	$procsql = " AND status='102' AND back_status!=102 AND available=1";
    	$oversql = " AND status='103' AND back_status!=102 AND available=1";
    	$sendsql = " AND status='201' AND back_status!=102 AND available=1";
    	$recsql  = " AND status='202' AND back_status!=102 AND available=1";
    	$evasql  = " AND status='301' AND back_status!=102 AND available=1";
    	$cansql  = " AND status='401' AND back_status!=102 AND available=1";
    	$notsql  = " AND back_status=102 AND available=1";
    	//全部订单数
    	$this->view->allnum  = $inqsoService->getRowNum($allsql);
    	//待付款订单数
    	$this->view->wpaynum = $inqsoService->getRowNum($wpaysql);
    	//处理中订单数
    	$this->view->procnum = $inqsoService->getRowNum($procsql);
    	//待支付剩余货款
    	$this->view->overnum = $inqsoService->getRowNum($oversql);
    	//待发货
    	$this->view->sendnum = $inqsoService->getRowNum($sendsql);
    	//待确认收货
    	$this->view->recnum = $inqsoService->getRowNum($recsql);
    	//待评价
    	$this->view->evanum = $inqsoService->getRowNum($evasql);
    	//已取消
    	$this->view->cannum = $inqsoService->getRowNum($cansql);
    	//审核不通过
    	$this->view->notnum =  $inqsoService->getRowNum($notsql);
    	
    	if(in_array($typetmp,$typeArr))
    	{
    		$this->view->type =$typetmp;
    		if($typetmp == 'wpay') {
    			$typestr = $wpaysql;
    			$total = $this->view->wpaynum;
    		}elseif($typetmp == 'proc') {
    			$typestr = $procsql;
    			$total = $this->view->procnum;
    		}elseif($typetmp == 'over') {
    			$typestr = $oversql;
    			$total = $this->view->overnum;
    		}
    		elseif($typetmp == 'send') {
    			$typestr = $sendsql;
    			$total = $this->view->sendnum;
    		}
    		elseif($typetmp == 'rec') {
    			$typestr = $recsql;
    			$total = $this->view->recnum;
    		}
    		elseif($typetmp == 'eva') {
    			$typestr = $evasql;
    			$total = $this->view->evanum;
    		}
    		elseif($typetmp == 'can') {
    			$typestr = $cansql;
    			$total = $this->view->cannum;
    		}elseif($typetmp == 'not') {
    			$typestr = $notsql;
    			$total = $this->view->notnum;
    		}
    	}else {
    		$this->view->type='';
    		$total   = $this->view->allnum;
    		$typestr = $allsql;
    	}
    	//分页
    	$perpage = $this->commonconfig->page->orderlist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->orderlisttype);
    	$soArr = $inqsoService->getAllSo($offset, $perpage, $typestr);
    	//在线订单总数
    	$this->view->onlineSoNum = $soService->onlineSoNum();
    	//询价订单总数
    	$this->view->inqSoNum = $inqsoService->inqSoNum();
    	
    	$prodService = new Icwebadmin_Service_ProductService();
    	$numAll = count($soArr);
    	for($i=0;$i<$numAll;$i++){
    		$data = $soArr[$i];
    		$product = $ptmp = array();
    		$product = $spModel->getAllByWhere("salesnumber='".$data['salesnumber']."'");
    		for($j=0;$j<count($product);$j++){
    			$data2=$product[$j];
    			$partinfo = $prodService->getProductByID($data2['prod_id']);
    			$ptmp[$data2['prod_id']]=$partinfo;
    		}
    		$data['prodarr']=$ptmp;
    		$sotmp[] = $data;
    	}
    	$this->view->salesorder = $sotmp;
    }
    /**
     * 我的询价
     */
    public function inquiryAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] = 'inquiry';
    	$inqService = new Default_Service_InquiryService();
    	//选择不同的类型
    	$this->view->type = $typetmp = '';
    	if(isset($_GET['type'])){
    	   $this->view->type = $typetmp = $_GET['type'];
    	}
    	$typestr    = " AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$allstr     = " AND iq.status!='0' AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$waitstr    = " AND iq.status ='1' AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$alreadystr = " AND iq.status IN ('2','5','6') AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$verifystr  = " AND iq.status ='3' AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$notpassstr = " AND iq.status ='4' AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	$orderstr   = " AND iq.status IN ('5','6') AND iq.re_inquiry=0 AND iq.back_inquiry=0";
    	//全部
    	$this->view->allnum  = $inqService->getNum($allstr);
    	//待报价
    	$this->view->waitnum  = $inqService->getNum($waitstr);
    	//已报价
    	$this->view->alreadynum  = $inqService->getNum($alreadystr);
    	//待审核
    	$this->view->verifynum  = $inqService->getNum($verifystr);
    	//审核不通过
    	$this->view->notpassnum  = $inqService->getNum($notpassstr);
    	//已经下单
    	$this->view->ordernum  = $inqService->getNum($orderstr);
    	
    	if($typetmp == 'wait') {
    		$total   = $this->view->waitnum;
    		$typestr = $waitstr;
    	}elseif($typetmp == 'already') {
    		$total   = $this->view->alreadynum;
    		$typestr = $alreadystr;
    	}elseif($typetmp == 'verify') {
    		$total   = $this->view->verifynum;
    		$typestr = $verifystr;
    	}elseif($typetmp == 'notpass') {
    		$total   = $this->view->notpassnum;
    		$typestr = $notpassstr;
    	}elseif($typetmp == 'order') {
    		$total   = $this->view->ordernum;
    		$typestr = $orderstr;
    	}else{
    		$total   = $this->view->allnum;
    		$typestr = $allstr;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->inquirylist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->inquirylisttype);

    	$this->view->inquiry = $inqService->getInquiry($offset,$perpage,$typestr);
    }
    /**
     * 我优惠券
    */
    public function couponAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='coupon';
    	$couponService = new Default_Service_CouponService();
    	//选择不同的类型
    	$typeArr =array('can','used','notdue','expired');
    	$typetmp = '';
    	if(isset($_GET['type'])) $typetmp = $_GET['type'];
    	if(!in_array($typetmp, $typeArr)){
    		$this->view->type = 'can';
    	}else{
    		$this->view->type = $typetmp;
    	}
    	$cansql     = " AND cp.status='200' AND cp.start_date<='".time()."' AND cp.end_date >='".time()."'";
    	$usedsql    = " AND cp.status='201'";
    	$notduesql  = " AND cp.status='200' AND cp.start_date >'".time()."'";
    	$expiredsql = " AND cp.status='200' AND cp.end_date <'".time()."'";

    	$this->view->cantotal     = $couponService->getNum($cansql);
    	$this->view->usedtotal    = $couponService->getNum($usedsql);
    	$this->view->notduetotal  = $couponService->getNum($notduesql);
    	$this->view->expiredtotal = $couponService->getNum($expiredsql);
    	$sql = '';
    	if($this->view->type == 'can') {
    		$total = $this->view->cantotal;
    		$sql   = $cansql;
    	}elseif($this->view->type == 'used') {
    		$total = $this->view->usedtotal;
    		$sql   = $usedsql;
    	}elseif($this->view->type == 'notdue') {
    		$total = $this->view->notduetotal;
    		$sql   = $notduesql;
    	}elseif($this->view->type == 'expired') {
    		$total = $this->view->expiredtotal;
    		$sql   = $expiredsql;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->invoicelist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->invoicelisttype);
    	$this->view->couponall = $couponService->getRecord($offset,$perpage,$sql);
    }
    /**
     * Bom采购
     */
    public function bomAction()
    {	
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='bom';
    	$bomService = new Default_Service_BomService();
    	//选择不同的类型
    	$this->view->type = $typetmp = '';
    	if(isset($_GET['type'])){
    		$this->view->type = $typetmp = $_GET['type'];
    	}
    	$typestr    = "";
    	$allstr     = " AND bo.status!='0'";
    	$waitstr    = " AND bo.status ='1'";
    	$alreadystr = " AND bo.status ='2'";
    	
    	//全部
    	$this->view->allnum  = $bomService->getNum($allstr);
    	//待报价
    	$this->view->waitnum  = $bomService->getNum($waitstr);
    	//已报价
    	$this->view->alreadynum  = $bomService->getNum($alreadystr);
    	 
    	if($typetmp == 'wait') {
    		$total   = $this->view->waitnum;
    		$typestr = $waitstr;
    	}elseif($typetmp == 'already') {
    		$total   = $this->view->alreadynum;
    		$typestr = $alreadystr;
    	}else{
    		$total   = $this->view->allnum;
    		$typestr = $allstr;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->inquirylist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->inquirylisttype);
    	
    	$this->view->bom = $bomService->getBom($offset,$perpage,$typestr);
    	
    }
    /**
     * 交货期变更
     */
    public function deliveryAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='delivery';
    	//变更窗口期 单位为天
    	$windowtime = time()+WINDOW_DAY*86400;
    	$inqsoService = new Default_Service_InqOrderService();
    	$soModel = new Default_Model_DbTable_InqSalesOrder();
    	$spModel = new Default_Model_DbTable_SalesProduct();
    	$soService = new Default_Service_OrderService();
    	$inqsoService = new Default_Service_InqOrderService();
    	 
    	$sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
    	//选择不同的类型
    	$typeArr =array('wpay','nopass','pass');
    	$typetmp = '';
    	if(isset($_GET['type'])){
    		$typetmp = $_GET['type'];
    	}
    	$typestr ='';
    	$windowtimesql = " AND delivery_time >= {$windowtime}";
    	$allsql  = " AND status IN ('103','201') AND delivery_status=0 AND back_status!=102 {$windowtimesql}";
    	$wpaysql = " AND status IN ('103','201') AND delivery_status=101 AND back_status!=102";
    	$passsql = " AND status IN ('103','201') AND delivery_status=201 AND back_status!=102";
    	$nopasssql = " AND status IN ('103','201') AND delivery_status=301 AND back_status!=102";

    	//全部订单数
    	$this->view->allnum  = $inqsoService->getRowNum($allsql);
    	//待付款订单数
    	$this->view->wpaynum = $inqsoService->getRowNum($wpaysql);
    	//审核通过
    	$this->view->passnum = $inqsoService->getRowNum($passsql);
    	//审核不通过
    	$this->view->nopassnum =  $inqsoService->getRowNum($nopasssql);
    	 
    	if(in_array($typetmp,$typeArr))
    	{
    		$this->view->type =$typetmp;
    		if($typetmp == 'wpay') {
    			$typestr = $wpaysql;
    			$total = $this->view->wpaynum;
    		}elseif($typetmp == 'pass') {
    			$typestr = $passsql;
    			$total = $this->view->passnum;
    		}elseif($typetmp == 'nopass') {
    			$typestr = $nopasssql;
    			$total = $this->view->nopassnum;
    		}
    	}else {
    		$this->view->type='';
    		$total   = $this->view->allnum;
    		$typestr = $allsql;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->orderlist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->orderlisttype);
    	$this->view->salesorder = $inqsoService->getAllSo($offset, $perpage, $typestr);
    	//在线订单总数
    	$this->view->onlineSoNum = $soService->onlineSoNum();
    	//询价订单总数
    	$this->view->inqSoNum = $inqsoService->inqSoNum();
    }
    /*
     * 我的发票
     */
    public function invoiceAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='invoice';
    	$invoiceService = new Default_Service_InvoiceService();
    	//选择不同的类型
    	$typeArr =array('no','pass','nopass');
    	$this->view->type = $typetmp = 'no';
    	if(isset($_GET['type'])){
    	$this->view->type = $typetmp = $_GET['type'];
    	}
    	$typestr    =" AND ia.status='101'";
    	$passsql    = " AND ia.status='201'";
    	$nopasssql  = " AND ia.status='202'";
    	
    	//没有申请发票的订单数
    	$this->view->nototal  = $invoiceService->getNoNum();
    	//待付款订单数
    	$this->view->passtotal = $invoiceService->getNum($passsql);
    	//处理中订单数
    	$this->view->nopasstotal = $invoiceService->getNum($nopasssql);
    	
    	if($typetmp == 'no') {
    		$total = $this->view->nototal;
    	}elseif($typetmp == 'pass') {
    		$total = $this->view->passtotal;
    	}elseif($typetmp == 'nopass') {
    		$total = $this->view->nopasstotal;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->invoicelist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->invoicelisttype);
    	if($typetmp == 'no') {
    		$this->view->invoiceso = $invoiceService->getNoRecord($offset,$perpage);
    	}elseif($typetmp == 'pass') {
    		$this->view->invoiceso = $invoiceService->getRecord($offset,$perpage,$passsql);
    	}elseif($typetmp == 'nopass') {
    		$this->view->invoiceso = $invoiceService->getRecord($offset,$perpage,$nopasssql);
    	}
    	
    }
    /*
     * cod货到付款资格申请
    */
    public function codAction()
    {
    }
    /*
     * 我的收藏夹
     */
    public function favoritesAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='favorites';
    	$favService = new Default_Service_FavoritesService();
    	$sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
    	//分页
    	$total = $favService->getTotal();
    	$perpage=$this->commonconfig->page->favoriteslist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->favoriteslisttype);
    	$this->view->totle = $total;
    	$this->view->allProd = $favService->getRecord($offset,$perpage);
    }
    
    /**
     * 我的收货地址
     */
    public function addressAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] = 'address';
    	if(!isset($_SESSION['postsess']))
    	{
    		$_SESSION['postsess']=array();
    		$_SESSION['postsess']['error']   = 0;
    		$_SESSION['postsess']['message'] = '';
    	}
    	$addressModel = new Default_Model_DbTable_Address();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		if($formData['type']=='delete')
    		{
    			$this->_helper->layout->disableLayout();
    			$this->_helper->viewRenderer->setNoRender();
    			$id  = (int)($formData['ID']);
    			$addressModel->update(array('status'=>0),"id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    			$_SESSION['postsess']['message'] = "删除成功";
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"删除成功"));
    			exit;
    		}
    		if($formData['type']=='default')
    		{
    			$this->_helper->layout->disableLayout();
    			$this->_helper->viewRenderer->setNoRender();
    			$id  = (int)($formData['ID']);
    			$addressModel->update(array('default'=>0), "uid='".$_SESSION['userInfo']['uidSession']."'");
    			$addressModel->update(array('default'=>1), "id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    			$_SESSION['postsess']['message'] = "设置默认地址成功";
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"设置默认地址成功"));
    			exit;
    		}
    		if($formData['type']=='change')
    		{
    		 $shrname  = $this->filter->pregHtmlSql($formData['shrname']);
    		 $province = $this->filter->pregHtmlSql($formData['province']);
    		 $city     = $this->filter->pregHtmlSql($formData['city']);
    		 $area     = $this->filter->pregHtmlSql($formData['area']);
    		 $address  = $this->filter->pregHtmlSql($formData['address']);
    		 $zipcode  = $this->filter->pregHtmlSql($formData['zipcode']);
    		 $companyname  = $this->filter->pregHtmlSql($formData['companyname']);
    		 $warehousing  = $this->filter->pregHtmlSql($formData['warehousing']);
    		 $mobile   = $this->filter->pregHtmlSql($formData['mobile']);
    		 $tel      = $this->filter->pregHtmlSql($formData['tel']);
    		 $default  = (int)$formData['default']==''?0:$formData['default'];
    		 
    		 $error = 0;$message='';
    			if(!$shrname){
    				$message ='请填写收 货 人。<br/>';
    				$error++;
    			}
    			if(!$province){
    				$message ='请选择省。<br/>';
    				$error++;
    			}
    			if(!$city){
    				$message ='请选择市。<br/>';
    				$error++;
    			}if(!$area){
    				$message ='请选择区。<br/>';
    				$error++;
    			}
    			if(!$address){
    				$message ='请填写详细地址。<br/>';
    				$error++;
    			}
    			if($formData['tmp']=='add')
    			{
    			   //最多添加20个地址，防止攻击
    			   $addService = new Default_Service_AddressService();
    			   $addre = $addService->checkNum(20);
    			   if($addre){
    				   $message ='最多保存20个地址<br/>';
    				   $error++;
    			   }
    			}
    			if($error){
    				$_SESSION['postsess']['error']   = $error;
    				$_SESSION['postsess']['message'] = $message;
    			}else{
    				if($default==1){
    					$addressModel->update(array('default'=>0), "uid='".$_SESSION['userInfo']['uidSession']."'");
    				}
    				$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
    						'name'=>$shrname,
    						'companyname'=>$companyname,
    						'province'=>$province,
    						'city'=>$city,
    						'area'=>$area,
    						'address'=>$address,
    						'mobile'=>$mobile,
    						'tel'=>$tel,
    						'zipcode'=>$zipcode,
    						'warehousing'=>$warehousing,
    						'default'=>$default,
    						'created'=>time(),
    						'modified'=>time());
    				if($formData['tmp']=='add')
    				{
    				   $addressModel->addData($data);
    				   $_SESSION['postsess']['message'] = '添加收货地址成功';
    				   $this->_redirect('/center/address');
    				}
    				if($formData['tmp']=='edit')
    				{
    					$id = (int)($formData['addid']);
    					$addressModel->update($data,"id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    					$_SESSION['postsess']['message'] = '编辑收货地址成功';
    					$this->_redirect('/center/address');
    				}
    				
    			}
    		}
    	}
    	$myinfoarray = $this->user->getBySql("SELECT companyname FROM user_profile
    			WHERE  uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	$this->view->myinfo = $myinfoarray[0];
    	
    	$sqlstr ="SELECT a.id,a.uid,a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,
        a.warehousing,a.default,p.province,c.city,e.area 
    	FROM address as a LEFT JOIN province as p 
        ON a.province=p.provinceid 
        LEFT JOIN city as c 
        ON a.city=c.cityid
        LEFT JOIN area as e 
        ON a.area = e.areaid
    	WHERE a.uid=:uidtmp AND a.status = 1
    	ORDER BY `default` DESC";
    	$this->view->addressArr = $addressModel->getBySql($sqlstr, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    }
    /**
     * 个人资料
     */
    public function infoAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='info';
    	// 用于个人资料提示
    	if(!isset($_SESSION['postsess']))
    	{
    		$_SESSION['postsess']=array();
    		$_SESSION['postsess']['error']   = 0;
    		$_SESSION['postsess']['ptype']   =1;
    		$_SESSION['postsess']['message'] = '';
    	}
    	if(isset($_GET['type']) && $_GET['type']==2) $_SESSION['postsess']['ptype'] = 2;
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$ptype = (int)$formData['ptype'];
    		$error = 0;$message='';
    		$_SESSION['postsess']['ptype'] = $ptype;
    		//编辑基本资料
    		if($ptype=='1')
    		{
    			$myinfoarray = $this->user->getRowByWhere("uid='".$_SESSION['userInfo']['uidSession']."'");
    			
    			$memberSex = $this->filter->pregHtmlSql($formData['memberSex']);
    			$mobile    = $this->filter->pregHtmlSql($formData['mobile']);
    			$birthday  = $this->filter->pregHtmlSql($formData['birthday']);
    			if($myinfoarray['backedit']){
    				$uname = $this->filter->pregHtmlSql($formData['uname']);
    				$email = $this->filter->pregHtmlSql($formData['email']);
    				if(!$uname){
    					$message ='请填写用户名。<br/>';
    					$error++;
    				}else{
    					$reUser = $this->user->getRowByWhere("uname ='{$uname}' AND uid!='".$_SESSION['userInfo']['uidSession']."'");
    					if($reUser){
    						$message .= '用户名已经存在！';
    						$error ++;
    					}
    				}
    				$this->filter = new MyFilter();
    				if(!$email){
    					$message .='请填写邮箱地址。<br/>';
    					$error++;
    				}elseif(!$this->filter->checkEmail($email)){
    			         $message .= '请输入正确的邮箱地址！';
    			         $error ++;
    		        }else{
    			        $reUser = $this->user->getRowByWhere("email ='{$email}' AND uid!='".$_SESSION['userInfo']['uidSession']."'");
    			        if($reUser){
    				       $message .='邮箱已经存在！';
    				       $error ++;
    			        }
    		        }
    			}
    			if(!$memberSex){
    				$message ='请选择性别。<br/>';
    				$error++;
    			}
    		    if(!$mobile){
    		    	$message ='请填写手机。<br/>';
    		    	$error++;
    		    }
    		    if(!$birthday){
    		    	$message ='请填写出生日期。<br/>';
    		    	$error++;
    		    }
    		    if($error){
    		    	$_SESSION['postsess']['error']   = $error;
    		    	$_SESSION['postsess']['message'] = $message;
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>1,'temp4'=>'更新基本资料失败','description'=>$message));
    		    }else{
    		    	$data = array('gender'=>$memberSex,
    		    			'mobile'=>$mobile,
    		    			'birthday'=>$birthday);
    		    	if($myinfoarray['backedit']){
    		    		$udata['uname']    = $uname;
    		    		$udata['email']    = $email;
    		    		$udata['backedit'] = 0;
    		    		$this->user->updateByUid($udata, $_SESSION['userInfo']['uidSession']);
    		    		//记录日志
    		    		$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>1,'temp4'=>'更新uname和email成功','description'=>implode("|",$udata)));
    		    	}
    		    	$this->userprofile->updateByUid($data, $_SESSION['userInfo']['uidSession']);
    		    	$_SESSION['postsess']['message'] = '更新基本资料成功';
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>1,'temp4'=>'更新基本资料成功','description'=>implode("|",$data)));
    		    	$this->_redirect('/center/info');
    		    }
    		}elseif($ptype=='2')
    		{
    			$companyname = $this->filter->pregHtmlSql($formData['companyname']);
    			$contact     = $this->filter->pregHtmlSql($formData['contact']);
    			$property    = $this->filter->pregHtmlSql($formData['property']);
    			$currency    = $this->filter->pregHtmlSql($formData['currency']);
    			$tel         = $this->filter->pregHtmlSql($formData['tel']);
    			$fax         = $this->filter->pregHtmlSql($formData['fax']);
    			$province    = $this->filter->pregHtmlSql($formData['province']);
    			$city        = $this->filter->pregHtmlSql($formData['city']);
    			$area        = $this->filter->pregHtmlSql($formData['area']);
    			$department_id = (int)$formData['department_id'];
    			$industry    = $this->filter->pregHtmlSql($formData['industry']);
    			$industry_other = $this->filter->pregHtmlSql($formData['industry_other']);
    			$address     = $this->filter->pregHtmlSql($formData['address']);
    			$zipcode     = $this->filter->pregHtmlSql($formData['zipcode']);
    			$uploadtext1 = $_FILES['uploadtext1'];
    			$uploadtext2 = $_FILES['uploadtext2'];
    			$uploadtext3 = $_FILES['uploadtext3'];
    			if(!$companyname){
    				$message ='请填写公司名称。<br/>';
    				$error++;
    			}
    			if(!$contact){
    				$message ='请填写联系人。<br/>';
    				$error++;
    			}
    		    if(!$tel){
    			    $message ='请填写公司电话。<br/>';
    			    $error++;
    		    }
    		    if(!$province && !$city && !$area){
    		    	$message ='请选择地区。<br/>';
    		    	$error++;
    		    }
    		    if(!$address){
    		    	$message ='请填写公司地址。<br/>';
    		    	$error++;
    		    }
    		    if(!empty($uploadtext1['tmp_name']))
    		    {
    		    	if(!$this->filter->checkComFile($uploadtext1['type'],$uploadtext1['name']))
    		    	{
    		    		$message ='附件1格式不正确。<br/>';
    		    		$error++;
    		    	}
    		    	if(($uploadtext1["size"]/(1024*1024))>8) //大于8M
    		    	{
    		    		$message ='附件1超过8M。<br/>';
    		    		$error++;
    		    	}
    		    }
    		    if(!empty($uploadtext2['tmp_name']))
    		    {
    		    	if(!$this->filter->checkComFile($uploadtext2['type'],$uploadtext2['name']))
    		    	{
    		    		$message ='附件2格式不正确。<br/>';
    		    		$error++;
    		    	}
    		    	if(($uploadtext2["size"]/(1024*1024))>8) //大于8M
    		    	{
    		    		$message ='附件2超过8M。<br/>';
    		    		$error++;
    		    	}
    		    }
    		    if(!empty($uploadtext3['tmp_name']))
    		    {
    		    	if(!$this->filter->checkComFile($uploadtext3['type'],$uploadtext3['name']))
    		    	{
    		    		$message ='附件2格式不正确。<br/>';
    		    		$error++;
    		    	}
    		    	if(($uploadtext3["size"]/(1024*1024))>8) //大于8M
    		    	{
    		    		$message ='附件2超过8M。<br/>';
    		    		$error++;
    		    	}
    		    }
    		    if($error){
    		    	$_SESSION['postsess']['error']   = $error;
    		    	$_SESSION['postsess']['message'] = $message;
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>2,'temp4'=>'更新企业资料失败','description'=>$message));
    		    }else{
    		    	//公司附件路径
    		    	$path = COM_ANNEX.$_SESSION['userInfo']['uidSession'].'/';
    		    	if(!is_dir($path)) //判断是否存在文件夹
    		    	{
    		    		mkdir($path,0777);
    		    	}
    		    	$time = time();
    		    	//附件命名规则
    		    	$annex1 = $annex2 = $annex3 = '';
    		    	$uidmd5 = md5(md5($_SESSION['userInfo']['uidSession']));
    		    	if(!empty($uploadtext1['tmp_name'])){
    		    		$annex1=$uidmd5."1.".$this->filter->extend($uploadtext1['name']);
    		    		@move_uploaded_file($uploadtext1["tmp_name"],$path.$annex1);
    		    		@unlink($uploadtext1);
    		    	}
    		    	if(!empty($uploadtext2['tmp_name'])){
    		    		$annex2=$uidmd5."2.".$this->filter->extend($uploadtext2['name']);
    		    		@move_uploaded_file($uploadtext2["tmp_name"],$path.$annex2);
    		    		@unlink($uploadtext2);
    		    	}
    		    	if(!empty($uploadtext3['tmp_name'])){
    		    		$annex3=$uidmd5."3.".$this->filter->extend($uploadtext3['name']);
    		    		@move_uploaded_file($uploadtext3["tmp_name"],$path.$annex3);
    		    		@unlink($uploadtext3);
    		    	}
    		    	$data = array('companyname'=>$companyname,
    		    			'currency'=>$currency,
    		    			'truename'=>$contact,
    		    			'department_id'=>$department_id,
    		    			'companyapprove'=>0,
    		    			'detailed'=>1,
    		    			'tel'=>$tel,
    		    			'fax'=>$fax,
    		    			'province'=>$province,
    		    			'city'=>$city,
    		    			'area'=>$area,
    		    			'address'=>$address,
    		    			'zipcode'=>$zipcode,
    		    			'industry'=>$industry,
    		    			'personaldesc'=>$industry_other,
    		    			'modified'=>time(),
    		    			'property'=>$property);
    		        if($annex1) $data['annex1']=$annex1;
    		        if($annex2) $data['annex2']=$annex2;
    		        if($annex3) $data['annex3']=$annex3;
    		    	$this->userprofile->updateByUid($data, $_SESSION['userInfo']['uidSession']);
    		    	$_SESSION['postsess']['message'] = '编辑企业资料成功';
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>2,'temp4'=>'更新企业资料成功','description'=>implode("|",$data)));
    		    	$this->_redirect('/center/info');
    		    }
    		}elseif($ptype=='3')
    		{
    			$oldpass  = md5(md5($formData['oldpass']));
    			$newpass  = md5(md5($formData['newpass']));
    			$newpass2 = md5(md5($formData['newpass2']));
    			if(!$oldpass){
    				$message ='请填写旧密码。<br/>';
    				$error++;
    			}else{
    				$re = $this->user->getRowByWhere("pass='{$oldpass}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    				if(empty($re)){
    					$message ='密码错误。<br/>';
    					$error++;
    				}
    			}
    		    if(!$newpass){
    			    $message ='请填写新密码。<br/>';
    			    $error++;
    		    }
    		    if(!$newpass2){
    		    	$message ='请确认新密码。<br/>';
    		    	$error++;
    		    }elseif($newpass != $newpass2){
    		    	$message ='两次输入的密码不同。<br/>';
    		    	$error++;
    		    }
    		    
    		    if($error){
    		    	$_SESSION['postsess']['error']   = $error;
    		    	$_SESSION['postsess']['message'] = $message;
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>3,'temp4'=>'修改密码失败','description'=>$message));
    		    }else{
    		    	$data = array('pass'=>$newpass,'pass_back'=>'');
    		    	$this->user->updateByUid($data, $_SESSION['userInfo']['uidSession']);
    		    	$_SESSION['postsess']['message'] = '修改密码成功';
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>3,'temp4'=>'修改密码成功','description'=>implode("|",$data)));
    		    	$this->_redirect('/center/info');
    		    }
    		}
    	}
    	//查询应用
    	$appcModel = new Default_Model_DbTable_AppCategory();
    	$this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
    	
    	$myinfoarray = $this->user->getBySql("SELECT * FROM user as u,user_profile as up
    			WHERE u.uid = up.uid AND u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	$this->view->myinfo = $myinfoarray[0];
    	//查询企业所在地区 或 企业资料审核回复
    	if($myinfoarray[0]['companyapprove']==1){
    		$sqlstr ="SELECT p.province,c.city,e.area
    	              FROM province as p ,city as c ,area as e
    	              WHERE p.provinceid='".$myinfoarray[0]['province']."' 
    		          AND c.cityid = '".$myinfoarray[0]['city']."'
    		          AND e.areaid = '".$myinfoarray[0]['area']."'";
    	    $this->view->addressArr = $this->user->getBySql($sqlstr, array());
    	}else{
    		$replylogModel = new Icwebadmin_Model_DbTable_ReplyLog();
    		$this->view->replylog = $replylogModel->getAllByWhere("uid='".$_SESSION['userInfo']['uidSession']."' AND area_name='companyapprove'");
    	}
    	//部门
    	$officeModel = new Default_Model_DbTable_Model('user_department');
    	$this->view->office = $officeModel->getAllByWhere("status=1","displayorder ASC");
    }
    /**
     * 积分兑换
     */
    public function exchangeAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$this->_giftservice = new Default_Service_GiftService();
    	$_SESSION['leftmenu_select'] ='exchange';
    	$typestr = '';
    	$this->view->type = $_GET['type']?$_GET['type']:'wait';
    	//待处理
    	$waitsql   = " AND ge.status='101'";
    	//已处理
    	$alreadysql= " AND ge.status='301'";
    	//被取消
    	$cancelsql = " AND ge.status='401'";
    	
    	$this->view->waitnum = $this->_giftservice->getGiftExchangeNum($waitsql);
    	$this->view->alreadynum = $this->_giftservice->getGiftExchangeNum($alreadysql);
    	$this->view->cancelnum = $this->_giftservice->getGiftExchangeNum($cancelsql);
    	if($this->view->type=='wait'){
    		$total = $this->view->waitnum;
    		$typestr = $waitsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
    	}elseif($this->view->type=='cancel'){
    		$total = $this->view->cancelnum;
    		$typestr = $cancelsql;
    	}else $this->_redirect ( '/icwebadmin' );
    	
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->giftall = $this->_giftservice->getGiftExchange($offset, $perpage, $typestr);
    }
    //样片申请
    public function samplesAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$this->_samplesservice = new Default_Service_SamplesService();
    	$_SESSION['leftmenu_select'] ='samples';
    	$typestr = '';
    	$this->view->type = $_GET['type']?$_GET['type']:'proc';
    	//处理中
    	$procsql   = " AND spa.status IN ('100','200')";
    	//已处理
    	$alreadysql= " AND spa.status IN ('300','301')";
    	//被取消
    	$cancelsql = " AND spa.status='400'";
    	
    	$this->view->procnum    = $this->_samplesservice->getApplyNum($procsql);
    	$this->view->alreadynum = $this->_samplesservice->getApplyNum($alreadysql);
    	$this->view->cancelnum  = $this->_samplesservice->getApplyNum($cancelsql);
    	if($this->view->type=='proc'){
    		$total = $this->view->procnum;
    		$typestr = $procsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
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
     * ajax 我的易站左菜单
     */
    public function leftmenuAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->view->select = $_SESSION['leftmenu_select'];
    }
    /**
     * 检查密码
     * @access 	public
     * @param
     * @return 	bool
     */
    public function checkpassAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$oldpass  = md5(md5($_GET['oldpass']));
    	$re = $this->user->getRowByWhere("pass='{$oldpass}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    	if(!empty($re)){
    		echo Zend_Json_Encoder::encode(array("code"=>0));
    		exit;
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>1));
    		exit;
    	}
    }
    /**
     * 获取收货地址
     * @access 	public
     * @param
     * @return 	bool
     */
    public function getaddressAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id=(int)$formData['id'];
    		$addressModel = new Default_Model_DbTable_Address();
    		$re = $addressModel->getRowByWhere("id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		//IE6 arr.re.default报错
    		echo Zend_Json_Encoder::encode(array("code"=>0, "re"=>$re,"redefault"=>$re['default']));
    		exit;
    	}
    }
    /*
     * 企业资料填写弹出框
     */
    public function companyinfoAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->opbox = $_GET['opbox'];
    	$this->view->opurl = $_GET['opurl'];
    	$this->view->opcancel = $_GET['opcancel'];
    	$this->view->key = $_GET['key'];
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData  = $this->getRequest()->getPost();
    		$companyname = $this->filter->pregHtmlSql($formData['companyname']);
    		$contact = $this->filter->pregHtmlSql($formData['contact']);
    		$industry= (int)($formData['industry']);
    		$property  = $this->filter->pregHtmlSql($formData['property']);
    		$currency  = $this->filter->pregHtmlSql($formData['currency']);
    		$department_id = (int)$formData['department_id'];
    		$tel  = $this->filter->pregHtmlSql($formData['tel']);
    		$mobile  = $this->filter->pregHtmlSql($formData['mobile']);
    		$fax    = $this->filter->pregHtmlSql($formData['fax']);
    		$province  = $this->filter->pregHtmlSql($formData['province']);
    		$city  = $this->filter->pregHtmlSql($formData['city']);
    		$area  = $this->filter->pregHtmlSql($formData['area']);
    		$address  = $this->filter->pregHtmlSql($formData['address']);
    		$zipcode  = $this->filter->pregHtmlSql($formData['zipcode']);
    		$message = '';
    		$error = 0;
    		if(!$companyname){
    			$message .='请填写公司名称。';
    			$error++;
    		}if(!$contact){
    			$message .='请填写联系人。';
    			$error++;
    		}
    		if(!$tel){
    			$message .='请填写公司电话。';
    			$error++;
    		}
    		if(!$fax){
    			$message .='请填写公司传真。';
    			$error++;
    		}
    		if(!$province && !$city && !$area){
    			$message .='请选择地区。';
    			$error++;
    		}
    		if(!$address){
    			$message .='请填写公司地址。';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    		$data = array('companyname'=>$companyname,
    				    'currency'=>$currency,
    				    'industry'=>$industry,
    				    'truename'=>$contact,
    				    'department_id'=>$department_id,
    					'companyapprove'=>0,
    					'detailed'=>1,
    				    'mobile'=>$mobile,
    					'tel'=>$tel,
    					'fax'=>$fax,
    					'province'=>$province,
    					'city'=>$city,
    					'area'=>$area,
    					'address'=>$address,
    					'zipcode'=>$zipcode,
    					'modified'=>time(),
    					'property'=>$property);
    		$this->userprofile->updateByUid($data, $_SESSION['userInfo']['uidSession']);
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交企业资料成功'));
    		exit;
    	}
      }
      $myinfoarray = $this->user->getBySql("SELECT * FROM user as u,user_profile as up
    			WHERE u.uid = up.uid AND u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
      $this->view->myinfo = $myinfoarray[0];
      //部门
      $officeModel = new Default_Model_DbTable_Model('user_department');
      $this->view->office = $officeModel->getAllByWhere("status=1","displayorder ASC");
      //查询应用
      $appcModel = new Default_Model_DbTable_AppCategory();
      $this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
       
    }
    /**
     * 申请再开发票
     */
    public function applyinvoiceAction()
    {
    	$soService    = new Default_Service_OrderService();
    	$inqsoService = new Default_Service_InqOrderService();
    	$addressService = new Default_Service_AddressService();
    	//提交处理
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData   = $this->getRequest()->getPost();
    		$salesnumber= $this->filter->pregHtmlSql($formData['salesnumber']);
    		$addressid  = (int)$formData['addressid'];
    		$invoiceid  = (int)$formData['invoiceid'];
    		$sotype     = (int)$formData['sotype'];
    		//获取地址
    		$addressModel = new Default_Model_DbTable_Address();
    		$invoiceModel = new Default_Model_DbTable_Invoice();
    		$addre = $addressModel->getRowByWhere("id='{$addressid}'");
    		$invre = $invoiceModel->getRowByWhere("id='{$invoiceid}'");
    		$error = 0;$message='';
    		if(empty($invre)){
    			$message .='发票信息不存在，请重新选择。';
    			$error++;
    		}
    		if(empty($addre)){
    			$message .='收货人地址不存在，请重新选择。';
    			$error++;
    		}
    		if($error){
    			echo $message;
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			$soaddModel  = new Default_Model_DbTable_OrderAddress();
    			$inqappModel = new Default_Model_DbTable_InvoiceApply();
    			//记录收货地址
    			$soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
    					'salesnumber'=>$salesnumber,
    					'name'       =>$addre['name'],
    					'companyname'=>$addre['companyname'],
    					'province'   =>$addre['province'],
    					'city'       =>$addre['city'],
    					'area'       =>$addre['area'],
    					'address'    =>$addre['address'],
    					'mobile'     =>$addre['mobile'],
    					'tel'        =>$addre['tel'],
    					'zipcode'    =>$addre['zipcode'],
    					'created'    =>time());
    			$soaddid = $soaddModel->addData($soadd_data);
    			$inqappdata = array('salesnumber'=>$salesnumber,
    					'so_type'=>$sotype,
    					'uid'=>$_SESSION['userInfo']['uidSession'],
    					'addressid'=>$soaddid,
    					'invoiceid'=>$invoiceid,
    					'created'=>time());
    			$newid = $inqappModel->addData($inqappdata);
    			if($newid){
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'申请成功'));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'申请失败'));
    				exit;
    			}
    		}
    	}
    	
    	$salesnumber = $this->_getParam('salesnumber');
        $this->view->sotype = (int)$this->_getParam('sotype');
    	if($this->view->sotype==100){
    		$this->view->orderarr = $soService->geSoinfo($salesnumber);
    	}elseif($this->view->sotype==110){
    		$this->view->orderarr = $inqsoService->geSoinfo($salesnumber);
    	}else $this->_redirect('/center/invoice');
    	if(!$this->view->orderarr) $this->_redirect('/center/invoice');
    	//判断是否没有发票，和是否在180天内
    	if($this->view->orderarr['invoiceid']!=0 ||  $this->view->orderarr['created'] < strtotime("-".INVOICE_DAY." day") || $this->view->orderarr['iastatus']>0)
    	{
    		$this->_redirect('/center/invoice');
    	}
    	//收货地址
    	$this->view->addressArr = $arr = $addressService->getAddress();
    	if(!empty($arr))
    	{
    		$this->view->addressFirst = $arr[0];
    		foreach($arr as $arrtmp){
    			if($_SESSION['order_sess']['addressid'] == $arrtmp['id'])
    				$this->view->addressFirst = $arrtmp;
    		}
    	}else $this->view->addressFirst = array();
    	
    	//发票
    	$invoiceModel = new Default_Model_DbTable_Invoice();
    	$invarr = $invoiceModel->getAllByWhere("uid='".$_SESSION['userInfo']['uidSession']."'","created DESC");
    	if(!empty($invarr))
    	{
    		$this->view->invoiceFirst = $invarr[0];
    		$invoiceidTow = array();
    	
    		foreach($invarr as $arrtmp){
    			if($_SESSION['order_sess']['invoiceid'] == $arrtmp['id'])
    				$this->view->invoiceFirst = $arrtmp;
    			if($arrtmp['type']==2 && empty($invoiceidTow)){
    				$invoiceidTow = $arrtmp;
    			}
    		}
    	}else $this->view->invoiceFirst = array();
    	$this->view->invoiceidTow = $invoiceidTow;
    	$myinfoarray = $this->userprofile->getBySql("SELECT companyname,annex1,annex2 FROM user_profile
    			WHERE  uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	$this->view->myinfo = $myinfoarray[0];
    }
    /**
     * 查看发票申请
     */
    public function viewinvoiceAction()
    {
    	$soService    = new Default_Service_OrderService();
    	$inqsoService = new Default_Service_InqOrderService();
    	$addressService = new Default_Service_AddressService();
    	$salesnumber = $this->_getParam('salesnumber');
    	$this->view->sotype = (int)$this->_getParam('sotype');
    	if($this->view->sotype==100){
    		$this->view->orderarr = $soService->geSoinfo($salesnumber);
    	}elseif($this->view->sotype==110){
    		$this->view->orderarr = $inqsoService->geSoinfo($salesnumber);
    	}else $this->_redirect('/center/invoice');
    	if(!$this->view->orderarr) $this->_redirect('/center/invoice');
    	//收货地址
    	$this->view->addressFirst = $addressService->getSoAddress($this->view->orderarr['iaaddressid']);
		//发票
    	$invoiceModel = new Default_Model_DbTable_Invoice();
    	$this->view->invoiceFirst = $invoiceModel->getRowByWhere("id='".$this->view->orderarr['iainvoiceid']."' AND uid='".$_SESSION['userInfo']['uidSession']."'");
		
    }
    /**
     * 转账成功后，上传凭证
     */
    public function transferAction(){
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	$this->view->ordertype = $_GET['ordertype'];
    	if($this->view->ordertype=='online'){
    		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    	}elseif($this->view->ordertype=='inq'){
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    	}
    	$this->view->so = $salesorderModel->getRowByWhere("salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");	 
    }
    /**
     * 余款转账成功后，上传凭证
     */
    public function transfer2Action(){
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	$this->view->ordertype = $_GET['ordertype'];
    	if($this->view->ordertype=='inq'){
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    	}
    	$this->view->so = $salesorderModel->getRowByWhere("salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
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
    	  if($ordertype=='online'){
    	  	$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    	  	$orderService = new Default_Service_OrderService();
    	  	$orderarr = $orderService->geSoinfo($salesnumber,1);
			if($orderarr['status']==101){
				$updata = array('receipt'=>$receipt,'pay_time'=>time(),'status'=>201);
			}else{
				$updata = array('receipt'=>$receipt,'pay_time'=>time());
			}
    	  }elseif($ordertype=='inq'){
    	  	$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    	  	$orderService = new Default_Service_InqOrderService();
    	  	$orderarr = $orderService->geSoinfo($salesnumber,1);
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
    	  }
    	  
    	  $salesorderModel->update($updata, "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    	  //发邮件
    	  $this->_userService->transferuplodeEamil($orderarr,$orderService,$ordertype,UP_RECEIPT.$receipt,$formData['tablerow']);
    	}
    }
    /**
     * 我的实验室
     */
    public function mylaboratoryAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='mylaboratory';
    	
    	$labService = new Default_Service_LabService();
    	//选择不同的类型
    	$typeArr =array('wait','pass','com','notpass');
    	$typetmp = '';
    	if(isset($_GET['type'])) $typetmp = $_GET['type'];
    	if(!in_array($typetmp, $typeArr)){
    		$this->view->type = 'wait';
    	}else{
    		$this->view->type = $typetmp;
    	}
    	$waitsql     = " AND la.status='100' ";
    	$passsql     = " AND la.status='201'";
    	$comsql      = " AND la.status='202'";
    	$notpasssql  = " AND la.status='401'";

    	
    	$this->view->waittotal    = $labService->getNum($waitsql);
    	$this->view->passtotal    = $labService->getNum($passsql);
    	$this->view->comtotal     = $labService->getNum($comsql);
    	$this->view->notpasstotal = $labService->getNum($notpasssql);
    	$sql = '';
    	if($this->view->type == 'wait') {
    		$total = $this->view->waittotal;
    		$sql   = $waitsql;
    	}elseif($this->view->type == 'pass') {
    		$total = $this->view->passtotal;
    		$sql   = $passsql;
    	}elseif($this->view->type == 'com') {
    		$total = $this->view->comtotal;
    		$sql   = $comsql;
    	}elseif($this->view->type == 'notpass') {
    		$total = $this->view->notpasstotal;
    		$sql   = $notpasssql;
    	}
    	//分页
    	$perpage=$this->commonconfig->page->invoicelist;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show($this->commonconfig->page->invoicelisttype);
    	$this->view->applyall = $labService->getRecord($offset,$perpage,$sql);
    	
    	//实验器材
    	$this->view->instrument = $labService->getInst();
    }
    /**
     * 申请实验室
     */
    public function applylaboratoryAction()
    {
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='mylaboratory';
    	
    	$labService = new Default_Service_LabService();
    	//地区
    	$this->view->place = $labService->getPlace();
    	//用户信息
    	$this->view->usrinfo = $this->_userService->getUserProfileByUid($_SESSION['userInfo']['uidSession']);
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$data = array();
    		    $place              = $formData['place'];
    			$customers          = $this->filter->pregHtmlSql($formData['customers']);
    			$customers_bumen    = $customers==2?$this->filter->pregHtmlSql($formData['customers_bumen']):'';
    			$vist_time          = $this->filter->pregHtmlSql($formData['vist_time']);
    			$vist_time_radio    = $this->filter->pregHtmlSql($formData['vist_time_radio']);
    			$vist_number        = $this->filter->pregHtmlSql($formData['vist_number']);
    			$company            = $this->filter->pregHtmlSql($formData['company']);
    			$address            = $this->filter->pregHtmlSql($formData['address']);
    			$contact            = $this->filter->pregHtmlSql($formData['contact']);
    			$phone              = $this->filter->pregHtmlSql($formData['phone']);
    			$email              = $this->filter->pregHtmlSql($formData['email']);
    			$instrument    = $formData['instrument']?$formData['instrument']:array();
    			$project_name = $this->filter->pregHtmlSql($formData['project_name']);
    			$expected_time = $formData['expected_time'];
    			$project_des = $this->filter->pregHtmlSql($formData['project_des']);
    			$project_images_files = $_FILES['project_images'];
    			$project_bom_files    = $_FILES['project_bom'];
    			$project_device       = '';
    			foreach($formData['part_no'] as $i=>$part_no){
    				if($part_no){
    					 if($i==0) $project_device =$part_no.';'.$formData['brand_name'][$i];
    				     else $project_device .='|'.$part_no.';'.$formData['brand_name'][$i];
    				}
    			}			
    			
    		$this->labModel = new Default_Model_DbTable_Model('lab_apply');
    		$id = $this->labModel->addData(array('uid'=>$_SESSION['userInfo']['uidSession'],
    				'place'=>$place,
    				'customer'=>$customers,
    				'follow'=>$customers_bumen,
    				'vist_time'=>$vist_time.' '.$vist_time_radio,
    				'vist_number'=>$vist_number,
    				'company'=>$company,
    				'address'=>$address,
    				'contact'=>$contact,
    				'phone'=>$phone,
    				'email'=>$email,
    				'instruments'=>implode(',',$instrument),
    				'project_name'=>$project_name,
    				'expected_time'=>$expected_time,
    				'project_des'=>$project_des,
    				'project_device'=>$project_device,
    				'created'=>time()));
    		if($id){
    		
    			//附件命名规则
    			$project_images = $project_bom =  '';
    			$path = 'upload/event/201402135/lab/files/';
    			$pi = $pb = '';
    			if(!empty($project_images_files['tmp_name'])){
    				$project_images=$_SESSION['userInfo']['uidSession'].'_'.$id.'_img.'.$this->filter->extend($project_images_files['name']);
    				@move_uploaded_file($project_images_files["tmp_name"],$path.$project_images);
    				@unlink($project_images_files);
    				$pi = $path.$project_images;
    			}
    		    if(!empty($project_bom_files['tmp_name'])){
    				$project_bom=$_SESSION['userInfo']['uidSession'].'_'.$id.'_bom.'.$this->filter->extend($project_bom_files['name']);
    				@move_uploaded_file($project_bom_files["tmp_name"],$path.$project_bom);
    				@unlink($project_bom_files);
    				$pb = $path.$project_bom;
    			}
    			$this->labModel->update(array('project_images'=>$pi,'project_bom'=>$pb), "id='$id'");
    		  //记录日志
    		  $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$id,'temp4'=>'客户申请实验室成功'));
    		  //发送邮件
    		  $labService->mailalert($id,$place,$customers_bumen);
    		  $_SESSION['postsess']['message'] ='申请已成功提交，请等待管理员审核';
    		  $this->_redirect('/center/applylaboratory');
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$id,'temp4'=>'客户申请实验室失败'));
    			$_SESSION['postsess']['message'] ='申请实验室失败';
    		}
    	}
    	$this->view->waitnum = $labService->getNum(" AND la.status='100'");
    	$this->view->passnum = $labService->getNum(" AND la.status='201'");
    }
    /**
     * 评价
     */
    public function assesslabAction(){
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='mylaboratory';
    	$this->_helper->layout->disableLayout();
    	$labService = new Default_Service_LabService();
    	$this->view->id =$id = $_GET['id'];
    	$this->view->apply = $labService->getRecord(0,1," AND la.id='{$id}'");
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$data = array();
    		$id             = $formData['id'];
    		$followup          = $this->filter->pregHtmlSql($formData['followup']);
    		$test_case    = $this->filter->pregHtmlSql($formData['test_case']);
    		$wish          = $this->filter->pregHtmlSql($formData['wish']);
    		$this->labModel = new Default_Model_DbTable_Model('lab_apply');
    		
    		$re = $this->labModel->update(array('followup'=>$followup,'test_case'=>$test_case,'wish'=>$wish,'status'=>202), "id='$id'");
    		if($re){	//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$id,'temp4'=>'客户填写报告成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'提交成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$id,'temp4'=>'客户填写报告失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'提交失败'));
    			exit;
    		}
    	}
    }
    /**
     * ajax获取实验器材
     */
    public function ajaxinstrumentAction(){
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	$_SESSION['leftmenu_select'] ='mylaboratory';
    	$this->_helper->layout->disableLayout();
    	$place = $_GET['place']?$_GET['place']:1;
    	$labService = new Default_Service_LabService();
    	//实验室
    	$this->view->room = $labService->getRoom();
    	//实验器材
    	$this->view->instrument = $labService->getInstrumentByPlace($place);
    }
}