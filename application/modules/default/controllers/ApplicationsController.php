<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class ApplicationsController extends Zend_Controller_Action {
	private $_defaultlogService;
	private $_ruledModel;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'applications';
		 
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		$this->fun = $this->view->fun = new MyFun();
		
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		
		$this->seoconfig = Zend_Registry::get('seoconfig');
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		$this->_ruledModel = new Icwebadmin_Model_DbTable_Model("user_rule_detailed");
		
		$this->_searchService = new Default_Service_SearchService();
		$this->PartNokeywords = $this->_searchService->getClickPartNokeywords();
	}
	public function homeAction(){
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
		$appcModel = new Default_Model_DbTable_AppCategory();
		$solutionModel = new Default_Model_DbTable_Solution();
		$hpModer = new Default_Model_DbTable_HomePhoto();
		$this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		$this->_sprodModer = new Default_Model_DbTable_Model('solution_product');
		//一般用户
		if($this->view->loginview){
			if($this->view->solutionrule){
				$rim = " OR sp.type='rim'";
			}
		}
		$this->view->brandarr = $this->_sprodModer->Query("SELECT distinct(id),name
				FROM `brand` WHERE status=1 AND id IN(
				SELECT distinct(pod.manufacturer)
				FROM product as pod
				LEFT JOIN solution_product as sp ON sp.prod_id=pod.id
				WHERE (sp.type='core' $rim) AND sp.status=1 AND pod.status=1 )");
		//滚动
		$this->view->topimageArr = $hpModer->getAllByWhere("status='1' AND type='solhome'",array("displayorder ASC","id DESC"));
		
	   //top 应用方案
		
		$sqlstr ="SELECT sol.*,app.name as appname FROM solution as sol
		LEFT JOIN app_category as app ON  sol.app_level1 = app.id
		WHERE sol.status=1 AND sol.title!='' ORDER BY sol.home DESC,sol.created DESC LIMIT 0 , 5";
		$this->view->solution = $solutionModel->getBySql($sqlstr);
	}
	public function indexAction() {
		$this->config = Zend_Registry::get('config');
		$this->commonconfig = Zend_Registry::get('commonconfig');
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
		if($this->_getParam('solutionlist')){
			$solutionlist = explode("-",$this->_getParam('solutionlist'));
			$appid  = isset($solutionlist[1])?(int)$solutionlist[1]:'';
		}else{
			header( "HTTP/1.1 301 Moved Permanently" ) ;
			$appid = (int)$_GET['appid'];
			if($appid) header("Location:/solutionlist-".$appid.".html");
			else header("Location:/solutionlist");
		}
		
		
		//重新设置headtitle 、 description和keywords等
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();
		
		
		$appcModel = new Default_Model_DbTable_AppCategory();
		
		//找到品牌
		if($appid) {$appstr = "LEFT JOIN solution as s ON s.id=sp.solution_id ";
		$appstr2 = "AND s.app_level1=$appid";
		}
		
		$this->common = new MyCommon();
		$this->view->loginview = $this->common->isLoginAndEmail();
		if($this->view->loginview){
			$userruleService = new Default_Service_UserruleService();
			$solutionRule = $userruleService->getRuleByType('solution');			
			//访问权限  1、按组
			$this->view->solutionrule = $userruleService->getSolutionRule($solutionRule);
			
		}
		//一般用户
		if($this->view->loginview){
			if($this->view->solutionrule){
				$rim = " OR sp.type='rim'";
			}
		}
		
		$this->_sprodModer = new Default_Model_DbTable_Model('solution_product');
		$this->view->brandarr = $this->_sprodModer->Query("SELECT distinct(id),name
FROM `brand` WHERE status=1 AND id IN(
				SELECT distinct(pod.manufacturer)
                FROM product as pod
				LEFT JOIN solution_product as sp ON sp.prod_id=pod.id 
				{$appstr}
				WHERE (sp.type='core' $rim) AND sp.status=1 AND pod.status=1 {$appstr2})");
		
		$this->view->appLevel1 = $appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
		if(empty($appid)){
			$viewobj->headTitle($this->seoconfig->general->applications_title,'SET');
			$viewobj->headMeta()->setName('description',$this->seoconfig->general->applications_description);
			$viewobj->headMeta()->setName('keywords',$this->seoconfig->general->applications_keywords.$this->PartNokeywords);
		   $sqltmp = "";
		}else {
			$app_category = '';
			foreach($appLevel1 as $app){
				if($appid==$app['id']){
					$app_category = $app['name'];
				}
			}
			$viewobj->headTitle(str_ireplace(array("<app_category>"),array($app_category),$this->seoconfig->general->applications_app_title),'SET');
			$viewobj->headMeta()->setName('description',str_ireplace(array("<app_category>"),array($app_category),$this->seoconfig->general->applications_app_description));
			$viewobj->headMeta()->setName('keywords',str_ireplace(array("<app_category>"),array($app_category),$this->seoconfig->general->applications_app_keywords.$this->PartNokeywords));
		   
			$sqltmp = "AND app_level1='{$appid}'";
		}
		$this->view->selectid = $appid;
		//品牌
		$this->view->brandselect = array();
		$brandid =$_GET['b'];
		if($brandid){
			$this->view->brandid = $brandid;
			$this->view->brandselect = explode('_',$brandid);
			$brandidsql = str_ireplace('_',"','",$brandid);
			$sqltmp .= " AND s.id IN( 
				SELECT distinct(sp.solution_id)
                FROM product as pod
				LEFT JOIN solution_product as sp ON sp.prod_id=pod.id
				{$appstr}
				WHERE pod.manufacturer IN ('$brandidsql') AND (sp.type='core' $rim) AND sp.status=1 AND pod.status=1 )";
		}
		//查询方案列表
		$solModel = new Default_Model_DbTable_Solution();
		
		//总数
		$sqlstr = "SELECT count(id) as num FROM solution s WHERE s.status=1 {$sqltmp}";
		$allcan = $solModel->getBySql($sqlstr,array());
		$total = $allcan[0]['num'];
		//分页
		$perpage = $this->commonconfig->page->news;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show($this->commonconfig->page->newstype);
		
		$sqlstr ="SELECT s.*,app.name as appname FROM solution as s
		LEFT JOIN app_category as app ON  s.app_level1 = app.id
		WHERE s.status=1 {$sqltmp} ORDER BY s.created DESC LIMIT {$offset},{$perpage}";
		$this->view->allSol = $solModel->getBySql($sqlstr, array());
		
	}
	/*
	 * 详细页面
	 */
	public function detailsAction(){
		$userruleService = new Default_Service_UserruleService();
		$appService = new Default_Service_ApplicationsService();
		$partService = new Default_Service_ProductService();
		$solModel = new Default_Model_DbTable_Solution();
		if($this->_getParam('solution')){
			$solution = explode("-",$this->_getParam('solution'));
			$this->view->solid = $solid  = (int)$solution[1];
		}else{
			header( "HTTP/1.1 301 Moved Permanently" ) ;
			$solid = (int)$_GET['solid'];
			if($solid) header("Location:/solution-".$solid.".html");
		    else header("Location:/solutionlist");
		}
		//查询方案
		$sqlstr ="SELECT so.*,app.id as appid,app.name
		FROM solution as so LEFT JOIN app_category as app
		ON so.app_level1 = app.id
		WHERE so.id='{$solid}' AND so.status=1";
		$soltmp = $solModel->getByOneSql($sqlstr);
		if(empty($soltmp)) $this->_redirect ( '/solutionlist' );
		//获取用户权限
		$this->common = new MyCommon();
		$this->view->loginview = $this->common->isLoginAndEmail();
		if($this->view->loginview){
			$userruleService = new Default_Service_UserruleService();
			$solutionRule = $userruleService->getRuleByType('solution');
			if(empty($solutionRule)){
				//按单个
				$this->view->ruled = $userruleService->getRuleDetailed($solid,'solution');
				if($this->view->ruled['areas'] && $this->view->ruled['rights'] && $this->view->ruled['apply']==2){
					$this->view->solutionrule = $userruleService->getSolutionRuleBys($this->view->ruled['areas'],$this->view->ruled['rights']);
				}else
				    $this->view->loginview = false;
			}else{
				//访问权限  1、按组
				$this->view->solutionrule = $userruleService->getSolutionRule($solutionRule);
			}
		}
		//一般用户
		if($this->view->loginview){
			if($this->view->solutionrule){
				//核心器件
				if(array_key_exists('hxqj',$this->view->solutionrule)){
					$hx_part = $appService->getPartID($solid,'core');
					$this->view->hxpart = array();
					foreach($hx_part as $id){
						$hxpartarr = $partService->getPartById($id);
						if($hxpartarr) $this->view->hxpart[] = $hxpartarr;
					}
					//相关视频
					$this->view->outside = $appService->getOutside($solid);
					//相关资讯
					$this->view->news = $appService->getNews($solid);
				}
				//周边器件
				if(array_key_exists('zbqj',$this->view->solutionrule)){
					$zb_part = $appService->getPartID($solid,'rim');
					$this->view->zbpart = array();
					foreach($zb_part as $id){
						$zbarr = $partService->getPartById($id);
						if($zbarr) $this->view->zbpart[] = $zbarr;
					}
				}
				//bom单
				if(array_key_exists('bomd',$this->view->solutionrule)){
					$bom = $appService->getPart($solid,'bom');
					foreach($bom as $key=>$bomarray){
						if($bomarray['prod_id']){
							$bomarr = $partService->getPartById($bomarray['prod_id']);
							if($bomarr) $bom[$key]['prod_info'] = $bomarr;
						}else{
							$bom[$key]['prod_info'] = array();
						}
					}
					$this->view->bom = $bom;
				}
				//成功案例
				if(array_key_exists('cgal',$this->view->solutionrule)){
					$this->view->caseinfo = $appService->getCase($solid);
				}
				//设计文档
				if(array_key_exists('sjwd',$this->view->solutionrule)){
					$this->view->document = $appService->getDocument($solid);
				}
				//技术支持
				if(array_key_exists('jszq',$this->view->solutionrule)){
					$this->view->engineer = $appService->getEngineer($solid);
				}
			}
		}else{
			$this->view->solprodArray = array();
			//查询关联元件，核心和周边器件都显示
			$coreprod = $appService->getPartID($solid,'core');
			$rimprod  = array();//$appService->getPartID($solid,'rim');//
			$solprod  = array_unique(array_merge ($coreprod,$rimprod));
			if(!empty($solprod)){
				$prodTmp = array();
				foreach($solprod as $prod_id){
					$prat_arr = $partService->getPartById($prod_id);
					if($prat_arr){
						$this->view->solprodArray[] = $prat_arr;
					}
				}
			}
		}
		$this->view->solution = $soltmp;   
		//重新设置headtitle 、 description和keywords等
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();
		$solution_name = $this->view->solution['title'];
		$app_category  = $this->view->solution['name'];
		$solution_no   = $this->view->solution['solution_no'];
		$viewobj->headTitle(str_ireplace(array("<solution_name>","<app_category>"),array($solution_name,$app_category),$this->seoconfig->general->applications_details_title),'SET');
		$viewobj->headMeta()->setName('description',str_ireplace(array("<description>"),array($this->view->solution['description']),$this->seoconfig->general->applications_details_description));
		//$viewobj->headMeta()->setName('keywords',str_ireplace(array("<solution_name>","<app_category>","<solution_no>"),array($solution_name,$app_category,$solution_no),$this->seoconfig->general->applications_details_keywords));
		$keywords = $soltmp['title'].",".$soltmp['tags'];
		$viewobj->headMeta()->setName('keywords',$keywords.$this->PartNokeywords);

		//记录当前session
		if(!isset($_SESSION['view_now'][$solid])){
			$_SESSION['view_now'][$solid]['number'] = 1;
			$_SESSION['view_now'][$solid]['visit']  = 1;
			$_SESSION['view_now'][$solid]['type']   = 'fags';
			$_SESSION['view_now'][$solid]['visittype']  = 'fags';
		}
		//新版本
		if(isset($_SESSION['new_version'])){
			$this->fun = new MyFun();
			$this->fun->changeView($this->view,$_SESSION['new_version']);
			$this->commonconfig->page->newstype = 9;
		}
	}
	/**
	 * 离开页面记录停留时间
	 */
	public function recordtimeAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
		  $formData  = $this->getRequest()->getPost();
		  $nowtime = time();
		  $solid   = $formData['solid'];
		  $type    = $formData['type'];
		  $nowtype = $formData['nowtype'];
		  $temp4   = $formData['s_time'].','.$nowtime;
		  //改变最后访问数
		  $_SESSION['view_now'][$solid]['visit']     = $formData['num'];
		  $_SESSION['view_now'][$solid]['visittype'] = $type; 
		  $_SESSION['view_now'][$solid]['type']      = $nowtype;
		  if($formData['num']>$_SESSION['view_now'][$solid]['number']){
		  	 $_SESSION['view_now'][$solid]['number'] = $formData['num'];
		  }
		  //历史访问时间
		  $oldtime = $this->_defaultlogService->getVappTime($solid,$nowtype);
		  $v_time  = $nowtime-$formData['s_time']+$oldtime;
		  $this->_defaultlogService->addLog(array('log_id'=>'V','temp1'=>$solid,'temp2'=>'viewsolution','temp3'=>$nowtype,'temp4'=>$temp4,'description'=>"访问应用方案:$solid,$nowtype,$type"));
		  //历史访问总时间
		  $typename = array('fags'=>'方案概述','hxyx'=>'核心优势','fakt'=>'方案框图',
		  		'hxqj'=>'核心器件','zbqj'=>'周边器件','bomd'=>'BOM单',
		  		'cgal'=>'成功案例','sjwd'=>'设计文档','jszq'=>'技术支持');
		  $needtime = array('fags'=>10,'hxyx'=>10,'fakt'=>8,
		  		'hxqj'=>15,'zbqj'=>10,'bomd'=>10,
		  		'cgal'=>5,'sjwd'=>5,'jszq'=>5);
		  $total = $this->_defaultlogService->getVappTotalTime($solid);
		  if($v_time>60){$showvtime = number_format(($v_time/60),1)."分钟";}
		  else{$showvtime = ($v_time)."秒";}
		  if($total>60){$showtotal = number_format(($total/60),1)."分钟";}
		  else{$showtotal = ($total)."秒";}
		  $messagehtml = '<p><b>'.$typename[$nowtype].'</b></p>
            <p>标准时间：'.$needtime[$nowtype].'分钟</p>
            <p>实际花费：'.$showvtime.'</p>
			<p>总共花费：'.$showtotal.'</p>';
		  echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'记录成功','messagehtml'=>$messagehtml,'nowtime'=>$nowtime,'v_time'=>$v_time));
		  exit;
		}else{
			$this->_redirect ( '/solutionlist' );
		}
	}
	/**
	 * 下载bom单，导出excel
	 */
    public function exportbomAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	//获取用户权限
		$this->common = new MyCommon();
		$loginview = $this->common->isLoginAndEmail();
		if(!$loginview) $this->_redirect ('/solutionlist');
		$userruleService = new Default_Service_UserruleService();
		$rule = $userruleService->getRuleByType('solution');
		$solutionrule = $userruleService->getSolutionRule($rule);
		$solid = $this->fun->decryptVerification($this->_getParam('key'));
		if($solid && array_key_exists('bomd',$solutionrule)){
    		$appService = new Default_Service_ApplicationsService();
    		$partService = new Default_Service_ProductService();
    		$solModel = new Default_Model_DbTable_Solution();
    		$sqlstr ="SELECT so.title FROM solution as so WHERE so.id='{$solid}' AND so.status=1";
    		$soltmp = $solModel->getByOneSql($sqlstr);
    		
    		$bom = $appService->getPart($solid,'bom');
    		foreach($bom as $key=>$bomarray){
    			if($bomarray['prod_id']){
    				$bom[$key]['prod_info'] = $partService->getPartById($bomarray['prod_id']);
    			}else{
    				$bom[$key]['prod_info'] = array();
    			}
    		}
    		if(!$bom) $this->_redirect ('/solutionlist');
    		
    		$newname = "bom_".$soltmp['title'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		
    		if($bom){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"型号");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"品牌");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"用量");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"类型");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"封装");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"备注");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
    			
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:F1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			
    			$row1 = 2;
    			foreach($bom as $v){
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$v['prod_info']['part_no']?$v['prod_info']['part_no']:$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['brand_name']?$v['brand_name']:$v['prod_info']['bname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['dosage']?$v['dosage']:'--');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['category_type']?$v['category_type']:($v['prod_info']['cname1']?$v['prod_info']['cname1']:'--'));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['sd_package']?$v['sd_package']:($v['prod_info']['supplier_device_package']?$v['prod_info']['supplier_device_package']:'--'));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['remark']?$v['remark']:'--');
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
                $objWriter->save('php://output');  
    		}
    	}else $this->_redirect ('/solutionlist');
    }
    /**
     * 申请获取更多方案资料，打开页面
     */
    public function applyappruleAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_helper->layout->disableLayout();
    	$this->_userService = new Default_Service_UserService();
    	$this->view->myinfo = $this->_userService->getUserProfile();
    	$this->view->appkey    = $this->_getParam('key');
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData = $this->getRequest()->getPost();
    		$solid= (int)$this->fun->decryptVerification($formData['key']);
    		//检查用户企业资料是否完备
    		if(!$this->_userService->checkDetailed())
    		{
    			echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'请提交相关企业资料。'));
    			exit;
    		}
    		if(!$solid){
    			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'方案信息不存在。'));
    			exit;
    		}
    		//记录申请
    		
    		//查询是否已经提交申请
    		$re = $this->_ruledModel->getRowByWhere("uid=".$_SESSION['userInfo']['uidSession']." AND did='$solid' AND type='solution'");
    		if($re){
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'您已经提交过申请，不需要重复提交。'));
    			exit;
    		}
    		$id = $this->_ruledModel->addData(array('uid'=>$_SESSION['userInfo']['uidSession'],
    				'did'=>$solid,
    				'type'=>'solution',
    				'explanation'=>$formData['explanation'],
    				'created'=>time()));
    		if($id){
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$id,'temp4'=>'提交申请应用方案访问权限成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交申请成功，请耐心等待盛芯电子审批。'));
    			exit;
    		}else{
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$id,'temp4'=>'提交申请应用方案访问权限失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'提交申请失败，系统繁忙。'));
    			exit;
    		}
    	}
    }
    /**
     * 下载设计文档
     */
    public function solutiondocAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$appService = new Default_Service_ApplicationsService();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$docid= $this->fun->decryptVerification($_GET['key']);
    	$docarr = $appService->getDocumentByid($docid);
    	$docpart = substr($docarr['url'],1);
    	if(md5(session_id())==$_GET['s'] && !empty($docarr['url']) && file_exists($docpart)){
    		$solutionrule = $appService->getSolutionrule($docarr['solution_id']);
    		//设计文档
    		if(!array_key_exists('sjwd',$solutionrule)){
    			$this->_redirect ('/solutionlist');
    		}else{
    			$docre = explode("/", $docpart);
    			$docname = $docre[(count($docre)-1)];
    			$this->fun->filedownloadpage($docpart,$docname);
    		}
    	}else $this->_redirect ('/solutionlist');
    }
}

