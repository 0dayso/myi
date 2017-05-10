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

       //热销产品
	   $allhotArr = $this->productService->homeHot();

	   $bandhotArr = array();
	   for($i=0;$i<count($allhotArr);$i++)
	   {
	   	 $bandhotArr[$allhotArr[$i]['cat_id']][] = $allhotArr[$i];
	   }
	   $this->view->bandhotArr = $bandhotArr;
	   //代理品牌
	   $sqlstr ="SELECT re.comid,b.name
		     FROM recommend as re 
	   		 LEFT JOIN brand as b ON re.comid=b.id
		     WHERE re.type='acting_brand' AND re.part='home' AND re.status = 1  AND b.status = 1 ORDER BY re.displayorder LIMIT 0 , 26";
	   $this->view->actingBrand = $rModer->getBySql($sqlstr, array());
	   //热推产品
	   $this->view->hot_prod = $this->productService->homeHotPush();
	   //推荐应用方案 和 相关产品
	   $sqlstr ="SELECT re.cat_id,re.head,
	   		   sol.id as solid,sol.title,sol.sol_img,sol.solution_img
		       FROM recommend as re
			   LEFT JOIN solution as sol ON re.comid=sol.id
		       WHERE re.type='solution' AND re.part='home' AND re.status = 1";
	    $solutionArr = $rModer->getBySql($sqlstr, array());
	    if(!empty($solutionArr)){
	    	foreach($solutionArr as $key=>$soltmp)
	    	{
	    		//查询关联元件
	    		$sqlstr ="SELECT * FROM solution_product WHERE solution_id='".$soltmp['solid']."' AND status=1";
	    		$solprod = $rModer->getBySql($sqlstr, array());
	    		$prodTmp = array();
	    		if(!empty($solprod)){
	    			foreach($solprod as $v){
	    				$retmp = $this->productService->homeSolProduct($v['prod_id']);
	    				if(!empty($retmp[0])) $prodTmp[] = $retmp[0];
	    			}
	    		}
	    		$solutionArr[$key]['rec_prod'] = $prodTmp;
	    	}
	    }
		//应用推荐产品
		$allApps = $this->productService->homeAppProduct();
		
		//应用品牌
	    $sqlstr ="SELECT re.cat_id,b.id,b.name,b.name_en,b.logo
		    FROM recommend as re LEFT JOIN brand as b
		    ON re.comid=b.id
		    WHERE re.type='brand' AND re.part='home' AND re.status = 1 AND b.status='1' 
		    ORDER BY re.head DESC,re.displayorder";
		$allBrand = $rModer->getBySql($sqlstr, array());
		//应用种类
		$sqlstr ="SELECT re.cat_id,app.name,app.name_en,app.icon 
		     FROM recommend as re,app_category as app
		     WHERE re.type='app' AND re.part='home' AND re.status = 1 AND re.cat_id=app.id AND app.status='1' AND re.status = 1
			ORDER BY app.displayorder ASC";
		$appArrs = $rModer->getBySql($sqlstr, array());

		//所有二级应用分类
		$appModer = new Default_Model_DbTable_AppCategory();
		$appcall = $appModer->getAllByWhere ("status='1'","displayorder ASC");
		
		$appArr=array();
		foreach($appArrs as $tmp){
			if(!array_key_exists($tmp['cat_id'],$appArr)){
				$solutionrr = $apparr = $brand = $sonapp = array();
				$arr=array('name'=>$tmp['name'],
						'name_en'=>$tmp['name_en'],
						'icon'=>$tmp['icon']);
				$appArr[$tmp['cat_id']]['par']=$arr;
				//推荐应用方案
				foreach($solutionArr as $soltmp){
					if($soltmp['cat_id'] == $tmp['cat_id']){
						$solutionrr[] = $soltmp;
					}
				}
				$appArr[$tmp['cat_id']]['solution'] = $solutionrr;
				//产品
				foreach($allApps as $apptmp){
					if($apptmp['cat_id'] == $tmp['cat_id']){
						$apparr[] = $apptmp;
					}
				}
				$appArr[$tmp['cat_id']]['value'] = $apparr;
				//品牌
				foreach($allBrand as $brandtmp){
					if($brandtmp['cat_id'] == $tmp['cat_id']){
						$brand[] = $brandtmp;
					}
				}
				$appArr[$tmp['cat_id']]['brand'] = $brand;
				//应用分类
				foreach($appcall as $apptmp){
					if($apptmp['parent_id'] == $tmp['cat_id']){
						$sonapp[] = $apptmp;
					}
				}
				$appArr[$tmp['cat_id']]['sonapp'] = $sonapp;
			}
		}
		
		
		//滚动图片
		$this->view->topimageArr = $hpModer->getAllByWhere("status='1' AND type='home'",array("displayorder ASC","id DESC"));
		$this->view->solutionimageArr = $hpModer->getAllByWhere("status='1' AND type='solution'",array("displayorder ASC","id DESC"));
		$this->view->specialimageArr = $hpModer->getAllByWhere("status='1' AND type='special'",array("displayorder ASC","id DESC"));
		
		$listnum = 5;
		$this->view->appArr = $appArr;
        //技术研讨会
		$semdertModel = new Default_Model_DbTable_Seminar();
		$sqlstr ="SELECT sem.id,sem.type,sem.title
		FROM seminar as sem
		WHERE sem.status=1 AND sem.title!='' ORDER BY sem.home DESC,sem.type DESC,sem.created DESC LIMIT 0 , {$listnum}";
		$this->view->sem = $semdertModel->getBySql($sqlstr);
		
		//应用方案
		$solutionModel = new Default_Model_DbTable_Solution();
		$sqlstr ="SELECT sol.id,sol.title
		FROM solution as sol
		WHERE sol.status=1 AND sol.title!='' ORDER BY sol.home DESC,sol.created DESC LIMIT 0 , {$listnum}";
		$this->view->solution = $solutionModel->getBySql($sqlstr);
		
		//产品资讯
		$newsModel = new Default_Model_DbTable_Model('news');
		$sqlstr ="SELECT id,news_type_id,title
		FROM news
		WHERE status=1 AND title!='' ORDER BY home DESC,created DESC LIMIT 0 , {$listnum}";
		$this->view->news = $newsModel->getBySql($sqlstr);
		
		
			
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

