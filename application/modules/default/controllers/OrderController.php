<?php
require_once 'Iceaclib/common/fpdf/pdfclass.php';
require_once 'Iceaclib/default/cart.php';
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';
class OrderController extends Zend_Controller_Action
{
	private $_orderService;
	private $_defaultlogService;
	private $_productService;
    public function init()
    {
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$_SESSION['menu'] = 'order';
    	//获取购物车寄存，要在$this->cart = new Cart()前
    	$cartService = new Default_Service_CartService();
    	$cartService->getCartDeposit();
    	
        /* Initialize action controller here */
    	$this->filter = new MyFilter();
    	$this->cart   = new Cart();
    	$this->userprofile = new Default_Model_DbTable_UserProfile();
    	if(!isset($_SESSION['order_sess']))
    	{
    		$order_sess = new Zend_Session_Namespace('order_sess');
    		$order_sess->addressid = 0;
    		$order_sess->invoiceid = 0;
    	}
    	$this->fun = $this->view->fun = new MyFun();
    	
    	$this->config = Zend_Registry::get('config');
    	$this->view->freetotl = $this->config->cost->online_free;
    	$this->view->gdfreight = $this->config->cost->online_gd_freight;
    	$this->view->otherfreight = $this->config->cost->online_other_freight;
    	//满500元免，广东省（440000）10，其余30。运费及包装费
    	$this->gdfreight    = $this->config->cost->online_gd_freight;
    	$this->otherfreight = $this->config->cost->online_other_freight;
    	$this->usdfreight    = $this->config->cost->inquiry_gd_freight1_USD;
    	$this->tenArr = array('440000');
    	$this->_orderService = new Default_Service_OrderService();
    	$this->_defaultlogService = new Default_Service_DefaultlogService();
    	$this->_productService = new Default_Service_ProductService();
    	//产品目录
    	$prodService = new Default_Service_ProductService();
    	$prodCategory = $prodService->getProdCategory();
    	$this->view->first = $prodCategory['first'];
    	$this->view->second = $prodCategory['second'];
    	$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		$this->_fun =$this->view->fun= new MyFun();
    }

    public function indexAction()
    {   
    	$bppService = new Default_Service_BppService();
    	//取货地址
    	$this->config = Zend_Registry::get('config');
    	$this->view->delivery_add_sz = $this->config->order->delivery_add_sz;
    	$this->view->delivery_tel_sz = $this->config->order->delivery_tel_sz;
    	$this->view->delivery_workdate_sz = $this->config->order->delivery_workdate_sz;
    	$this->view->delivery_des_sz = $this->config->order->delivery_des_sz;
    	
    	$this->view->delivery_add_hk = $this->config->order->delivery_add_hk;
    	$this->view->delivery_tel_hk = $this->config->order->delivery_tel_hk;
    	$this->view->delivery_workdate_hk = $this->config->order->delivery_workdate_hk;
    	$this->view->delivery_des_hk = $this->config->order->delivery_des_hk;
    	$this->view->freermb = $this->config->cost->inquiry_free_RMB;
    	$this->view->freeusd = $this->config->cost->inquiry_free_USD;
    	
    	if($this->_getParam('key') != md5(session_id())) {
    		echo 'dd';exit;
    		$this->_redirect('/cart');
    	}
    	$this->view->delivery = $delivery = $this->fun->decrypt_aes($this->_getParam('items'));
    	//收货地址
    	$addressModel = new Default_Model_DbTable_Address();
    	$sqlstr ="SELECT a.id,a.uid,a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.default,a.warehousing,
    	p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area 
    	FROM address as a LEFT JOIN province as p 
        ON a.province=p.provinceid 
        LEFT JOIN city as c 
        ON a.city=c.cityid
        LEFT JOIN area as e 
        ON a.area = e.areaid
    	WHERE a.uid=:uidtmp AND a.status=1 AND a.name!=''
    	ORDER BY `default` DESC";
    	$this->view->addressArr = $arr = $addressModel->getBySql($sqlstr, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	if(!empty($arr))
    	{
    		$this->view->addressFirst = $arr[0];
    		foreach($arr as $arrtmp){
    			if($_SESSION['order_sess']['addressid'] == $arrtmp['id']) 
    				$this->view->addressFirst = $arrtmp;
    		}
    	}else $this->view->addressFirst = array();
    	//处理香港默认地址
    	if($delivery=='HK'){
    		if($this->view->addressFirst['provinceid']!=810000){
    			$this->view->addressFirst = array();
    			foreach($arr as $arrtmp){
    				if($arrtmp['provinceid'] == 810000)
    					$this->view->addressFirst = $arrtmp;
    			}
    			if(empty($this->view->addressFirst)){
    				$this->view->addressFirst['provinceid'] = 810000;
    			    $this->view->addressFirst['cityid']=810100;
    			    $this->view->addressFirst['areaid']=810101;
    			}
    		}
    	}
    	if($delivery=='SZ'){
    		if($this->view->addressFirst['provinceid']==810000){
    			foreach($arr as $arrtmp){
    				if($arrtmp['provinceid'] != 810000)
    					$this->view->addressFirst = $arrtmp;
    			}
    		}
    	}
    	//获取购物车
    	$cartall = $this->cart->contents_by_delivery();
    	$this->view->items = $cartall[$delivery];
    	if(empty($this->view->items))
    	{
    		$this->_redirect('/');
    	}else{
    		$total=0; $this->view->freight=0;
    		//代购运费
    		$daigou = 0;
    		foreach($this->view->items as $item){
    			$total +=$item['qty']*$item['byprice'];
    			//查询代购费
    			if($item['options']['bpp_stock_id']){
    				if($delivery=='SZ') $currency = "RMB"; else $currency = "USD";
    				$daigou += $bppService->daigou($item['options']['bpp_stock_id'],$currency,$total);
    			}
    		}
    		if($delivery=='SZ'){
    			if($total < $this->view->freermb){
    				//满500元免，广东省（440000）10，其余30。运费及包装费
    				if(in_array($this->view->addressFirst['provinceid'],$this->tenArr))
    					$this->view->freight = $daigou?$daigou:$this->gdfreight;
    				else $this->view->freight = $daigou?$daigou:$this->otherfreight;
    			}
    		}
    		if($delivery=='HK'){
    			if($total < $this->view->freeusd){
    				$this->view->freight = $daigou?$daigou:$this->usdfreight;
    				$this->view->packageprice = 0;//$this->view->freight/2;
    			}
    		}
    	}
    	//发票
    	if($delivery=='SZ'){
    	   $_SESSION['NeedInvoice'] = 'yes';//默认需要发票
    	}
    	$invoiceModel = new Default_Model_DbTable_Invoice();
    	$invarr = $invoiceModel->getAllByWhere("uid='".$_SESSION['userInfo']['uidSession']."' AND name!=''","created DESC");
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
    	//货币单位
    	$this->view->total = $total;
    	$this->view->unit = $this->view->items[0]['unit'];

    	//快递商
    	$addService = new Default_Service_AddressService();
    	$this->view->expressadd = $addService->getExpressAddress();
    }
    public function changeaddressAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$addressModel = new Default_Model_DbTable_Address();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
   
    			$shrname = $this->filter->pregHtmlSql($formData['shrname']);
    			$province= $this->filter->pregHtmlSql($formData['province']);
    			$city    = $this->filter->pregHtmlSql($formData['city']);
    			$area    = $this->filter->pregHtmlSql($formData['area']);
    			$address = $this->filter->pregHtmlSql($formData['address']);
    			$zipcode = $this->filter->pregHtmlSql($formData['zipcode']);
    			$companyname  = $this->filter->pregHtmlSql($formData['companyname']);
    			$warehousing  = $this->filter->pregHtmlSql($formData['warehousing']);
    			$mobile  = $this->filter->pregHtmlSql($formData['mobile']);
    			$tel     = $this->filter->pregHtmlSql($formData['tel']);
    			$default  = (int)$formData['default']==''?0:$formData['default'];
    			$error = 0;$message='';
    			if(!$shrname){
    				$message ='请填写收 货 人。';
    				$error++;
    			}
    	        if(!$province){
    				$message ='请选择省。';
    				$error++;
    			}
    			if(!$city){
    				$message ='请选择市。';
    				$error++;
    			}if(!$area){
    				$message ='请选择区。';
    				$error++;
    			}
    			if(!$address){
    				$message ='请填写详细地址。';
    				$error++;
    			}
    		if($formData['tmp']=='add')
    		{
    			//最多添加20个地址，防止攻击
    			$addService = new Default_Service_AddressService();
    			$addre = $addService->checkNum(20);
    			if($addre){
    				$message ='最多保存20个地址';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    		    exit;
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
    				$id = $addressModel->addData($data);
    				$_SESSION['order_sess']['addressid'] = $id;
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"编辑收货地址成功","addid"=>$id));
    				exit;
    			}elseif($formData['tmp']=='edit')
    			{
    				$id = (int)($formData['addid']);
    				$_SESSION['order_sess']['addressid'] = $id;
    				$addressModel->update($data,"id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"编辑收货地址成功","addid"=>$id));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'tmp参数错误'));
    				exit;
    			}
    		}
    	}
    }
    /**
     * 改变发票
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    public function invoiceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$invoiceModel = new Default_Model_DbTable_Invoice();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$invoicetype = (int)($formData['invoicetype']);
    		$_SESSION['NeedInvoice'] = $needinvoice = $this->filter->pregHtmlSql($formData['needinvoice']);
    		$error = 0;$message='';
    		if($invoicetype==1)
    		{
    			$taitouname  = $this->filter->pregHtmlSql($formData['taitouname']);
    		    $contype     = (int)($formData['contype']);
    		    if(!$taitouname){
    		 	   $message ='请填写发票抬头。';
    			   $error++;
    		    }
    		    if($error){
    		    	echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    		    	exit;
    		    }else{
    		    	$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
    		    			'type'=>$invoicetype,
    		    			'name'=>$taitouname,
    		    			'contype'=>$contype,
    		    			'created'=>time(),
    		    			'modified'=>time());
    		    	$id = $invoiceModel->addData($data);
    		    	$_SESSION['order_sess']['invoiceid'] = $id;
    		    	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"保存成功","invid"=>$id));
    		    	exit;
    		    }
    		}
    		if($invoicetype==2)
    		{
    			$taitouname  = $this->filter->pregHtmlSql($formData['name']);
    			$identifier  = $this->filter->pregHtmlSql($formData['identifier']);
    			$regaddress  = $this->filter->pregHtmlSql($formData['regaddress']);
    			$regphone    = $this->filter->pregHtmlSql($formData['regphone']);
    			$bank        = $this->filter->pregHtmlSql($formData['bank']);
    			$account     = $this->filter->pregHtmlSql($formData['account']);
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
    				if($formData['annex1'] || $formData['annex2']){
    				   $this->userprofile = new Default_Model_DbTable_UserProfile();
    				   $updata = array();
    				   if($formData['annex1']){
    				   	  $updata['annex1']=$formData['annex1'];
    				   }
    				   if($formData['annex2']){
    				   	  $updata['annex2']=$formData['annex2'];
    				   }
    				   if($updata)
    				   $this->userprofile->updateByUid($updata, $_SESSION['userInfo']['uidSession']);
    				}
    				$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
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
    				$_SESSION['order_sess']['invoiceid'] = $id;
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"保存成功","invid"=>$id));
    				exit;
    			}
    		}
    	}else $this->_redirect('/');
    }
    /**
     * 提交订单处理
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    public function handleAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		
    		//取货地址
    		$this->config = Zend_Registry::get('config');
    		$this->freermb = $this->config->cost->inquiry_free_RMB;
    		$this->freeusd = $this->config->cost->inquiry_free_USD;
    		
    		$delivery       = ($formData['delivery']);
    		$addressid      = (int)($formData['addressid']);
    		$delivery_type  = (int)($formData['delivery_type']);
    		$delivery_type_4_array = $formData['delivery_type_4_array'];
    		$invoiceid      = (int)($formData['invoiceid']);
    		$invoiceaddress = (int)($formData['invoiceaddress']);
    		$paymenttype = $this->filter->pregHtmlSql($formData['paymenttype']);
    		$needinvoice = $this->filter->pregHtmlSql($formData['needinvoice']);
    		$remark      =  $this->filter->pregHtmlSql($formData['remark']);
    		$customer_material = $formData['customer_material'];
    		$error = 0;$message='';
    		$addressModel = new Default_Model_DbTable_Address();
    		$invoiceModel = new Default_Model_DbTable_Invoice();
    		$addre = $addressModel->getRowByWhere("id='{$addressid}'");
    		
    		
    		//如果发票地址不等与收货地址
    		$invoiceaddre = array();
    		if($addressid != $invoiceaddress)
    		{
    			$invoiceaddre = $addressModel->getRowByWhere("id='{$invoiceaddress}'");
    		}
    		if(!in_array($delivery,array('HK','SZ'))){
    			$message ='配送方式错误。';
    			$error++;
    		}
    		if($delivery=='HK'){
    			if(!in_array($paymenttype,array('transfer','coupon'))){
    				$message ='支付方式错误。';
    				$error++;
    			}
    		}
    		if(!empty($needinvoice)){
    		  $invre = $invoiceModel->getRowByWhere("id='{$invoiceid}'");
    		  if(empty($invre)){
    			$message ='发票信息不存在，请重新选择。';
    			$error++;
    		  }
    		}
    		if($delivery_type == 1 || $delivery_type == 2){
    		  if(empty($addre)){
    			 $message ='收货人地址不存在，请重新选择。';
    			 $error++;
    		  }
    		}elseif($delivery_type==4){
    			//客户提供账号
    			foreach($delivery_type_4_array as $dv){
    				if(!$dv){
    					$message ='请填写客户提供快递账号信息';
    					$error++;
    				}
    			}
    			$addre  = array('name'=>$delivery_type_4_array[1],
    					'companyname'    =>$delivery_type_4_array[5],
    					'address'        =>$delivery_type_4_array[0],
    					'tel'            =>$delivery_type_4_array[2],
    					'express_name'   =>$delivery_type_4_array[3],
    					'express_account'=>$delivery_type_4_array[4]);
    		}
    		//获取购物车
    		$cartall = $this->cart->contents_by_delivery();
    		$subtract = $cartall[$delivery];
    		if(empty($subtract)){
    			$this->_redirect('/');
    		}
  
    		//每次最多购买20条产品，防止攻击
    		if(count($subtract)>20) {
    			$message ='数据出错，每个订单最多添加购买20个产品';
    			$error++;
    		}
    		//最多添加10个还没付款订单，防止攻击
    		$orderService = new Default_Service_OrderService();
    		$orderre = $orderService->checkNum(10);
    		if($orderre){
    			$message ='很抱歉，你下的订单过多，请及时付款。付款后才能继续下单。';
    			$error++;
    		}

    		if($error){
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'在线订单提交失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    			$prodModel         = new Default_Model_DbTable_Product();
    			$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    			$salesproductModel = new Default_Model_DbTable_SalesProduct();
    			$soaddModel        = new Default_Model_DbTable_OrderAddress();
    		//开始事务
    	    $salesorderModel->beginTransaction();
    		try{	
    			$this->cartService = new Default_Service_CartService();
    			//订单号
    			$salesnumber = '1'.(intval(date('Y'))%10).date('m').date('d').substr(microtime(),2,4).'-'.substr(time(),-5);
    			//多个part_no
    			$part_nos = '';
    			$total = $freight = $quantitys = $items = 0;
    			//记录产品销售详细
    			$itemdata = array();
    			$items = count($subtract);
    			
    			foreach($subtract as $j=>$item){
    			   $tmp=array('salesnumber'=>$salesnumber,
    			   				'collection_id'=>$item['collection_id'],
    			   				'supplier_id'=>$item['supplier_id'],
    						    'prod_id' =>$item['pord_id'],
    						    'part_no' =>$item['part_no'],
    			   		        'customer_material'=>$this->filter->pregHtmlSql($customer_material[$j]),
    						    'brand'   =>$item['options']['brand'],
    							'buynum'  =>$item['qty'],
    							'buyprice'=>$item['byprice'],
    							'created' =>time());
    			   $itemdata[]=$tmp;
    			   $total +=$item['qty']*$item['byprice'];
    			   $quantitys +=$item['qty'];
    			   if($j==0) $part_nos = $item['part_no'];
    			   else $part_nos .= ','.$item['part_no'];
    			}
    			//运费
    			$this->freight = 0;
    			if($delivery=='SZ'){
    				if($total < $this->freermb){
    					//满500元免，广东省（440000）10，其余30。运费及包装费
    					if(in_array($addre['province'],$this->tenArr)){
    						$this->freight = $this->gdfreight;
    					}else{
    						$this->freight = $this->otherfreight;
    					}
    				}
    			}
    			if($delivery=='HK'){
    				if($total < $this->freeusd){
    					if($delivery_type == 1 || $delivery_type == 2)$this->freight = $this->usdfreight;
    					else $this->freight = 0;//$this->usdfreight/2;
    				}
    			}
    			//记录收货地址
    			if($delivery_type != 3){
    			  $soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
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
    			  //记录发票地址
    			  if(!empty($invoiceaddre)){
    			  	$invoice_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
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
    			  }else{
    			  	$invoiceaddid = $soaddid;
    			  }
    			}
    			//如果用户没有填写过公司信息就更新
    			$this->_userService = new Default_Service_UserService();
    			$data = array('companyname'=>$addre['companyname']?$addre['companyname']:$invre['name'],
    					'truename'=>$addre['name'],
    					'mobile'=>$addre['mobile'],
    					'tel'=>$addre['tel'],
    					'province'=>$addre['province'],
    					'city'=>$addre['city'],
    					'area'=>$addre['area'],
    					'address'=>$addre['address'],
    					'zipcode'=>$addre['zipcode'],
    					'modified'=>time());
    			$this->_userService->upInfoByOrder($data,$_SESSION['userInfo']['uidSession']);
    			
    			$paytotal = ($total+$this->freight);
    			$deductions = 0;
    			if($deductions >= $paytotal){
    				$paytotal = 0;
    				$status = 201;
    			}else{
    				$paytotal = $paytotal-$deductions;
    				$status = 101;
    			}
    
    			$orderdata = array('uid'=>$_SESSION['userInfo']['uidSession'],
    					'salesnumber'=>$salesnumber,
    					'addressid'=>$soaddid,
    					'invoiceaddress'=>$invoiceaddid,
    					'paytype'=>$paymenttype,
    					'coupon_code'=>$coupon_code,
    					'freight'=>$this->freight,
    					'quantitys'=>$quantitys,
    					'items'=>$items,
    					'total'=>$paytotal,
    					'total_back'=>($total+$this->freight),
    					'deductions'=>$deductions,
    					'delivery_place'=>$delivery,
    					'delivery_type' =>$delivery_type,
    					'shipments' =>($udstr?'spot':'order'),
    					'currency'=>($delivery=='HK'?'USD':'RMB'),
    					'consignee'=>$addre['name'],
    					'partnos'=>$part_nos,
    					'status'=>$status,
    					'available'=>1,
    					'remark'   =>$remark,
    					'ip'=>$this->fun->getIp(),
    					'created'=>time(),
    					'modified'=>time());
    			if(!empty($needinvoice)) $orderdata['invoiceid']=$invoiceid;
    			$orderid = $salesorderModel->addData($orderdata);
    			if($orderid)
    			{
    				
    				//记录产品销售详细
				    $salesproductModel->addDatas($itemdata);
				    $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$salesnumber,'temp4'=>'在线订单提交成功'));
				    //清空购物车和寄存
				    $this->_cartService = new Default_Service_CartService();
				    foreach($subtract as $item){
				    	$this->_cartService->delectCart($item['id']);
				    	$this->cart->update(array('rowid' => $item['rowid'],'qty'=> 0));
				    }
				    $_SESSION['cartnumber']=$this->cart->total_items();
				    $salesorderModel->commit();
				   //异步请求开始
    			   $this->fun->asynchronousStarts();
    			   $orderarr = $this->_orderService->geSoinfo($salesnumber);
    			   $orderSer = new Default_Service_OrderService();
    			   echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'下单成功。','hashkey'=>$this->fun->encryptVerification($salesnumber)));
    			   //异步请求开始
    			   $this->fun->asynchronousEnd();
				   exit;
    			}else{
    				$salesorderModel->rollBack();
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'服务器忙，下单失败'));
    				exit;
    			}
    		} catch (Exception $e) {	 
    				$salesorderModel->rollBack();
    				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'在线订单提交失败','description'=>'很抱歉，系统繁忙'));
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'.$e.message));
    				exit;
    		  }
    		}
    	}else $this->_redirect('/');
    }
    /**
     * 订单成功页面
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    public function successAction(){
    	$hashkey = $_GET['hashkey'];
    	$salesnumber = $this->fun->decryptVerification($hashkey);
    	$orderarr = $this->_orderService->geSoinfo($salesnumber);
    	if(!empty($orderarr))
    	{   
    		$this->view->orderarr=$orderarr;
    		
    	}else $this->_redirect('/');
    }
    /**
     * 订单详细查看
     */
    public function viewAction(){
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
    	
    	$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	$this->view->orderarr = $this->_orderService->geSoinfo($salesnumber);
    	$this->view->pordarr  = $this->view->orderarr['pordarr'];
    	if(empty($this->view->orderarr)) $this->_redirect('/center/order');
    }
    /**
     * 取消订单
     */
    public function cancelAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber  = $formData['salesnumber'];
    		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    		$re = $salesorderModel->update(array('status'=>401), "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//恢复产品数量
    			$this->_productService->reinstate($salesnumber);
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'订单取消成功'));
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='订单取消成功';
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单取消成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'订单取消失败'));
    			$_SESSION['postsess']['code'] = 100;
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单取消失败'));
    			exit;
    		}
    	}
    }
    /**
     * 恢复订单
     */
    public function restorationAction(){
    	echo '暂时不可用';exit;
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = ($formData['salesnumber']);
    		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    		$nowtime = time();
    		$re = $salesorderModel->update(array('status'=>101,'created'=>$nowtime,'modified'=>$nowtime), "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//占用产品数量
    			$this->_productService->occupation($salesnumber);
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'订单恢复成功'));
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='订单恢复成功';
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单恢复成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'订单恢复失败'));
    			$_SESSION['postsess']['code'] = 100;
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单恢复失败'));
    			exit;
    		}
    	}
    }
    /**
     * 删除订单
     */
    public function deleteAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$id  = (int)($formData['id']);
    		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    		$re = $salesorderModel->update(array('available'=>0), "id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='订单删除成功';
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单删除成功'));
    			exit;
    		}else{
    			$_SESSION['postsess']['code'] = 100;
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'订单删除失败'));
    			exit;
    		}
    	}
    }
    /**
     * 确认收货成功
     */
    public function receiptAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$id  = (int)($formData['id']);
    		$salesnumber  = $formData['salesnumber'];
    		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    		$re = $salesorderModel->update(array('status'=>301,'receiving_time'=>time()), "  status!=301 AND salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//增加用户积分
    			$score = $this->_orderService->addScore($salesnumber);
    			
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='确认收货成功'.($score>0?"，并成功获得 $score 积分":'');
    			
    			//异步请求开始
    			$this->fun->asynchronousStarts();
    			$orderarr = $this->_orderService->geSoinfo($salesnumber);
    			$orderSer = new Default_Service_OrderService();
    			//用户信息
    			$this->_userService = new Default_Service_UserService();
    			$user = $this->_userService->getUserProfile();
    			$orderSer->completemail($user['email'],$user['uname'],$orderarr);
    			
    			
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp3'=>$score,'temp4'=>'确认收货成功','description'=>'增加'.$score.'积分'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'确认收货成功'.($score>0?"，并成功获得 $score 积分":'')));
    			//异步请求开始
    			$this->fun->asynchronousEnd();
    			exit;
    			
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'确认收货失败'));
    			$_SESSION['postsess']['code'] = 100;
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'确认收货失败'));
    			exit;
    		}
    	}
    }
    /**
     * 转账信息
     */
    public function transferAction(){
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $_POST['sonum'];
    	$salesorderModel   = new Default_Model_DbTable_SalesOrder();
    	$this->view->so = $salesorderModel->getRowByWhere("salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    	
    }
    /*
     * 查看快递信息
    */
    public function courierAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->salesnumber  = $salesnumber = $_POST['sonum'];
    	$sonid  = $_POST['sonid'];
    	$chModel = new Default_Model_DbTable_CourierHistory();
    	$sqlstr ="SELECT ch.*,c.name FROM courier_history as ch
                  LEFT JOIN courier as c ON ch.cou_id=c.id
    	          WHERE salesnumber='{$salesnumber}'";
    	$rearraytmp = $chModel->getBySql($sqlstr);
    	$rearray = $rearraytmp[0];
    	$this->view->id  = $rearray['so_id'];
    	//查询记录
    	$courier ='';
    	$soModel   = new Default_Model_DbTable_SalesOrder();
    	$re = $soModel->getRowByWhere("id='".$rearray['so_id']."' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    	if(empty($re)) $courier='此物流信息不存在';
    	$tmie = time();
    	$oldtime = $tmie-3600;//每一小时更新
    	$courier = $rearray['track'];
    	$this->view->courier     = '';
    	$this->view->cou_name    = $rearray['name'];
    	$this->view->cou_number  = $rearray['cou_number'];
    	$this->view->content     = $courier;
    }
    /**
     * 检查优惠券是否可以使用
     */
    public function checkcouponAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$couponcode = (int)$_POST['couponcode'];
    	$delivery   = $_POST['delivery'];
    	$couponservice = new Default_Service_CouponService();
    	$coupon = $couponservice->getCouponByCode($couponcode,"AND cp.status='200'");
    	if(!$coupon){
    		echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'优惠券已经不可用'));
    		exit;
    	}
    	//取购物车
    	$cartall = $this->cart->contents_by_delivery();
    	$items = $cartall[$delivery];
    	if(!$items){
    		echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'产品数据错误'));
    		exit;
    	}
    	echo Zend_Json_Encoder::encode($couponservice->checkCoupon($coupon,$items,$delivery));
    	exit;
    }
    /**
     * 查看生成合同
     */
    public function digitalcontractAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if( $this->_getParam('key') != md5(session_id())) {exit;$this->_redirect('/error');}
    	//订单详情
    	$orderarr = $this->_orderService->geSoinfo($this->fun->decryptVerification($this->_getParam('item')));
    	//产品详细
    	$orderarr['pordarr'] = $orderarr['pordarr'];
    	//用户资料
    	$userService = new Default_Service_UserService();
    	$userinfo = $userService->getUserProfile();
    	if(empty($userinfo)) {$this->_redirect('/error');};
    	//如果存在
    	$pdfpart = ORDER_ELECTRONIC.md5('order'.$orderarr['salesnumber']).'.pdf';
    	if(file_exists($pdfpart)){
    		$this->_redirect($pdfpart);
    	}else{
    	    $currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
    	    $unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
    	    $definqorderService = new Default_Service_InqOrderService();
    	    if($orderarr['delivery_place'] == 'SZ') $return=$definqorderService->szDigitalContract($orderarr,$userinfo);
    	    elseif($orderarr['delivery_place'] == 'HK') $return=$definqorderService->hkDigitalContract($orderarr,$userinfo);
    		if($return) $this->_redirect($pdfpart);
    	}
    }
}

