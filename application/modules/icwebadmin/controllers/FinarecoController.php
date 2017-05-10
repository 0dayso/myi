<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_FinaRecoController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_finaservice;
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
    	$this->_finaservice = new Icwebadmin_Service_FinanceService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$typestr = '';
    	$waitsql    = " AND (sapo.check IS NULL OR sapo.check=0)";
    	$alreadysql = " AND sapo.check=1";
    	
    	$this->view->type  = $_GET['type']?$_GET['type']:'wait';
    	
    	//开始和结束时间
    	$this->view->sdata = $sdata = $_GET['sdata'];
    	$this->view->edata = $_GET['edata'];
    	if($sdata){
    		$this->view->edata = $edata = $this->view->edata?$this->view->edata:date("Y-m-d");
    		$xswhere .=" AND sotmp.created BETWEEN ".strtotime($sdata)." AND ".strtotime($edata.' 23:59:59');
    	}
    	$this->view->keyword = trim($_GET['keyword']);
    	if($this->view->keyword){
    		$xswhere .="  AND sotmp.salesnumber LIKE '%".$this->view->keyword. "%'";
    	}
    	
    	$this->view->waitnum    = $this->_finaservice->getOrderNum($waitsql.$xswhere);
    	$this->view->alreadynum = $this->_finaservice->getOrderNum($alreadysql.$xswhere);
    	if($this->view->type=='wait'){
    		$total =$this->view->waitnum;
    		$typestr = $waitsql;
    	}elseif($this->view->type=='already'){
    		$total = $this->view->alreadynum;
    		$typestr = $alreadysql;
    	}else $this->_redirect($this->indexurl);
    	
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	$this->view->saporder = $this->_finaservice->getSapOrder($offset, $perpage, $typestr.$xswhere);
    }
    /**
     * 对账成功
     */
    public function editAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$salesnumber = $formData['salesnumber'];
    		
    		$re = $this->_finaservice->update(array('check'=>1), "salesnumber = '$salesnumber'");
    		if($re){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$salesnumber,'temp4'=>'对账成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"对账成功"));
    			exit;
    		}else{
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$salesnumber,'temp4'=>'对账失败'));
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'对账失败，系统错误'));
    			exit;
    		}
    	}
    }
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    		$typestr = '';
    		$waitsql    = " AND (sapo.check IS NULL OR sapo.check=0)";
    		$alreadysql = " AND sapo.check=1";
    		 
    		$this->view->type  = $_GET['type']?$_GET['type']:'wait';
    		 
    		//开始和结束时间
    		$this->view->sdata = $sdata = $_GET['sdata'];
    		$this->view->edata = $_GET['edata'];
    		if($sdata){
    			$this->view->edata = $edata = $this->view->edata?$this->view->edata:date("Y-m-d");
    			$xswhere .=" AND sotmp.created BETWEEN ".strtotime($sdata)." AND ".strtotime($edata.' 23:59:59');
    		}
    		$this->view->keyword = trim($_GET['keyword']);
    		if($this->view->keyword){
    			$xswhere .="  AND sotmp.salesnumber LIKE '%".$this->view->keyword. "%'";
    		}
    		
    		$newname = "IC_order_".$sdata."_".$edata.".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		
    		if($this->view->type=='wait'){
    			$typestr = $waitsql;
    		}elseif($this->view->type=='already'){
    			$typestr = $alreadysql;
    		}else $this->_redirect($this->indexurl);

    		$soprod = $this->_finaservice->getSapOrder('', '', $typestr.$xswhere);
    		if($soprod){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"订单号");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"IC易站客户名称");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"货币");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"订单总金额");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"SAP订单号");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"SAP订单类型");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"SAP客户号");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"SAP客户名称");
    			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"SAP发票号");
    			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,1,"下单时间");
    			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);

    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:J1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			 
    			$row1 = 2;
    			foreach($soprod as $v){
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$v['salesnumber']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['companyname']?$v['companyname']:$v['uname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['currency']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['total']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['order_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['auart']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['kunnr']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$v['cname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,$v['invoice_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,$row1,date('Y/m/d',$v['created']));

    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    }
}