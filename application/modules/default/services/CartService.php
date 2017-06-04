<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
class Default_Service_CartService
{
	private $_proService;
	private $_inqService;
	private $_cart;
	private $_fun;
	public function __construct()
	{
		$this->_proService = new Default_Service_ProductService();
		$this->_inqService = new Default_Service_InquiryService();
		$this->_cart = new Cart();
		$this->_fun  = new MyFun();
	}
	//根据ip取购物车寄存并加入购物车
	public function getCartDeposit()
	{
		return true;
		if(!isset($_SESSION['cartnumber']) && !isset($_SESSION['inquirynumber']))
		{
			$cdModel  = new Default_Model_DbTable_CartDeposit();
			$productModel = new Default_Model_DbTable_Product();
			$allcart = $cdModel->getAllByWhere("ip='".$this->_fun->getIp()."' AND status=1");
			for($i=0;$i<count($allcart);$i++){
				$id         = $allcart[$i]['prod_id'];
				$qty        = $allcart[$i]['number'];
				$bpp_stock_id = $allcart[$i]['bpp_stock_id'];
				$hope_price = $allcart[$i]['hope_price'];
				$expected_amount = $allcart[$i]['expected_amount'];
				$type= $allcart[$i]['type'];
				//购物车
				if($type==1){
					$product = $this->_proService->getStockProd($id);
					if(!empty($product))
					{
						$this->addCartlist($id, $qty, $product,$allcart[$i]['delivery'],$bpp_stock_id);
					}
				}
				//询价列表
				if($type==2){
					$product = $this->_proService->getInqProd($id);
					if(!empty($product))
					{
					    $this->_inqService->addInquiry($id,$qty,$hope_price,$expected_amount,$product);
					}
				}
			}
			//购物车记录数，用于头部显示
			$_SESSION['cartnumber']    = $this->total_items();
			$_SESSION['inquirynumber'] = $this->_inqService->total_items();
		}
	}
	/*
	 * 插入到购物车
	 */
	public function addCartlist($collection_id,$supplier_id,$id,$qty,$product,$delivery_place,$bpp_stock_id=0){
		if($delivery_place=='SZ'){
		  $show_break_price = $product['stockInfo']['rmbprice'];
		  $byprice = $this->_fun->getPrice($product['stockInfo']['rmbprice'], $qty);
		  $unit = $product['f_rmb'];
		  $show_bprice = $this->_fun->getbreakprice_notitle($product['stockInfo']['rmbprice'],$unit);
		}elseif($delivery_place=='HK'){
		  $show_break_price = $product['stockInfo']['usdprice'];
		  $byprice = $this->_fun->getPrice($product['stockInfo']['usdprice'], $qty);
		  $unit = $product['f_usd'];
		  $show_bprice = $this->_fun->getbreakprice_notitle($product['stockInfo']['usdprice'],$unit);
		}
		$items = array(
				'pord_id'        => $id,
				'collection_id'  => $collection_id,
				'supplier_id'    => $supplier_id,
				'id'             => $id.$delivery_place.$supplier_id,
				'part_no'        => $product['part_no'],
				'qty'            => $qty,
				'delivery_place' => $delivery_place,
				'break_price'    => $show_break_price,
				'unit'           => $unit,
				'byprice'        => $this->_fun->formnum($byprice),
				'moq'            => $product['stockInfo']['moq'],
				'mpq'            => $product['stockInfo']['moq'],
				'options'        => array(
						'brand'       => $product['manufacturer'],
						'part_img'    => $product['part_img'],
						'show_bprice' => $show_bprice,
						'description' => $product['description']
				));
		//加入购物车
		return $this->_cart->add($items);
	}
	/*
	 * 返回条目数
	 */
	public function total_items(){
		return $this->_cart->total_items();
	}
	/**
	 * 删除购物车寄存
	 */
	public function delectCart($ids){
		$prod_id  = substr($ids,0,strlen($ids)-2);
		$delivery = substr($ids,strlen($ids)-2,2);
		$this->cdModel = new Default_Model_DbTable_CartDeposit();
		return $this->cdModel->update(array('status'=>0),"ip='".$this->_fun->getIp()."' AND prod_id='$prod_id' AND delivery='$delivery' AND type=1");
	}
	/**
	 * 检查购物车数据是否有效
	 */
	public function checkProdInCart($product,$delivery_place,$pordarr)
	{
		//print_r($pordarr);
		$product = $this->_fun->filterProduct($product,$pordarr['options']['bpp_stock_id']);
		if(!$product){
			return false;
		}
		
		if($delivery_place=='SZ'){
			//检查价格
			if(!$product['f_show_price_sz']){
				return false;
			}
			if($product['break_price_rmb']!=$pordarr['break_price']){
				return false;
			}
			//检查moq
			if($product['moq']!=$pordarr['moq']){
				return false;
			}
			//检查库存
			if($product['f_stock_sz']<$pordarr['qty']){
				return 1;
			}
		}elseif($delivery_place=='HK'){
			//检查价格
			if(!$product['f_show_price_hk']){
				return false;
			}
			if($product['break_price']!=$pordarr['break_price']){
				return false;
			}
			//检查moq
			if($product['moq']!=$pordarr['moq']){
				return false;
			}
			//检查库存
			if($product['f_stock_hk']<$pordarr['qty']){
				return 1;
			}
		}
		return 2;
	}
}