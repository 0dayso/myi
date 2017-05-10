<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';

require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';

class Icwebadmin_OrUnsoController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_staffService;
	private $_uerService;
	private $_adminlogService;
	private $_inqorderService;
	private $_inqservice;
	private $_productService;
	private $_inqsoService;
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
    	
    	$this->_staffService = new Icwebadmin_Service_StaffService();
    	$this->_uerService = new Icwebadmin_Service_UserService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->_inqorderService = new Icwebadmin_Service_InqOrderService();
    	$this->_inqservice = new Icwebadmin_Service_InquiryService();
    	$this->_productService = new Default_Service_ProductService();
    	$this->_inqsoService = new Icwebadmin_Service_InqOrderService();
    	$this->view->fun = $this->fun = new MyFun();
    	
    }
    public function indexAction(){
    	//获取负责客户列表
    	$this->view->userlist = $this->_uerService->getUserSell();
    	//用户信息
    	$this->view->user = array();
    	$uid = $this->_getParam('uid');
    	
    	if($uid){
    		$this->view->user = $this->_uerService->getUserProfile($uid);
    	}
    	//OA产品线
    	$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    	$this->view->oaproductline = $oa_sellline_model->getAllByWhere("type='line'"," oa_name ASC");
    	//交货地和货币
    	$this->view->currency = $this->_getParam('currency');
    	$this->view->delivery = $this->_getParam('delivery');
    	//获取可以合并发货的订单
    	if($this->_getParam('currency') && $this->_getParam('delivery') && $this->_getParam('uid')){
    	   $this->_dfinqService = new Default_Service_InqOrderService();
    	   $this->view->shiporder = $this->_dfinqService->getInqOrderShipments($this->view->delivery,$this->_getParam('uid'));
    	  
    	   //获取客户收货地址
    	   $this->view->addressArr = $this->_uerService->getUserAddress($uid);
    	   	$this->view->addressFirst = $this->view->addressArr[0];
    	   	foreach($this->view->addressArr as $arrtmp){
    	   		if($arrtmp['default']) $this->view->addressFirst = $arrtmp;break;
    	   	}
    	   $invoiceModel = new Default_Model_DbTable_Invoice();
    	   $invarr = $invoiceModel->getAllByWhere("uid='".$uid."'","created DESC");
    	   $this->view->invoiceFirst = $invarr[0];
    	}
    	if($this->_getParam('delivery')=='HK'){
    	    //快递商
    	    $this->view->expressadd = $this->_uerService->getExpressAddress($uid);
    	}
    }
    /**
     * 查看记录
     */
    public function listAction(){
    	$inqsoModel = new Icwebadmin_Model_DbTable_InqSalesOrder;
    	$spModel = new Icwebadmin_Model_DbTable_SalesProduct();
    	
    	$sqlarr = array();
    	$selectstr = ' AND so.back_order = 1';
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
    	//交货地
    	$this->view->delivery_place = $delivery_place = $_GET['delivery_place'];
    	if(in_array($delivery_place,array('HK','SZ'))){
    		$selectstr .=" AND so.delivery_place='{$delivery_place}' ";
    	} 
    	//选择不同的类型  (待释放，待付款，待发货)
    	$typeArr =array('eva','can','select');
    	$this->view->type = $typetmp = $_GET['type']==''?'eva':$_GET['type'];
    	$typestr = $selectstr;
    	$evasql  = " AND so.status='301' AND so.back_status=202".$selectstr;
    	$cansql  = " AND so.status='401'".$selectstr;
    	$this->view->sap = $_GET['sap'];
        if($_GET['sap']==1){
    		$evasql .=" AND sapo.order_no!='' ";
    		$cansql .=" AND sapo.order_no!='' ";
    	}elseif($_GET['sap']==2){
    		$evasql .=" AND sapo.order_no IS NULL ";
    		$cansql .=" AND sapo.order_no IS NULL ";
    	}
    	//已完成
    	$this->view->evanum = $this->_inqorderService->getRowNum($evasql);
    	//已取消
    	$this->view->cannum = $this->_inqorderService->getRowNum($cansql);
    	if($typetmp == 'eva'){
    		$typestr = $evasql;
    		$total = $this->view->evanum;
    	}elseif($typetmp == 'can'){
    		$typestr = $cansql;
    		$total = $this->view->cannum;
    	}
    	if($_GET['salesnumber']){
    		$this->view->salesnumber = $this->_filter->pregHtmlSql($_GET['salesnumber']);
    		$typestr = $selectsql=" AND so.salesnumber LIKE '%".$this->view->salesnumber. "%'".$selectstr;
    		$this->view->selectnum = $total = $this->_inqorderService->getRowNum($selectsql);
    		
    	}
        if($_GET['sap']==1){
    		$typestr .=" AND sapo.order_no!='' ";
    	}elseif($_GET['sap']==2){
    		$typestr .=" AND sapo.order_no IS NULL ";
    	}
    	if($typetmp=='select'){
    		$total = $this->_inqorderService->getRowNum($typestr);
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->salesorder = $this->_inqorderService->getAllSo($offset, $perpage, $typestr,$orderbystr);
    	 
    }
    /**
     * 添加用户
     */
    public function adduserAction(){
    	$this->_helper->layout->disableLayout();
    	//查询应用
    	$appcModel = new Default_Model_DbTable_AppCategory();
    	$this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
    	if($this->getRequest()->isPost()){
    		$this->_helper->layout->disableLayout();
    		$this->_helper->viewRenderer->setNoRender();
    		$formData   = $this->getRequest()->getPost();
    		$uname = $this->_filter->pregHtmlSql($formData['uname']);
    		$email = $this->_filter->pregHtmlSql($formData['email']);
    		$error = 0;$message='';
    		$userModel = new Default_Model_DbTable_User();
    
    		if(!$uname)
    		{
    			$message .= '用户名不能为空！';
    			$error ++;
    		}else{
    			$reUser = $userModel->getByName($uname);
    			if($reUser){
    				$message .= '用户名已经存在！';
    				$error ++;
    			}
    		}
    		if($email)
    		{
    			if(!$this->_filter->checkEmail($email)){
    				$message .= $description .=$this->view->emailMess = '请输入正确的邮箱地址！';
    				$error ++;
    			}else{
    				$reUser = $userModel->getByEmail($email);
    				if($reUser){
    					$message .= '邮箱已经存在！';
    					$error ++;
    				}
    			}
    		}
    		if($error){
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'后台注册用户失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			$pass = rand(111111,999999);
    			$password = md5(md5($pass));
    			$ip = $this->fun->getIp();
    			$newid = $userModel->addUser(array('uname'=>$uname,
    					'pass'=>$password,
    					'pass_back'=>$pass,
    					'backstage'=>1,
    					'email'=>$email,
    					'emailapprove'=>1,
    					'created'=>time(),
    					'lasttime'=>time(),
    					'ip'=>$ip,
    					'lastip'=>$ip));
    			$userprofile = new Default_Model_DbTable_UserProfile();
    			$uparr = array('uid'=>$newid,
    					'currency'=>$formData['currency'],
    					'truename'=>$formData['truename'],
    					'companyname'=>$formData['companyname'],
    					'province'=>$formData['province'],
    					'city'=>$formData['city'],
    					'area'=>$formData['area'],
    					'address'=>$formData['address'],
    					'zipcode'=>$formData['zipcode'],
    					'tel'=>$formData['tel'],
    					'mobile'=>$formData['mobile'],
    					'fax'=>$formData['fax'],
    					'personaldesc'=>$formData['industry_other'],
    					'industry'=>$formData['industry'],
    					'property'=>$formData['property'],
    					'staffid'=>$_SESSION['staff_sess']['staff_id'],
    					'created'=>time());
    			$userprofile->addUser($uparr);
    			 
    			//添加地址
    			if($formData['addyes']){
    				if($formData['province'] && $formData['city'] && $formData['area'] && $formData['address']){
    					$adddata = array('uid'=>$newid,
    							'name'=>$formData['truename'],
    							'companyname'=>$formData['companyname'],
    							'province'=>$formData['province'],
    							'city'=>$formData['city'],
    							'area'=>$formData['area'],
    							'address'=>$formData['address'],
    							'mobile'=>$formData['mobile'],
    							'tel'=>$formData['tel'],
    							'zipcode'=>$formData['zipcode'],
    							'created'=>time(),
    							'modified'=>time());
    					$addressModel = new Default_Model_DbTable_Address();
    					$addressModel->addData($adddata);
    				}
    			}
    
    			//发送验证email
    			$emailreturn = $this->fun->senduseremail($_SESSION['staff_sess']['email'],$uname,$pass,$newid);
    			//邮件日志
    			if($emailreturn ){
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'发送注册邮件成功'));
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'发送注册邮件失败'));
    			}
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'后台注册用户成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加用户成功','uid'=>$newid));
    			exit;
    		}
    	}
    	$this->view->url = $_GET['url'];
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
    		$re = $addressModel->getRowByWhere("id='{$id}'");
    		//IE6 arr.re.default报错
    		echo Zend_Json_Encoder::encode(array("code"=>0, "re"=>$re,"redefault"=>$re['default']));
    		exit;
    	}
    }
    /**
     * 添加或修改客户收货地址
     */
    public function changeaddressAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$addressModel = new Default_Model_DbTable_Address();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$uid     = $formData['uid'];
    		$addid = (int)($formData['addid']);
    		$shrname = $formData['shrname'];
    		$province= $formData['province'];
    		$city    = $formData['city'];
    		$area    = $formData['area'];
    		$address = $formData['address'];
    		$zipcode = $formData['zipcode'];
    		$companyname  = $formData['companyname'];
    		$warehousing  = $formData['warehousing'];
    		$mobile  = $formData['mobile'];
    		$tel     = $formData['tel'];
    		$default  = (int)$formData['default']==''?0:$formData['default'];
    		$error = 0;$message='';
    		if(!$shrname){
    			$message .='请填写收 货 人。';
    			$error++;
    		}
    		/*if(!$province){
    			$message .='请选择省。';
    			$error++;
    		}
    		if(!$city){
    			$message .='请选择市。';
    			$error++;
    		}if(!$area){
    			$message .='请选择区。';
    			$error++;
    		}*/
    		if(!$address){
    			$message .='请填写详细地址。';
    			$error++;
    		}
    		if($error){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$uid,'temp4'=>'保存地址失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			if($default==1){
    				$addressModel->update(array('default'=>0), "uid='".$uid."'");
    			}
    			$data = array('uid'=>$uid,
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
    			if(!$addid)
    			{
    				$re = $addid = $addressModel->addData($data);
    			}else{
    				$re = $addressModel->update($data,"id='{$addid}' AND uid='$uid'");
    			}
    			if($re){
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$uid,'temp3'=>$addid,'temp4'=>'保存收货信息成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "addid"=>$addid,"message"=>'保存收货信息成功'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$uid,'temp3'=>$addid,'temp4'=>'保存收货信息失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'保存收货信息失败'));
    				exit;
    			}
    		}
    	}
    }
    /**
     * 修改发票
     */
    public function changeinvoiceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$invoiceModel = new Default_Model_DbTable_Invoice();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$invoicetype = $formData['invoicetype'];
    		$error = 0;$message='';
    		if($invoicetype==1)
    		{
    			$taitouname  = $formData['taitouname'];
    			$contype     = (int)($formData['contype']);
    			if(!$taitouname){
    				$message ='请填写发票抬头。';
    				$error++;
    			}
    			if($error){
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    				exit;
    			}else{
    				$data = array('uid'=>$formData['uid'],
    						'type'=>$invoicetype,
    						'name'=>$taitouname,
    						'contype'=>$contype,
    						'created'=>time(),
    						'modified'=>time());
    				$id = $invoiceModel->addData($data);
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"保存成功","invid"=>$id));
    				exit;
    			}
    		}
    		if($invoicetype==2)
    		{
    			$taitouname  = $formData['name'];
    			$identifier  = $formData['identifier'];
    			$regaddress  = $formData['regaddress'];
    			$regphone    = $formData['regphone'];
    			$bank        = $formData['bank'];
    			$account     = $formData['account'];
    			$contype     = (int)($formData['contype']);
    			if(!$taitouname){
    				$message ='请填写单位名称。';
    				$error++;
    			}
    			if(!$identifier){
    				$message ='请填写纳税人识别号。';
    				$error++;
    			}
    			if(!$regaddress){
    				$message ='请填写注册地址。';
    				$error++;
    			}
    			if(!$regphone){
    				$message ='请填写注册电话。';
    				$error++;
    			}
    			if(!$bank){
    				$message ='请填写开户银行。';
    				$error++;
    			}
    			if(!$account){
    				$message ='请填写银行帐户。';
    				$error++;
    			}
    			if($error){
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    				exit;
    			}else{
    				$data = array('uid'=>$formData['uid'],
    						'type'=>$invoicetype,
    						'name'=>$taitouname,
    						'identifier'=>$identifier,
    						'regaddress'=>$regaddress,
    						'regphone'=>$regphone,
    						'bank'=>$bank,
    						'account'=>$account,
    						'contype'=>$contype,
    						'created'=>time(),
    						'modified'=>time());
    				$id = $invoiceModel->addData($data);
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"保存成功","invid"=>$id));
    				exit;
    			}
    		}
    	}
    }
    /*
     * 添加订单
    */
    public function addinqorderAction(){
    	if($this->getRequest()->isPost()){
    		$this->_helper->layout->disableLayout();
    		$this->_helper->viewRenderer->setNoRender();
    		$formData   = $this->getRequest()->getPost();
    		$uid   = $formData['uid'];
    		$currency   = $formData['currency'];
    		$delivery   = $formData['delivery'];
			$delivery_type = $formData['delivery_type'];
			$freight = $formData['freight'];
			$exp_paytype = $formData['exp_paytype'];
    		$addid = $formData['addid'];
    		$shipoder_salesnumber = $formData['shipoder_salesnumber'];
    		$paytype = $formData['paytype'];
    		$percentage = $formData['percentage'];
    		$down_payment = $formData['down_payment'];
    		$paytype_other = $formData['paytype_other'];
    		$incoice_type = $formData['incoice_type'];
    		$invoiceaddressradio = $formData['invoiceaddressradio'];
    		$delivery_type_4_array = $formData['delivery_type_4_array'];
    		$part_no = $formData['part_no'];
    		$oa_productline = $formData['oa_productline'];
    		$customer_material = $formData['customer_material'];
    		$price = $formData['price'];
    		$buy_number = $formData['buy_number'];
    		$needs_time = $formData['needs_time'];
    		$standard_delivery = $formData['standard_delivery'];
    		$remark = $formData['remark'];
    		$error = 0;$message='';
    		$userModel = new Default_Model_DbTable_User();
    		if(!$uid){
    			$message .= '请选择用户！';
    			$error ++;
    		}
    		  $addre = $invoiceaddre = array();
    		  if($delivery_type == 1 || $delivery_type == 2){
    		  	  if($addid){
    				 $addressModel = new Default_Model_DbTable_Address();
    				 $addre        = $addressModel->getRowByWhere("id='{$addid}'");
    				 if(empty($addre)){
    					$message ='收货人地址不存在，请重新选择';
    					$error++;
    				 }
    				 //如果发票地址不等与收货地址
    				 if($addid != $invoiceaddressradio)
    				 {
    					$invoiceaddre = $addressModel->getRowByWhere("id='{$invoiceaddressradio}'");
    				 }
    		  	  }
    		  }elseif($delivery_type == 4){//客户提供账号
    			   	   /*foreach($delivery_type_4_array as $dv){
    			   	   	   if(!$dv){
    			   	   	   	  $message ='请填写客户提供快递账号信息';
    			   	   	      $error++;
    			   	   	   }
    			   	   }*/
    			   	   $addre  = array('name'=>$delivery_type_4_array[1],
				                       'companyname'    =>$delivery_type_4_array[5],
				                       'address'        =>$delivery_type_4_array[0],
				                       'tel'            =>$delivery_type_4_array[2],
    			   	   		           'express_name'   =>$delivery_type_4_array[3],
		   		                       'express_account'=>$delivery_type_4_array[4]);
    		  }
    		if($error){
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'添加线下订单失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			//事务开始
    			$this->_inqorderService->beginTransaction();
    		try {
    			//添加询价单
    			$inqtime     = time()-(60*60*24)-rand(60*60*1,60*60*4);
    			$inqbacktime = $inqtime+rand(60*60*1,60*60*4);
    			$inqModer         = new Icwebadmin_Model_DbTable_Inquiry();
    			$inqdetModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
    			$data = array('uid'=>$uid,
    					'back_inquiry'=>1,
    					'currency'=>$currency,
    					'delivery'=>$delivery,
    					'status'=>1,
    					'created'=>$inqtime,
    					'modified'=>$inqbacktime,
    					'modified_by'=>$_SESSION['staff_sess']['staff_id'],
    					'staffid'=>$_SESSION['staff_sess']['staff_id']
    			);
    			$new_inqid = $inqModer->addData($data);
    			if(!$new_inqid){
    				$this->_inqorderService->rollBack();
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$new_inqid,'temp4'=>'添加询价单失败，inqid为空'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'添加询价单失败'));
    				exit;
    			}
    			//更新询价编号
    			$inqModer->update(array("inq_number"=>'RFQ'.$new_inqid.substr(microtime(),2,4)), "id='{$new_inqid}'");
    			for($i=0;$i<count($part_no);$i++){
    				$part_no_s = trim($part_no[$i]);
    				$part_id = $this->_productService->getPid($part_no_s);
    				$datas[] = array('inq_id'=>$new_inqid,
    						'part_id'   =>$part_id,
    						'part_no'   =>$part_no_s,
    						'brand_name'=>$oa_productline[$i],
    						'number'    =>$buy_number[$i],
    						'pmpq'      =>$buy_number[$i],
    						'result_price'=>$price[$i]
    				);
    			}
    			$inqdetModer->addDatas($datas);
    			
    			//订单号
    			$ordertime = time();
    			$salesnumber = '9'.(intval(date('Y'))%10).date('m').date('d').substr(microtime(),2,4).'-'.substr(time(),-5);
    			//添加询价订单
    			$inqhistory = $this->_inqservice->getInquiryHistory($new_inqid);
    			$inqinfo    = $inqhistory[0];
    			$datas = array();$part_nos = '';
    			$total = $quantitys = 0;
    			foreach($inqinfo['detaile'] as $i=>$detaile){
    				//订单
    				$part_no = $detaile['part_no']?$detaile['part_no']:$detaile['part_no2'];
    				$itemdata[]=array('salesnumber'=>$salesnumber,
    								'prod_id'  =>$detaile['part_id'],
    								'inqdet_id'=>$detaile['id'],
    								'part_no'  =>$part_no,
    						        'customer_material'=>$customer_material[$i],
    								'brand'    =>$detaile['brand']?$detaile['brand']:$detaile['brand_name'],
    								'buynum'   =>$detaile['pmpq'],
    								'buyprice' =>$detaile['result_price'],
    								'needs_time'=>($needs_time[$i]?strtotime($needs_time[$i]):0),
    								'lead_time'=>$standard_delivery[$i],
    								'remark'   =>$remark[$i],
    								'created'  =>time());
    							
    					if($i==0) $part_nos = $part_no;
    					else $part_nos .= ','.$part_no;
    					$quantitys += $detaile['pmpq'];
    					$total += ($detaile['pmpq']*$detaile['result_price']);
    			}
    			//加上运费
    			$total +=$freight;
    			//是否需要发票
    			$incoice_id = 0;
    			if($incoice_type){
    				$invoiceModel = new Default_Model_DbTable_Invoice();
    				$data = array('uid'=>$uid,
    						'type'=>$incoice_type,
    						'name'=>'',
    						'identifier'=>'',
    						'regaddress'=>'',
    						'regphone'=>'',
    						'bank'=>'',
    						'account'=>'',
    						'contype'=>'',
    						'created'=>time(),
    						'modified'=>time());
    				$incoice_id = $invoiceModel->addData($data);
    			}
    			//添加订单
    			//记录收货地址
    			if($addre){
    			  $soaddModel  = new Default_Model_DbTable_OrderAddress();
    			  $soadd_data = array('uid'=>$uid,
    					'salesnumber'=>$salesnumber,
    					'name'=>$addre['name'],
    					'companyname'=>$addre['companyname'],
    					'province'=>$addre['province'],
    					'city'=>$addre['city'],
    					'area'=>$addre['area'],
    					'address'=>$addre['address'],
    					'mobile'=>$addre['mobile'],
    					'tel'=>$addre['tel'],
    					'zipcode'=>$addre['zipcode'],
    			  		'warehousing'=>$addre['warehousing'],
    			  		'express_name'=>$addre['express_name'],
    			  		'express_account'=>$addre['express_account'],
    					'created'=>time());
    			   $soaddid = $soaddModel->addData($soadd_data);
    			}
    			//记录发票地址
    			if(!empty($invoiceaddressradio)){
    				if($addid != $invoiceaddressradio && $invoiceaddre){
    					$invoice_data = array('uid'=>$uid,
    							'salesnumber'=>$salesnumber,
    							'name'=>$invoiceaddre['name'],
    							'companyname'=>$invoiceaddre['companyname'],
    							'province'=>$invoiceaddre['province'],
    							'city'=>$invoiceaddre['city'],
    							'area'=>$invoiceaddre['area'],
    							'address'=>$invoiceaddre['address'],
    							'mobile'=>$invoiceaddre['mobile'],
    							'tel'=>$invoiceaddre['tel'],
    							'zipcode'=>$invoiceaddre['zipcode'],
    							'created'=>time());
    					$invoiceaddid = $soaddModel->addData($invoice_data);
    				}elseif($soaddid){
    					$invoiceaddid = $soaddid;
    				}
    			}
    			//订单
    			
    			if(in_array($formData['paytype'],array('transfer_100','transfer_pec','down_payment')))
    				 $paytype = 'transfer';
    			else $paytype = $formData['paytype'];
    			if($formData['paytype']=='transfer_pec'){
    				$down_payment_total = $formData['percentage']*$total*0.01;
    			}elseif($formData['paytype']=='down_payment'){
    				$down_payment_total = $formData['down_payment'];
    			}else $down_payment_total = $total;
    			$modified = $ordertime+rand(60*60*1,60*60*4);
    			$orderdata = array('uid'=>$uid,
    					'salesnumber'   =>$salesnumber,
    					'ship_salesnumber'=>$shipoder_salesnumber,
    					'back_order'    =>1,
    					'inquiry_id'    =>$new_inqid,
    					'addressid'     =>$soaddid,
    					'percentage'    =>$percentage,
    					'invoiceid'     =>$incoice_id,
    					'invoiceaddress'=>$invoiceaddid,
    					'paytype'       =>$paytype,
    					'paper_contract'=>1,
    					'paytype_other' =>$paytype_other,
    					'delivery_type' =>$delivery_type,
    					'exp_paytype'   =>$exp_paytype,
    					'quantitys'     =>$quantitys,
    					'items'         =>count($inqinfo['part_no']),
    					'down_payment'  =>$down_payment_total,
    					'freight'       =>$freight,
    					'total'         =>$total,
    					'shipments'     =>"order",
    					'delivery_place'=>$delivery,
    					'currency'      =>$currency,
    					'consignee'     =>$addre['name'],
    					'partnos'       =>$part_nos,
    					'status'        =>301,
    					'back_status'   =>202,
    					'available'     =>1,
    					'ip'=>$this->fun->getIp(),
    					'created'=>time(),
    					'modified'=>$modified);
    			//print_r($orderdata);exit;
    			$this->_inqsalesorderModel= new Default_Model_DbTable_InqSalesOrder();
    			$orderid = $this->_inqsalesorderModel->addData($orderdata);
    			$this->_salesproductModel = new Default_Model_DbTable_SalesProduct();
    			$this->_salesproductModel->addDatas($itemdata);
    			//更新询价订单
    			$inqModer = new Default_Model_DbTable_Inquiry();
    			$inqModer->updateById(array('status'=>6,'salesnumber'=>$salesnumber), $new_inqid);
    			$this->_inqorderService->commit();
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'添加线下订单成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加线下订单成功','salesnumber'=>$salesnumber));
    			exit;
    		} catch (Exception $e) {
    			$this->_inqorderService->rollBack();
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'添加线下订单失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'很抱歉，系统出错'));
    			exit;
    		}
    	  }
    	}
    }
    /*
     * 添加订单
    */
    public function addinqordersapAction(){
    	exit;
    	$exec_time = 7000; //sec
    	set_time_limit($exec_time);
    	$inputFileName = "sap2.xlsx";
    	$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
    	$objWorksheet = $objPHPExcel->getActiveSheet();
    	$tmp_arr   = $objPHPExcel->getActiveSheet()->toArray();
    	
    	$this->view->saporder = array();
    	$upModel = new Icwebadmin_Service_UserService();
    		
    	foreach($tmp_arr as $key=>$v){
    		if($key>0){
    			$this->view->saporder[$v[0]][] = $v;
    		}
    	}
		echo '<table>';
    	foreach($this->view->saporder as $sapid=>$formData){
    		$addtime = strtotime($formData[0][3])+rand(28800,61200);
    		$uid        = trim($formData[0][11]);
    		$currency   = trim($formData[0][2]);
    		$delivery   = trim($formData[0][8]);
    		$delivery_type = $formData[0][8]=='HK'?2:1;
    		$freight = 0;
    		$exp_paytype = 1;
    		$addid = 0;
    		$shipoder_salesnumber = 0;
    		$paytype = 'mts';
    		$percentage = 100;
    		$down_payment = 0;
    		$paytype_other = '';
    		$incoice_type = 2;
    		$invoiceaddressradio = 0;
    		$delivery_type_4_array = array();
    		$part_no = $oa_productline = $customer_material =$price=$buy_number=$needs_time=$standard_delivery=$remark=array();
    		$havespart = array();
    		foreach($formData as $p){
    			if(!in_array($p[7],$havespart)){
    			$havespart[] = $p[7];
    		    $part_no[] = $p[7];
    		    $oa_productline[] = $p[9];
    		    $price[] = $p[5];
    		    $buy_number[] = $p[4];
    			}
    		}
    		
    		$error = 0;$message='';
    		$userModel = new Default_Model_DbTable_User();

    		$addre = $invoiceaddre = array();
    		
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			//事务开始
    			$this->_inqorderService->beginTransaction();
    			//try {
    				//添加询价单
    				$inqtime     = $addtime-(60*60*24)-rand(60*60*1,60*60*4);
    				$inqbacktime = $inqtime+rand(60*60*1,60*60*4);
    				$inqModer         = new Icwebadmin_Model_DbTable_Inquiry();
    				$inqdetModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
    				$data = array('uid'=>$uid,
    						'back_inquiry'=>1,
    						'currency'=>$currency,
    						'delivery'=>$delivery,
    						'status'=>1,
    						'created'=>$inqtime,
    						'modified'=>$inqbacktime
    				);
    				$new_inqid = $inqModer->addData($data);
    				if(!$new_inqid){
    					$this->_inqorderService->rollBack();
    					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'添加询价单失败'));
    					exit;
    				}
    				//更新询价编号
    				$inqModer->update(array("inq_number"=>'RFQ'.$new_inqid.substr(microtime(),2,4)), "id='{$new_inqid}'");
    				$datas = array();
    				for($i=0;$i<count($part_no);$i++){
    					$part_id = $this->_productService->getPid($part_no[$i]);
    					$datas[] = array('inq_id'=>$new_inqid,
    							'part_id'   =>$part_id,
    							'part_no'   =>$part_no[$i],
    							'brand_name'=>$oa_productline[$i],
    							'number'    =>$buy_number[$i],
    							'pmpq'      =>$buy_number[$i],
    							'result_price'=>$price[$i]
    					);
    				}
    				$inqdetModer->addDatas($datas);
    				 
    				//订单号
    				$ordertime = $addtime;
    				$salesnumber = '9'.(intval(date('Y',$ordertime))%10).date('m',$ordertime).date('d',$ordertime).substr(microtime(),2,4).'-'.substr($ordertime,-5);
    				
    				foreach($formData as $key=>$v){
    					echo "<tr><td>$v[0]</td><td>$salesnumber</td></tr>";
    				}
    				
    				//添加询价订单
    				$inqhistory = $this->_inqservice->getInquiryHistory($new_inqid);
    				$inqinfo    = $inqhistory[0];
    				$datas = array();$part_nos = '';
    				$total = $quantitys = 0;
    				$itemdata=array();
    				$havepart_no = array();
    				foreach($inqinfo['detaile'] as $i=>$detaile){
    					if(!in_array($part_no,$havepart_no)){
    					//订单
    					$part_no = $detaile['part_no']?$detaile['part_no']:$detaile['part_no2'];
    					$havepart_no[] = $part_no;
    					$itemdata[]=array('salesnumber'=>$salesnumber,
    							'prod_id'  =>$detaile['part_id'],
    							'inqdet_id'=>$detaile['id'],
    							'part_no'  =>$part_no,
    							'customer_material'=>$customer_material[$i],
    							'brand'    =>$detaile['brand']?$detaile['brand']:$detaile['brand_name'],
    							'buynum'   =>$detaile['pmpq'],
    							'buyprice' =>$detaile['result_price'],
    							'needs_time'=>($needs_time[$i]?strtotime($needs_time[$i]):0),
    							'lead_time'=>$standard_delivery[$i],
    							'remark'   =>$remark[$i],
    							'created'  =>$ordertime);
    						
    					if($i==0) $part_nos = $part_no;
    					else $part_nos .= ','.$part_no;
    					$quantitys += $detaile['pmpq'];
    					$total += ($detaile['pmpq']*$detaile['result_price']);
    					}
    				}
    				//加上运费
    				$total +=$freight;
    				//是否需要发票
    				$incoice_id = $soaddid = $invoiceaddid = 0;
    				
    				
    				
    				//订单
    				 
    				$down_payment_total = $total;
    				$modified = $ordertime+rand(60*60*1,60*60*4);
    				$orderdata = array('uid'=>$uid,
    						'salesnumber'   =>$salesnumber,
    						'son_salesnumber'=>$sapid,
    						'back_order'    =>1,
    						'inquiry_id'    =>$new_inqid,
    						'addressid'     =>$soaddid,
    						'percentage'    =>$percentage,
    						'invoiceid'     =>$incoice_id,
    						'invoiceaddress'=>$invoiceaddid,
    						'paytype'       =>$paytype,
    						'paper_contract'=>1,
    						'paytype_other' =>$paytype_other,
    						'delivery_type' =>$delivery_type,
    						'exp_paytype'   =>$exp_paytype,
    						'quantitys'     =>$quantitys,
    						'items'         =>count($inqinfo['part_no']),
    						'down_payment'  =>$down_payment_total,
    						'freight'       =>$freight,
    						'total'         =>$total,
    						'shipments'     =>"order",
    						'delivery_place'=>$delivery,
    						'currency'      =>$currency,
    						'consignee'     =>$addre['name'],
    						'partnos'       =>$part_nos,
    						'status'        =>301,
    						'back_status'   =>202,
    						'available'     =>1,
    						'ip'=>$this->fun->getIp(),
    						'created'=>$ordertime,
    						'modified'=>$modified);
    				//print_r($orderdata);exit;
    				$this->_inqsalesorderModel= new Default_Model_DbTable_InqSalesOrder();
    				$orderid = $this->_inqsalesorderModel->addData($orderdata);
    				$this->_salesproductModel = new Default_Model_DbTable_SalesProduct();
    				$this->_salesproductModel->addDatas($itemdata);
    				//更新询价订单
    				$inqModer = new Default_Model_DbTable_Inquiry();
    				$inqModer->updateById(array('status'=>6,'salesnumber'=>$salesnumber), $new_inqid);
    				$this->_inqorderService->commit();
    				
    			//} catch (Exception $e) {
    			//	echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'很抱歉，系统出错'));
    			//	exit;
    			//}
    		}
    	}echo '</table>';
    }
    /*
     * 订单成功提示
    */
    public function successAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->sodata = $this->_inqorderService->getSoInfo($_GET['salesnumber']);
    }
    /*
     * 订单成功提示
    */
    public function editsuccessAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->sodata = $this->_inqorderService->getSoInfo($_GET['salesnumber']);
    }
    /**
     * 取消订单
     */
    public function cancelorderAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber  = $_GET['sonum'];
    	$this->view->sonid        = $_GET['sonid'];
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$admin_notes = $formData['admin_notes'];
    		$re = $this->_inqorderService->updateByNum(array('status'=>'401','admin_notes'=>$admin_notes,'modified'=>time()), $salesnumber);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'取消线下订单成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'操作成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'取消线下订单失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'操作失败。'));
    			exit;
    		}
    	
    	}
    }
    /**
     * 编辑订单
     */
    public function editAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
    	//订单详细
    	$soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    	if(!$soarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	//产品详细
    	$soarray['pordarr'] = $this->_inqsoService->getSoPart($salesnumber);
    	if(!$soarray['pordarr']){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	//用户资料
    	$userService = new Icwebadmin_Service_UserService();
    	$this->view->user = $userService->getUserProfile($soarray['uid']);
    	if(!$this->view->user){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
		$this->view->soarray = $soarray;
    }
    /**
     * 修改结算货币
     */
    public function currencyAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$currency   = $formData['currency'];
    		$delivery   = $formData['delivery'];
    		$re = $this->_inqorderService->updateByNum(array('currency'=>$currency,'delivery_place'=>$delivery), $salesnumber);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改结算货币成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改结算货币失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'修改失败。'));
    			exit;
    		}
    		 
    	}
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
    	$soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    	if(!$soarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	$this->view->soarray = $soarray;
    }
    /**
     * 修改配送方式
     */
    public function deliverytypeAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber   = $formData['salesnumber'];
    		$soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    		$delivery_type = $formData['delivery_type'];
    		$exp_paytype   = $formData['exp_paytype'];
    		$freight       = $formData['freight'];
    		
    		$total = $soarray["total"]-$soarray["freight"]+$freight;
    		$re = $this->_inqorderService->updateByNum(array('delivery_type'=>$delivery_type,
    				'freight'=>$freight,
    				'down_payment'=>$total,
    				'total'=>$total,
    				'exp_paytype'=>$exp_paytype), $salesnumber);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改配送方成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改配送方失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'修改失败。'));
    			exit;
    		}
    		 
    	}
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
        $soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    	if(!$soarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	$this->view->soarray = $soarray;
    }
    /**
     * 修改收货地址
     */
    public function orderaddressAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber   = $formData['salesnumber'];
    		$id      = $formData['id'];
    		$updata = array();
    		$updata['name'] = $formData['shrname'];
    		$updata['province']= $formData['province'];
    		$updata['city']    = $formData['city'];
    		$updata['area']    = $formData['area'];
    		$updata['address'] = $formData['address'];
    		$updata['zipcode'] = $formData['zipcode'];
    		$updata['companyname']= $formData['companyname'];
    		$updata['warehousing']= $formData['warehousing'];
    		$updata['mobile']  = $formData['mobile'];
    		$updata['tel']     = $formData['tel'];
    		$this->_soaddressModel = new Icwebadmin_Model_DbTable_OrderAddress();
    		$re = $this->_soaddressModel->updateById($updata, $id);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改收货信息成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改收货信息失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'修改失败。'));
    			exit;
    		}
    		 
    	}
    	$addservice = new Icwebadmin_Service_AddressService();
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
        $this->view->addarray =$addservice->getSoAddressBySalesnumber($salesnumber);
    	if(!$this->view->addarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    }
    /**
     * 修改结算方式
     */
    public function paytypeAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		$soarray = $this->_inqsoService->getSoInfo($salesnumber);
    		if(in_array($formData['paytype'],array('transfer_100','transfer_pec','down_payment')))
    			 $paytype = 'transfer';
    		else $paytype = $formData['paytype'];
    		if($formData['paytype']=='transfer_pec'){
    			$down_payment_total = $formData['percentage']*$soarray['total']*0.01;
    		}elseif($formData['paytype']=='down_payment'){
    			$down_payment_total = $formData['down_payment'];
    		}else $down_payment_total = $soarray['total'];
    		$orderdata = array(
    				'percentage'    =>$formData['percentage'],
    				'paytype'       =>$paytype,
    				'down_payment'=>$down_payment_total,
    				'paytype_other' =>$formData['paytype_other']);
    		$re = $this->_inqorderService->updateByNum($orderdata, $salesnumber);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改结算方式成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改结算方式失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'修改失败。'));
    			exit;
    		}
    		 
    	}
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
    	$soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    	if(!$soarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	$this->view->soarray = $soarray;
    }
    /**
     * 修改发票
     */
    public function invoiceAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber   = $formData['salesnumber'];
    		$invoicetype   = $formData['invoicetype'];
    		if($invoicetype == '1') $invoiceid = '1';
    		elseif($invoicetype == '2') $invoiceid = '2';
    		else $invoiceid = '';
    		$re = $this->_inqorderService->updateByNum(array('invoiceid'=>$invoiceid), $salesnumber);
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改发票成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功。'));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改发票失败','description'=>'系统出错'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'修改失败。'));
    			exit;
    		}
    		 
    	}
    	$salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
    	$soarray     = $this->_inqsoService->getSoInfo($salesnumber);
    	if(!$soarray){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	$this->view->soarray = $soarray;
    }
    /**
     * 修改产品
     */
    public function productAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$salesnumber   = $formData['salesnumber'];
    		$this->_inqSalesOrder = new Icwebadmin_Model_DbTable_InqSalesOrder();
    		$this->_salesProduct = new Icwebadmin_Model_DbTable_SalesProduct();
    		//事务开始
    		$this->_inqorderService->beginTransaction();
    		try {
    		$part_nos = '';
    		$total = $quantitys = 0;
    		$itemdata = array();
    		
    		foreach($formData['part_no'] as $i=>$part_no){
    			$part_id = $this->_productService->getPid($part_no);
    			$itemdata[]=array('salesnumber'=>$salesnumber,
    					'prod_id'  =>$part_id?$part_id:0,
    					'part_no'  =>$part_no,
    					'customer_material'=>$formData['customer_material'][$i],
    					'brand'    =>$formData['oa_productline'][$i],
    					'buynum'   =>$formData['buy_number'][$i],
    					'buyprice' =>$formData['price'][$i],
    					'needs_time'=>($formData['needs_time'][$i]?strtotime($formData['needs_time'][$i]):0),
    					'lead_time'=>$formData['standard_delivery'][$i],
    					'remark'   =>$formData['remark'][$i],
    					'created'  =>time());
    				
    			if($i==0) $part_nos = $part_no;
    			else $part_nos .= ','.$part_no;
    			$quantitys += $formData['buy_number'][$i];
    			$total += ($formData['buy_number'][$i]*$formData['price'][$i]);
    		}
    		
    		//更新salesnumber为空
    		$this->_salesProduct->update(array('salesnumber'=>''), "salesnumber='{$salesnumber}'");
    		foreach($itemdata as $i=>$item){
    			//更新$formData['id']
    			if($formData['id'][$i]){
    				$this->_salesProduct->update($item, "id='".$formData['id'][$i]."'");
    			}else{//添加
    				$this->_salesProduct->addData($item);
    			}
    		}
    		//更新订单详细
    	    $soarray = $this->_inqsoService->getSoInfo($salesnumber);
    	    if($soarray['percentage']) $down_payment = $soarray['percentage']*$total*0.01;
    	    else $down_payment = $soarray['down_payment'];
    	    $updata = array('down_payment'=>$down_payment,
    	    		        'total'=>$total,
    	    		        'items'=>count($itemdata),
    	    		        'quantitys'=>$quantitys,
    	    		        'partnos'=>$part_nos);
    	    $this->_inqSalesOrder->update($updata, "salesnumber='{$salesnumber}'");
    	  
    	    $this->_inqorderService->commit();
    	    $this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'修改产品成功'));
    	    echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改产品成功'));
    	    exit;
    	   
    	    } catch (Exception $e) {
    	    	$this->_inqorderService->rollBack();
    	    	$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'修改产品失败','description'=>$e->getMessage()));
    	    	echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'很抱歉，系统出错'));
    	    	exit;
    	    }
    	}
    	$this->view->salesnumber = $salesnumber = $this->fun->decryptVerification($this->_getParam('k'));
    	//产品详细
    	$this->view->pordarr = $this->_inqsoService->getSoPart($salesnumber);
    	if(!$this->view->pordarr){
    		$this->_redirect('/icwebadmin/OrUnso/list');
    	}
    	//OA产品线
    	$oa_sellline_model = new Icwebadmin_Model_DbTable_Model('oa_sellline');
    	$this->view->oaproductline = $oa_sellline_model->getAllByWhere("type='line'"," oa_name ASC");
    }
    /**
     * 修改订单，更新pdf
     */
    public function editorderAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    	   //订单详情
    	   $salesnumber = $formData['salesnumber'];
    	  //订单详细
    	  $soarray = $this->_inqsoService->getSoInfo($salesnumber);
    	  //产品详细
    	  $soarray['pordarr'] = $this->_inqsoService->getSoPart($salesnumber);
    	  if(empty($soarray)) {
    	       echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'订单信息为空'));
    		   exit;
    	  }
    	  //用户资料
    	  $userService = new Icwebadmin_Service_UserService();
    	  $userinfo = $userService->getUserProfile($soarray['uid']);
    	  if(empty($userinfo)) {
    	       echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'用户为空'));
    		   exit;
    	  }
    	  //国内合同
    	  $currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
          $unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
    	  $definqorderService = new Default_Service_InqOrderService();
    		if($soarray['delivery_place'] == 'SZ'){
    			$return=$definqorderService->szContract_Line($soarray,$userinfo,$currencyArr,$unit);
    		}elseif($soarray['delivery_place'] == 'HK'){
    			$return=$definqorderService->hkContract_Line($soarray,$userinfo,$currencyArr,$unit);
    		}
    		if($return){
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'保存订单修改成功','salesnumber'=>$salesnumber));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'保存订单修改失败','salesnumber'=>$salesnumber));
    			exit;
    		}
    	}
    }
}