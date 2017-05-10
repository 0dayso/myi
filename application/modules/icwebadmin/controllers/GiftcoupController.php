<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_GiftCoupController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_couponService;
	private $_adminlogService;
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
    	
    	$this->_couponService = new Icwebadmin_Service_CouponService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$where=" cp.code!='' ";
    	$this->view->type = $_GET['type'];
    	if($this->view->type){
    		$where .=" AND cp.type='{$this->view->type}' ";
    	}
    	if($this->_mycommon->checkW($this->Staff_Area_ID))
		{
			$where .=" AND cp.created_by='".$_SESSION['staff_sess']['staff_id']."'";
		}
    	$this->view->status = $_GET['status'];
    	if($this->view->status){
    	    if($this->view->status=='can'){
    		   $where .=" AND cp.status='200' AND cp.start_date<='".time()."' AND cp.end_date >='".time()."'";
    		}elseif($this->view->status=='used'){
    		   $where .=" AND cp.status='201'";
    		}elseif($this->view->status=='notdue'){
    		   $where  .= " AND cp.status='200' AND cp.start_date >'".time()."'";
    		}elseif($this->view->status=='expired'){
    		   $where .= " AND cp.status='200' AND cp.end_date <'".time()."'";
    		}
    	}
    	$perpage=20;
    	$total = $this->_couponService->getRowNum($where);
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->data = $this->_couponService->getAllCoupon($offset, $perpage,$where);
    }
    /**
     * 添加
     */
    public function addAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$couptype        = (int)($formData['couptype']);
    		$uname        = ($formData['uname']);
    		$email        = ($formData['email']);
    		$part_no    = ($formData['part_no']);
    		$buy_number = (int)($formData['buy_number']);
    		$money_rmb  = ($formData['money_rmb']);
    		$brand_id   = (int)($formData['brand_id']);
    		$brand_id_2   = (int)($formData['brand_id_2']);
    		$start_date = ($formData['start_date']);
    		$end_date   = ($formData['end_date']);
    		$remark     = $formData['remark'];
    		$error = 0;$message='';
    		$part_id = 0;
    		if($part_no){
    			$prodservice = new Icwebadmin_Service_ProductService();
    			$part_id = $prodservice->getIdByBP($brand_id,$part_no);
    			if(!$part_id){
    				$message ='此产品不存在。<br/>';
    				$error++;
    			}
    		}
			if($couptype==1){
				if(!$part_no || !$buy_number){
					$message ='请填入产品型号和兑换数量。<br/>';
					$error++;
				}
				$brand_id_2 = $money_rmb = 0;
			}elseif($couptype==2){
				if(!$money_rmb){
					$message ='请填入抵扣金额(RMB)。<br/>';
					$error++;
				}
				$part_id = $buy_number = $brand_id_2 = 0;
			}elseif($couptype==3){
				if(!$money_rmb || !$brand_id_2){
					$message ='请填入抵扣金额(RMB)和品牌ID。<br/>';
					$error++;
				}
				$part_id = $buy_number = 0 ;
			}else{
				$message ='类型错误。<br/>';
				$error++;
			}
    		if(!$start_date || !$end_date){
    			$message ='请填入数据。<br/>';
    			$error++;
    		}
    		if($start_date>$end_date){
    			$message ='开始日期必须小于结束日期。<br/>';
    			$error++;
    		}
    		if($uname || $email){
    			$userservice = new Icwebadmin_Service_UserService();
    			if($uname){
    				$uid = $userservice->getUid($uname);
    			}
    			if($email){
    				$uid = $userservice->getUidByEmail($email);
    			}
    			if(!$uid){
    			   $message ='此用户名或者Email不存在。<br/>';
    			   $error++;
    		    }
    		}else{
    			$message ='请填入数据。<br/>';
    			$error++;
    		}
    		
    		if(!$remark){
    			$message ='请填写原因。<br/>';
    			$error++;
    		}
    		
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//获取唯一优惠券号
    			$code = $this->_couponService->getCoupon();
    			if($money_rmb){
    			  $rateModel = new Default_Model_DbTable_Rate();
    			  $arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
    			  $money_usd = $money_rmb/$arr['rate_value'];
    			}else $money_usd = 0;
    			$adddate = array('uid'=>$uid,
    					'type'=>$couptype,
    					'code'=>$code[0],
    					'part_id'=>$part_id,
    					'buy_number'=>$buy_number,
    					'money_rmb' =>$money_rmb,
    					'money_usd' =>$money_usd,
    					'brand_id' =>$brand_id_2,
    					'status'=>200,
    					'remark'=>$remark,
    					'start_date'=>strtotime($start_date),
    					'end_date'=>strtotime($end_date.' 23:59:59'),
    					'created'=>time(),
    					'created_by'=>$_SESSION['staff_sess']['staff_id']);
    			$couponModel = new Icwebadmin_Model_DbTable_Model("coupon");
    			$newid = $couponModel->addData($adddate);
    			if($newid){
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'优惠券添加成功'));
    				//发送邮件
    				$emailreturn = $this->_couponService->sendAlertEmail($newid);
    				//邮件日志
    				if($emailreturn){
    					$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'发送优惠券邮件成功'));
    					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功并发送通知邮件成功。'));exit;
    				}else{
    					$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'发送优惠券邮件失败'));
    					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功但发送通知邮件失败。'));exit;
    				}
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'添加失败。'));exit;
    			}
    		}
    	}
    	//获取品牌
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    }
    /**
     * 编辑
     */
    public function editAction(){
    	$userservice = new Icwebadmin_Service_UserService();
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData   = $this->getRequest()->getPost();
    		$couptype   = (int)($formData['couptype']);
    		$id         = (int)($formData['id']);
    		$uname      = ($formData['uname']);
    		$part_no    = ($formData['part_no']);
    		$buy_number = (int)($formData['buy_number']);
    		$start_date = ($formData['start_date']);
    		$end_date   = ($formData['end_date']);
    		$money_rmb  = ($formData['money_rmb']);
    		$brand_id   = (int)($formData['brand_id']);
    		$brand_id_2   = (int)($formData['brand_id_2']);
    		$remark     = $formData['remark'];
    	    $error = 0;$message='';
    	    if($part_no){
    	    	$prodservice = new Icwebadmin_Service_ProductService();
    	    	$part_id = $prodservice->getIdByBP($brand_id,$part_no);
    	    	if(!$part_id){
    	    		$message ='此产品不存在。<br/>';
    	    		$error++;
    	    	}
    	    }
			if($couptype==1){
				if(!$part_no || !$buy_number){
					$message ='请填入产品ID和兑换数量。<br/>';
					$error++;
				}
				$brand_id_2 = $money_rmb = 0;
			}elseif($couptype==2){
				if(!$money_rmb){
					$message ='请填入抵扣金额(RMB)。<br/>';
					$error++;
				}
				$part_id = $buy_number = $brand_id_2 = 0;
			}elseif($couptype==3){
				if(!$money_rmb || !$brand_id_2){
					$message ='请填入抵扣金额(RMB)和品牌ID。<br/>';
					$error++;
				}
				$part_id = $buy_number = 0 ;
			}else{
				$message ='类型错误。<br/>';
				$error++;
			}
    		if(!$uname || !$start_date || !$end_date){
    			$message ='请填入数据。<br/>';
    			$error++;
    		}
    		if($start_date>$end_date){
    			$message ='开始日期必须小于结束日期。<br/>';
    			$error++;
    		}
    		if($uname){
    			$userservice = new Icwebadmin_Service_UserService();
    			$uid = $userservice->getUid($uname);
    			if(!$uid){
    				$message ='此用户名不存在。<br/>';
    				$error++;
    			}
    		}
    		if(!$remark){
    			$message ='请填写原因。<br/>';
    			$error++;
    		}
    		
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			if($money_rmb){
    				$rateModel = new Default_Model_DbTable_Rate();
    				$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
    				$money_usd = $money_rmb/$arr['rate_value'];
    			}else $money_usd = 0;
    			$editdate = array('uid'=>$uid,
    					'type'=>$couptype,
    					'part_id'=>$part_id,
    					'buy_number'=>$buy_number,
    					'money_rmb' =>$money_rmb,
    					'money_usd' =>$money_usd,
    					'brand_id' =>$brand_id_2,
    					'remark'=>$remark,
    					'start_date'=>strtotime($start_date),
    					'end_date'=>strtotime($end_date.' 23:59:59'),
    					'modified'=>time(),
    					'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$couponModel = new Icwebadmin_Model_DbTable_Model("coupon");
    			$couponModel->update($editdate, "id='{$id}'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    	$id = $this->_getParam("coupid");
    	$this->view->coupon = $this->_couponService->getCouponByWhere("cp.id='{$id}'");
    	//获取品牌
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    }
}