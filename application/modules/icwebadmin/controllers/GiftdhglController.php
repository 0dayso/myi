<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_GiftDhglController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_giftservice;
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
    	$this->_giftservice = new Icwebadmin_Service_GiftService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$typestr = '';
    	$this->view->type = $_GET['type']?$_GET['type']:'wait';
    	//待处理
    	$waitsql   = " AND ge.status='101'";
    	//已处理
    	$alreadysql= " AND ge.status='301'";
    	//被取消
    	$cancelsql = " AND ge.status='401'";
    	
    	$this->view->waitnum = $this->_giftservice->getGiftExchangeNum($waitsql);
    	$this->view->alreadynum = $this->_giftservice->getGiftExchangeNum($alreadysql);
    	$this->view->cancelnum = $this->_giftservice->getGiftExchangeNum($cancelsql);
    	if($this->view->type=='wait'){
    		$total = $this->view->waitnum;
    		$typestr = $waitsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
    	}elseif($this->view->type=='cancel'){
    		$total = $this->view->cancelnum;
    		$typestr = $cancelsql;
    	}else $this->_redirect ( '/icwebadmin' );

    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->giftall = $this->_giftservice->getGiftExchange($offset, $perpage, $typestr);
    }
    /**
     * 处理
     */
    public function processAction(){
    	$this->_helper->layout->disableLayout();
    	$couModel = new Icwebadmin_Model_DbTable_Courier();
    	//处理提交
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id        = (int)$formData['id'];
    		$courier   = (int)$formData['courier'];
    		$cou_number= $formData['cou_number'];
    		$notice    = trim($formData['notice']);
    		$error = 0;$message='';
    		$giftexchange = $this->_giftservice->getGiftExchangeByid($id);
    		if(!$id){
    			$message ='ID不存在。';
    			$error++;
    		}
    		if(!$giftexchange){
    			$message ='兑换记录不存在';
    			$error++;
    		}
    		if($giftexchange['status']!=101){
    			$message ='兑换记录已经处理';
    			$error++;
    		}
    		if($error){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'取消兑换失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//记录快递信息
    			$couhid = 0;
    			if($giftexchange['gifttype']==1){
    				$couhModel = new Icwebadmin_Model_DbTable_CourierHistory();
    				$addarray = array('cou_id'=>$courier,
    						'so_id'=>$id,
    						'created'=>time(),
    						'created_by'=>$_SESSION['staff_sess']['staff_id']);
    				if($courier!=3){
    					$addarray['cou_number'] = $cou_number;
    					$courier = $couModel->getRowByWhere("id={$courier}");
    					$addarray['track'] = "很抱歉，暂时没有查询物流信息。你可以选择去".$courier['name']."官网查询：<p><a href='".$courier['url']."' target='_blank'>".$courier['url']."</a></p>";
    				}else{
    					$addarray['track'] = $cou_number;
    				}
    				$couhid = $couhModel->add($addarray);
    			}
    			$model = new Icwebadmin_Model_DbTable_Model("gift_exchange");
    			$re = $model->update(array('status'=>301,
    					'notice'=>$notice,
    					'courierid'=>$couhid,
    					'modified'=>time()),"id = {$id}");
    			//邮件通知
    			$userservice = new Icwebadmin_Service_UserService();
    			$userinfo = $userservice->getUserProfile($giftexchange['uid']);
    			$this->_giftservice->mailalertuser($userinfo);
    			//更新库存
    			$res = $this->_giftservice->updatestock($giftexchange['giftid'],$giftexchange['number']);
    			if($res){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$giftexchange['giftid'],'temp4'=>'处理兑换，更新礼品库存成功','description'=>$giftexchange['number']));
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$giftexchange['giftid'],'temp4'=>'处理兑换，更新礼品库存失败','description'=>$giftexchange['number']));
    			}
    			if($re){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'处理兑换成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'处理成功'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'处理兑换失败','description'=>'更新数据库失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'处理失败'));
    				exit;
    			}
    		}
    	}
    	$this->view->giftexchange = $this->_giftservice->getGiftExchangeByid($_GET['id']);
    	if($this->view->giftexchange['gifttype']==1){
    		//实物发快递
    		$this->view->courier = $couModel->getAllByWhere("id!=''","displayorder ASC");
    	}
    }
    /**
     * 取消兑换
     */
    public function cancelAction(){
    	$this->_helper->layout->disableLayout();
    	//处理提交
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id = (int)$formData['id'];
    		$notice = trim($formData['notice']);
    		$error = 0;$message='';
    		$giftexchange = $this->_giftservice->getGiftExchangeByid($id);
    		if(!$id){
    			$message ='ID不存在。';
    			$error++;
    		}
    		if(!$giftexchange){
    			$message ='兑换记录不存在';
    			$error++;
    		}
    		if($giftexchange['status']!=101){
    			$message ='兑换记录已经处理';
    			$error++;
    		}
    		if(!$notice){
    			$message ='请填写说明。';
    			$error++;
    		}
    		if($error){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'取消兑换失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			
    			$model = new Icwebadmin_Model_DbTable_Model("gift_exchange");
    		    $re = $model->update(array('status'=>401,'notice'=>$notice,'modified'=>time()),"id = {$id}");
    		    //恢复库存
    		    $res = $this->_giftservice->resstockcover($giftexchange['giftid'],$giftexchange['number']);
    		    if($res){
    		    	$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$giftexchange['giftid'],'temp4'=>'取消兑换，恢复礼品库存成功','description'=>$giftexchange['number']));
    		    }else{
    		    	$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$giftexchange['giftid'],'temp4'=>'取消兑换，恢复礼品库存失败','description'=>$giftexchange['number']));
    		    }
    		    //恢复积分
    		    $dservice = new Default_Service_ScoreService();
    		    $dservice->restore($giftexchange['score'],$id,'后台取消兑换恢复积分',$giftexchange['uid']);
    			if($re){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'取消兑换成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'取消兑换成功，并成功恢复消费积分：'.$giftexchange['score']));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'取消兑换失败','description'=>'更新数据库失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'取消失败'));
    				exit;
    			}
    		}
    	}
    	$this->view->giftexchange = $this->_giftservice->getGiftExchangeByid($_GET['id']);
    	
    }
}