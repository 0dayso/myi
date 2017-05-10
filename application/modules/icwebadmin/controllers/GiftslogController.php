<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_GiftSlogController extends Zend_Controller_Action
{
    private $_filter;
	private $_mycommon;
    public function init(){
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
    	$this->Section_Area_ID = $this->view->Section_Area_ID = $controllerArray[1];
    	$this->Staff_Area_ID   = $this->view->Staff_Area_ID = $controllerArray[2];
    	
    	/*************************************************************
    	 ***		创建一些通用url             ***
    	**************************************************************/
    	$this->indexurl = $this->view->indexurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}";
    	$this->addurl   = $this->view->addurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/add";
    	$this->editurl  = $this->view->editurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/edit";
    	$this->deleteurl= $this->view->deleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delete";
    	$this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->areaService = new Icwebadmin_Service_AreaService();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	
    	$this->_logservice = new Icwebadmin_Service_ScorelogService();
    	$this->fun = $this->view->fun = new MyFun();
    }
    public function indexAction(){

    	$total = $this->_logservice->getDefaultRowNum();
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$this->view->alllogs = $this->_logservice->getDefaultAll($offset, $perpage);
    	
    }
    /**
     * 追踪
     */
    public function trackAction(){
    	
    	$this->view->valuetype = $valuetype  = $this->_getParam('valuetype');
    	$this->view->value     =  $value = $this->_getParam('value');
    	$this->view->valuetype_array = explode('||', $valuetype);
    	$this->view->value_array = explode('||', $value);
    	$sql = '';
    	
    		foreach($this->view->valuetype_array as $key=>$vtarr){
    			if($vtarr=='session_id'){
    				$sql .= " AND dl.session_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='uid'){
    				$sql .= " AND dl.uid='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='ip'){
    				$sql .= " AND dl.ip='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='controller'){
    				$sql .= " AND dl.controller='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='action'){
    				$sql .= " AND dl.action='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='log_id'){
    				$sql .= " AND dl.log_id='".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='temp1'){
    				if($this->view->value_array[$key]) $sql .= " AND dl.temp1='".$this->view->value_array[$key]."'";
    				else $sql .= " AND dl.temp1 IS NULL";
    			}
    			if($vtarr=='temp2'){
    				$sql .= " AND dl.temp2 LIKE '%".$this->view->value_array[$key]."%'";
    			}
    			if($vtarr=='temp3'){
    				$sql .= " AND dl.temp3 = '".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='temp5'){
    				$sql .= " AND dl.temp5 = '".$this->view->value_array[$key]."'";
    			}
    			if($vtarr=='created'){
    				$sql .= " AND dl.created BETWEEN ".(strtotime(date(('Y-m-d'),$this->view->value_array[$key])))." AND ".(strtotime(date(('Y-m-d 23:59:59'),$this->view->value_array[$key])))."";
    			}
    			if($vtarr=='temp4'){
    				
    				$sql .= " AND dl.temp4='".$this->view->value_array[$key]."'";
    			}
    			
    		}
    		$total = $this->_logservice->getDefaultRowNum($sql);
    		//分页
    		$perpage=20;
    		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    		$offset  = $page_ob->offset();
    		$this->view->page_bar= $page_ob->show(6);
    		$this->view->alllogs = $this->_logservice->getDefaultAll($offset, $perpage,$sql);

    }
    /**
     * 邀请排行
     */
    public function invitetopAction(){
    	//活动结束时间
		$endtime = '1390406399';
    	$slogModel   = new Default_Model_DbTable_Model('score_log');
    	$userService = new Default_Service_UserService();
    	$alllog = $slogModel->Query("SELECT distinct(sl.uid),sl.temp5 FROM `score_log` as sl WHERE sl.`temp2` = 'invite' AND sl.temp5!='' AND sl.created < '{$endtime}'");
    	$temp = $topnum = $topnum2 = $top = array();
    	foreach($alllog as $v){
    		$temp[$v['temp5']][] = $v['uid'];
    	}
    	$jifenviewall = $viewpageall = $shareall = $clickall = $inviteall = 0;
    	$this->view->jifenviewtop = $this->view->viewpagetop = $this->view->sharetop = $this->view->clicktop = $this->view->invitetop = 0;
    	$this->view->alltop =$this->view->jlangtop = $this->view->slangtop=0;
    	foreach($temp as $uid=>$ivarr){
    		$top[$uid]['userinfo'] = $userService->getUserProfileByUid($uid);
    		$t=array();
    		foreach($ivarr as $ivuid){
    			$t[] = $userService->getUserProfileByUid($ivuid);
    		}
    		$top[$uid]['invite'] = $t;
    		$inviteall += count($t);
    		if(count($t) > $this->view->invitetop)
    			$this->view->invitetop = count($t);
    		$email = explode('@',$top[$uid]['userinfo']['email']);
    		//签订数量
    		$top[$uid]['jifenviewnum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
    				WHERE uid = '{$uid}' AND temp2='jifenview' AND temp3>0 AND created < '{$endtime}'");
    		$jifenviewall +=$top[$uid]['jifenviewnum'];
    		if($top[$uid]['jifenviewnum'] > $this->view->jifenviewtop) 
    			$this->view->jifenviewtop = $top[$uid]['jifenviewnum'];
    		$top[$uid]['viewpagenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
    				WHERE uid = '{$uid}' AND temp2='viewpage' AND temp3>0 AND created < '{$endtime}'");
    		$viewpageall +=$top[$uid]['viewpagenum'];
    		if($top[$uid]['viewpagenum'] > $this->view->viewpagetop)
    			$this->view->viewpagetop = $top[$uid]['viewpagenum'];
    		$top[$uid]['sharenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
    				WHERE uid = '{$uid}' AND temp2='share' AND temp3>0 AND created < '{$endtime}'");
    		$shareall +=$top[$uid]['sharenum'];
    		if($top[$uid]['sharenum'] > $this->view->sharetop)
    			$this->view->sharetop = $top[$uid]['sharenum'];
    		//$top[$uid]['loginnum'] = $slogModel->QueryItem("SELECT count(id) FROM `default_log`
    		//WHERE uid = '{$uid}' AND (action='login' OR action='ajaxlogin') AND temp1 IS NULL");
    		$top[$uid]['clicknum'] = $slogModel->QueryItem("SELECT count(id) FROM `default_view_log`
    				WHERE uid = '{$uid}' AND created < '{$endtime}'");
    		$clickall +=$top[$uid]['clicknum'];
    		if($top[$uid]['clicknum'] > $this->view->clicktop)
    			$this->view->clicktop = $top[$uid]['clicknum'];
    		if($email[1]=='ceacsz.com.cn'){
    			$topnum2[$uid]  = count($ivarr);
    			//订单统计
    			$tm = $tm2 = array();
    			foreach($top[$uid]['invite'] as $ikey=>$invarr){	
    				$re = $slogModel->Query("SELECT salesnumber,currency,total FROM `inq_sales_order`
    						WHERE salesnumber!='' AND uid = '".$invarr['uid']."' AND back_status!=102 AND status IN('102','103','201','202','301','302')");
    				if($re) $tm[$invarr['uid']] = $re;
    				$re2 = $slogModel->Query("SELECT salesnumber,currency,total FROM `sales_order`
    						WHERE salesnumber!='' AND uid = '".$invarr['uid']."' AND back_status!=102 AND status IN('102','103','201','202','301','302')");
    				if($re2) $tm2[$invarr['uid']] = $re2;
    			}
    			$top[$uid]['inqorder'] = $tm;
    			$top[$uid]['order'] = $tm2;
    		}else{
    			$topnum[$uid]  = count($ivarr);
    		}
    		
    		//最长连续签到
    		$jvarr = $slogModel->Query("SELECT created FROM `score_log`
    				WHERE uid = '{$uid}' AND temp2='jifenview' AND temp3>0 AND created < '{$endtime}'");
    				$jlang = count($jvarr)>0?1:0;
    				foreach($jvarr as $k=>$arr){
    				$jvarr[$k] = date("ymd",$arr['created']);
    		}
    		foreach($jvarr as $k=>$arr){
    		if((int)$jvarr[$k]==((int)$jvarr[$k+1]-1)) {$jlang ++;}
    		}
    		$top[$uid]['jlang'] = $jlang;
    		if($jlang > $this->view->jlangtop)
    			$this->view->jlangtop = $jlang;
    		
    		//最长分享
    		$svarr = $slogModel->Query("SELECT created FROM `score_log`
    				WHERE uid = '{$uid}' AND temp2='share' AND temp3>0 AND created < '{$endtime}'");
    				$slang = count($svarr)>0?1:0;
    		foreach($svarr as $k=>$arr){
    		   $svarr[$k] = date("ymd",$arr['created']);
    		}
    		foreach($svarr as $k=>$arr){
    		   if((int)$svarr[$k]==((int)$svarr[$k+1]-1)) {$slang ++;}
    		}
    		$top[$uid]['slang'] = $slang;
    		if($slang > $this->view->slangtop)
    			$this->view->slangtop = $slang;
    		
    	}
    	foreach($top as $uid=>$t){
    		$top[$uid]['jp'] = $top[$uid]['jifenviewnum']/$this->view->viewpagetop*100;
    		$top[$uid]['jlangp'] = $top[$uid]['jlang']/$this->view->jlangtop*100;
    		$top[$uid]['vp'] = $top[$uid]['viewpagenum']/$this->view->viewpagetop*100;
    		$top[$uid]['sp'] = $top[$uid]['sharenum']/$this->view->sharetop*100;
    		$top[$uid]['slangp'] = $top[$uid]['slang']/$this->view->slangtop*100;
    		$top[$uid]['cp'] = $top[$uid]['clicknum']/$this->view->clicktop*100;
    		$top[$uid]['ip'] = count($top[$uid]['invite'])/$this->view->invitetop*100;
    		$top[$uid]['alltoptmp'] = ($top[$uid]['jp']+$top[$uid]['jlangp']+$top[$uid]['vp']+$top[$uid]['sp']+$top[$uid]['slangp']+$top[$uid]['cp']+$top[$uid]['ip'])/7;
    		if($top[$uid]['alltoptmp'] > $this->view->alltop)
    			$this->view->alltop = $top[$uid]['alltoptmp'];
    	}
    	arsort($topnum);
    	arsort($topnum2);
    	$this->view->jifenviewall = $jifenviewall;
    	$this->view->viewpageall = $viewpageall; 
    	$this->view->shareall= $shareall;
    	$this->view->clickall = $clickall;
    	$this->view->inviteall = $inviteall;
    	$this->view->topnum = $topnum;
    	$this->view->topnum2 = $topnum2;
    	$this->view->top = $top;
    	
    	//产品目录
    	$frontendOptions = array('lifeTime' => null,'automatic_serialization' => true);
    	$backendOptions = array('cache_dir' => CACHE_PATH);
    	//$cache 在先前的例子中已经初始化了
    	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    	// 查看一个缓存是否存在:
    	$cache_key = 'jifenchongzhi_cache';
    	$this->view->chongzhiarr    = $cache->load($cache_key);
    }
    public function chongzhiAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	//产品目录
    	$frontendOptions = array('lifeTime' => null,'automatic_serialization' => true);
    	$backendOptions = array('cache_dir' => CACHE_PATH);
    	//$cache 在先前的例子中已经初始化了
    	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    	// 查看一个缓存是否存在:
    	$cache_key = 'jifenchongzhi_cache';
    	$arr    = $cache->load($cache_key);
    	$edituid = $_POST['uid'];$type = $_POST['type'];
    	$arr[$edituid] = $type;
    	$cache->save($arr,$cache_key);
    }
}