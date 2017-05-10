<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class SearchaController extends Zend_Controller_Action {
	
	private $_searchService;
	private $_defaultlogService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = '';
		$this->filter = new MyFilter();
		$this->view->fun = new MyFun();
		$this->_searchService = new Default_Service_SearchService();
		$this->_defaultlogService = new Default_Service_DefaultlogService();
	}
	public function indexAction() {
		$this->_helper->layout->disableLayout();
		$this->view->st = $searchtype = $_GET['st'];
		$this->view->q = $q = $this->filter->pregHtmlSql($_GET['q']);
		$this->view->rearray = array();
		if(empty($q)){
			$this->view->message = '请输入搜索内容！';
		}else{
			$sModel = new Default_Model_DbTable_Model('solution');
			//型号
			$productsqltmp = " AND (part_no LIKE '%{$q}%')";
			$productsqlstr = "SELECT count(id) as num FROM product WHERE status=1 {$productsqltmp}";
			$this->view->producttotal = $sModel->QueryItem($productsqlstr);
			//方案
			$solutionsqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$solutionsqlstr = "SELECT count(id) as num FROM solution WHERE status=1 {$solutionsqltmp}";
			$this->view->solutiontotal = $sModel->QueryItem($solutionsqlstr);
			//资讯
			$newssqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$newssqlstr = "SELECT count(id) as num FROM news WHERE status=1 {$newssqltmp}";
			$this->view->newstotal = $sModel->QueryItem($newssqlstr);
			//研讨会
			$seminarsqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$seminarsqlstr = "SELECT count(id) as num FROM seminar WHERE status=1 {$seminarsqltmp}";
			$this->view->seminartotal = $sModel->QueryItem($seminarsqlstr);
			//代码库
			$codesqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$codesqlstr = "SELECT count(id) as num FROM app_code WHERE status=1 AND published<=".(time())." {$codesqltmp}";
			$this->view->codetotal = $sModel->QueryItem($codesqlstr);
				
			//分页
			$this->view->total = 0;
			$perpage = 10;
			if($searchtype=='searchsolution'){
				$this->view->total = $this->view->solutiontotal;
				$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
				$offset  = $page_ob->offset();
				$this->view->page_bar= $page_ob->show(5);
				$sqlstr ="SELECT id,title,description FROM solution
				WHERE status=1 {$solutionsqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
				$this->view->rearray = $sModel->Query($sqlstr);
			}elseif($searchtype=='searchnews'){
				$this->view->total = $this->view->newstotal;
				$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
				$offset  = $page_ob->offset();
				$this->view->page_bar= $page_ob->show(5);
				$sqlstr ="SELECT id,title,description FROM news
					WHERE status=1 {$newssqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
				$this->view->rearray = $sModel->Query($sqlstr);
			}elseif($searchtype=='searchwebinar'){
				$this->view->total = $this->view->seminartotal;
				$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
				$offset  = $page_ob->offset();
				$this->view->page_bar= $page_ob->show(5);
				$sqlstr ="SELECT id,title,description FROM seminar
					WHERE status=1 {$seminarsqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
				$this->view->rearray = $sModel->Query($sqlstr);
			}elseif($searchtype=='searchcode'){
				$this->view->total = $this->view->codetotal;
				$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
				$offset  = $page_ob->offset();
				$this->view->page_bar= $page_ob->show(5);
				$sqlstr ="SELECT id,title,description FROM app_code
					WHERE status=1  AND published<=".(time())." {$codesqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
				$this->view->rearray = $sModel->Query($sqlstr);
			}else{
				//google全文搜索
				$this->view->google = 1;
			}
			if($this->view->total<=0){
				$this->view->message = '没有搜索到相关结果！';
			}
			//记录搜索日志
			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$q,'temp3'=>$this->view->total,'temp4'=>$searchtype));
		}
	}
}

