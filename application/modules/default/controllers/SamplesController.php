<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class SamplesController extends Zend_Controller_Action {
	private $_prodService;
	private $_samplesService;
	private $_defaultlogService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'samples';
		
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		$this->view->fun =$this->fun =new MyFun();
		$this->filter = new MyFilter();
		$this->_samplesService = new Default_Service_SamplesService();
		//产品目录
		$this->_prodService = new Default_Service_ProductService();
		$prodCategory = $this->_prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $this->_prodService->getCategoryBrand();
		//样片申请车
		/*$this->view->samplescart = array();
		if(!isset($_SESSION['samplescart'])){
			$_SESSION['samplescart'] = array();
		}else{
			//已经存在样品车的样片
			$this->view->samplescart = array();
			foreach($_SESSION['samplescart'] as $key=>$sarr){
				$this->view->samplescart[$key] = $sarr[0];
			}
		}*/
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		
		//自定义标题、关键字和描述
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();;
		$viewobj->headTitle('IC易站，样片申请！','SET');
		$viewobj->headMeta()->setName('description','IC易站样片申请！');
		$viewobj->headMeta()->setName('keywords','IC易站,样片,样片申请,IC样片,IC样片申请,免费样片,免费IC样片');
	}
	/**
	 * 申请成功弹出框
	 */
	public function successAction(){
		$this->_helper->layout->disableLayout();
		$prod_id = $this->fun->decryptVerification($_GET['pid']);
		$appserivce = new Default_Service_ApplicationsService;
		$this->view->solution = $appserivce->getSolutionByCode($prod_id);
	}
	public function indexAction() {

		$id = '201405089';
		if($_GET['s']==1){
		   $this->_helper->layout->disableLayout();
		   $page = 'search.php';
		}else{
		   $page = 'index.php';
		}
		$upload_part = 'upload/event/'.$id.'/';
		$templateurl = $upload_part.$page;//模板文件路径
		$this->_eventservice = new Default_Service_EventService();
		if (file_exists($templateurl)) {
			$event = $this->_eventservice->getEvent("eventnumber='{$id}'");
		
			//重新设置headtitle 、 description和keywords等
			$layout = $this->_helper->layout();
			$view = $layout->getView();
			if($event['headtitle']) $view->headTitle($event['headtitle'],'SET');
			if($event['description']) $view->headMeta()->setName('description',$event['description']);
			if($event['keywords']) $view->headMeta()->setName('keywords',$event['keywords']);
			//加载view
			Zend_Loader::loadClass('Zend_View');
			$viewer = new Zend_View();
			$viewer->setScriptPath($upload_part);
			//id
			$viewer->id = $id;
			//执行php代码
			if($event['data'] || $event['code']){
				eval($event['data'].$event['code']);
			}
			
			echo $viewer->render($page);
		}
		
	}
	/**
	 * 提交样片申请
	 */
	public function submitAction(){
		
		if($this->getRequest()->isPost()){
			//登录检查
		    $this->common = new MyCommon();
		    $this->common->loginCheck();
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$formData    = $this->getRequest()->getPost();
			
		   //检查用户企业资料是否完备
		   $this->_userService = new Default_Service_UserService();
		   if(!$this->_userService->checkDetailed())
		   {
			   echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
			   exit;
		   }
		   $error = 0;$message='';
		   $spid        = $formData['spid'];
		   $part_no     = $formData['part_no'];
		   $brandname   = $formData['brandname'];
		   $applynum    = $formData['applynum'];
		   $d_remark    = $formData['d_remark'];
		   
		   $projectname = $formData['projectname'];
		   $projectstatus = $formData['projectstatus'];
		   $engineer    = $formData['engineer'];
		   $contact     = $formData['contact'];
		   $testcycle   = $formData['testcycle'];
		   $amount      = $formData['amount'];
		   $productiondate = $formData['productiondate'];
		   $instructions   = $formData['instructions'];
		   $addressradio   = $formData['addressradio'];
		   if(empty($spid) || empty($_SESSION['samplescart'])){$error++;$message='样片为空';}
		   if(empty($projectname)){$error++;$message='请输入项目名称';}
		   if(empty($projectstatus)){$error++;$message='请输入项目状态';}
		   if(empty($engineer)){$error++;$message='请输入工程师姓名';}
		   if(empty($contact)){$error++;$message='请输入工程师联系电话';}
		   if(empty($testcycle)){$error++;$message='请输入预计测试周期';}
		   if(empty($amount)){$error++;$message='请输入预计后期年用量';}
		   if(empty($productiondate)){$error++;$message='请输入预计批量生产日期';}
		   if(empty($instructions)){$error++;$message='请输入申请说明';}
		   $addressModel = new Default_Model_DbTable_Address();
		   $addre = $addressModel->getRowByWhere("id='{$addressradio}' AND status=1");
		   if(empty($addre)){$error++;$message='请选择正确的配送地址';}
		   if($error){
		   	 //记录日志
		   	 $this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>$message));
		   	 echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
		   	 exit;
		   }
		   //开始事务
		   $this->_samplesService->beginTransaction();
		   try{
		   	    $soaddModel        = new Default_Model_DbTable_OrderAddress();
		   	    $soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
		   			'name'=>$addre['name'],
		   			'companyname'=>$addre['companyname'],
		   			'province'=>$addre['province'],
		   			'city'=>$addre['city'],
		   			'area'=>$addre['area'],
		   			'address'=>$addre['address'],
		   			'mobile'=>$addre['mobile'],
		   			'tel'=>$addre['tel'],
		   			'zipcode'=>$addre['zipcode'],
		   			'warehousing'=>$addre['warehousing'],
		   			'express_name'=>$addre['express_name'],
		   			'express_account'=>$addre['express_account'],
		   			'created'=>time());
		   	    $soaddid = $soaddModel->addData($soadd_data);
		   	    $adddata = array('uid'=>$_SESSION['userInfo']['uidSession'],
		   	    		'addressid'=>$soaddid,
		   	    		'projectname'=>$projectname,
		   	    		'projectstatus'=>$projectstatus,
		   	    		'engineer'=>$engineer,
		   	    		'contact'=>$contact,
		   	    		'testcycle'=>$testcycle,
		   	    		'amount'=>$amount,
		   	    		'productiondate'=>$productiondate,
		   	    		'instructions'=>$instructions,
		   	    		'created'=>time());
		   	    $newid = $this->_samplesService->addapply($adddata);
		   	    if($soaddid && $newid)
		   	    {
		   	    	//记录详细
		   	    	$sdetailed = array();
		   	    	foreach($spid as $key=>$spid){
		   	    		$sdetailed[] = array('applyid'=>$newid,
		   	    				'spid'=>$spid,
		   	    				'part_no'=>$part_no[$key],
		   	    				'brandname'=>$brandname[$key],
		   	    				'applynum'=>(int)$applynum[$key],
		   	    				'd_remark'=>$d_remark[$key],
		   	    				'created'=>time());
		   	    	}
		   	    	$this->_samplesService->adddetaileds($sdetailed);
		   	    	//清空样片车
		   	    	unset($_SESSION['samplescart']);
		   	    	$this->_samplesService->commit();
		   	        $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'样片申请提交成功'));
		   	        echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'样片申请提交成功'));
		            exit;
		   	    }else{
		   	    	$this->_samplesService->rollBack();
		   	    	$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>'id返回为空'));
		   	    	echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
		   	    	exit;
		   	    } 
		   } catch (Exception $e) {	 
    			$this->_samplesService->rollBack();
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>$e->getMessage()));
    			echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
    			exit;
    	   }
		}else{
		   $this->view->items = array();
		   if(count($_SESSION['samplescart']) > 5){
			   unset($_SESSION['samplescart']);
			   $this->_redirect('/samples');
		   }
		   foreach($_SESSION['samplescart'] as $key=>$sarr){
			   $this->view->items[$key] = $this->_samplesService->getSamplesByid($sarr[0]);
		   }
		   //收货地址
		   $addressService = new Default_Service_AddressService();
		   
		   $this->view->addressArr = $addressService->getAddress();
		   	
		}
	}
	//加入样片车
	public function addsamplescartAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$spid = (int)$formData['spid'];
			if(in_array($spid,$this->view->samplescart)){
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'此样片已经在样片车里'));
				exit;
			}
			if(count($_SESSION['samplescart']) >= 5){
			   echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'每次申请最多支持5种样片'));
			   exit;
			}else{
			  $_SESSION['samplescart'][] = array($spid);
			  //记录日志
			  $keyarr = array_reverse(array_keys($_SESSION['samplescart']));
			  $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$spid,'temp4'=>'添加样片到样片车'));
			  echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功',key=>$keyarr[0],'num'=>count($_SESSION['samplescart'])));
			  exit;
			}
		}
	}
	//加入样片车
	public function delectsamplescartAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$key = (int)$formData['key'];
			unset($_SESSION['samplescart'][$key]);
			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'删除成功','num'=>count($_SESSION['samplescart'])));
			exit;
		}
	}
	//样片申请弹出框
	public function appAction(){
		$this->_helper->layout->disableLayout();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$proservice = new Default_Service_ProductService();
		if($this->getRequest()->isPost()){
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$formData    = $this->getRequest()->getPost();
				
			//检查用户企业资料是否完备
			$this->_userService = new Default_Service_UserService();
			if(!$this->_userService->checkDetailed())
			{
				echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
				exit;
			}
			$error = 0;$message='';
			$partid     = $this->fun->decryptVerification($formData['partid']);
			$part_info  = $proservice->getPartByid($partid);
			if(empty($part_info) && $part_info['samples']!=1){
				$error++;$message='很抱歉，此产品已经不提供样片申请。';
			}
			$spservice = new Default_Service_SamplesService();
			if($spservice->checkApplyPartid($partid)){
				$error++;$message='很抱歉，你已经提交过申请，我们正在审核中。';
			}
			if($spservice->checkApplyNum(5)){
				$error++;$message='很抱歉，最多不要超过5个样片申请。';
			}
			$part_no     = $part_info['part_no'];
			$brandname   = $part_info['bname'];
			$applynum    = $formData['applynum'];
			 
			$projectname = $formData['projectname'];
			$projectapp  = $formData['projectapp'];
			$projectstatus = $formData['projectstatus'];
			$engineer    = $formData['engineer'];
			$contact     = $formData['contact'];
			$amount      = $formData['amount'];
			$productiondate = $formData['productiondate'];
			$instructions   = $formData['instructions'];
			$addressradio   = $formData['addressradio'];
			//最多申请样片数
			$appnum = $this->_samplesService->getCanApplyNum($part_info);
			if($applynum > $appnum){
				$error++;$message='申请数量不要超过'.$appnum.'片';
			}
			//判断库存是否满足
			if(empty($projectname)){$error++;$message='请输入项目名称';}
			if(empty($projectstatus)){$error++;$message='请输入项目状态';}
			if(empty($engineer)){$error++;$message='请输入工程师姓名';}
			if(empty($contact)){$error++;$message='请输入工程师联系电话';}
			if(empty($projectapp)){$error++;$message='请输入项目应用';}
			$addressModel = new Default_Model_DbTable_Address();
			$addre = $addressModel->getRowByWhere("id='{$addressradio}' AND status=1");
			if(empty($addre)){$error++;$message='请选择正确的配送地址';}
			if($error){
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>$message));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
				exit;
			}
			//开始事务
			$this->_samplesService->beginTransaction();
			try{
				$soaddModel        = new Default_Model_DbTable_OrderAddress();
				$soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'name'=>$addre['name'],
						'companyname'=>$addre['companyname'],
						'province'=>$addre['province'],
						'city'=>$addre['city'],
						'area'=>$addre['area'],
						'address'=>$addre['address'],
						'mobile'=>$addre['mobile'],
						'tel'=>$addre['tel'],
						'zipcode'=>$addre['zipcode'],
						'warehousing'=>$addre['warehousing'],
						'express_name'=>$addre['express_name'],
						'express_account'=>$addre['express_account'],
						'created'=>time());
				$soaddid = $soaddModel->addData($soadd_data);
				$adddata = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'addressid'=>$soaddid,
						'projectname'=>$projectname,
						'projectapp'=>$projectapp,
                		'projectstatus'=>$projectstatus,
						'engineer'=>$engineer,
						'contact'=>$contact,
						'amount'=>$amount,
						'productiondate'=>$productiondate,
						'instructions'=>$instructions,
						'created'=>time());
				$newid = $this->_samplesService->addapply($adddata);
				if($soaddid && $newid)
				{
					//记录详细
					$sdetailed = array('applyid'=>$newid,
								'part_id'=>$partid,
								'part_no'=>$part_no,
								'brandname'=>$brandname,
								'applynum'=>(int)$applynum,
								'created'=>time());
					$this->_samplesdetailedModel = new Default_Model_DbTable_Model('samples_detailed');
					$re= $this->_samplesdetailedModel->addData($sdetailed);
					if($re){
					  $this->_samplesService->commit();
					  $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'样片申请提交成功'));
					  //发送邮件
					  $this->_samplesService->emailalert($newid,$_SESSION['userInfo']['uidSession']);
					  echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'样片申请提交成功'));
					  exit;
					}else{
						$this->_samplesService->rollBack();
						$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>'did返回为空'));
					    echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
					    exit;
					}
				}else{
					$this->_samplesService->rollBack();
					$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>'id返回为空'));
					echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
					exit;
				}
			} catch (Exception $e) {
				$this->_samplesService->rollBack();
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'样片申请提交失败','description'=>$e->getMessage()));
				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
				exit;
			}
		}
		$this->view->partid = $_GET['key'];
		$this->view->samplesarr = array();
		$this->view->appnum = 0;
		$pid = $this->fun->decryptVerification($this->view->partid);
		if(!$_GET['key']){
			$this->view->samplesarr = $this->_samplesService->getSamplesByWhere(" AND pro.manufacturer=17");
		}else{
			$this->view->partno =  $this->_prodService->getPartNo($pid);
			$part_info  = $proservice->getPartByid($pid);
			//最多申请样片数
			$this->view->appnum = $this->_samplesService->getCanApplyNum($part_info);
		}
		//收货地址
		$addressService = new Default_Service_AddressService();
		$this->view->addressArr = $addressService->getAddress();
	}
	//选项资料申请
	public function dataapplyAction(){
		$this->_helper->layout->disableLayout();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		if($this->getRequest()->isPost()){
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$formData    = $this->getRequest()->getPost();
	
			//检查用户企业资料是否完备
			$this->_userService = new Default_Service_UserService();
			if(!$this->_userService->checkDetailed())
			{
				echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
				exit;
			}
			$error = 0;$message='';
			$brandid     = $formData['brandid'];

			$spservice = new Default_Service_SamplesService();
			
			if($spservice->checkDataApply($brandid)){
				$error++;$message='很抱歉，你已经提交过申请，我们正在审核中。';
			}
			$applynum    = (int)$formData['applynum'];
			$addressradio   = $formData['addressradio'];

			$addressModel = new Default_Model_DbTable_Address();
			$addre = $addressModel->getRowByWhere("id='{$addressradio}' AND status=1");
			if(empty($addre)){$error++;$message='请选择正确的配送地址';}
			if(!$applynum){$error++;$message='请填写数量';}
			if($error){
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'选型资料申请提交失败','description'=>$message));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
				exit;
			}
			//开始事务
			$this->_samplesService->beginTransaction();
			try{
				$soaddModel        = new Default_Model_DbTable_OrderAddress();
				$soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'name'=>$addre['name'],
						'companyname'=>$addre['companyname'],
						'province'=>$addre['province'],
						'city'=>$addre['city'],
						'area'=>$addre['area'],
						'address'=>$addre['address'],
						'mobile'=>$addre['mobile'],
						'tel'=>$addre['tel'],
						'zipcode'=>$addre['zipcode'],
						'warehousing'=>$addre['warehousing'],
						'express_name'=>$addre['express_name'],
						'express_account'=>$addre['express_account'],
						'created'=>time());
				$soaddid = $soaddModel->addData($soadd_data);
				$adddata = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'addressid'=>$soaddid,
						'brandid'=>$brandid,
						'number'=>$applynum,
						'created'=>time());
				$newid = $this->_samplesService-> dataapply($adddata);
				if($newid)
				{
					    //发送邮件
					    $this->_samplesService->dataemailalert();
						$this->_samplesService->commit();
						$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'选型资料申请提交成功'));
						echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'选型资料申请提交成功'));
						exit;
					
				}else{
					$this->_samplesService->rollBack();
					$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'选型资料申请提交失败','description'=>'id返回为空'));
					echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
					exit;
				}
			} catch (Exception $e) {
				$this->_samplesService->rollBack();
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'选型资料申请提交失败','description'=>$e->getMessage()));
				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
				exit;
			}
		}
		$this->view->brandid = $_GET['key'];
		//收货地址
		$addressService = new Default_Service_AddressService();
		$this->view->addressArr = $addressService->getAddress();
	}
	//下载贝岭产品目录
	public function bellingapplyAction(){

		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();

		//检查用户企业资料是否完备
		$this->_userService = new Default_Service_UserService();
		if(!$this->_userService->checkDetailed())
		{
			echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
			exit;
		}
		$error = 0;$message='';
			
		if($error){
			//记录日志
			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'下载贝岭产品目录失败','description'=>$message));
			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
			exit;
		}
		$this->_defaultlogService->addLog(array('log_id'=>'A','temp4'=>'下载贝岭产品目录成功'));
		//打开下载
		$this->fun->filedownloadpage("upload/belingbrandV1.pdf","贝岭2014产品目录.pdf");
			
		

	}
	
	//下载Epson产品目录
	public function epsonapplyAction(){
	
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
	
		//检查用户企业资料是否完备
		$this->_userService = new Default_Service_UserService();
		if(!$this->_userService->checkDetailed())
		{
			echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
			exit;
		}
		$error = 0;$message='';
			
		if($error){
			//记录日志
			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'下载Epson产品目录失败','description'=>$message));
			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$message));
			exit;
		}
		$this->_defaultlogService->addLog(array('log_id'=>'A','temp4'=>'下载Epson产品目录成功'));
		//打开下载
		$this->fun->filedownloadpage("upload/2014Catalog.pdf","Epson_2014Catalog.pdf");
	
	}
}

