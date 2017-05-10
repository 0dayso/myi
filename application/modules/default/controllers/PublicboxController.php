<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
class PublicboxController extends Zend_Controller_Action {

	public function init() {
		/*
		 * Initialize action controller here
		 */
		$this->view->fun =$this->fun =new MyFun();
		$this->semModer = new Default_Model_DbTable_Seminar();
	}
	public function indexAction() {
		$this->_redirect ( '/' );
	}
	/*
	 * 获取热销产品
	 */
	public function hotpordAction() {
		$this->_helper->layout->disableLayout();
		$rModer = new Default_Model_DbTable_Recommend();
		$sqlstr ="SELECT re.cat_id,br.name as brandname,pro.part_level1,pro.part_level2,pro.part_level3,
			pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.break_price,pro.moq,pro.mpq,
		    pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,pro.bpp_stock,pro.bpp_cover,pro.can_sell,
			pro.surplus_stock_sell,pro.special_break_prices,pro.show_price,pro.price_valid,pro.price_valid_rmb,pc.name,
				pc1.name as cname1,pc2.name as cname2,pc3.name as cname3
		FROM recommend as re 
		LEFT JOIN product as pro ON re.comid=pro.id
		LEFT JOIN prod_category as pc ON pro.part_level3=pc.id
		LEFT JOIN brand as br ON re.cat_id=br.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON pro.part_level1=pc1.id
		WHERE re.type='hot' AND re.part='home' AND re.status = 1 AND br.status = 1 ORDER BY re.displayorder ASC";
		$allhotArr = $rModer->getBySql($sqlstr, array());
		$bandhotArr = array();
		for($i=0;$i<count($allhotArr);$i++)
		{
		   $bandhotArr[$allhotArr[$i]['cat_id']][] = $allhotArr[$i];
		}
		//随机取出品牌
		$this->view->bandid    = array_rand($bandhotArr);
		$this->view->hotArr    = $bandhotArr[$this->view->bandid];
		$this->view->brandname = $this->view->hotArr[0]['brandname'];
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	/*
	 * 获取热门方案
	*/
	public function hotschemeAction() {
		$appid = $_POST['appid'];
		$this->_helper->layout->disableLayout();
		
		//产品目录
		//$frontendOptions = array('lifeTime' => 3600,'automatic_serialization' => true);
		//$backendOptions = array('cache_dir' => CACHE_PATH);
		// $cache 在先前的例子中已经初始化了
		//$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		//$cache_key = 'hotscheme_cache_'.$appid;
		//if(!$hotArr = $cache->load($cache_key)) {
			$semModer = new Default_Model_DbTable_Seminar();
		    $sqlstr =" SELECT so.id,so.title,so.created,so.sol_img,app.name,so.solution_img
				 FROM solution as so
				 LEFT JOIN app_category as app ON so.app_level1=app.id
				 WHERE so.app_level1='{$appid}' AND so.status = 1 ORDER BY Rand() LIMIT 5 ";
		    $hotArr = $semModer->getBySql($sqlstr);
			//$cache->save($hotArr,$cache_key);
		//}
		$this->view->hotArr = $hotArr;
	}
	/*
	 * 弹出提示框
	*/
	public function alertAction() {
		$this->_helper->layout->disableLayout();
		$this->view->message = $this->_getParam('message');
	}
	/*
	 * 用户订单信息下拉
	 */
	public function userinfoAction(){
		$this->_helper->layout->disableLayout();
		if($_SESSION['userInfo']['unameSession']){
			//用户积分
			$this->_jifenService = new Default_Service_JifenService();
			$this->view->myscore = $this->_jifenService->getSurplusScore();
			//订单相关
			$this->_userService = new Default_Service_UserService();
			$this->view->onlinesoinfo = $this->_userService->getOnlineSoInfo();
			$this->view->inqsoinfo = $this->_userService->getInqSoInfo();
			//询价相关
			$this->view->inqinfo = $this->_userService->getInqInfo();
		}
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	/**
	 *热卖推荐
	 */
	public function hotrecommendAction(){
		$this->_helper->layout->disableLayout();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		//热推产品
		$prosql_str = "pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,
				pro.break_price,pro.moq,pro.mpq,pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,
				pro.bpp_stock,pro.bpp_cover,pro.show_price,pro.can_sell,pro.noinquiry,
				pro.surplus_stock_sell,pro.special_break_prices,pro.price_valid,pro.price_valid_rmb,";
		$sqlstr ="SELECT re.id,re.comid,re.status,re.displayorder,{$prosql_str}
		b.id as brandid,b.name as bname,pc2.name as bname2,pc3.name as bname3
		FROM recommend as re
		LEFT JOIN product as pro ON re.comid=pro.id
		LEFT JOIN brand as b ON b.id=pro.manufacturer
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		WHERE re.type='hot_prod' AND re.part='home' AND re.status=1 ORDER BY re.displayorder";
		$rModer = new Default_Model_DbTable_Recommend();
		$hot_prod = $rModer->getBySql($sqlstr, array());
		//随机取出品牌
		$randid    = array_rand($hot_prod,4);
	    $hottmp = array();
		foreach($randid as $id){
		  $hottmp[] = $hot_prod[$id];
	    }
	    $this->view->hot_prod = $hottmp;
	}
	/**
	 * 回到顶部
	 */
	public function totopAction(){
		$this->_helper->layout->disableLayout();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_scoreService = new Default_Service_ScoreService();
		$slogModel   = new Default_Model_DbTable_Model('score_log');
		//获取是否已经签订
		$this->view->jifenview = $this->_scoreService->checkgetscore('jifenview',$_SESSION['userInfo']['uidSession']);
		//次数
		$uid = $_SESSION['userInfo']['uidSession'];
		$top['jifenviewnum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
				WHERE uid = '{$uid}' AND temp2='jifenview' AND temp3>0");
		$top['viewpagenum'] = $slogModel->QueryItem("SELECT count(id) FROM `default_view_log`
				WHERE uid = '{$uid}'");
		$top['sharenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
				WHERE uid = '{$uid}' AND temp2='share' AND temp3>0");
		$top['invitenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
				WHERE temp5 = '{$uid}' AND temp2='invite'");
		$this->view->top = $top;
	}
	/**
	 * 热门搜索
	 */
	public function hotsearchAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$this->_searchService = new Default_Service_SearchService();
		$this->view->searchRight = $this->_searchService->getClickPartNo();
	}
	/**
	 * 热门方案
	 */
	public function hotsolutionAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->view->hotArr = array();
		$this->_helper->layout->disableLayout();
		$this->view->sid = $sid = $_GET['sid'];
		if($sid){
			//推荐方案
			$this->_sprodModer = new Default_Model_DbTable_Model('solution_product');
			$arr = $this->_sprodModer->Query("SELECT distinct(sp.prod_id) FROM `solution_product` as sp
				WHERE sp.solution_id ='{$sid}' AND sp.type='core' AND sp.status=1");
			$appserivce = new Default_Service_ApplicationsService;
			if($arr){
			  foreach($arr as $v){
			  	  $solution = $appserivce->getSolutionByCode($v['prod_id']);
			  	  $this->view->hotArr = array_merge($solution,$this->view->hotArr);
			  }
			}
		}
		if(empty($this->view->hotArr)){
		   $sqlstr =" SELECT id,title
		   FROM solution
		   WHERE  status = 1 ORDER BY Rand() LIMIT 5 ";
		   $hotArr = $this->semModer->getBySql($sqlstr);
		   $this->view->hotArr = $hotArr;
		}
	}
	/**
	 * 热门资讯
	 */
	public function hotnewsAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$sqlstr =" SELECT id,news_type_id,title
		FROM news
		WHERE  status = 1 ORDER BY Rand() LIMIT 5 ";
		$hotArr = $this->semModer->getBySql($sqlstr);
		$this->view->hotArr = $hotArr;
		
	}
	/**
	 * 热门研讨会
	 */
	public function hotwebinarAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$sqlstr =" SELECT id,title
		FROM seminar
		WHERE  status = 1 ORDER BY Rand() LIMIT 5 ";
		$hotArr = $this->semModer->getBySql($sqlstr);
		$this->view->hotArr = $hotArr;
	}
	/**
	 * 热门代码库
	 */
	public function htocodeAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$sqlstr =" SELECT id,title
		FROM app_code
		WHERE  status = 1 ORDER BY Rand() LIMIT 5 ";
		$hotArr = $this->semModer->getBySql($sqlstr);
		$this->view->hotArr = $hotArr;
	}
	/**
	 * 品牌关联方案
	 */
	public function brandsolutionAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$brandid=$_GET['brandid'];
		//查询方案列表
		$solModel = new Default_Model_DbTable_Solution();
		
		//总数

		$sqlstr = "SELECT count(distinct(s.id)) as num 
		FROM solution as s 
		LEFT JOIN solution_product  as sp ON s.id = sp.solution_id
		LEFT JOIN product as p ON sp.prod_id = p.id
		WHERE p.manufacturer='{$brandid}' AND sp.type='core' AND s.status=1 AND sp.status=1 ";
		$allcan = $solModel->getBySql($sqlstr,array());
		$total = $allcan[0]['num'];
		//分页
		$perpage = 10;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$page_ob->open_ajax("ajaxpage");
		$this->view->page_bar= $page_ob->show(9);
		
		$sqlstr ="SELECT distinct(s.id),s.*,app.name as appname FROM solution as s
		LEFT JOIN app_category as app ON  s.app_level1 = app.id
		LEFT JOIN solution_product  as sp ON s.id = sp.solution_id
		LEFT JOIN product as p ON sp.prod_id = p.id
		WHERE p.manufacturer='{$brandid}' AND sp.type='core' AND s.status=1 AND sp.status=1 ORDER BY s.created DESC LIMIT {$offset},{$perpage}";
		$this->view->allSol = $solModel->getBySql($sqlstr, array());
	}
	/**
	 * 方案首页ajax分页
	 */
	public function moresolAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$solModel = new Default_Model_DbTable_Solution();
		$this->_helper->layout->disableLayout();
		$num = (int)$_GET['num'];
		$snum = $num*5;
		$count = 5;
		$sqlstr ="SELECT sol.*,app.name as appname FROM solution as sol
		LEFT JOIN app_category as app ON  sol.app_level1 = app.id
		WHERE sol.status=1 AND sol.title!='' ORDER BY sol.created DESC LIMIT {$snum} , {$count}";
		$this->view->solution = $solModel->getBySql($sqlstr);
	}
}

