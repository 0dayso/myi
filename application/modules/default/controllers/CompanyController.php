<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
class CompanyController extends Zend_Controller_Action {
    private $_model;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'news';
		
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
		$this->_model = new Default_Model_DbTable_News();
		$this->seoconfig = Zend_Registry::get('seoconfig');
		$this->commonconfig = Zend_Registry::get('commonconfig');
		$this->_searchService = new Default_Service_SearchService();
	}
	public function indexAction() {
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
		$total = $this->_model->getTotalWhere('news_type_id=3');
		$perpage = $this->commonconfig->page->news;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->newstype);		
		$data = $this->_model->getAll($offset,$perpage,array('news_type_id=3'));
		$this->view->records = $data;
		$appModel = new Default_Model_DbTable_AppCategory();
		$this->view->semLevel1 = $semLevel1 = $appModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		$keywords = $this->seoconfig->general->news_keywords;
		$desc = $this->seoconfig->general->news_description;
		$page_title = $this->seoconfig->general->news_title;
		$this->view->headTitle($page_title,'SET');
		$this->view->headMeta()->setName('keywords', $keywords);
		$this->view->headMeta()->setName('description',$desc);		
	}
}

