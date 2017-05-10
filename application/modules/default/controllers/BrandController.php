<?php
class BrandController extends Zend_Controller_Action {
	
	public function init() {
		/*
		 * Initialize action controller here
		*/
		//菜单选择
		$_SESSION['menu'] = 'brand';
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		
		$this->fun =$this->view->fun= new MyFun();
		
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->brandservice = new Default_Service_BrandService();
	}
	public function indexAction() {
		$this->view->allbrand      = $this->brandservice->getAllFcBrand();
		$this->view->proxyallbrand = $this->brandservice->getProxyBrand();
		$this->view->proxybrand    = $this->brandservice->getProxyFcBrand();
	}
}

