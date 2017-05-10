<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';
class InquiryorderController extends Zend_Controller_Action
{
    private $_inqService;
    private $_inqorderService;
    private $_userService;
    private $_prodService;

    private $_defaultlogService;
    public function init()
    {
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$_SESSION['menu'] = 'inquiryorder';
    	//获取购物车寄存，要在$this->cart = new Cart()前
    	$cartService = new Default_Service_CartService();
    	$cartService->getCartDeposit();
    	
        /* Initialize action controller here */
    	$this->filter = new MyFilter();
    	$this->view->fun = $this->fun = new MyFun();
    	$this->_inqService = new Default_Service_InquiryService();
    	
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
    	
    	//满500元免，广东省（440000）20，其余30。运费及包装费
    	$this->view->freermb = $this->config->cost->inquiry_free_RMB;
    	$this->view->freeusd = $this->config->cost->inquiry_free_USD;
    	$this->view->freehkd = $this->config->cost->inquiry_free_HKD;
    	$this->morethan   = array('RMB'=>$this->config->cost->inquiry_free_RMB,
    			                  'USD'=>$this->config->cost->inquiry_free_USD,
    			                  'HKD'=>$this->config->cost->inquiry_free_HKD);
    	$this->freightArr = array('RMB'=>array($this->config->cost->inquiry_other_freight1_RMB,
    			                               $this->config->cost->inquiry_gd_freight1_RMB),
    			              'USD'=>array($this->config->cost->inquiry_gd_freight1_USD),
    			              'HKD'=>array($this->config->cost->inquiry_gd_freight1_HKD));
    	//分两张订单多收的运费
    	$this->twofreight = array('RMB'=>array($this->config->cost->inquiry_other_freight2_RMB,
    			                               $this->config->cost->inquiry_gd_freight2_RMB),
    			              'USD'=>array($this->config->cost->inquiry_gd_freight2_USD),
    			              'HKD'=>array($this->config->cost->inquiry_gd_freight2_HKD));
    	
    	$this->tenArr = array('440000');
    	
    	$this->_userService = new Default_Service_UserService();
    	$this->_prodService = new Default_Service_ProductService();
    	$this->_inqorderService = new Default_Service_InqOrderService();
    	$this->_defaultlogService = new Default_Service_DefaultlogService();
    	
    	//产品目录
    	$prodService = new Default_Service_ProductService();
    	$prodCategory = $prodService->getProdCategory();
    	$this->view->first = $prodCategory['first'];
    	$this->view->second = $prodCategory['second'];
    	$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
    }

    public function indexAction()
    {
    	$this->view->pdfkey = md5(session_id());
    	$this->view->inqkey = $this->_getParam('inqkey');
    	$inqid = $this->fun->decryptVerification($this->_getParam('inqkey'));
    	$inqhistory = $this->_inqService->getInquiryByID($inqid,1);
    	if(in_array($inqhistory['status'],array(2,3,5,6)) && !empty($inqhistory['detaile'])){
    		$this->view->inquiry = $inqhistory;
    	}else $this->_redirect('/center/inquiry');
    }
    /**
     * 填写核对信息单跳转页
     */
    public function chooseinfocheckAction(){
    	$this->_helper->layout->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$checkItem = $_POST['checkItem'];
    	$multiple = $_POST['multiple'];
    	if(empty($multiple)) $this->_redirect('/center/inquiry');
    	$i=1;
    	foreach($multiple as $idtmp=>$num){
    		if(in_array($idtmp,$checkItem)){
    		  if($i==1){$ids = $idtmp;$nums = $num;}
    		  else{$ids .= ','.$idtmp;$nums .= ','.$num;}
    		  $i=0;
    		}
    	}
    	$is = $this->fun->encryptVerification($ids);
    	$ns = $this->fun->encryptVerification($nums);
    	$this->_redirect('/inquiryorder/chooseinfo?key='.$_GET['key'].'&items='.rawurlencode($_GET['items']).'&is='.$is.'&ns='.$ns);
    	
    }
    /**
     * 填写核对信息单
    */
    public function chooseinfoAction(){
    	$is_arr = explode(',',$this->fun->decryptVerification($_GET['is']));
    	$ns_arr = explode(',',$this->fun->decryptVerification($_GET['ns']));
    	
    	if(empty($is_arr) || count($is_arr)!=count($ns_arr))
    		$this->_redirect('/center/inquiry');
    	$this->view->multiple = $multiple = array_combine($is_arr,$ns_arr);
		
    	$this->view->key = $key=$_GET['key'];
    	if($key != md5(session_id())) $this->_redirect('/center/inquiry');
    	$this->view->itemstr = $_GET['items'];
    	$this->view->items  =  $items = explode('_',$this->view->itemstr);
    	$this->view->inqkey = $items[0];	
    	if(count($is_arr) != (count($items)-1) || array_intersect($is_arr,$items)!=$is_arr)
    		$this->_redirect('/center/inquiry');
    	$this->view->inqid = $inqid = $this->fun->decryptVerification($this->filter->pregHtmlSql($this->view->inqkey));
    	//获取购买商品
    	$inquiry_history = $this->_inqService->getInquiryByID($inqid,1);
    	//空或者有等待处理订单不能再下单
    	if(in_array($inquiry_history['status'],array(2,3,5,6)) && !empty($inquiry_history['detaile'])){
    		$this->view->inquiry = $inquiry = $inquiry_history;
    	}else $this->_redirect('/center/inquiry');
    	
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
    	if($inquiry['delivery']=='HK'){
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
    	if($inquiry['delivery']=='SZ'){
    		if($this->view->addressFirst['provinceid']==810000){
    			foreach($arr as $arrtmp){
    				if($arrtmp['provinceid'] != 810000)
    					$this->view->addressFirst = $arrtmp;
    			}
    		}
    	}
    	//发票
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
    	//个人信息
    	$this->userprofile = new Default_Model_DbTable_UserProfile();
    	$myinfoarray = $this->userprofile->getBySql("SELECT * FROM user_profile
    			WHERE  uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	$this->view->myinfo = $myinfoarray[0];

    	//计算货品
    	$pricetotal=$qtytotal = 0;
    	foreach($inquiry['detaile'] as $item) {
    	   if(in_array($item['id'],$items) && array_key_exists($item['id'],$multiple)){
    		  $pricetotal    += $item['pmpq']*$multiple[$item['id']]*$item['result_price'];
    		  $qtytotal      += $item['pmpq']*$multiple[$item['id']];
    	   }
    	}
    	if($pricetotal <=0 ) $this->_redirect('/center/inquiry');

    	//满500元免，广东省（440000）10，其余30。运费及包装费
        if($pricetotal >= $this->morethan[$inquiry['currency']]){
        	$freight = 0;
        }elseif(in_array($this->view->addressFirst['provinceid'],$this->tenArr) && $inquiry['currency']=='RMB') {
        	$freight = $this->freightArr['RMB'][1];
        }else {
        	$freight = $this->freightArr[$inquiry['currency']][0];
        }
    	$this->view->freight = $freight;
    	$this->view->packageprice = 0;//$freight/2;
    	//多收一次运费
    	if(in_array($this->view->addressFirst['provinceid'],$this->tenArr) && $inquiry['currency']=='RMB') {$this->view->twofreight = $this->twofreight['RMB'][1];}
    	else {$this->view->twofreight = $this->twofreight[$inquiry['currency']][0];}
    	//需要发票
    	if($inquiry['delivery']=='SZ')
    	   $this->view->NeedInvoice='yes';
    	//获取可以合并发货的订单
    	$this->view->shiporder = $this->_inqorderService->getInqOrderShipments($inquiry['delivery']);
    	//快递商
    	$addService = new Default_Service_AddressService();
    	$this->view->expressadd = $addService->getExpressAddress();
    }
    /**
     * 提交订单处理
     */
    public function handleAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$error = 0;$message='';
    		$formData    = $this->getRequest()->getPost();
    		if($formData['key'] != md5(session_id())) {$message ='参数key错误';$error++;}

    		$items           = explode('_',$formData['items']);
    		$inqid           = (int)$this->fun->decryptVerification($items[0]);
    		$delivery_type   = (int)($formData['delivery_type']);
    		$delivery_type_4_array = $formData['delivery_type_4_array'];
    		$multiple        = $formData['multiple'];
    		$addressid       = (int)($formData['addressid']);
    		$invoiceid       = (int)($formData['invoiceid']);
    		$invoiceaddress  = (int)($formData['invoiceaddress']);
    		$paymenttype     = $this->filter->pregHtmlSql($formData['paymenttype']);
    		$needinvoice     = $this->filter->pregHtmlSql($formData['needinvoice']);
            $remark          = $this->filter->pregHtmlSql($formData['remark']);
            $shipoder_salesnumber   = $formData['shipoder_salesnumber'];
            $part_id_array          = $formData['part_id'];
            $buynum_array           = $formData['buynum'];
            $customer_material_array=$formData['customer_material'];
            $needs_time_array       = $formData['needs_time'];
            
    		//开始事务
    		$this->_inqorderService->beginTransaction();
    		
    		//最多添加50个还没付款订单，防止攻击
    		$inqorderService = new Default_Service_InqOrderService();
    		$orderre = $inqorderService->checkNum(50);
    		if($orderre){
    			$message ='很抱歉，你下的订单过多，请及时付款。付款后才能继续下单';
    			$error++;
    		}
			
    		//获取购买商品
    		$inquiry = $this->_inqService->getInquiryByID($inqid,1);
    		if(!$inquiry){
    			$message ='参数错误';
    			$error++;
    		}
    		//支付百分比
    		$percentage = $inquiry['percentage']*0.01;
    		if($paymenttype=='transfer'){
    			$paymentratio = $percentage;
    		}elseif($paymenttype=='online'){
    			$paymentratio = 1;
    			//检查是否满足在线支付
    			if($inquiry['currency'] !='RMB'){
    				$message ='不满足在线支付条件';
    				$error++;
    			}
    		}elseif($paymenttype=='cod'){
    			$paymentratio = $percentage;
    		}else{
    			$message ='支付方式错误';
    			$error++;
    		}
    		//收货与发票地址，需要合并的订单
    		$addre = $invoiceaddre = $shipoder = array();
    		//如果合并订单
    		if($shipoder_salesnumber){
    			$shipoder = $this->_inqorderService->getOneInqOrderShipments($shipoder_salesnumber);
    			if(empty($shipoder)){
    				$message ='很抱歉，您选择合并的订单不存在，请刷新页面再试。';
    				$error++;
    			}
    			//判断是否有选择了分批发货
    			if(count($part_id_array) != count($inquiry['detaile'])){
    				$message ='很抱歉，您已经选择合并的订单，不再支持分批发货，请取消其中一种。';
    				$error++;
    			}
    		}else{
    		    //配送方式 国内快递或公司配送
    		    //发票地址
    		    $invoiceaddre = array();
    		    if(in_array($delivery_type,array(1,2,3,4)))
    		    {
    			  if($delivery_type == 1 || $delivery_type == 2){
    				 $addressModel = new Default_Model_DbTable_Address();
    				 $addre        = $addressModel->getRowByWhere("id='{$addressid}'");
    				 if(empty($addre)){
    					$message ='收货人地址不存在，请重新选择';
    					$error++;
    				 }
    				 //如果发票地址不等与收货地址
    				 if($addressid != $invoiceaddress)
    				 {
    					$invoiceaddre = $addressModel->getRowByWhere("id='{$invoiceaddress}'");
    				 }
    			   }elseif($delivery_type == 4){//客户提供账号
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
    		    }else{
    			    $message ='暂不支持此配送方式';
    			    $error++;
    		   }
    		}
    		//发票
    		$invoiceModel = new Default_Model_DbTable_Invoice();
    		if(!empty($needinvoice)){
    			$invre = $invoiceModel->getRowByWhere("id='{$invoiceid}'");
    			if(empty($invre)){
    				$message ='发票信息不存在，请重新选择';
    				$error++;
    			}
    		}
    		if(empty($inquiry['detaile'])) {
    			$message ='数据出错';
    			$error++;
    		}
    		if($error){
    			$this->_inqorderService->rollBack();
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$inquiry['inq_number'],'temp4'=>'询价订单提交失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
    			exit;
    		}else{
    		    //添加到数据库的基本信息
    			$addinfo = array('inqid'=>$inqid,
    					'paymenttype'=>$paymenttype,
    					'percentage'=>$paymentratio*100,
    					'delivery_type'=>$delivery_type,
    					'delivery'=>$inquiry['delivery'],
    					'currency'=>$inquiry['currency'],
    					'paper_contract'=>$inquiry['paper_contract'],
    					'needinvoice'=>$needinvoice,
    					'invoiceid'=>$invoiceid,
    					'son_salesnumber'=>'',
    					'needs_time'=>0,
    					'remark'=>$remark,
    					'son_so'=>0,
    					'shipments'=>'');
    		try {	
    			$prodModel         = new Default_Model_DbTable_Product();
    		    $totaldetaile = array();
    		    $pricetotal = 0;
    		    $addinfo['shipments'] ='spot';
    		    //拆分产品Item
    		    foreach($part_id_array as $key=>$part_id){
    		    	foreach($inquiry['detaile'] as $j=>$item) {
    				if(in_array($item['id'],$items) && $part_id==$item['part_id']){
    					$partnum   = $item['buynum'] = $buynum_array[$key];
    					$pricetotal     += $partnum*$item['result_price'];
    					$item['customer_material'] = $customer_material_array[$j];
    					$item['needs_time']        = ($needs_time_array[$key]?strtotime($needs_time_array[$key]):0);
    					//查询产品信息
    				    $prod_new = $this->fun->filterProduct($this->_prodService->getStockProd($item['part_id']));
    				    //首先判断产品库存如果有库存就继续库存更新
    				    //如果现货更新产品库存
    				    $udstr = '';$sz_cover = $hk_cover = $bpp_cover = 0;
    				    if($inquiry['delivery']=='SZ'){
    				    	if($prod_new['f_stock_sz'] >= $partnum){
    				    		if($prod_new['f_stock_sz_tmp'] >= $partnum){
    				    			$sz_cover = $partnum;
    				    			$udstr = "UPDATE product SET sz_cover = sz_cover + ".$sz_cover." WHERE id='".$item['part_id']."'";
    				    		}else{
    				    			//国内占用仓库后剩余
    				    			$surplus_1 = $partnum-$prod_new['f_stock_sz_tmp'];
    				    			if($prod_new['f_stock_hk'] >= $surplus_1){
    				    				$sz_cover = $prod_new['f_stock_sz_tmp'];
    				    				$hk_cover = $surplus_1;
    				    				$udstr = "UPDATE product SET hk_cover = hk_cover + ".$hk_cover.", sz_cover =sz_cover + ".$sz_cover." WHERE id='".$item['part_id']."'";
    				    			}else{
    				    				$sz_cover  = $prod_new['f_stock_sz_tmp'];
    				    				$hk_cover  = $prod_new['f_stock_hk'];
    				    				$bpp_cover = $item['qty']-$sz_cover-$hk_cover;
    				    				$udstr = "UPDATE product SET hk_cover = hk_cover + ".$hk_cover.", sz_cover =sz_cover + ".$sz_cover.", bpp_cover =bpp_cover + ".$bpp_cover." WHERE id='".$item['part_id']."'";
    				    			}
    				    		}
    				    	}
    				    }elseif($inquiry['delivery']=='HK'){
    				    	if($prod_new['f_stock_hk_tmp'] >= $partnum){
    				    		$hk_cover = $partnum;
    				    		$udstr = "UPDATE product SET hk_cover =hk_cover + ".$hk_cover." WHERE id='".$item['part_id']."'";
    				    	}else{
    				    		$hk_cover  = $prod_new['f_stock_hk_tmp'];
    				    		$bpp_cover = $partnum-$hk_cover;
    				    		$udstr = "UPDATE product SET hk_cover = hk_cover + ".$hk_cover.", bpp_cover =bpp_cover + ".$bpp_cover." WHERE id='".$item['part_id']."'";
    				    	}
    				    }
    				    //执行更新
    				    if($udstr) {
    				    	$prodModel->updateBySql($udstr);
    				    	$item['shipments'] = 'spot';
    				    }else{
    				    	$item['shipments']    = 'order';
    				    	$addinfo['shipments'] ='order';
    				    }
    				    $item['staged']  = $prod_new['staged'];
    				    $item['sz_cover']=$sz_cover;
    				    $item['hk_cover']=$hk_cover;
    				    $item['bpp_cover']=$bpp_cover;
    				    $totaldetaile[]  = $item;
    				}
    			  }	
    		    } //end foreach($part_id_array as $key=>$part_id){

    			//满500元免，广东省（440000）10，其余30。运费及包装费
    			if($pricetotal >= $this->morethan[$inquiry['currency']]){
    				$freight = 0;
    			}elseif(in_array($addre['province'],$this->tenArr) && $inquiry['currency']=='RMB') {
    				$freight = $this->freightArr['RMB'][1];
    			}else {
    				$freight = $this->freightArr[$inquiry['currency']][0];
    			}
    			//如果用户自取 只收包装费
    			if($delivery_type == 3) $freight = 0;//$freight/2;
    			elseif(!empty($shipoder)){//如果选择合并订单
    				//只收取包装费
    				$freight = 0;//$freight/2;
    			}
    			//订单信息
    			$total            = $pricetotal+$freight;
    			if($paymentratio>0) $down_payment = $total*$paymentratio;
    			else $down_payment = $inquiry['down_payment'];
    			$rearr   = $this->_inqorderService->addinqOrder($totaldetaile,$multiple,$addre,$addinfo,$invoiceaddre,$freight,$down_payment,$total,$shipoder);
    			if($rearr)
    			{
    				
    				//记录日志
    				$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$rearr['salesnumber'],'temp4'=>'询价订单提交成功'));
    				//更新询价 已经下单状态
    				$this->_inqService->updateStatus($inqid,5);
    				//添加积分
    				$this->_scoreService = new Default_Service_ScoreService();
    				$this->_scoreService->addScore('first_inqorder');
    				$this->_inqorderService->commit();
    				//异步请求开始
    				$this->fun->asynchronousStarts();
    				//用户信息
    				$user = $this->_userService->getUserProfile();
    				$emailreturn = $this->_inqorderService->sendinqsomail($user['email'],$user['uname'],$rearr['salesnumber']);
    				//邮件日志
    				if($emailreturn){
    					$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$rearr['salesnumber'],'temp4'=>'发送询价订单邮件给客户成功'));
    				}else{
    					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$rearr['salesnumber'],'temp4'=>'发送询价订单邮件给客户失败'));
    				}
    				//发邮件给销售
    				$this->_inqorderService->sendordermailsell($rearr['salesnumber']);
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'下单成功。','hashkey'=>$this->fun->encryptVerification($rearr['salesnumber'])));
    				//异步请求开始
    				$this->fun->asynchronousEnd();
    				exit;
    			}else{
    			   	$this->_inqorderService->rollBack();
    			   	//记录日志
    			   	$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$inquiry['inq_number'],'temp4'=>'询价订单提交失败','description'=>'返回数据为空'));
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'服务器忙，下单失败。'));
    				exit;
    			}
    		  } catch (Exception $e) {	 
    				$this->_inqorderService->rollBack();
    				//记录日志
    				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$inquiry['inq_number'],'temp4'=>'询价订单提交失败','description'=>'系统繁忙'));
    				echo Zend_Json_Encoder::encode(array("code"=>111, "message"=>'系统繁忙'));
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
    	$this->view->orderarr =  $this->_inqorderService->getinqOrder($salesnumber);
    	if(empty($this->view->orderarr)) $this->_redirect('/');
    }
    /**
     * 订单详细查看
     */
    public function viewAction(){
    	$salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	$this->view->orderarr = $this->_inqorderService->geSoinfo($salesnumber);
    	$this->view->pordarr  = $this->view->orderarr['pordarr'];
    	if(empty($this->view->orderarr)) $this->_redirect('/center/inqorder');
    }
    /**
     * 取消订单
     */
    public function cancelAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$id  = (int)($formData['id']);
    		$salesnumber = $formData['salesnumber'];
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    		$re = $salesorderModel->update(array('status'=>401), "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//恢复产品数量
    			$this->_prodService->reinstate($salesnumber);
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
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$id  = (int)($formData['id']);
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    		$nowtime = time();
    		$re = $salesorderModel->update(array('status'=>101,'created'=>$nowtime,'modified'=>$nowtime), "id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'订单恢复成功'));
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='订单恢复成功';
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单恢复成功'));
    			exit;
    		}else{
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'订单恢复失败'));
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
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
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
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    		$re = $salesorderModel->update(array('status'=>301,'receiving_time'=>time()), " status!=301 AND salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    		if($re){
    			//更新询价单状态
    			$this->_inqorderService->restoreInq($salesnumber);
    			//增加用户积分
    			$score = $this->_inqorderService->addScore($salesnumber);
    			
    			$_SESSION['postsess']['code'] = 0;
    			$_SESSION['postsess']['message']='确认收货成功'.($score>0?"，并成功获得 $score 积分":'');
    			
    			//异步请求开始
    			$this->fun->asynchronousStarts();
    			//用户信息
    			$user = $this->_userService->getUserProfile();	
    			$this->_inqorderService->completemail($user['email'],$user['uname'],$salesnumber);
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
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'确认收货失败，不要重复确认'));
    			exit;
    		}
    	}
    }
    /**
     * 交货期更改申请
     */
    public function deliverychangeAction(){
    	$this->_helper->layout->disableLayout();
    	$windowtime = time()+WINDOW_DAY*86400;
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData    = $this->getRequest()->getPost();
    		$salesnumber = $this->filter->pregHtmlSql($formData['salesnumber']);
    		$delivery_change_date = $this->filter->pregHtmlSql($formData['delivery_change_date']);
    		$so = $this->_inqorderService->getinqOrder($salesnumber);
    		if(empty($delivery_change_date)){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请选择日期'));
    			exit;
    		}elseif(strtotime($delivery_change_date) < time()){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请选择大于今天的日期'));
    			exit;
    		}elseif( $windowtime > $so['delivery_time']){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'不能再提交更改交货期申请'));
    			exit;
    		}else{
    			$salesorderModel   = new Default_Model_DbTable_InqSalesOrder();
    			$update = array('delivery_status'=>101,
    					'delivery_change_date'=>strtotime($delivery_change_date));
    			$re = $salesorderModel->update($update, "salesnumber='{$salesnumber}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
    			if($re){
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交交货期申请成功'));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'提交交货期申请失败'));
    				exit;
    			}
    		}
    	}else{
    	  $salesnumber = $this->filter->pregHtmlSql($_GET['salesnumber']);
    	  $this->view->so = $this->_inqorderService->getinqOrder($salesnumber);
    	}
    }
    /**
     * 转账成功后，上传凭证
     */
    public function transferAction(){
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $_POST['sonum'];
    	$this->view->so = $this->_inqorderService->getinqOrder($salesnumber);
    }
    /*
     * 查看快递信息
    */
    public function courierAction(){
    	$this->_helper->layout->disableLayout();
    	$salesnumber = $this->view->salesnumber  = $_POST['sonum'];
    	$chModel = new Default_Model_DbTable_CourierHistory();
    	$sqlstr ="SELECT ch.*,c.name
    	                  FROM courier_history as ch
                          LEFT JOIN courier as c
    	                  ON ch.cou_id=c.id
    	                 WHERE salesnumber=:salesnumbertmp";
    	$rearraytmp = $chModel->getBySql($sqlstr, array('salesnumbertmp'=>$salesnumber));
    	$rearray = $rearraytmp[0];
    	$this->view->id  = $rearray['so_id'];
    	//查询记录
    	$courier ='';
    	$re = $this->_inqorderService->getinqOrder($salesnumber);
    	if(empty($re)) $courier='此物流信息不存在。';
    	$tmie = time();
    	$oldtime = $tmie-3600;//每一小时更新
    	$cou_number = $rearray['cou_number'];
    	$courier = $rearray['track'];
    	$this->view->courier = '';
    	$this->view->cou_name    = $rearray['name'];
    	$this->view->cou_number  = $rearray['cou_number'];
    	$this->view->content     = $courier;
    }
    /*
     * 动态生成询价单pdf
     */
    public function createpdfAction(){
    	$this->_helper->layout->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$pdfkey   = $_GET['pdfkey'];		
    	if($pdfkey != md5(session_id())) $this->_redirect('/center/inquiry');
    	$itemsArray = explode('_',$_GET['items']);
    	if(isset($_POST['multiple'])) $multiple = $_POST['multiple'];
    	else{
    		foreach($itemsArray as $idtmp){
    			$multiple[$idtmp] = 1;
    		}
    	}
    	$inqkey = $this->fun->decryptVerification($this->filter->pregHtmlSql($itemsArray[0]));
    	$inquiry_history = $this->_inqService->getInquiryByID($inqkey,1);
    	
    	if(empty($inquiry_history)) $this->_redirect('/center/inquiry');
    	else $inquiry = $inquiry_history;

    	if(!$inquiry) $this->_redirect('/center/inquiry');
		//用户资料
		$user = $this->_userService->getUserProfile();
    	$this->_inqorderService->inqpdf($inquiry,$user,$itemsArray,$multiple);
    }
    /*
     * 下单后生成询价单pdf
    */
    public function orderinqpdfAction(){
    	$this->_helper->layout->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$pdfkey   = $_GET['pdfkey'];
    	if($pdfkey != md5(session_id())) $this->_redirect('/center/inqorder');
    	$salesnumber = $this->fun->decryptVerification($_GET['part']);
    	$orderarr = $this->_inqorderService->geSoinfo($salesnumber);
    	if(!$orderarr) $this->_redirect('/center/inqorder');
    	$inquiry = $this->_inqService->getInquiryByID($orderarr['inquiry_id']);
    	//用户资料
    	$user = $this->_userService->getUserProfile();
    	//如果存在
    	$pdfpart = ORDER_INQPDF.md5('inq'.$salesnumber).'.pdf';
    	if(file_exists($pdfpart)){
    		$this->_redirect($pdfpart);
    	}else{
    	    $return = $this->_inqorderService->inqorderpdf($orderarr,$user,$inquiry);
    	    if($return) $this->_redirect($pdfpart);
    	}
    }
    /**
     * 生成合同
     */
    public function iccontractAction()
    {
    	$this->_helper->layout->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	
    	if($this->_getParam('key') != md5(session_id())) $this->_redirect('/center/inqorder');
    	//订单详情
    	$soarray = $this->_inqorderService->geSoinfo($this->fun->decryptVerification($this->_getParam('item')));
    	if(empty($soarray)) $this->_redirect('/center/inqorder');
    	//用户资料
    	$userinfo = $this->_userService->getUserProfile();
    	if(empty($userinfo)) $this->_redirect('/center/inqorder');
    	
    	//如果存在
    	$pdfpart = ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf';
    	if(file_exists($pdfpart)){
    		$this->_redirect($pdfpart);
    	}else{
    		//国内合同
    		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
    		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
    		if($soarray['delivery_place'] == 'SZ') $return=$this->_inqorderService->szContract($soarray,$userinfo,$currencyArr,$unit);
    		elseif($soarray['delivery_place'] == 'HK') $return=$this->_inqorderService->hkContract($soarray,$userinfo,$currencyArr,$unit);
    		else $this->_redirect('/center/inqorder');
    		if($return) $this->_redirect($pdfpart);
    	}
    	
    }
    /**
     * 查看生成数字合同
     */
    public function digitalcontractAction()
    {
    	$this->_helper->layout->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	
    	if($this->_getParam('key') != md5(session_id())) $this->_redirect('/center/inqorder');
    	//订单详情
    	$soarray = $this->_inqorderService->geSoinfo($this->fun->decryptVerification($this->_getParam('item')));
    	if(empty($soarray)) $this->_redirect('/center/inqorder');
    	//用户资料
    	$userinfo = $this->_userService->getUserProfile();
    	if(empty($userinfo)) $this->_redirect('/center/inqorder');
    	
    	//如果存在
    	$pdfpart = ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf';
    	if(file_exists($pdfpart)){
    		$this->_redirect($pdfpart);
    	}else{
    		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
    	    $unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
    	    if($soarray['delivery_place'] == 'SZ') $return=$this->_inqorderService->szDigitalContract($soarray,$userinfo);
    	    elseif($soarray['delivery_place'] == 'HK') $return=$this->_inqorderService->hkDigitalContract($soarray,$userinfo);
    	    else $this->_redirect('/center/inqorder');
    	    if($return) $this->_redirect($pdfpart);
    	}
    }
    /**
     * 拆分产品Item
     */
    public function splititemAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->view->partarray = array();
    	$this->view->buynum  = $this->_getParam('buynum');
    	$inquiry = $this->_inqService->getInquiryByID($this->_getParam('inquiry_id'),1);
    	if(empty($inquiry)){
    		$this->_redirect('/');
    	}else{
    		foreach($inquiry['detaile'] as $inqdet){
    			if($inqdet['part_id'] == $this->_getParam('part_id')){
    				$this->view->partarray = $inqdet;
    			}
    		}
    		if(empty($this->view->partarray)){
    			$this->_redirect('/');
    		}
    	}
    }
}

