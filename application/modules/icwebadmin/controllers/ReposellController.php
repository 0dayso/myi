<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_RepoSellController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_repoService;
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
    	$this->_repoService = new Icwebadmin_Service_RepoService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    }
    public function indexAction(){
    	if($_GET['sdata']){
    		$this->view->property = $_GET['property'];
    		$this->view->selectbrand = $_GET['brand'];
    		$this->view->xsname = $_GET['xsname'];
    		$this->view->type = $_GET['type'];
    		$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    		$this->view->sdata = $_GET['sdata'];
    		$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    		$salesproductModel = new Icwebadmin_Model_DbTable_Model('sales_product');
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']." 23:59:59");
    		$sql = " AND sp.created BETWEEN '$stmie' AND '$etmie'";
    		$sql2 = '';
    		if($this->view->type=='sqs') $sql2 = " AND so.sqs_code=1";
    		elseif($this->view->type=='normal'){
    			$sql2 = " AND so.sqs_code=0";
    		}
    		//线下订单、全部订单
    		if($this->view->ordertype=='online') $sql2 .= " AND so.back_order=0";
    		elseif($this->view->ordertype=='outline'){ 
    			$sql2 .= " AND so.back_order=1";
    		}
    		//客户性质
    		if($this->view->property){
    			$sql .= " AND up.property='{$this->view->property}'";
    		}
    		//产品线
    		if($this->view->selectbrand){
    			$sql .= " AND po.manufacturer='{$this->view->selectbrand}'";
    		}
    		//负责销售
    		if($this->view->xsname){
    			$sql .= " AND up.staffid='{$this->view->xsname}'";
    		}
    		if($_GET['log']){
    			$this->view->logtype = $_GET['log'];
    			$this->view->sellprod = $this->_repoService->sellRepo2($sql,$_GET['type'],$sql2);
    		}else{
    		    $this->view->sellprod = $this->_repoService->sellRepo($sql,$_GET['type'],$sql2,$this->view->ordertype);
    		}
    	}
    	//销售
    	$this->_staffservice = new Icwebadmin_Service_StaffService();
    	$this->view->xs_staff = $this->_staffservice->getXiaoShou();
    	//获取品牌
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!='' AND status=1" ," name ASC");
    }
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($_GET['sdata']){
    		$newname = "IC_sellorder_".$_GET['sdata']."_".$_GET['edata'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');

    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']?$_GET['edata']:date("Y-m-d")." 23:59:59");
    		$sql = " AND sp.created BETWEEN '$stmie' AND '$etmie'";
    		$sql2 = '';
    		if($_GET['type']=='sqs') $sql2 = " AND so.sqs_code=1";
    		elseif($_GET['type']=='normal'){
    			$sql2 = " AND so.sqs_code=0";
    		}
    		$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    		//线下订单、全部订单
    		if($this->view->ordertype=='online') $sql2 .= " AND so.back_order=0";
    		elseif($this->view->ordertype=='outline'){
    			$sql2 .= " AND so.back_order=1";
    		}
    		$this->view->property = $_GET['property'];
    		$this->view->selectbrand = $_GET['brand'];
    		$this->view->xsname = $_GET['xsname'];
    		//客户性质
    		if($this->view->property){
    			$sql .= " AND up.property='{$this->view->property}'";
    		}
    		//产品线
    		if($this->view->selectbrand){
    			$sql .= " AND po.manufacturer='{$this->view->selectbrand}'";
    		}
    		//负责销售
    		if($this->view->xsname){
    			$sql .= " AND up.staffid='{$this->view->xsname}'";
    		}
    		$soprod = $this->_repoService->sellRepo($sql,$_GET['type'],$sql2,$this->view->ordertype);
    		if($soprod){	 	 	 	 	 	 	 	 	 	 	
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"月份");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"销售人员代码");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"销售人员");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"部门");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"客户名称");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
   
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"IC易站订单号");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"产品线");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"型号");
    			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(18);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"销售数量");
    			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,1,"单价");
    			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,1,"收入合计(RMB)");
    			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,1,"收入合计(USD)");
    			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,1,"收入合计(HKD)");
    			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,1,"SAP订单号");
    			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,1,"SAP订单类型");
    			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(15);
    			
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(15,1,"SAP客户");
    			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(25);
    			
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:P1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			                
    			$row1 = 2;
    			foreach($soprod as $v){
    				if($v['companyname']) $companyname = $v['companyname'];
    				elseif($v['invtype']==2 && $v['invname']) $companyname = $v['invname'];
    				else $companyname = $v['uname'];
    				$rmbtotal = $usdtotal = $hkdtotal = '';
    				if($v['currency']=='RMB') $rmbtotal = $v['buynum']*$v['buyprice'];
    				elseif($v['currency']=='USD') $usdtotal = $v['buynum']*$v['buyprice'];
    				elseif($v['currency']=='HKD') $hkdtotal = $v['buynum']*$v['buyprice'];
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,date('Y/m',$v['created']));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,'');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['lastname'].$v['firstname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['department']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$companyname);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['salesnumber']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['brand']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,$v['buynum']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,$row1,$v['buyprice']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,$row1,$rmbtotal);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,$row1,$usdtotal);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,$row1,$hkdtotal);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,$row1,$v['order_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,$row1,$v['auart']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(15,$row1,$v['cname']);
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    	}else $this->_redirect('/icwebadmin/RepoSell');
    }
}