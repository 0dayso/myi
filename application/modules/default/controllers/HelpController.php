<?php
require_once 'Iceaclib/common/fun.php';
class HelpController extends Zend_Controller_Action {
	private $_helpModel;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'help';
		
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
		$this->_helpModel = new Default_Model_DbTable_HelpCenter();
	}
	public function indexAction() {
		$this->view->type = ($this->_getParam('type')==''?'login':$this->_getParam('type'));
		$this->view->typecom = $this->_helpModel->getRowByWhere("type = '{$this->view->type}' AND status=1");
	}
	/**
	 * 客服中心
	 */
    public function customerAction() {
		$this->view->type = ($this->_getParam('type')==''?'login':$this->_getParam('type'));
		$this->view->typecom = $this->_helpModel->getRowByWhere("type = '{$this->view->type}' AND status=1");
	}
}

