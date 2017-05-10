<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_RepoLabController extends Zend_Controller_Action
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
    	
    	$this->labService = new Icwebadmin_Service_LabService();
    }
    public function indexAction(){
    	if($_GET['sdata']){
    		$sql = " AND (la.status='201' OR la.status='202') ";
    		
    		$this->view->sdata = $_GET['sdata'];
    		$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']." 23:59:59");
    		$sql .= " AND la.created BETWEEN '$stmie' AND '$etmie'";
    		
    		$this->view->record = $this->labService->getInstrumentRecord($sql);
    		
    	}
    }
    
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	 
    	if($_GET['sdata']){
    		$newname = "IC_Lab_".$_GET['sdata']."_".$_GET['edata'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		
    		$sql = " AND (la.status='201' OR la.status='202') ";
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']." 23:59:59");
    		$sql .= " AND la.created BETWEEN '$stmie' AND '$etmie'";
    		
    		$record = $this->labService->getInstrumentRecord($sql);
    		
    		if($record){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
    			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
    			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
    			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
    			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"器材类型");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"实验器材");	
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"器材型号");			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"器材品牌");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"地点");	
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"项目名称");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"项目描述");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"申请客户 ");	 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"客户情况");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,1,"使用时间");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,1,"联络人");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,1,"电话");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,1,"邮箱");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,1,"协助人");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,1,"协助人部门");
    
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:O1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    
    			$row1 = 2;
    			foreach($record as $v){

    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$v['inst']['roomname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['inst']['ins_name']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['inst']['model']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['inst']['brand']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['city']);
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['project_name']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['project_des']);
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$v['company']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,($v['customer']==2?$v['follow']:'新客户'));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,$row1,$v['vist_time']);
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,$row1,$v['contact']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,$row1,$v['phone']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,$row1,$v['email']);
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,$row1,$v['help_name']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,$row1,$v['help_dep']);
    				
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    	}else $this->_redirect('/icwebadmin/RepoLab');
    }
}