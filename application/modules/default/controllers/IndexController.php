<?php
require_once 'Iceaclib/common/fun.php';
class IndexController extends Zend_Controller_Action {
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'index';
		
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
		$this->productService = new Default_Service_ProductService();
	}
	public function indexAction() {
		$rModer = new Default_Model_DbTable_Recommend();
		$hpModer = new Default_Model_DbTable_HomePhoto();

       
	   //代理品牌
	   $sqlstr ="SELECT re.comid,b.name
		     FROM recommend as re 
	   		 LEFT JOIN brand as b ON re.comid=b.id
		     WHERE re.type='acting_brand' AND re.part='home' AND re.status = 1  AND b.status = 1 ORDER BY re.displayorder LIMIT 0 , 26";
	   $this->view->actingBrand = $rModer->getBySql($sqlstr, array());
	   
		
		//滚动图片
		$this->view->topimageArr = $hpModer->getAllByWhere("status='1' AND type='home'",array("displayorder ASC","id DESC"));
		$this->view->solutionimageArr = $hpModer->getAllByWhere("status='1' AND type='solution'",array("displayorder ASC","id DESC"));
		$this->view->specialimageArr = $hpModer->getAllByWhere("status='1' AND type='special'",array("displayorder ASC","id DESC"));
		
		$listnum = 5;

		//行业新闻
		$newsModel = new Default_Model_DbTable_Model('news');
		$sqlstr ="SELECT id,news_type_id,title
		FROM news
		WHERE status=1 AND news_type_id=1 AND title!='' ORDER BY home DESC,created DESC LIMIT 0 , {$listnum}";
		$this->view->hynews = $newsModel->getBySql($sqlstr);
		//企业动态
		$sqlstr ="SELECT id,news_type_id,title
		FROM news
		WHERE status=1 AND news_type_id=3 AND title!='' ORDER BY home DESC,created DESC LIMIT 0 , {$listnum}";
		$this->view->qynews = $newsModel->getBySql($sqlstr);
		
		//供应商
		$supplierGrab = new Default_Model_DbTable_SupplierGrab();
		$sqlstr ="SELECT * FROM sx_supplier_grab WHERE state = 1 LIMIT 0 , 8";
		$this->view->supplier = $supplierGrab->getBySql($sqlstr);
	}
	/*
	 * ajax 检查是否登录
	*/
	public function checkloginAction() {
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$user = new Default_Model_DbTable_User();
		$sql = "SELECT u.emailapprove,up.companyapprove FROM user as u,user_profile as up
    			WHERE u.uid = up.uid AND u.uid=:uidtmp AND u.enable='1'";
		$reUser = $user->getRowBySql($sql, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(empty($_SESSION['userInfo']['uidSession']))
		{
			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'请登录'));
			exit;
		}elseif($reUser['emailapprove']!=1){
			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'请通过邮箱验证'));
			exit;
		}else{
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'已经登录'));
			exit;
		}
	}
	/**
	 * 
	 */
	public function opensearchAction() {
		$this->_helper->layout->disableLayout();
		$this->view->html ='<?xml version="1.0" encoding="UTF-8"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName>IC易站</ShortName>
  <Description>IC易站 - 为您提供电子元器件设计链、供应链全程服务，行业领先的一站式电子元器件电子商务交易平台！</Description>
  <Url type="text/html" template="http://www.iceasy.com/search?keyword={searchTerms}"/>
  <Language>zh</Language>
  <OutputEncoding>utf-8</OutputEncoding>
  <Contact>Jamie.Feng@ceacsz.com.cn</Contact>
  <Image height="16" width="16" type="image/png">http://www.iceasy.com/images/default/favicon.ico</Image>
</OpenSearchDescription>
';
	}
}

