<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
class NewsController extends Zend_Controller_Action {
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
		$this->PartNokeywords = $this->_searchService->getClickPartNokeywords();
	}
	public function indexAction() {
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
		$total = $this->_model->getTotalWhere('news_type_id=2');
		$perpage = $this->commonconfig->page->news;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->newstype);		
		$data = $this->_model->getAll($offset,$perpage,array('news_type_id=2'));
		$this->view->records = $data;
		$appModel = new Default_Model_DbTable_AppCategory();
		$this->view->semLevel1 = $semLevel1 = $appModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		$keywords = $this->seoconfig->general->news_keywords.$this->PartNokeywords;
		$desc = $this->seoconfig->general->news_description;
		$page_title = $this->seoconfig->general->news_title;
		$this->view->headTitle($page_title,'SET');
		$this->view->headMeta()->setName('keywords', $keywords);
		$this->view->headMeta()->setName('description',$desc);		
	}
	/**
	 * 查看详情
	 */
	public function viewAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$id =(int) $this->getRequest()->getParam('id');
		$preview = (int) $this->getRequest()->getParam('preview');
		if($preview==1){
			$data = $this->_model->getOneById($id);
		}else{
			$data = $this->_model->getOneById($id,1);
		}
		$product_model = new Default_Model_DbTable_Product();
		$sqlstr ="SELECT
				pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,pro.break_price,
		   	   	pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,pro.bpp_stock,pro.bpp_cover,
		   	    pro.mpq,pro.moq,pro.can_sell,pro.surplus_stock_sell,pro.special_break_prices,
				pro.show_price,pro.price_valid,pro.price_valid_rmb,pro.description,pro.datasheet,pro.supplier_device_package,pro.packaging,
				pro.rohs,pro.noinquiry,
		   	   	pc.id as pcid,pc.name,br.name as bname
		   	   	FROM product as pro
		   	    LEFT JOIN prod_category as pc ON pro.part_level3 = pc.id
		   	   	LEFT JOIN brand as br ON pro.manufacturer=br.id
		   	   	WHERE pro.id  in ($data[part_id]) AND pro.status=1";
		$part_arr = ($data['part_id']) ?  $product_model->getBySql($sqlstr) : array();
		$total = sizeof($part_arr);
		$page_ob = new Page(array('total'=>$total,'perpage'=>2));	
		$this->view->solprodArray = $part_arr;
		$related = $this->_model->getRelatedNews($id);
		$this->view->related = $related;
		$this->view->data = $data;
		$keywords = $data['keyword'];
		$desc = strip_tags($data['description']);
		$newtype = array('1'=>'行业新闻','2'=>'产品资讯','3'=>'企业动态');
		$page_title = $newtype[$data['news_type_id']]." - ".$data['title'];
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->prodlistype);
		$this->view->headTitle($page_title,'SET');
		$this->view->headMeta()->setName('keywords', $keywords.$this->PartNokeywords);
		$this->view->headMeta()->setName('description',$desc);		
	}
	
	public function listAction()
	{
		$app_level1 =(int) $this->getRequest()->getParam('app_level1');
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
		$where[] = 'a.app_level1='.$app_level1;
		$where[] = 'news_type_id=2';
		$total = $this->_model->getTotalWhere('app_level1='.$app_level1.' AND news_type_id=2');//$this->_model->getTotal();
		$perpage = $this->commonconfig->page->news;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->selectid = $app_level1;
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->newstype);		
		$data = $this->_model->getAll($offset,$perpage,$where);
		$this->view->records = $data;
		$appModel = new Default_Model_DbTable_AppCategory();
		$this->view->semLevel1 = $semLevel1 = $appModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");	
		foreach($semLevel1 as $k=>$val)
		{
			if($val['id']==$app_level1)
			{
				$app_category = $val['name'];
			}
		}
		$keywords = str_replace('<app_category>', $app_category, $this->seoconfig->general->news_app_keywords);
		$desc = str_replace('<app_category>', $app_category, $this->seoconfig->general->news_app_description);
		$page_title =  str_replace('<app_category>', $app_category, $this->seoconfig->general->news_app_title);
		$this->view->headTitle($page_title,'SET');
		$this->view->headMeta()->setName('keywords', $keywords.$this->PartNokeywords);
		$this->view->headMeta()->setName('description',$desc);	
	}

}

