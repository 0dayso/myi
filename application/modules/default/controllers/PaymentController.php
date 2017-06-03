<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
// tenpay
require_once ("Iceaclib/default/tenpay/config.php");
require_once ("Iceaclib/default/tenpay/classes/RequestHandler.php");
require_once ("Iceaclib/default/tenpay/classes/ResponseHandler.php");
require_once ("Iceaclib/default/tenpay/classes/client/ClientResponseHandler.php");
require_once ("Iceaclib/default/tenpay/classes/client/TenpayHttpClient.php");
require_once ("Iceaclib/default/tenpay/classes/function.php");
// alipay
require_once ("Iceaclib/default/alipay/config.php");
require_once ("Iceaclib/default/alipay/lib/alipay_core.php");
require_once ("Iceaclib/default/alipay/lib/alipay_notify.php");
require_once ("Iceaclib/default/alipay/lib/alipay_service.php");
require_once ("Iceaclib/default/alipay/lib/alipay_submit.php");

class PaymentController extends Zend_Controller_Action {
	private $_defaultlogService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'payment';
		//由于支付宝和财付通异步返回不需要判断登录情况所以要单独出来登录检查
		$this->filter = new MyFilter ();
		$this->salesorderModel    = new Default_Model_DbTable_SalesOrder ();
		$this->inqsalesorderModel = new Default_Model_DbTable_InqSalesOrder ();
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		// 财付通参数
		$tenpatobj = new tenpayconfig();
		$tenpayconfig = $tenpatobj->getConfig();
		$this->tenpayspname  = $tenpayconfig['tenpayspname'];
		$this->tenpaypartner = $tenpayconfig['tenpaypartner'];
		$this->tenpaykey     = $tenpayconfig['tenpaykey'];
		// 进入前台回调页面
		$this->tenpayreturn_url = "http://" . $_SERVER ['HTTP_HOST'] . "/payment/tenpayview";
		// 进入后台回调页面
		$this->tenpaynotify_url = "http://" . $_SERVER ['HTTP_HOST'] . "/payment/tenpaycontrollers";
		
		// 支付宝参数
		$alipayconfig = new alipayconfig ();
		$this->aliconfig = $alipayconfig->getConfig ();
		
		$this->fun =$this->view->fun= new MyFun();
	}
	
	public function indexAction() {
		$this->_redirect ( '/error' );
	}
	/*
	 * 订单支付
	 */
	public function orderpayAction() {
		// 登录检查
		$this->common = new MyCommon ();
		$this->common->loginCheck ();
		
		$salesnumber = $this->filter->pregHtmlSql ($_GET ['salesnumber']);
		$sqlstr = "SELECT so.salesnumber,so.paytype,so.so_type,so.total,p.province,c.city,e.area,a.name,a.address,a.mobile,a.tel
    		FROM sales_order as so 
			LEFT JOIN order_address as a ON so.addressid=a.id
    		LEFT JOIN province as p ON a.province=p.provinceid
    		LEFT JOIN city as c ON a.city=c.cityid
    		LEFT JOIN area as e ON a.area = e.areaid
    		WHERE so.salesnumber=:sonum AND so.uid=:uidtmp AND so.status='101' AND so.paytype='online' AND so.available='1'";
		$orderarr = $this->salesorderModel->getBySql ( $sqlstr, array ('sonum' => $salesnumber, 'uidtmp' => $_SESSION ['userInfo'] ['uidSession'] ) );
		//如果询价订单
		if(empty ( $orderarr )){
			$sqlstr = "SELECT so.salesnumber,so.paytype,so.so_type,so.total,p.province,c.city,e.area,a.name,a.address,a.mobile,a.tel
    		FROM inq_sales_order as so
			LEFT JOIN order_address as a ON so.addressid=a.id
    		LEFT JOIN province as p ON a.province=p.provinceid
    		LEFT JOIN city as c ON a.city=c.cityid
    		LEFT JOIN area as e ON a.area = e.areaid
    		WHERE so.salesnumber=:sonum AND so.uid=:uidtmp AND so.status='101' AND so.paytype='online' AND so.available='1'";
			$orderarr = $this->inqsalesorderModel->getBySql ( $sqlstr, array ('sonum' => $salesnumber, 'uidtmp' => $_SESSION ['userInfo'] ['uidSession'] ) );
		}
		if (! empty ( $orderarr )) {
			$this->view->orderarr = $orderarr [0];
		} else
			$this->_redirect ( '/center/order' );
	}
	/*
	 * 订单支付post处理
	 */
	public function payactionAction() {
		// 登录检查
		$this->common = new MyCommon ();
		$this->common->loginCheck ();
		
		$this->_helper->layout->disableLayout ();
		// 提交处理
		if ($this->getRequest ()->isPost ()) {
			$formData = $this->getRequest ()->getPost ();
			$salesnumber = $this->filter->pregHtmlSql ( $formData ['salesnumber'] );
			$productname = $this->filter->pregHtmlSql ( $formData ['productname'] );
			$orderprice = $this->filter->pregHtmlSql ( $formData ['paynumber'] );
			$remarkexplain = $this->filter->pregHtmlSql ( $formData ['remarkexplain'] );
			$paytype = $this->filter->pregHtmlSql ( $formData ['paytype'] );
			$error = 0;
			$message = '';
			if (! $salesnumber || ! $productname || ! $orderprice || ! $remarkexplain || ! $paytype) {
				$message = '参数为空。';
				$error ++;
			}
			$re = $this->salesorderModel->getRowByWhere ( "salesnumber='{$salesnumber}' AND uid='" . $_SESSION ['userInfo'] ['uidSession'] . "' AND status='101'  AND paytype='online' AND available='1'" );
			//如果询价订单
			if(empty ( $re )){
				$re = $this->inqsalesorderModel->getRowByWhere ( "salesnumber='{$salesnumber}' AND uid='" . $_SESSION ['userInfo'] ['uidSession'] . "' AND status='101'  AND paytype='online' AND available='1'" );
			    if (empty ( $re )) {
				  $message = '订单不存在。';
				  $error ++;
			    }
			}
			// ---------------------------------------------------------
			// 财付通即时到帐支付请求示例，商户按照此文档进行开发即可
			// ---------------------------------------------------------
			if ($error) {
				echo $message;
				// $this->_redirect('/');
				exit ();
			} else {
				// 财付通支付种类
				$banktypeArray = array ('tenpay' => "DEFAULT", 'zsyh' => 1001, 'gsyh' => 1002, 'jsyh' => 1003, 
						'pdfzyh' => 1004, 'lyyh' => 1005, 'msyh' => 1006, 
						'szfzyh' => 1008, 'xyyh' => 1009, 'payh' => 1010, 
						'jtyh' => 1020, 'zxyh' => 1021, 'gdyh' => 1022, 
						'shyh' => 1024, 'hxyh' => 1025, 'gfyh' => 1027, 
						'yzyh' => 1028, 'zsyhxyk' => 1038, 'bjyh' => 1032, 
						'wht' => 1033, 'zgyh' => 1052 );
				// 支付宝银行支付种类
				$albanktypeArray = array ('alipay' => "alipay", 'ICBCB2C' => 'ICBCB2C', 'CMB' => 'CMB', 'CCB' => 'CCB',
						'BOCB2C' => 'BOCB2C', 'ABC' => 'ABC', 'COMM' => 'COMM',
						'PSBC-DEBIT' => 'PSBC-DEBIT', 'CEBBANK' => 'CEBBANK', 'SPDB' => 'SPDB',
						'GDB' => 'GDB', 'CITIC' => 'CITIC', 'CIB' => 'CIB',
						'SDB' => 'SDB', 'CMBC' => 'CMBC', 'BJBANK' => 'BJBANK',
						'HZCBB2C' => 'HZCBB2C', 'SHBANK' => 'SHBANK', 'BJRCB' => 'BJRCB',
						'SPABANK' => 'SPABANK', 'FDB' => 'FDB','WZCBB2C-DEBIT'=>'WZCBB2C-DEBIT',
						'NBBANK' => 'NBBANK', 'ICBCBTB' => 'ICBCBTB','CCBBTB'=>'CCBBTB',
						'SPDBB2B' => 'SPDBB2B', 'ABCBTB' => 'ABCBTB');
				// 支付宝
				//默认支付方式，取值见“纯网关接口”技术文档中的请求参数列表
				$paymethod    = '';
				//默认网银代号，代号列表见“纯网关接口”技术文档“附录”→“银行列表”
				$defaultbank  = '';
				if (array_key_exists ( $paytype, $albanktypeArray ))
				{
				    if ($paytype == 'alipay'){
	                    $paymethod = 'directPay';
                    }else {
	                    $paymethod = 'bankPay';
	                    $defaultbank = $albanktypeArray[$paytype];
                    }
					$paymethod = 'directPay';
					$this->view->alipayhtml = $this->alipaydual ( $salesnumber, $productname, $orderprice, $remarkexplain,$paymethod,$defaultbank);
					$this->view->paytype = $paytype;
					$this->view->banktypeArray = array();
				} elseif (array_key_exists ( $paytype, $banktypeArray )) 				// 财付通
				{
					// 请求的URL
					$reqHandler = $this->tenpaydual ( $salesnumber, $productname, $orderprice, $remarkexplain, $banktypeArray [$paytype] );
					$this->view->paytype = $paytype;
					$this->view->banktypeArray = $banktypeArray;
					$this->view->salesnumber = $salesnumber;
					$this->view->params = $reqHandler->getAllParameters ();
					$this->view->reqHandler = $reqHandler->getGateUrl ();
				
				} else {
					$message = '支付平台不存在。';
					$this->_redirect ( '/' );
					exit ();
				}
			}
		} else
			$this->_redirect ( '/' );
	}
	/*
	 * 支付post后调转页面
	 */
	public function orderpayshowAction() {
		// 登录检查
		$this->common = new MyCommon ();
		$this->common->loginCheck ();
		$this->view->ordertype = 'online';
		$salesnumber = $this->filter->pregHtmlSql ( $_GET ['salesnumber'] );
		$sqlstr = "SELECT so.salesnumber,so.paytype,so.total,p.province,c.city,e.area,a.name,a.address,a.mobile,a.tel
    	FROM sales_order as so 
		LEFT JOIN order_address as a ON so.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	WHERE so.salesnumber=:sonum AND so.uid=:uidtmp AND so.status='101' AND so.paytype='online' AND so.available='1'";
		$orderarr = $this->salesorderModel->getBySql ( $sqlstr, array ('sonum' => $salesnumber, 'uidtmp' => $_SESSION ['userInfo'] ['uidSession'] ) );
		//如果询价订单
		if(empty ( $orderarr )){
			$sqlstr = "SELECT so.salesnumber,so.paytype,so.total,p.province,c.city,e.area,a.name,a.address,a.mobile,a.tel
    		FROM inq_sales_order as so
			LEFT JOIN order_address as a ON so.addressid=a.id
    		LEFT JOIN province as p ON a.province=p.provinceid
    		LEFT JOIN city as c ON a.city=c.cityid
    		LEFT JOIN area as e ON a.area = e.areaid
    		WHERE so.salesnumber=:sonum AND so.uid=:uidtmp AND so.status='101' AND so.paytype='online' AND so.available='1'";
			$orderarr = $this->inqsalesorderModel->getBySql ( $sqlstr, array ('sonum' => $salesnumber, 'uidtmp' => $_SESSION ['userInfo'] ['uidSession'] ) );
			$this->view->ordertype = 'inq';
		}
		if (! empty ( $orderarr )) {
			$this->view->orderarr = $orderarr [0];
		} else
			$this->_redirect ( '/center/order' );
	}
	/*
	 * 财付通支付 获取参数
	*/
	public function tenpaydual($out_trade_no, $product_name, $order_price, $remarkexplain, $bank_type) {
		// 登录检查
		$this->common = new MyCommon ();
		$this->common->loginCheck ();
		/*
		 * 获取提交的订单号 $out_trade_no
		*/
		/* 获取提交的商品名称 $product_name;*/
		/* 获取提交的商品价格 $order_price*/
		/* 获取提交的备注信息$remarkexplain */
		/* 银行类型 $bank_type */
		/* 商品价格（包含运费），以分为单位 */
		$total_fee = $order_price * 100;
		/*
		 * 商品名称
		*/
		$desc = $product_name . ",备注:" . $remarkexplain;
		/*
		 * 创建支付请求对象
		*/
		$reqHandler = new RequestHandler ();
		$reqHandler->init ();
		$reqHandler->setKey ( $this->tenpaykey );
		$reqHandler->setGateUrl ( "https://gw.tenpay.com/gateway/pay.htm" );
		// ----------------------------------------
		// 设置支付参数
		// ----------------------------------------
		$reqHandler->setParameter ( "partner", $this->tenpaypartner );
		$reqHandler->setParameter ( "out_trade_no", $out_trade_no );
		$reqHandler->setParameter ( "total_fee", $total_fee ); // 总金额
		$reqHandler->setParameter ( "return_url", $this->tenpayreturn_url );
		$reqHandler->setParameter ( "notify_url", $this->tenpaynotify_url );
		$reqHandler->setParameter ( "body", $desc );
		$reqHandler->setParameter ( "bank_type", $bank_type ); // 银行类型，默认为财付通
		// 用户ip
		$reqHandler->setParameter ( "spbill_create_ip", $_SERVER ['REMOTE_ADDR'] ); // 客户端IP
		$reqHandler->setParameter ( "fee_type", "1" ); // 币种
		$reqHandler->setParameter ( "subject", $desc ); // 商品名称，（中介交易时必填）
	
		// 系统可选参数
		$reqHandler->setParameter ( "sign_type", "MD5" ); // 签名方式，默认为MD5，可选RSA
		$reqHandler->setParameter ( "service_version", "1.0" ); // 接口版本号
		$reqHandler->setParameter ( "input_charset", "utf-8" ); // 字符集
		$reqHandler->setParameter ( "sign_key_index", "1" ); // 密钥序号
	
		// 业务可选参数
		$reqHandler->setParameter ( "attach", "" ); // 附件数据，原样返回就可以了
		$reqHandler->setParameter ( "product_fee", "" ); // 商品费用
		$reqHandler->setParameter ( "transport_fee", "0" ); // 物流费用
		$reqHandler->setParameter ( "time_start", date ( "YmdHis" ) ); // 订单生成时间
		$reqHandler->setParameter ( "time_expire", "" ); // 订单失效时间
		$reqHandler->setParameter ( "buyer_id", "" ); // 买方财付通帐号
		$reqHandler->setParameter ( "goods_tag", "" ); // 商品标记
		$reqHandler->setParameter ( "trade_mode", "1" ); // 交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
		$reqHandler->setParameter ( "transport_desc", "" ); // 物流说明
		$reqHandler->setParameter ( "trans_type", "1" ); // 交易类型
		$reqHandler->setParameter ( "agentid", "" ); // 平台ID
		$reqHandler->setParameter ( "agent_type", "" ); // 代理模式（0.无代理，1.表示卡易售模式，2.表示网店模式）
		$reqHandler->setParameter ( "seller_id", "" ); // 卖家的商户号
		// 运行
		$reqHandler->getRequestURL ();
		return $reqHandler;
	}
	/*
	 * 财付通:进入前台回调页面
	 */
	public function tenpayviewAction() {
		$this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		// 登录检查
		/*
		 * 创建支付应答对象
		 */
		$resHandler = new ResponseHandler ();
		$resHandler->setKey ( $this->tenpaykey );
		
		// 判断签名
		if ($resHandler->isTenpaySign ()) {
			// 通知id
			$notify_id = $resHandler->getParameter ( "notify_id" );
			// 商户订单号
			$out_trade_no = $resHandler->getParameter ( "out_trade_no" );
			// 财付通订单号
			$transaction_id = $resHandler->getParameter ( "transaction_id" );
			// 金额,以分为单位
			$total_fee = $resHandler->getParameter ( "total_fee" );
			// 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
			$discount = $resHandler->getParameter ( "discount" );
			// 支付结果
			$trade_state = $resHandler->getParameter ( "trade_state" );
			// 交易模式,1即时到账
			$trade_mode = $resHandler->getParameter ( "trade_mode" );
			
			if ("1" == $trade_mode) {
				if ("0" == $trade_state) {
					// ------------------------------
					// 处理业务开始
					// ------------------------------
					// 注意交易单不要重复处理
					// 注意判断返回金额
					echo $notify_id . ' ; ' . $out_trade_no . ' ; ' . $transaction_id . ' ; ' . $total_fee . ' ; ' . $discount . ' ; ' . $trade_state . ' ; ' . $trade_mode;
					echo "<pre>";
					print_r ( $out_trade_no );
					// ------------------------------
					// 处理业务完毕
					// ------------------------------
					echo "<br/>" . "即时到帐支付成功" . "<br/>";
				} else {
					// 当做不成功处理
					echo "<br/>" . "即时到帐支付失败" . "<br/>";
				}
			} elseif ("2" == $trade_mode) {
				if ("0" == $trade_state) {
					// ------------------------------
					// 处理业务开始
					// ------------------------------
					// 注意交易单不要重复处理
					// 注意判断返回金额
					
					// ------------------------------
					// 处理业务完毕
					// ------------------------------
					echo "<br/>" . "中介担保支付成功" . "<br/>";
				} else {
					// 当做不成功处理
					echo "<br/>" . "中介担保支付失败" . "<br/>";
				}
			}
		} else {
			echo "<br/>" . "认证签名失败" . "<br/>";
			echo $resHandler->getDebugInfo () . "<br>";
		}
	}
	/*
	 * 财付通:进入后台回调页面
	 */
	public function tenpaycontrollersAction() {
		$this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		/*
		 * 创建支付应答对象
		 */
		$resHandler = new ResponseHandler ();
		$resHandler->setKey ( $this->tenpaykey );
		
		// 判断签名
		if ($resHandler->isTenpaySign ()) {
			
			// 通知id
			$notify_id = $resHandler->getParameter ( "notify_id" );
			
			// 通过通知ID查询，确保通知来至财付通
			// 创建查询请求
			$queryReq = new RequestHandler ();
			$queryReq->init ();
			$queryReq->setKey ( $this->tenpaykey );
			$queryReq->setGateUrl ( "https://gw.tenpay.com/gateway/simpleverifynotifyid.xml" );
			$queryReq->setParameter ( "partner", $this->tenpaypartner );
			$queryReq->setParameter ( "notify_id", $notify_id );
			
			// 通信对象
			$httpClient = new TenpayHttpClient ();
			$httpClient->setTimeOut ( 5 );
			// 设置请求内容
			$httpClient->setReqContent ( $queryReq->getRequestURL () );
			
			// 后台调用
			if ($httpClient->call ()) {
				// 设置结果参数
				$queryRes = new ClientResponseHandler ();
				$queryRes->setContent ( $httpClient->getResContent () );
				$queryRes->setKey ( $this->tenpaykey );
				
				if ($resHandler->getParameter ( "trade_mode" ) == "1") {
					// 判断签名及结果（即时到帐）
					// 只有签名正确,retcode为0，trade_state为0才是支付成功
					if ($queryRes->isTenpaySign () && $queryRes->getParameter ( "retcode" ) == "0" && $resHandler->getParameter ( "trade_state" ) == "0") {
						log_result ( "即时到帐验签ID成功" );
						// 取结果参数做业务处理
						$out_trade_no = $resHandler->getParameter ( "out_trade_no" );
						// 财付通订单号
						$transaction_id = $resHandler->getParameter ( "transaction_id" );
						// 金额,以分为单位
						$total_fee = $resHandler->getParameter ( "total_fee" );
						// 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
						$discount = $resHandler->getParameter ( "discount" );
						
						// ------------------------------
						// 处理业务开始
						// ------------------------------
						
						// 处理数据库逻辑
						// 注意交易单不要重复处理
						// 注意判断返回金额
						echo $notify_id . ' ; ' . $out_trade_no . ' ; ' . $transaction_id . ' ; ' . $total_fee . ' ; ' . $discount;
						echo "<pre>";
						print_r ( $out_trade_no );
						// ------------------------------
						// 处理业务完毕
						// ------------------------------
						log_result ( "即时到帐后台回调成功" );
						echo "success";
					
					} else {
						// 错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
						// echo "验证签名失败 或 业务错误信息:trade_state=" .
						// $resHandler->getParameter("trade_state") .
						// ",retcode=" . $queryRes-> getParameter("retcode").
						// ",retmsg=" . $queryRes->getParameter("retmsg") .
						// "<br/>" ;
						log_result ( "即时到帐后台回调失败" );
						echo "fail";
					}
				} elseif ($resHandler->getParameter ( "trade_mode" ) == "2") 

				{
					// 判断签名及结果（中介担保）
					// 只有签名正确,retcode为0，trade_state为0才是支付成功
					if ($queryRes->isTenpaySign () && $queryRes->getParameter ( "retcode" ) == "0") {
						log_result ( "中介担保验签ID成功" );
						// 取结果参数做业务处理
						$out_trade_no = $resHandler->getParameter ( "out_trade_no" );
						// 财付通订单号
						$transaction_id = $resHandler->getParameter ( "transaction_id" );
						// 金额,以分为单位
						$total_fee = $resHandler->getParameter ( "total_fee" );
						// 如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
						$discount = $resHandler->getParameter ( "discount" );
						
						// ------------------------------
						// 处理业务开始
						// ------------------------------
						
						// 处理数据库逻辑
						// 注意交易单不要重复处理
						// 注意判断返回金额
						
						log_result ( "中介担保后台回调，trade_state=" + $resHandler->getParameter ( "trade_state" ) );
						switch ($resHandler->getParameter ( "trade_state" )) {
							case "0" : // 付款成功
								
								break;
							case "1" : // 交易创建
								
								break;
							case "2" : // 收获地址填写完毕
								
								break;
							case "4" : // 卖家发货成功
								
								break;
							case "5" : // 买家收货确认，交易成功
								
								break;
							case "6" : // 交易关闭，未完成超时关闭
								
								break;
							case "7" : // 修改交易价格成功
								
								break;
							case "8" : // 买家发起退款
								
								break;
							case "9" : // 退款成功
								
								break;
							case "10" : // 退款关闭
								
								break;
							default :
								// nothing to do
								break;
						}
						// ------------------------------
						// 处理业务完毕
						// ------------------------------
						echo "success";
					} else {
						// 错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
						// echo "验证签名失败 或 业务错误信息:trade_state=" .
						// $resHandler->getParameter("trade_state") .
						// ",retcode=" . $queryRes-> getParameter("retcode").
						// ",retmsg=" . $queryRes->getParameter("retmsg") .
						// "<br/>" ;
						log_result ( "中介担保后台回调失败" );
						echo "fail";
					}
				}
				// 获取查询的debug信息,建议把请求、应答内容、debug信息，通信返回码写入日志，方便定位问题
				/*
				 * echo
				 * "<br>------------------------------------------------------<br>";
				 * echo "http res:" . $httpClient->getResponseCode() . "," .
				 * $httpClient->getErrInfo() . "<br>"; echo "query req:" .
				 * htmlentities($queryReq->getRequestURL(), ENT_NOQUOTES,
				 * "GB2312") . "<br><br>"; echo "query res:" .
				 * htmlentities($queryRes->getContent(), ENT_NOQUOTES, "GB2312")
				 * . "<br><br>"; echo "query reqdebug:" .
				 * $queryReq->getDebugInfo() . "<br><br>" ; echo "query
				 * resdebug:" . $queryRes->getDebugInfo() . "<br><br>";
				 */
			} else {
				// 通信失败
				echo "fail";
				// 后台调用通信失败,写日志，方便定位问题
				echo "<br>call err:" . $httpClient->getResponseCode () . "," . $httpClient->getErrInfo () . "<br>";
			}
		} else {
			echo "<br/>" . "认证签名失败" . "<br/>";
			echo $resHandler->getDebugInfo () . "<br>";
		}
	}
	
	/*
	 * 支付宝支付 获取参数
	*/
	public function alipaydual($salesnumber, $productname, $orderprice, $remarkexplain,$paymethod,$defaultbank) {
		/**
		 * ************************请求参数*************************
		 */
		// 登录检查
		$this->common = new MyCommon ();
		$this->common->loginCheck ();
		// 必填参数//
	
		// 请与贵网站订单系统中的唯一订单号匹配
		$out_trade_no = $salesnumber;
		// 订单名称，显示在支付宝收银台里的“商品名称”里，显示在支付宝的交易管理的“商品名称”的列表里。
		$subject = $productname;
		// 订单描述、订单详细、订单备注，显示在支付宝收银台里的“商品描述”里
		$body = $remarkexplain;
		// 订单总金额，显示在支付宝收银台里的“应付总额”里
		$total_fee = $orderprice;
	
		// 扩展功能参数——默认支付方式//
	
		// 默认支付方式，取值见“纯网关接口”技术文档中的请求参数列表
		$paymethod = '';
		// 默认网银代号，代号列表见“纯网关接口”技术文档“附录”→“银行列表”
		//$defaultbank = '';
		// 支付宝
		//$paymethod = 'directPay';
	
		// 扩展功能参数——防钓鱼//
	
		// 防钓鱼时间戳
		$anti_phishing_key = '';
		// 获取客户端的IP地址，建议：编写获取客户端IP地址的程序
		$exter_invoke_ip = '';
		// 注意：
		// 1.请慎重选择是否开启防钓鱼功能
		// 2.exter_invoke_ip、anti_phishing_key一旦被使用过，那么它们就会成为必填参数
		// 3.开启防钓鱼功能后，服务器、本机电脑必须支持SSL，请配置好该环境。
		// 示例：
		// $exter_invoke_ip = '202.1.1.1';
		// $ali_service_timestamp = new AlipayService($aliapy_config);
		// $anti_phishing_key =
		// $ali_service_timestamp->query_timestamp();//获取防钓鱼时间戳函数
		// 扩展功能参数——其他//
	
		// 商品展示地址，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
		$show_url = "http://".$_SERVER ['HTTP_HOST'];
		// 自定义参数，可存放任何内容（除=、&等特殊字符外），不会显示在页面上
		$extra_common_param = '';
	
		// 扩展功能参数——分润(若要使用，请按照注释要求的格式赋值)
		$royalty_type = ""; // 提成类型，该值为固定值：10，不需要修改
		$royalty_parameters = "";
		// 注意：
		// 提成信息集，与需要结合商户网站自身情况动态获取每笔交易的各分润收款账号、各分润金额、各分润说明。最多只能设置10条
		// 各分润金额的总和须小于等于total_fee
		// 提成信息集格式为：收款方Email_1^金额1^备注1|收款方Email_2^金额2^备注2
		// 示例：
		// royalty_type = "10"
		// royalty_parameters= "111@126.com^0.01^分润备注一|222@126.com^0.01^分润备注二"
	
		// 构造要请求的参数数组
		$parameter = array ("service" => "create_direct_pay_by_user",
				"payment_type" => "1",
				"partner" => trim ( $this->aliconfig ['partner'] ),
				"_input_charset" => trim ( strtolower ( $this->aliconfig ['input_charset'] ) ),
				"seller_email" => trim ( $this->aliconfig ['seller_email'] ),
				"return_url" => trim ( $this->aliconfig ['return_url'] ),
				"notify_url" => trim ( $this->aliconfig ['notify_url'] ),
				"out_trade_no" => $out_trade_no,
				"subject" => $subject,
				"body" => $body,
				"total_fee" => $total_fee,
				"paymethod" => $paymethod,
				"defaultbank" => $defaultbank,
				"anti_phishing_key" => $anti_phishing_key,
				"exter_invoke_ip" => $exter_invoke_ip,
				"show_url" => $show_url,
				"extra_common_param" => $extra_common_param,
				"royalty_type" => $royalty_type,
				"royalty_parameters" => $royalty_parameters );
		// 构造纯网关接口
		$alipayService = new AlipayService ( $this->aliconfig );
		$html_text = $alipayService->create_direct_pay_by_user ( $parameter );
		return $html_text;
	}
	/*
	 * 支付宝:服务器异步通知页面
	*/
	public function alipaynotifyAction() {
		$this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		/*
		 * 功能：支付宝服务器异步通知页面 版本：3.2 日期：2011-03-25 说明：
		* 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
		* 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
		* ************************页面功能说明*************************
		* 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
		* 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
		* 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
		* TRADE_FINISHED(表示交易已经成功结束，并不能再对该交易做后续操作);
		* TRADE_SUCCESS(表示交易已经成功结束，可以对该交易做后续操作，如：分润、退款等);
		*/
	
		// 计算得出通知验证结果
	
		$alipayNotify = new AlipayNotify ( $this->aliconfig );
	
		$verify_result = $alipayNotify->verifyNotify ();
		if ($verify_result) { // 验证成功
	
			// 请在这里加上商户的业务逻辑程序代
	
			// ——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			// 获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			$out_trade_no = $_POST ['out_trade_no']; // 获取订单号
			$trade_no     = $_POST ['trade_no']; // 获取支付宝交易号
			$total_fee    = $_POST ['total_fee']; // 获取总价格
	
			if ($_POST ['trade_status'] == 'TRADE_FINISHED') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
	             
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。
				$re = $this->updateOrder('alipay',$trade_no,$out_trade_no,$total_fee);
				if($re) $this->sendMail('alipay',$out_trade_no,$total_fee,$re);
 				// 调试用，写文本函数记录程序运行情况是否正常
				// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
			} else if ($_POST ['trade_status'] == 'TRADE_SUCCESS') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				$re = $this->updateOrder('alipay',$trade_no,$out_trade_no,$total_fee);
				if($re) $this->sendMail('alipay',$out_trade_no,$total_fee,$re);
				// 注意：
				// 该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。
				// 调试用，写文本函数记录程序运行情况是否正常
				// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
			}
	
			// ——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	
			echo "success"; // 请不要修改或删除
	
		} else {
			// 验证失败
			echo "fail";
	
			// 调试用，写文本函数记录程序运行情况是否正常
			// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}
	
	/*
	 * 支付宝:服务器同步通知页面
	*/
	public function alipayreturnAction() {
		$this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		/*
		 * 功能：支付宝页面跳转同步通知页面 版本：3.2 日期：2011-03-25 说明：
		* 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
		* 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
		* ************************页面功能说明************************* 该页面可在本机电脑测试
		* 可放入HTML等美化页面的代码、商户业务逻辑程序代码
		* 该页面可以使用PHP开发工具调试，也可以使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyReturn
		* TRADE_FINISHED(表示交易已经成功结束，并不能再对该交易做后续操作);
		* TRADE_SUCCESS(表示交易已经成功结束，可以对该交易做后续操作，如：分润、退款等);
		*/
		// 计算得出通知验证结果
		$alipayNotify = new AlipayNotify ( $this->aliconfig );
		$verify_result = $alipayNotify->verifyReturn ();
		if ($verify_result) { // 验证成功
			// 请在这里加上商户的业务逻辑程序代码
	
			// ——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			// 获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
			$out_trade_no = $_GET ['out_trade_no']; // 获取订单号
			$trade_no = $_GET ['trade_no']; // 获取支付宝交易号
			$total_fee = $_GET ['total_fee']; // 获取总价格
	
			if ($_GET ['trade_status'] == 'TRADE_FINISHED' || $_GET ['trade_status'] == 'TRADE_SUCCESS') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				$re = $this->updateOrder('alipay',$trade_no,$out_trade_no,$total_fee);
				if($re) $this->sendMail('alipay',$out_trade_no,$total_fee,$re);
				//echo $trade_no.' , '.$out_trade_no.' , '.$total_fee;
				if($re == 'online'){
					$this->_redirect ( '/center/order?type=send' );
				}elseif($re == 'inq'){
					$this->_redirect ( '/center/inqorder?type=proc' );
				}else {$this->_redirect ( '/center' );}
			} else {
				//echo "trade_status=" . $_GET ['trade_status'];
				$this->_redirect ( '/center/order?type=send' );
			}
			// ——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
		} else {
			// 验证失败
			// 如要调试，请看alipay_notify.php页面的verifyReturn函数，比对sign和mysign的值是否相等，或者检查$responseTxt有没有返回true
			echo "支付失败";
			$this->_redirect ( '/center/order?type=rec' );
		}
	}
	/*
	 * 支付成功，更新订单
	 */
	private function updateOrder($patname,$patid,$salesnumer,$total){
		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
		$uparr = array('pay_name'=>$patname,'payid'=>$patid,'status'=>201,'pay_time'=>time());
		$re = $salesorderModel->update($uparr, "salesnumber='{$salesnumer}' AND status=101 AND total='{$total}'");
		//记录日志
		$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumer,'temp4'=>'支付宝支付成功','description'=>$total));
		if($re) return 'online';
		else{
			$inqsalesorderModel   = new Default_Model_DbTable_InqSalesOrder();
			$uparr = array('pay_name'=>$patname,'payid'=>$patid,'status'=>102,'pay_time'=>time());
			$re = $inqsalesorderModel->update($uparr, "salesnumber='{$salesnumer}' AND status=101 AND total='{$total}'");
			if($re) return 'inq';
			return false;
		}
	}
	/**
	 * 支付成功发邮件通知，内部
	 */
	public function sendMail($patname,$salesnumer,$total,$ordertype){
		
		//销售信息
		$staffservice = new Icwebadmin_Service_StaffService();
		
		$this->emailService = new Default_Service_EmailtypeService();
		if($ordertype=='online'){
			$orderService = new Default_Service_OrderService();
			$orderarr = $orderService->geSoinfo($salesnumer,1);
			$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                                在线订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$salesnumer.'</strong>，客户已经在线支付了货款，支付金额：<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB</span>'.$total.'</strong>。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">请到盛芯电子后台 <b style="font-size:14px; color:#fd2323">释放订单</b>，谢谢！</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
			$mess .= $orderService->getOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
			$fromname = '盛芯电子';
			$title    = '在线订单#：'.$salesnumer.'的货款已支付，请确认是否到账';
		}elseif($ordertype=='inq'){
			$orderService = new Default_Service_InqOrderService();
			$orderarr = $orderService->geSoinfo($salesnumer,1);
			$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                                询价订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$salesnumer.'</strong>，客户已经在线支付了货款，支付金额：<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB</span>'.$total.'</strong>。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">请到盛芯电子后台 <b style="font-size:14px; color:#fd2323">释放订单</b>，谢谢！</div></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
			$mess .= $orderService->getInqOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
			$fromname = '盛芯电子';
			$title    = '询价订单#：'.$salesnumer.'的货款已支付，请确认是否到账';
		}
		$emailarr = $this->emailService->getEmailAddress('online_pay_alert',$orderarr['uid']);
		$emailto = array($sellinfo['email']);
		//如果有抄送人
		$staffService = new Icwebadmin_Service_StaffService();
		$emailcc = $staffService->mailtostaff($sellinfo['mail_to']);
		$emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailcc,$emailarr['cc']);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//记录日志
		$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumer,'temp4'=>'支付宝支付成功提醒销售邮件成功'));
		$this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
		
		//发邮件给客户
		$this->sendMailToUser($salesnumer,$total,$ordertype,$orderService);
	}
	/**
	 * 支付成功发邮件通知，用户
	 */
	public function sendMailToUser($salesnumer,$total,$ordertype,$orderService){
		$this->emailService = new Default_Service_EmailtypeService();
		//用户信息
		$userService = new Default_Service_UserService();
		if($ordertype=='online'){
			$orderarr = $orderService->geSoinfo($salesnumer,1);
			$userinfo = $userService->getUserProfileByUid($orderarr['uid']);
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$userinfo['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                                感谢您对盛芯电子的惠顾！在线订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;"> '.$salesnumer.' </strong>，我们已收到您在线支付的货款，支付金额：<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB</span> '.$total.'</strong>。<br />我们会尽快安排发货。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
			$mess .= $orderService->getOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
			$fromname = '盛芯电子';
			$title    = '您的盛芯电子订单#：'.$salesnumer.'的货款已确认收到';
		}elseif($ordertype=='inq'){
			$orderarr = $orderService->geSoinfo($salesnumer,1);
			$userinfo = $userService->getUserProfileByUid($orderarr['uid']);
				$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$userinfo['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                                感谢您对盛芯电子的惠顾！询价订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;"> '.$salesnumer.' </strong>，我们已收到您在线支付的货款，支付金额：<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB</span> '.$total.'</strong>。<br />我们会尽快安排发货。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
			$mess .= $orderService->getInqOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
			$fromname = '盛芯电子';
			$title    = '您的盛芯电子订单#：'.$salesnumer.'的货款已确认收到';
		}
		$emailarr = $this->emailService->getEmailAddress('online_order',$orderarr['uid']);
		$emailto = array('0'=>$userinfo['email']);
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
		$this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
		//记录日志
		$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$salesnumer,'temp4'=>'支付宝支付成功提醒客户邮件成功'));
	}
}

