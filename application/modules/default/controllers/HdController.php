<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/common.php';
class HdController extends Zend_Controller_Action {
	private $_nxpService;
	private $_defaultlogService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'huodong';
		
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		$this->view->fun =$this->fun =new MyFun();
		
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		$this->_nxpService = new Default_Service_HuodongService();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_defaultlogService = new Default_Service_DefaultlogService();
	}
	public function indexAction() {
		$this->_redirect('/');	
	}
	/**
	 * nxp秒杀详细页
	 */
	public function nxpAction(){
		
		$part_id = (int)$_GET['p'];
		if(!$part_id){ $this->_redirect('/');}
		
		$this->view->prodarr =  $this->_nxpService->getNxpProd($part_id);
		if(empty($this->view->prodarr)) { $this->_redirect('/');}

		
	}
	/**
	 * nxp秒杀抢购订单提交页
	 */
	public function qianggouAction(){
		$_SESSION['menu'] = 'hd';
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$part_id = $this->fun->decryptVerification($_GET['k']);
		if(!$part_id){ $this->_redirect('/');}
		
		$this->view->item =  $this->_nxpService->getNxpProd($part_id);
		if(empty($this->view->item)) { $this->_redirect('/hd/nxp?p='.$part_id);}
		elseif($this->view->item['nxp_show_dinghuo']!=0){ $this->_redirect('/hd/nxp?p='.$part_id);}
		//收货地址
		$this->view->addressArr = $arr = $this->_nxpService->getAddress($_SESSION['userInfo']['uidSession']);
		if(!empty($arr))
		{
			$this->view->addressFirst = $arr[0];
			foreach($arr as $arrtmp){
				if($_SESSION['order_sess']['addressid'] == $arrtmp['id'])
					$this->view->addressFirst = $arrtmp;
			}
		}else $this->view->addressFirst = array();
	}
	/**
	 * 秒杀nxp提交订单
	 */
	public function qianggoutijiaoAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$part_id = $this->fun->decryptVerification($_POST['part_id']);
		//产品信息
		$data = $this->_nxpService->getNxpProd($part_id);
		if(!$part_id){
			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'网络繁忙。'));
			exit;
		}
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		//检查用户企业资料是否完备
		$this->_userService = new Default_Service_UserService();
		if(!$this->_userService->checkDetailed()){
			echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'请提交相关企业资料。'));
			exit;
		}
		//判断此用户是否已经购买过
		if($this->_nxpService->checkBuy($_SESSION['userInfo']['uidSession'])){
			echo Zend_Json_Encoder::encode(array("code"=>300,"message"=>'很抱歉，每个账号只允许抢购一次。'));
			exit;
		}
		//判断产品是否售罄
		if($this->_nxpService->checkStock($data)){
			echo Zend_Json_Encoder::encode(array("code"=>500,"message"=>'很抱歉，此型号已经售罄。'));
			exit;
		}
		
		$addressid     = $_POST['addressid'];
		$delivery      = $_POST['delivery'];
		$delivery_type = $_POST['delivery_type'];
		$paymenttype   = $_POST['paymenttype'];
		$remark        = $_POST['remark'];
		
		//取地址
		$addressModel = new Default_Model_DbTable_Address();
		$addre = $addressModel->getRowByWhere("id='{$addressid}'");
		$error = 0;
		if(empty($addre)){
			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'收货人地址不存在，请重新选择。'));
			exit;
		}
		
		//创建订单
		$prodModel         = new Default_Model_DbTable_Product();
		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
		$salesproductModel = new Default_Model_DbTable_SalesProduct();
		$soaddModel        = new Default_Model_DbTable_OrderAddress();
		//开始事务
		$salesorderModel->beginTransaction();
		try{
			//订单号
			$salesnumber = '1'.(intval(date('Y'))%10).date('m').date('d').substr(microtime(),2,4).'-'.substr(time(),-5);
			//占用库存
			$prodModel->updateBySql("UPDATE huodong_nxp_ms SET nxp_stock_cover = nxp_stock_cover + ".$data['nxp_sell_number']." WHERE nxp_id='".$data['nxp_id']."'");
			//地址
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
			//订单主数据
			$paytotal = $data['nxp_sell_number']*$data['nxp_sell_price'];
			$orderdata = array('uid'=>$_SESSION['userInfo']['uidSession'],
					'salesnumber'=>$salesnumber,
					'addressid'=>$soaddid,
					'paytype'=>$paymenttype,
					'freight'=>0,
					'total'=>$paytotal,
					'total_back'=>$paytotal,
					'delivery_place'=>$delivery,
					'delivery_type' =>$delivery_type,
					'currency'=>'RMB',
					'so_type'=>102,
					'consignee'=>$addre['name'],
					'partnos'=>$data['part_no'],
					'status'=>101,
					'available'=>1,
					'remark'   =>$remark,
					'ip'=>$this->fun->getIp(),
					'created'=>time(),
					'modified'=>time());
			$orderid = $salesorderModel->addData($orderdata);
			//订单详细
			if($orderid)
			{
				//记录产品销售详细
				$itemdata = array('salesnumber'=>$salesnumber,
    						    'prod_id' =>$data['id'],
    						    'part_no' =>$data['part_no'],
    			   		        'brand'   =>$data['bname'],
    							'buynum'  =>$data['nxp_sell_number'],
    			   		        'sz_cover'=>$data['nxp_sell_number'],
    			   		        'hk_cover'=>0,
    			   		        'bpp_cover'=>0,
    							'buyprice'=>$data['nxp_sell_price'],
    							'created' =>time());
				$salesproductModel->addData($itemdata);
				//提交完成
				$salesorderModel->commit();
				//异步请求开始
				$this->fun->asynchronousStarts();
				
				//发邮件给销售
				$this->_orderService = new Default_Service_OrderService();
				$orderarr = $this->_orderService->geSoinfo($salesnumber);
				$orderSer = new Default_Service_OrderService();
				$orderSer->sendordermailsell($orderarr);
				
				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'下单成功。','hashkey'=>$this->fun->encryptVerification($salesnumber)));
				//异步请求开始
				$this->fun->asynchronousEnd();
				exit;
			}else{
				$salesorderModel->rollBack();
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'抢购订单提交失败','description'=>'插入主数据失败'));
				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'服务器忙，下单失败'));
				exit;
			}
		} catch (Exception $e) {
				$salesorderModel->rollBack();
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'抢购订单提交失败','description'=>'很抱歉，系统繁忙'));
				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
				exit;
		}
		
	}
	/**
	 * nxp售罄提示
	 */
	public function shouqingAction(){
		$this->_helper->layout->disableLayout();
		$this->view->partId = $_GET['partId'];
		$this->view->r      = $_GET['r'];
		//随机产品
		$rModer = new Default_Model_DbTable_Recommend();
		$sqlstr ="SELECT re.cat_id,br.name as brandname,pro.part_level1,pro.part_level2,pro.part_level3,
			pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.break_price,pro.moq,pro.mpq,
		    pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,pro.bpp_stock,pro.bpp_cover,pro.can_sell,
			pro.surplus_stock_sell,pro.special_break_prices,pro.show_price,pro.price_valid,pro.price_valid_rmb,pc.name,
				pc1.name as cname1,pc2.name as cname2,pc3.name as cname3
		FROM recommend as re
		LEFT JOIN product as pro ON re.comid=pro.id
		LEFT JOIN prod_category as pc ON pro.part_level3=pc.id
		LEFT JOIN brand as br ON re.cat_id=br.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON pro.part_level1=pc1.id
		WHERE re.type='hot' AND re.part='home' AND re.status = 1 AND br.status = 1";
		$allhotArr = $rModer->getBySql($sqlstr, array());
		//随机取出品牌
		$ids    = array_rand($allhotArr,2);
		$hottmp = array();
		foreach($ids as $id){
			$hottmp[] = $allhotArr[$id];
		}
		$this->view->hotprod = $hottmp;
	}
}

