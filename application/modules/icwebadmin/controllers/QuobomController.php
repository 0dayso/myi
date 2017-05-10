<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_QuobomController extends Zend_Controller_Action
{
	private $_model; 
	private $_bomService;
	private $_adminlogService;
	private $_staffservice;
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
    	$this->replyurl  = $this->view->replyurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/reply";
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
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	$this->_model  = new Icwebadmin_Model_DbTable_Bom();
    	$this->fun = $this->view->fun = new MyFun();
    	$this->_bomService      = new Icwebadmin_Service_BomService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->_staffservice    = new Icwebadmin_Service_StaffService();
    }
    public function indexAction(){
    	
    	//选择不同的类型
    	$typeArr =array('pending','wait','already','no','select');
    	$this->view->select = $_GET['select'];
    	$typetmp =$this->view->type = $_GET['type']==''?'pending':$_GET['type'];
    	$typestr ='';
    	 
    	//如果销售只能看到自己负责的询价
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	$xswhere = '';
    	if($staffinfo['level_id']=='XS'){
    		if($staffinfo['control_staff']){
    			$control_staff_arr = explode(',', $staffinfo['control_staff'].','.$_SESSION['staff_sess']['staff_id']);
    			$control_staff_str = implode("','",$control_staff_arr);
    			$xswhere .= " AND up.staffid IN ('".$control_staff_str."')";
    		}else{
    			$xswhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
    		}
    	}else{
    		//根据应用领域分配跟进销售
    		$this->view->xs_staff = $this->_staffservice->getXiaoShou();
    	}
    	//搜索
    	if($this->view->select=='select'){
    	    //开始和结束时间
    	    $this->view->sdata = $sdata = $_GET['sdata'];
    	    $this->view->edata = $edata = $_GET['edata'];
    	    if($sdata){
    		    $edata = $edata?strtotime($edata):time();
    		    $xswhere .=" AND bo.created BETWEEN ".strtotime($sdata)." AND ".$edata;
    	    }
    	    //销售
    	    $this->view->xsname = $_GET['xsname'];
    	    if($_GET['xsname']){
    	    	$xswhere .= " AND up.staffid = '".$_GET['xsname']."'";
    	    }
    	    //编号
    	    if($_GET['keyword']){
    	    	$this->view->keyword = $keyword = $_GET['keyword'];
    	    	$xswhere .="  AND bo.bom_number LIKE '%".$keyword. "%'";
    	    }
    	    //交货地
    	    $this->view->delivery = $delivery = $_GET['delivery'];
    	    if(in_array($delivery,array('HK','SZ'))){
    	    	$xswhere .=" AND bo.delivery='{$delivery}' ";
    	    }
    	}
    	//待处理
    	$pending_sql = " AND bo.status=1";
    	$wait_sql = " AND bo.status=2";
    	$already_sql = " AND bo.status=3";
    	$no_sql = " AND bo.status=4";
    	
    	$this->view->pendingnum   = $this->_bomService->getNumber($pending_sql.$xswhere);
    	$this->view->waitnum      = $this->_bomService->getNumber($wait_sql.$xswhere);
    	$this->view->alreadynum   = $this->_bomService->getNumber($already_sql.$xswhere);
    	$this->view->nonum        = $this->_bomService->getNumber($no_sql.$xswhere);
    	
    	if($typetmp=='pending') $total=$this->view->pendingnum;
    	elseif($typetmp=='wait') $total=$this->view->waitnum;
    	elseif($typetmp=='already') $total=$this->view->alreadynum;
    	elseif($typetmp=='no') $total=$this->view->nonum;
    	 
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	 
    	if($typetmp=='pending')  $inquires =$this->_bomService->getBom($offset,$perpage,$pending_sql.$xswhere);
    	elseif($typetmp=='wait')  $inquires =$this->_bomService->getBom($offset,$perpage,$wait_sql.$xswhere);
    	elseif($typetmp=='already')  $inquires =$this->_bomService->getBom($offset,$perpage,$already_sql.$xswhere);
    	elseif($typetmp=='no')  $inquires =$this->_bomService->getBom($offset,$perpage,$no_sql.$xswhere);
    	
    	$this->view->inquiry = $inquires;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    /*
     * 回复
     */
    public function editAction(){
    	$this->view->id = $id = $this->_getParam('id');
    	$this->_helper->layout->disableLayout();
    	//询价者信息
    	$this->view->user = $user = $this->_bomService->getUserById($id);
    	if($this->getRequest()->isPost()){
    	    $data = $this->getRequest()->getPost();
    	    $id         = $data['id'];
    		$changeinq  = $data['changeinq'];
    		$bom_number = $data['bom_number'];
    		$error = 0;$message='';
    		$upData = array();
    		if(!$bom_number){
    			$message .='询价编号为空';
    			$error++;
    		}
    		if(empty($changeinq)){
    			$message .='请选择需要转询价的型号';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_bomModer = new Icwebadmin_Model_DbTable_Bom();
    			$bom = $this->_bomService->getBomByID($id);
    			$dids = implode(',',$changeinq);
    			$tdids= time().';'.$dids;
    			$changeinq = ($bom['changeinq']?$bom['changeinq'].'<>'.$tdids:$tdids);
    			$upData    = array('status'=>2,'changeinq'=>$changeinq,'modified'=>time(),'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$re = $this->_bomModer->updateBom($id,$upData);
    			if($re){//日志
    			   //发邮件通知
    			   //负责销售
    			   $staffinfo = $this->_bomService->getSellByBomid($id);
    			   $email = $this->_bomService->emailaddprod($bom,$staffinfo,$data['changeinq']);
    			   if($email){
    			   	  $this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$bom_number,'temp4'=>'BOM转询价提醒添加型号邮件成功'));
    			   }else{
    			   	  $this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价提醒添加型号邮件失败'));
    			   }
    			   $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$bom_number,'temp4'=>'BOM转询价提交成功'));
    		       echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交成功'));
    		       exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价提交失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交失败'));
    				exit;
    			}
    		}
    	}
    	//询价详细
    	$this->view->bom = $this->_bomService->getBomByID($id);
    }
    public function viewAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->_getParam('number')){
    		$this->view->bom = $this->_bomService->getBomByNumber($this->_getParam('number'));
    		$this->view->id = $id = $this->view->bom['id'];
    	}else{
    		$this->view->id = $id = $this->_getParam('id');
    		$this->view->bom = $this->_bomService->getBomByID($id);
    	}
    	$this->view->user = $this->_bomService->getUserById($id);
    	
    }
    /*
     * 回复
    */
    public function cancelAction(){
    	$this->view->id = $id = $this->_getParam('id');
    	$this->_helper->layout->disableLayout();
    	//询价者信息
    	$this->view->user = $user = $this->_bomService->getUserById($id);
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$id         = $data['id'];
    		$result_remark  = $data['result_remark'];
    		$bom_number = $data['bom_number'];
    		$error = 0;$message='';
    		$upData = array();
    		if(!$bom_number){
    			$message .='询价编号为空';
    			$error++;
    		}
    		if(empty($result_remark)){
    			$message .='请填写说明';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_bomModer = new Icwebadmin_Model_DbTable_Bom();
    			$upData    = array('status'=>4,'result_remark'=>$result_remark,'modified'=>time(),'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$re = $this->_bomModer->updateBom($id,$upData);
    			if($re){//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$bom_number,'temp4'=>'不通过BOM采购提交成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交成功'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'不通过BOM采购提交失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交失败'));
    				exit;
    			}
    		}
    	}
    	//询价详细
    	$this->view->bom = $this->_bomService->getBomByID($id);
    }
    /*
     * ajax获取询价编号
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	//如果销售只能看到自己负责的询价
    	$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
    	$xswhere = '';
    	if($staffinfo['level_id']=='XS') $xswhere = " AND staffid='".$_SESSION['staff_sess']['staff_id']."'";
    	 
    	$keyword = $_GET['q'];
    	$where="`bom_number` LIKE '%".$keyword. "%'".$xswhere;
    	$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    	$sqlstr ="SELECT `bom_number`  FROM `bom` WHERE {$where}";
    	$soArr = $this->_model->getBySql($sqlstr,array());
    
    	for($i=0;$i<count($soArr);$i++)
    	{
    	echo $keyword = $soArr[$i]['bom_number'] . "\n";
    	}
    }
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$bomid= $this->fun->decryptVerification($this->_getParam('id'));
    	$user = $this->_bomService->getUserById($bomid);
    	$bom = $this->_bomService->getBomByID($bomid);
    	$property_tmp = array('enduser'=>'终端用户','merchant'=>'贸易商');
    	$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
    	$currency = array('RMB'=>'￥','USD'=>'$','HKD'=>'HK$');
    	if($bom){
    		$newname = "IC_".$bom['bom_number'].'_'.$user['companyname'].'_'.$deliveryArr[$bom['delivery']].'_'.$property_tmp[$user['property']].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		if($bom){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"编号");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"型号");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"品牌");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"需求数量");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"目标单价");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"要求交货日期");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"备注");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(50);
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:G1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			 
    			$row1 = 2;
    			foreach($bom['detaile'] as $k=>$v){
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,($k+1));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['brand_name']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['number']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['price']>0?$currency[$bom['currency']].$v['price']:'');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['deliverydate']?date('Y-m-d',$v['deliverydate']):'');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['description']);
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    	}else $this->_redirect('/icwebadmin/QuoBom');
    }
    /**
     * 发放积分
     */
    public function jifenAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $id = $this->_getParam('id');
    	$this->view->bom = $this->_bomService->getBomByID($id);
    	$this->view->user = $this->_bomService->getUserById($id);
    	$scoreservice = new Default_Service_ScoreService();
    	//获取BOM活动积分
    	$this->_scoreruleModel = new Icwebadmin_Model_DbTable_Model('score_rule');
    	$this->view->score = $this->_scoreruleModel->getRowByWhere("type='uplodebom'");
    	if($this->getRequest()->isPost()){
    		$data  = $this->getRequest()->getPost();
    		$id    = $data['id'];
    		$score = $data['score'];
    		$allscore = $scoreservice->getAllScoreBom($this->view->user['uid']);
    		if($allscore + $score > $this->view->score['caps']){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>404,'temp2'=>$id,'temp4'=>'BOM发放积分失败，总积分超过限制'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'发放积分失败，总积分超过限制。此用户还能获取：'.($this->view->score['caps']-$allscore).'分'));
    			exit;
    		}
    		$re = $scoreservice->upScore($score,$this->view->user['uid']);
    		if($re){
    			$this->_bomModel =  new Icwebadmin_Model_DbTable_Model('bom');
    			$this->_bomModel->update(array('bomscore'=>$score), "id='$id'");
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'发放积分成功'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'发放积分失败'));
    			exit;
    		}
    	}
    }
}