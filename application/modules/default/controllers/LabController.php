<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';

class LabController extends Zend_Controller_Action
{
    public function init()
    {
    	/*
    	 * Initialize action controller here
    	*/
    	//菜单选择
    	$_SESSION['menu'] = 'lab';
    	
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
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    }

    public function indexAction()
    {
    	$id = '201404177';
    	$page = 'index.php';
    	$upload_part = 'upload/event/'.$id.'/';
    	$templateurl = $upload_part.$page;//模板文件路径
    	$this->_eventservice = new Default_Service_EventService();
    	if (file_exists($templateurl)) {
    		$event = $this->_eventservice->getEvent("eventnumber='{$id}'");

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
    		//id
    		$viewer->id = $id;
    		//执行php代码
    		if($event['code']){
    			eval($event['data'].$event['code']);
    		}
    		echo $viewer->render($page);
    	}
    }
    
}

