<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class SearchController extends Zend_Controller_Action {
	
	private $_searchService;
	private $_prodService;
	private $_defaultlogService;
	private $_supplierCrabService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'search';
		$this->filter = new MyFilter();
		$this->fun = $this->view->fun = new MyFun();
		$this->_searchService = new Default_Service_SearchService();
		
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		$this->_prodService = new Default_Service_ProductService();
		$this->config = Zend_Registry::get('config');
		$this->commonconfig = Zend_Registry::get('commonconfig');
		$this->_defaultlogService = new Default_Service_DefaultlogService();
	}
	public function createtmpAction(){
		$re = '';
		if($_GET['key']=='d28f4bc8f53fbb5a2f8d54a389f8d11e'){
		  $this->_helper->layout->disableLayout();
		  $this->_helper->viewRenderer->setNoRender();
		  $re = $this->_prodService->createTmp();
		}
		echo $re?'更新成功':'更新失败';
	}

	public function indexAction() {
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->view->keyword = trim($this->filter->pregHtmlSql($this->_getParam('keyword')));
		$this->view->sup = $sup = $_GET['sup'];
		$keyword = strtoupper($this->view->keyword);
		if(!empty($keyword)){
					
		}else{
			$_SESSION['message'] = '请输入搜索内容！';
		}
		//获取供应商
		$this->_supplierCrabService = new Default_Service_SupplierGrabService();
		$this->view->supplierCrab = $this->_supplierCrabService->getSupplierGrab();
		
	}
	/**
	 * 搜索型号
	 */
	public function productAction(){
		$this->_helper->layout->disableLayout();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$sup = intval($this->_getParam('sup'));
		if($sup==1){
			$this->_helper->viewRenderer->setNoRender();
			echo Zend_Json_Encoder::encode('no');
    		exit;
		}
		if($sup>0){
			$keyworld = trim($this->filter->pregHtmlSql($this->_getParam('keyworld')));
			//获取供应商
			$this->_supplierCrabService = new Default_Service_SupplierGrabService();
			$this->view->supplierCrab = $this->_supplierCrabService->getOneSupplierGrab($sup);
			$crawlerService = new Default_Service_CrawlerService();
			$this->view->product = $crawlerService->getProduct($sup,$keyworld);
			if(empty($this->view->product)){
				$this->_helper->viewRenderer->setNoRender();
				echo Zend_Json_Encoder::encode('no');
				exit;
			}
		}else{
			$this->_helper->viewRenderer->setNoRender();
			echo Zend_Json_Encoder::encode('no');
    		exit;
		}
	}
	/**
	 * supplierGrabTab
	 */
	public function supplierGrabTabAction(){
		$this->_helper->layout->disableLayout();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		
	}
}

