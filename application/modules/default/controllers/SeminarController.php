<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
class SeminarController extends Zend_Controller_Action {
    private $_semModel;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'seminar';
		
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
		
		$this->_semModel = new Default_Model_DbTable_Seminar();
		$this->seoconfig = Zend_Registry::get('seoconfig');
		$this->_searchService = new Default_Service_SearchService();
		$this->PartNokeywords = $this->_searchService->getClickPartNokeywords();
	}
	public function indexAction() {
		if($this->_getParam('webinarlist')){
			$seminarlist = explode("-",$this->_getParam('webinarlist'));
			$semid  = isset($seminarlist[1])?(int)$seminarlist[1]:'';
		}elseif($this->_getParam('seminarlist')){
			header( "HTTP/1.1 301 Moved Permanently" );
			header("Location:".str_replace('seminarlist','webinarlist',$_SERVER['REQUEST_URI']));
		}else{
			header( "HTTP/1.1 301 Moved Permanently" ) ;
			$semid = (int)$_GET['semid'];
			if($semid) header("Location:/webinarlist-".$semid.".html");
			else header("Location:/webinarlist");
		}
		$this->config = Zend_Registry::get('config');
		$this->commonconfig = Zend_Registry::get('commonconfig');
		$appModel = new Default_Model_DbTable_AppCategory();
		$this->view->semLevel1 = $semLevel1 = $appModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		//重新设置headtitle 、 description和keywords等
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();
		
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
		
		if(empty($semid)){
			$viewobj->headTitle($this->seoconfig->general->seminar_title,'SET');
			$viewobj->headMeta()->setName('description',$this->seoconfig->general->seminar_description);
			$viewobj->headMeta()->setName('keywords',$this->seoconfig->general->seminar_keywords.$this->PartNokeywords); 
			$sqltmp = "";
		}else{
			$seminar_category = '';
			foreach($semLevel1 as $app){
				if($semid==$app['id']){
					$seminar_category = $app['name'];
				}
			}
			$viewobj->headTitle(str_ireplace(array("<app_category>"),array($seminar_category),$this->seoconfig->general->seminar_app_title),'SET');
			$viewobj->headMeta()->setName('description',str_ireplace(array("<app_category>"),array($seminar_category),$this->seoconfig->general->seminar_app_description));
			$viewobj->headMeta()->setName('keywords',str_ireplace(array("<app_category>"),array($seminar_category),$this->seoconfig->general->seminar_app_keywords.$this->PartNokeywords));
			 
			$sqltmp = "AND app_level1='{$semid}'";
		}
		$this->view->selectid = $semid;
		//查询技术研讨会
		//总数
		$sqlstr = "SELECT count(id) as num FROM seminar WHERE status=1 {$sqltmp}";
		$allcan = $this->_semModel->getBySql($sqlstr,array());
		$total = $allcan[0]['num'];
		//分页
		$perpage = $this->commonconfig->page->news;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->newstype);
		
		$sqlstr ="SELECT se.*,app.name as appname FROM seminar as se
		LEFT JOIN app_category as app ON  se.app_level1 = app.id
		WHERE se.status=1 {$sqltmp} ORDER BY se.type DESC,se.created DESC LIMIT {$offset},{$perpage} ";
		$this->view->allSol = $this->_semModel->getBySql($sqlstr, array());
		
	}
	/**
	 * 查看详情
	 */
	public function detailsAction() {
		if($this->_getParam('webinar')){
			$seminar = explode("-",$this->_getParam('webinar'));
			$semid  = (int)$seminar[1];
		}else{
			header( "HTTP/1.1 301 Moved Permanently" ) ;
			$semid = (int)$_GET['semid'];
			if($semid) header("Location:/webinar-".$semid.".html");
			else header("Location:/webinarlist");
		}
		$sqlstr ="SELECT sem.*,app.id as semcid,app.name
		FROM seminar as sem LEFT JOIN app_category as app
		ON sem.app_level1 = app.id
		WHERE sem.id='{$semid}' AND sem.status=1";
		$semtmp = $this->_semModel->getBySql($sqlstr, array());
		if(!empty($semtmp))
		{
			$this->view->sem=$semtmp[0];
			$sqlstr ="SELECT id,type,title,created FROM seminar WHERE status=1 ORDER BY id DESC LIMIT 0,5";
			$this->view->newsemder = $this->_semModel->getBySql($sqlstr, array());
			
			//重新设置headtitle 、 description和keywords等
			$layout = $this->_helper->layout();
			$viewobj = $layout->getView();
			$seminar_name = $this->view->sem['title'];
			$app_category = $this->view->sem['name'];
			$keyword  = $this->view->sem['keyword'];
			$viewobj->headTitle(str_ireplace(array("<seminar_name>","<app_category>"),array($seminar_name,$app_category),$this->seoconfig->general->seminar_details_title),'SET');
			$viewobj->headMeta()->setName('description',str_ireplace(array("<description>"),array($this->view->sem['description']),$this->seoconfig->general->seminar_details_description));
			//$viewobj->headMeta()->setName('keywords',str_ireplace(array("<seminar_name>","<app_category>","<seminar_key>"),array($seminar_name,$app_category,$keyword),$this->seoconfig->general->seminar_details_keywords));
			$keywords = $semtmp[0]['title'].",".$semtmp[0]['keyword'];
			$viewobj->headMeta()->setName('keywords',$keywords.$this->PartNokeywords);
			 
		}else $this->_redirect ( '/seminarlist' );
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	/**
	 * 光看技术研讨会视频
	 */
	public function onlinedetailsAction() {
		$this->_helper->layout->disableLayout();
		if($this->_getParam('webinarvideo')){
			$seminarvideo = explode("-",$this->_getParam('webinarvideo'));
			$semid  = (int)$seminarvideo[1];
		}else{
			header( "HTTP/1.1 301 Moved Permanently" ) ;
			$semid = $this->_getParam('semid');
			if($semid) header("Location:/webinarvideo-".$semid.".html");
			else header("Location:/webinarlist");
		}
		$this->view->semdet = $this->_semModel->getRowByWhere("id = '{$semid}' AND status =1");
		if(empty($this->view->semdet) || $this->view->semdet['type']!='vdo') $this->_redirect ( '/seminarlist' );

		$seminar_name = $this->view->semdet['title'];
		$app_category = $this->view->semdet['name'];
		$keyword  = $this->view->semdet['keyword'];
		
		$this->view->headTitle = str_ireplace(array("<seminar_name>","<app_category>"),array($seminar_name,$app_category),$this->seoconfig->general->seminar_details_title);
		$this->view->description = str_ireplace(array("<description>"),array($this->view->semdet['description']),$this->seoconfig->general->seminar_details_description);
		$this->view->keywords = str_ireplace(array("<seminar_name>","<app_category>","<seminar_key>"),array($seminar_name,$app_category,$keyword),$this->seoconfig->general->seminar_details_keywords);
			
	}
}

