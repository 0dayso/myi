<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/default/common.php';
class CodeController extends Zend_Controller_Action {
    private $_codeservice;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'appcode';
		
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
		$this->seoconfig = Zend_Registry::get('seoconfig');
		$this->commonconfig = Zend_Registry::get('commonconfig');
		
		$this->_codeservice = new Default_Service_AppcodeService();
		$this->_searchService = new Default_Service_SearchService();
		$this->view->PartNokeywords = $this->_searchService->getClickPartNokeywords();
	}
	public function indexAction() {
		//新版本
		$pagetype = 6;
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$pagetype = 9;
		}
		if((int)$this->_getParam('app_level1')){
			$appid  = (int)$this->_getParam('app_level1');
		}
		$where = '';
		if($appid){
			$this->view->selectid = $appid;
			$where = " AND ac.app_level1='{$appid}'";
		}
		$this->view->o = $_GET['o'];
		if($_GET['o']=='m'){
		  $orderby = "ORDER BY ac.downloadnumber DESC";
		}else $orderby = "ORDER BY ac.id DESC";
		$total = $this->_codeservice->getNumber($where);
		//分页
		$perpage = 10;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show($pagetype);
		
		$this->view->all = $this->_codeservice->getAllCode($where,$offset,$perpage,$orderby);
		$appModel = new Default_Model_DbTable_AppCategory();
		$this->view->app = $appModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		
	}
	/**
	 * 查看详情
	 */
	public function viewAction(){
		if($this->_getParam('id')){
			$id  = (int)$this->_getParam('id');
		}
		if(!$id) $this->_redirect ( '/code' );
		if($_GET['preview']) $this->view->sem = $this->_codeservice->getCodeByidPreview($id);
	    else $this->view->sem = $this->_codeservice->getCodeByid($id);
	    if(empty($this->view->sem)) $this->_redirect ( '/code' );
	    
	    //最新
	    $this->view->newarr = $this->_codeservice->getAllCode('',0,5);
	    //下载还下载过
	    $this->view->donlog = $this->_codeservice->domlog($id);
	    if(isset($_SESSION['new_version'])){
	    	$this->fun = new MyFun();
	    	$this->fun->changeView($this->view,$_SESSION['new_version']);
	    }
	}
	/**
	 * 推荐
	 */
	public function pushAction(){
		$this->_helper->layout->disableLayout();
		$tmp = $this->_codeservice->getPushCode();
		if(!$tmp) $tmp = $this->_codeservice->getAllCode('',0,4);
		$this->view->showarr = array();
		if($tmp){
			if(count($tmp)>4) $n = 4;
			else $n = count($tmp);
			$rand_keys = array_rand($tmp, $n);
			$this->view->showarr[0] = $tmp[$rand_keys[0]];
			$this->view->showarr[1] = $tmp[$rand_keys[1]];
			$this->view->showarr[2] = $tmp[$rand_keys[2]];
			$this->view->showarr[3] = $tmp[$rand_keys[3]];
		}
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	public function inputpassAction(){
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$this->_helper->layout->disableLayout();
		$this->view->keyvalue = $_GET['key'];
		/**/
	}
	public function cscoreAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$docid= $this->fun->decryptVerification($_POST['key']);	
		$codeinfo = $this->_codeservice->getCodeByid($docid);
		if($codeinfo['need_pass']==1){
			if($_POST['pass']){
				if($codeinfo['pass']==0){
					echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'很抱歉，您的输入的密码错误'));
					exit;
				}elseif($codeinfo['pass']==$_POST['pass']){
					echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'密码正确'));
					exit;
				}else{
					echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'很抱歉，您的输入的密码错误'));
					exit;
				}
			}else{
			    echo Zend_Json_Encoder::encode(array("code"=>1,"message"=>'请输入密码'));
			    exit;
			}
		}else{
		    $re = $this->_codeservice->checkscore($docid);
		    if($re && $docid){
			    echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'积分足够'));
			    exit;
		    }else{
			    echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'很抱歉，您的积分不足'));
			    exit;
		    }
		}
	}
	/**
	 * 下载
	 */
	public function downloadAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$docid= $this->fun->decryptVerification($_GET['key']);
		
		$codeinfo = $this->_codeservice->getCodeByid($docid);
		if($codeinfo['need_pass']==1){
			if($codeinfo['pass']==0){
				$this->_redirect ('/code');
			}elseif($codeinfo['pass']!=$_GET['pass']){
				$this->_redirect ('/code');
			}
		}else{
		   $re = $this->_codeservice->checkscore($docid);
		   if(!$re){$this->_redirect ('/code');}
		}
		
		$docarr = $this->_codeservice->getCodeByid($docid);
	
		if(!empty($docarr['annexpath']) && file_exists($docarr['annexpath'])){
			
			$dservice = new Default_Service_ScoreService();
			if($codeinfo['need_pass']==1){
				$dservice->destore(0,$docid,'使用密码（'.$codeinfo['pass'].'）下载代码，'.$docarr['title']);
				//更新下载密码为0
				$codemode = new Default_Model_DbTable_Model('app_code');
				$codemode->update(array('pass'=>0),"id='$docid'");
			}else{
				//扣分
				$dservice->destore($docarr['spendpoints'],$docid,'下载代码扣分，'.$docarr['title']);
			}
			//更新下载次数
			$this->_codeservice->updnumber($docid);
			$docre = explode("/", $docarr['annexpath']);
			$newname = $docarr['annexname']?$docarr['annexname']:$docre[(count($docre)-1)];
			$this->fun->filedownloadpage($docarr['annexpath'],$newname);
			
		}else $this->_redirect ('/code');
	}
}

