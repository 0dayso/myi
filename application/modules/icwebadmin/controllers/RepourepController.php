<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_RepoUrepController extends Zend_Controller_Action
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
    	
    	$this->userservice = new Icwebadmin_Service_UserService();
    }
    public function indexAction(){
    	$this->view->sdata = $_GET['sdata']?$_GET['sdata']:date("Y-m-d",strtotime("-1 month"));
    	$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    	$stmie = strtotime($this->view->sdata." 00:00:00");
    	$etmie = strtotime($this->view->edata." 23:59:59");

    	//线上，线下
    	$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    	$back_order = 0;
    	if($this->view->ordertype=='online') $back_order = 1;
    	elseif($this->view->ordertype=='outline'){
    		$back_order = 2;
    	}
    	//类型
    	$this->view->type = $_GET['type']?$_GET['type']:'day';
    	$this->view->orderstrend =array();
    	if($this->view->sdata){
    		$bstr = '';
    		if($back_order==1){
    			$bstr = " AND u.backstage = 0";
    		}elseif($back_order==2){
    			$bstr = " AND u.backstage = 1";
    		}
    		$sql = "SELECT u.uid,u.created FROM `user` as u
    		WHERE u.emailapprove=1 AND u.enable = 1 $bstr AND u.created <= '$etmie' ";
    		$this->view->orderstrend = $this->userservice->userstrend($sql,$stmie,$etmie,$this->view->type);
    	}
    }
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	 
    	$this->view->sdata = $_GET['sdata']?$_GET['sdata']:date("Y-m-d",strtotime("-1 month"));
    	$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    	if($this->view->sdata){
    		$newname = "IC_user_".$_GET['sdata']."_".$_GET['edata'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    	
    	$stmie = strtotime($this->view->sdata." 00:00:00");
    	$etmie = strtotime($this->view->edata." 23:59:59");

    	//线上，线下
    	$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    	$back_order = 0;
    	if($this->view->ordertype=='online') $back_order = 1;
    	elseif($this->view->ordertype=='outline'){
    		$back_order = 2;
    	}
    	//类型
    	$this->view->type = $_GET['type']?$_GET['type']:'day';
    	$this->view->orderstrend =array();

    		$bstr = '';
    		if($back_order==1){
    			$bstr = " AND u.backstage = 0";
    		}elseif($back_order==2){
    			$bstr = " AND u.backstage = 1";
    		}
    		$sql = "SELECT u.uid,u.created FROM `user` as u
    		WHERE u.emailapprove=1 AND u.enable = 1 $bstr AND u.created <= '$etmie' ";
    		$soprod = $this->userservice->userstrend($sql,$stmie,$etmie,$this->view->type);
    	
    		if($soprod){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"日期");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"注册总人数");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"注册人数");
    			
    
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:C1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    
    			$row1 = 2;
    			$k = $total = $oldnum = 0;
    			foreach($soprod as $d=>$v){
    				if($oldnum==0) $sunum = 0;
    				else $sunum = $v - $oldnum ;
    				$total += $sunum;
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$d);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$sunum);
    				$oldnum = $v;
    				$row1++;
    			}
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,'');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,'总计：');
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$total);
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    	}else $this->_redirect('/icwebadmin/RepoUrep');
    }
}