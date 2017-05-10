<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_OrexprController extends Zend_Controller_Action
{
	private $_orexprService;
	private $_inqsoService;
	private $_soService;
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
    	$this->view->deliveryurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delivery";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	
    	$this->_orexprService = new Icwebadmin_Service_OrexprService();
    	$this->_inqsoService = new Icwebadmin_Service_InqOrderService();
    	$this->_soService = new Icwebadmin_Service_OrderService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	
    	$this->fun = $this->view->fun = new MyFun();
    }
    public function indexAction(){
    	$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    	$spModel = new Icwebadmin_Model_DbTable_SalesProduct();
    	$sqlarr = array();
    	//选择不同的类型  (待发货)
    	$typeArr =array('select','rec');
    	if(!in_array($_GET['type'],$typeArr)) $typetmp = 'rec';
    	else $typetmp=$_GET['type'];
    	
    	if($typetmp == 'rec') {
    		//待确认发货
    		$total = $this->view->recnum = $total = $this->_orexprService->getSendNum();
    	}elseif($typetmp == 'select') {
    		$this->view->keyword = $keyword = $this->filter->pregHtmlSql($_GET['keyword']);
       		if($keyword){
    			$total = $this->view->selnum = $total = $this->_orexprService->getSelectNum($keyword);
    		}else $this->_redirect ( $this->indexurl );
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	if($typetmp == 'rec') {
    		$this->view->salesorder = $this->_orexprService->getAllSend($offset,$perpage);
    	}elseif($typetmp == 'select') {
    		$this->view->salesorder = $this->_orexprService->getSelectSend($keyword,$offset,$perpage);
    	}
        $this->view->type =$typetmp;
    }
    /*
     * ajax获取订单号
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$soArr = $this->_orexprService->getSearchSo($_GET['q']);
        $restr = '';
    	for($i=0;$i<count($soArr);$i++)
    	{
    	   $restr .= $soArr[$i]['salesnumber'] . "\n";
    	}
    	echo $restr;
	}
	public function samplesAction(){
		$samples = new Icwebadmin_Service_SamplesService();
		$total = $samples->getApplyNum(" AND spa.status=300");
		//分页
		$perpage=20;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show(6);
	    $this->view->salesorder = $samples->getApply($offset,$perpage," AND spa.status=300");
	}
	/*
	 * 确认发货
	*/
	public function deliveryAction(){
		$this->_helper->layout->disableLayout();
		$this->view->sonum = $this->filter->pregHtmlSql($_GET['sonum']);
		$this->view->soid  = $_GET['sonid'];
		//快递公司
		$couModel = new Icwebadmin_Model_DbTable_Courier();
		$this->view->courier = $couModel->getAllByWhere("id!=''","displayorder ASC");
		if($this->getRequest()->isPost()){
			$formData    = $this->getRequest()->getPost();
			$so_id       = (int)$formData['soid'];
			$sonum        = $this->filter->pregHtmlSql($formData['sonum']);
			$courier     = $this->filter->pregHtmlSql($formData['courier']);
			$cou_number  = $this->filter->pregHtmlSql($formData['cou_number']);
			$error = 0;$message='';
			if(empty($sonum)){
				$message .='订单id不能为空。<br/>';
				$error++;
			}
			if(empty($courier)){
				$message .='快递公司不能为空。<br/>';
				$error++;
			}
			if($courier!=3)
			{
			   if(empty($cou_number)){
				$message .='快递号不能为空。<br/>';
				$error++;
			   }
			}else{
				if(empty($cou_number)){
					$message .='说明不能为空。<br/>';
					$error++;
				}
			}
			if($error){
				//日志
				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$sonum,'temp4'=>'确认发货失败','description'=>$message));
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				$couhModel = new Icwebadmin_Model_DbTable_CourierHistory();
				
				$addarray = array('cou_id'=>$courier,
						'so_id'=>$so_id,
						'salesnumber'=>$sonum,
						'created'=>time(),
						'created_by'=>$_SESSION['staff_sess']['staff_id']);
				if($courier!=3){
					$addarray['cou_number'] = $cou_number;
					$courier = $couModel->getRowByWhere("id={$courier}");
					$addarray['track'] = "很抱歉，暂时没有查询物流信息。你可以选择去".$courier['name']."官网查询：<p><a href='".$courier['url']."' target='_blank'>".$courier['url']."</a></p>";
				}else{
					$addarray['track']      = $cou_number;
				}
				$newid = $couhModel->add($addarray);
				$re = $this->_orexprService->updateStatus($sonum,$newid);
				
				try{
					//日志
					$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$sonum,'temp4'=>'确认发货成功'));
					//发送邮件
					$sotype = $this->_orexprService->checkSoType($sonum);
					if($sotype=='inq')
					{
						$orderarr = $this->_inqsoService->getSoInfo($sonum);
						$pordarr = $this->_inqsoService->getSoPart($sonum);
						//更新被占用库存
						$this->_soService->updateSzcover($pordarr);
						$emailreturn = $this->_orexprService->shipmentsEmail($orderarr,$pordarr,$sotype);
					}elseif($sotype=='online'){
						$orderarr = $this->_soService->getSoInfo($sonum);
						$pordarr  = $this->_soService->getSoPart($sonum);
						//更新被占用库存
						$this->_soService->updateSzcover($pordarr);
						
						$emailreturn = $this->_orexprService->shipmentsEmail($orderarr,$pordarr,$sotype);
					}elseif($sotype=='samples'){
						//邮件通知cse
						$emailreturn = $this->_orexprService->shipmentsSamplesEmail($so_id);
						$this->_samplesapplyModel = new Icwebadmin_Model_DbTable_Model("samples_apply");
						$this->_samplesapplyModel->update(array('status'=>301),"id = {$so_id}");
					}
					//邮件日志
					if($emailreturn){
						$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$sonum,'temp4'=>'发送确认发货邮件成功'));
					}else{
						$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$sonum,'temp4'=>'发送确认发货邮件失败'));
					}
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'确认发货成功。'));
					exit;
				} catch (Exception $e) {
						$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp4'=>'确认收货失败'));
						echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'确认收货失败'));
						exit;
				}
				
			}
		}
	}
}