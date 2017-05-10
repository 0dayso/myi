<?php
require_once 'Iceaclib/common/fun.php';
class CategoryController extends Zend_Controller_Action {
	
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'category';
		
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
		//重新设置headtitle 、 description和keywords等
		$this->seoconfig = Zend_Registry::get('seoconfig');
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();
		$viewobj->headTitle($this->seoconfig->general->category_title,'SET');
		$viewobj->headMeta()->setName('description',$this->seoconfig->general->category_description);
		$viewobj->headMeta()->setName('keywords',$this->seoconfig->general->category_keywords);
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	public function indexAction() {
	}
}

