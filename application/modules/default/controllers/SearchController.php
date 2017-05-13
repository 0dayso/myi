<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class SearchController extends Zend_Controller_Action {
	
	private $_searchService;
	private $_prodService;
	private $_defaultlogService;
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
		$this->view->st = $searchtype = $_GET['st'];
		$keyword = strtoupper($this->view->keyword);
		if(!empty($keyword)){
					
		}else{
			$_SESSION['message'] = '请输入搜索内容！';
		}
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
		$this->view->keyword = $keyword = trim($this->filter->pregHtmlSql(urldecode($this->_getParam('keyword'))));
		$searchtype = $_GET['st'];
		$keyword = strtoupper($keyword);
		
	   //产品总数
		$re = $this->_prodService->getPartTmp();
		$total = 0;$searcharr = array();
		$searchtmparr = $this->_prodService->getResultTmp();
		if($_GET['page'] && $searchtmparr && $_SESSION['keywordtmp']==$keyword){
			$searcharr = $searchtmparr;
			$total = count($searcharr);
		}else{
			foreach($re as $v){
				if(stristr($v['part_no'],$keyword)) {
					$searcharr[] = $v;
				}
			}
			$this->_prodService->createResultTmp($searcharr);
			$total = count($searcharr);
			$_SESSION['keywordtmp'] = $keyword;
		}
		//分页
		$perpage = $this->commonconfig->page->prodlist;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$page_ob->open_ajax("ajaxpage");
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->prodlistype);
		$this->view->allProd = $this->_prodService->getPartArrByIn($searcharr,$offset,$perpage,$total);
		$this->view->searchnum = $total;
		//记录搜索日志
		$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$keyword,'temp3'=>$total,'temp4'=>'搜索产品成功，搜索结果：'.$total));

		$brandModel = new Default_Model_DbTable_Brand();
		$this->view->allbrand = $allbrand = $brandModel->getAllByWhere("status='1'","name ASC");
		//获取登录者的信息
		if(isset($_SESSION['userInfo']['uidSession'])){
		$user = new Default_Model_DbTable_User();
		$myinfoarray = $user->getBySql("SELECT email,truename,companyname,tel,mobile,fax FROM user as u,user_profile as up
    			WHERE u.uid = up.uid AND u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$this->view->reUser = $myinfoarray[0];
		}
	}
	/**
	 * 其它搜索
	 */
	public function newsAction(){
		$this->_helper->layout->disableLayout();
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->view->keyword = $keyword = trim($this->filter->pregHtmlSql(urldecode($_GET['keyword'])));
		$this->view->st = $searchtype = $_GET['st'];
		$q = strtoupper($keyword);
		$sModel = new Default_Model_DbTable_Model('solution');
		//分页
		$this->view->total = 0;
		$perpage = 10;
		$this->view->rearray = array();
		$page_type = 9;
		if($searchtype=='searchsolution'){
			//方案
			$solutionsqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$solutionsqlstr = "SELECT count(id) as num FROM solution WHERE status=1 {$solutionsqltmp}";
			$this->view->solutiontotal = $sModel->QueryItem($solutionsqlstr);
			$this->view->total = $this->view->solutiontotal;
			$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
			$offset  = $page_ob->offset();
			$page_ob->open_ajax("ajaxpage");
			$this->view->page_bar= $page_ob->show($page_type);
			$sqlstr ="SELECT id,title,source,author,tags,description FROM solution
			WHERE status=1 {$solutionsqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
			$this->view->rearray = $sModel->Query($sqlstr);
			
		}elseif($searchtype=='searchnews'){
		//资讯
			$newssqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$newssqlstr = "SELECT count(id) as num FROM news WHERE status=1 {$newssqltmp}";
			$this->view->newstotal = $sModel->QueryItem($newssqlstr);
			$this->view->total = $this->view->newstotal;
			$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
			$offset  = $page_ob->offset();
			$page_ob->open_ajax("ajaxpage");
			$this->view->page_bar= $page_ob->show($page_type);
			$sqlstr ="SELECT id,title,source,author,keyword,description FROM news
			WHERE status=1 {$newssqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
			$this->view->rearray = $sModel->Query($sqlstr);
			
		}elseif($searchtype=='searchwebinar'){
		//研讨会
			$seminarsqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$seminarsqlstr = "SELECT count(id) as num FROM seminar WHERE status=1 {$seminarsqltmp}";
			$this->view->seminartotal = $sModel->QueryItem($seminarsqlstr);
			$this->view->total = $this->view->seminartotal;
			$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
			$offset  = $page_ob->offset();
			$page_ob->open_ajax("ajaxpage");
			$this->view->page_bar= $page_ob->show($page_type);
			$sqlstr ="SELECT id,title,source,author,keyword,description FROM seminar
			WHERE status=1 {$seminarsqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
			$this->view->rearray = $sModel->Query($sqlstr);
			
		}elseif($searchtype=='searchcode'){
		//代码库
			$codesqltmp = " AND (title LIKE '%{$q}%' OR description LIKE '%{$q}%')";
			$codesqlstr = "SELECT count(id) as num FROM app_code WHERE status=1 AND published<=".(time())." {$codesqltmp}";
			$this->view->codetotal = $sModel->QueryItem($codesqlstr);
			$this->view->total = $this->view->codetotal;
			$page_ob = new Page(array('total'=>$this->view->total,'perpage'=>$perpage));
			$offset  = $page_ob->offset();
			$page_ob->open_ajax("ajaxpage");
			$this->view->page_bar= $page_ob->show($page_type);
			$sqlstr ="SELECT id,title,source,author,keyword,description FROM app_code
					WHERE status=1  AND published<=".(time())." {$codesqltmp} ORDER BY created DESC LIMIT {$offset},{$perpage}";
			$this->view->rearray = $sModel->Query($sqlstr);
		}
		//记录搜索日志
		$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$q,'temp3'=>$this->view->total,'temp4'=>$searchtype));
	}
	public function noproductAction(){	
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$error = 0;$message='';
			$formData     = $this->getRequest()->getPost();
			$part_no      = $this->filter->pregHtmlSql($formData['part_no']);
			$brand        = $this->filter->pregHtmlSql($formData['brand']);
			$brand_other  = $this->filter->pregHtmlSql($formData['brand_other']);
			$contact_name = $this->filter->pregHtmlSql($formData['contact_name']);
			$contact      = $this->filter->pregHtmlSql($formData['contact']);
			$explanation  = $this->filter->pregHtmlSql($formData['explanation']);
		
			if(empty($part_no)) {
				$message ='型号不能为空';
				$error++;
			}
			if(empty($brand)) {
				$message ='请选择品牌';
				$error++;
			}elseif($brand=='Other' || $brand=='other'){
				if(empty($brand_other)){
					$message ='请填写其它品牌名称';
					$error++;
				}else{
					$brand = $brand_other;
				}
			}
			if(empty($contact_name)) {
				$message ='联系人不能为空';
				$error++;
			}
			if(empty($contact)) {
				$message ='联系方式不能为空';
				$error++;
			}
			//检查用户是否已经提交过相同part no
			if($this->_searchService->checkPart($part_no))
			{
				$message ='很抱歉，提交不成功。你已经提交过：'.$part_no;
				$error++;
			}
			//最多添加100个状态为0的记录，防止攻击
			$inqre = $this->_searchService->checkNum(1000);
			if($inqre){
				$error++;
				$message = '每个用户最多添加1000个询问记录。';
			}
			if($error){
				echo Zend_Json_Encoder::encode(array("code"=>1,"message"=>$message));
				exit;
			}else{
				$adddata = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'part_no'=>$part_no,
						'notice'=>$formData['notice']?$formData['notice']:0,
						'brand'=>$brand,
						'contact_name'=>$contact_name,
						'contact'=>$contact,
						'explanation'=>$explanation,
						'created'=>time());
				$searchModel = new Default_Model_DbTable_SearchInquiry();
				$newid = $searchModel->addData($adddata);
				//邮件提醒销售
				$emailreturn = $this->_searchService->sendAlertEmail($adddata);
				//邮件日志
				if($emailreturn){
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'客户提交搜索产品提醒销售邮件成功'));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'客户提交搜索产品提醒销售邮件失败'));
				}
				//客户邮件
				$userservice = new Default_Service_UserService();
				$userinfo = $userservice->getUserProfile();
				//邮件通知客户
				$emailreturn = $this->_searchService->sendAlertUserEmail($adddata,$userinfo);
				//邮件日志
				if($emailreturn){
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'客户提交搜索产品通知客户邮件成功'));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'客户提交搜索产品通知客户邮件失败'));
				}
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$part_no,'temp4'=>'添加搜索产品成功'));
				echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>"谢谢，提交成功，我们会及时处理你的请求。"));
				exit;
			}
		}
	}
	public function index_back_Action() {
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->view->keyword = $keyword = trim($this->filter->pregHtmlSql($this->_getParam('keyword')));
		$this->view->st = $searchtype = $_GET['st'];
		$keyword = strtoupper($this->view->keyword);
		if(!empty($keyword)){
			//产品总数
			$re = $this->_prodService->getPartTmp();
			$total = 0;$searcharr = array();
			$searchtmparr = $this->_prodService->getResultTmp();
			if($_GET['page'] && $searchtmparr && $_SESSION['keywordtmp']==$keyword){
				$searcharr = $searchtmparr;
				$total = count($searcharr);
			}else{
				foreach($re as $v){
					if(stristr($v['part_no'],$keyword)) {
						$searcharr[] = $v;
					}
				}
				$this->_prodService->createResultTmp($searcharr);
				$total = count($searcharr);
				$_SESSION['keywordtmp'] = $keyword;
			}
			//google搜索
			if($searchtype=='searchgoogle' && (int)$total <= 0){
				$this->_redirect('/searcha?q='.$keyword.'&st='.$searchtype);
			}elseif($searchtype=='searchall'){
				//ic易站全文搜索
				$this->_redirect('/searchi?query='.$keyword.'&search=1&st='.$searchtype);
			}elseif($searchtype=='searchcode' || $searchtype=='searchsolution' || $searchtype=='searchnews' || $searchtype=='searchwebinar'){
				//搜索方案、资讯、研讨会
				$this->_redirect('/searcha?q='.$keyword.'&st='.$searchtype);
			}
	
			//分页
			$perpage = $this->commonconfig->page->prodlist;
			$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
			$offset  = $page_ob->offset();
			$this->view->page_bar= $page_ob->show($this->commonconfig->page->prodlistype);
			$this->view->allProd = $this->_prodService->getPartArrByIn($searcharr,$offset,$perpage,$total);
			$this->view->searchnum = $total;
	
			$brandModel = new Default_Model_DbTable_Brand();
			$this->view->allbrand = $allbrand = $brandModel->getAllByWhere("status='1'","name ASC");
			//记录搜索日志
			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$keyword,'temp3'=>$total,'temp4'=>'搜索产品成功，搜索结果：'.$total));
			//如果只搜索到一个型号并型号一样直接跳到这页面
			if($total==1 && strtoupper($this->view->allProd[0]['part_no'])==strtoupper($keyword)){
				$url = "/item-b".$this->view->allProd[0]['manufacturer']."-".($this->view->allProd[0]['part_level3']?$this->view->allProd[0]['part_level3']:$this->view->allProd[0]['part_level2'])."-".$this->view->allProd[0]['id'].'-'.$this->fun->filterUrl($this->view->allProd[0]['part_no']).'.html';
				$this->_redirect($url.'?keyword='.$this->view->keyword);
			}
		}else{
			$_SESSION['message'] = '请输入产品型号！';
		}
		if($this->getRequest()->isPost() && $this->_getParam('type')==2){
			$error = 0;$message='';
			$formData     = $this->getRequest()->getPost();
			$part_no      = $this->filter->pregHtmlSql($formData['part_no']);
			$brand        = $this->filter->pregHtmlSql($formData['brand']);
			$brand_other  = $this->filter->pregHtmlSql($formData['brand_other']);
			$contact_name = $this->filter->pregHtmlSql($formData['contact_name']);
			$contact      = $this->filter->pregHtmlSql($formData['contact']);
			$explanation  = $this->filter->pregHtmlSql($formData['explanation']);
	
			if(empty($part_no)) {
				$message ='型号不能为空';
				$error++;
			}
			if(empty($brand)) {
				$message ='请选择品牌';
				$error++;
			}elseif($brand=='Other' || $brand=='other'){
				if(empty($brand_other)){
					$message ='请填写其它品牌名称';
					$error++;
				}else{
					$brand = $brand_other;
				}
			}
			if(empty($contact_name)) {
				$message ='联系人不能为空';
				$error++;
			}
			if(empty($contact)) {
				$message ='联系方式不能为空';
				$error++;
			}
			//登录检查
			$this->common = new MyCommon();
			$this->common->loginCheck();
			//检查用户是否已经提交过相同part no
			if($this->_searchService->checkPart($part_no))
			{
				$message ='很抱歉，提交不成功。你已经提交过：'.$part_no;
				$error++;
			}
			//最多添加100个状态为0的记录，防止攻击
			$inqre = $this->_searchService->checkNum(1000);
			if($inqre){
				$error++;
				$message = '每个用户最多添加1000个询问记录。';
			}
			//检查型号是否已经存在
			$exre = $this->_prodService->getProductByWhere(" AND p.part_no='$part_no' AND b.name='{$brand}'");
			if($exre){
				$this->_redirect('/search?keyword='.$part_no);
			}
			if(!$error){
	
				$adddata = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'part_no'=>$part_no,
						'notice'=>$formData['notice']?$formData['notice']:0,
						'brand'=>$brand,
						'contact_name'=>$contact_name,
						'contact'=>$contact,
						'explanation'=>$explanation,
						'created'=>time());
				$searchModel = new Default_Model_DbTable_SearchInquiry();
				$newid = $searchModel->addData($adddata);
				$_SESSION['code'] = 1;
				$_SESSION['message'] = '提交成功！';
				//邮件提醒销售
				$emailreturn = $this->_searchService->sendAlertEmail($adddata);
				//邮件日志
				if($emailreturn){
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'客户提交搜索产品提醒销售邮件成功'));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'客户提交搜索产品提醒销售邮件失败'));
				}
				//客户邮件
				$userservice = new Default_Service_UserService();
				$userinfo = $userservice->getUserProfile();
				//邮件通知客户
				$emailreturn = $this->_searchService->sendAlertUserEmail($adddata,$userinfo);
				//邮件日志
				if($emailreturn){
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'客户提交搜索产品通知客户邮件成功'));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'客户提交搜索产品通知客户邮件失败'));
				}
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$keyword,'temp4'=>'添加搜索产品成功'));
			}elseif($_SESSION['userInfo']){
				$_SESSION['code'] = 100;
				$_SESSION['message'] = $message;
				$_SESSION['brand'] = $formData['brand'];
				$_SESSION['brand_other'] = $formData['brand_other'];
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$keyword,'temp4'=>'添加搜索产品失败','description'=>$message));
			}
		}
		//获取登录者的信息
		$user = new Default_Model_DbTable_User();
		$myinfoarray = $user->getBySql("SELECT email,truename,companyname,tel,mobile,fax FROM user as u,user_profile as up
    			WHERE u.uid = up.uid AND u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$this->view->reUser = $myinfoarray[0];
	}
	/*
	 * ajax获取Part NO.
	*/
	public function getajaxtagAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		echo $this->_prodService->getPartNoLike($_GET['q']);
	}
}

