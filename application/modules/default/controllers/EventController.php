<?php
require_once 'Iceaclib/common/page.php';
class EventController extends Zend_Controller_Action {
	private $_eventservice;
	private $_productService;
	public function init() {
		$_SESSION['menu'] = 'event';
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
		
		$this->_eventservice = new Default_Service_EventService();
		
		$this->_productService = new Default_Service_ProductService();
	}
	public function indexAction() {
		$this->_redirect('/');
	}
	/*
	 * 
	 */
	public function viewAction() {
		$_SESSION['menu'] = 'event_view';
		$id = $this->_getParam('id');
		$page = $this->_getParam('pagename')?$this->_getParam('pagename').'.php':'index.php';
		$upload_part = 'upload/event/'.$id.'/';
		$templateurl = $upload_part.$page;//模板文件路径
		
		if (file_exists($templateurl)) {
			$event = $this->_eventservice->getEvent("eventnumber='{$id}' AND status=1");
			$_SESSION['no_logbar_event'] = !$event['logbar'];
			$_SESSION['no_menu_event']   = !$event['menu'];
			if(empty($event)) $this->_redirect('/event');
			//重新设置headtitle 、 description和keywords等
			$layout = $this->_helper->layout();
			$view = $layout->getView();
			if($event['headtitle']) $view->headTitle($event['headtitle'],'SET');
			if($event['description']) $view->headMeta()->setName('description',$event['description']);
			if($event['keywords']) $view->headMeta()->setName('keywords',$event['keywords']);
			//加载view
			Zend_Loader::loadClass('Zend_View');
			$viewer = new Zend_View();
			$viewer->setScriptPath($upload_part);
			$viewer->fun =new MyFun();
			//id
			$viewer->id = $id;	
			//执行php代码
		    if($event['code']){
			    eval($event['data'].$event['code']);
			}
			echo $viewer->render($page);
		}else{
			$this->_redirect('/event');
		}
	}
}

