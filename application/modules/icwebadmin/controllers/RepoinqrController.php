<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_RepoInqrController extends Zend_Controller_Action
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
    		$this->view->sdata = $_GET['sdata'];
    		$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    		$salesproductModel = new Icwebadmin_Model_DbTable_Model('sales_product');
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']." 23:59:59");
    		$sql = " AND inqd.created BETWEEN '$stmie' AND '$etmie'";
    		//客户性质
    		if($this->view->property){
    			$sql .= " AND up.property='{$this->view->property}'";
    		}
    		//产品线
    		if($this->view->selectbrand){
    			$sql .= " AND p.manufacturer='{$this->view->selectbrand}'";
    		}
    		//负责销售
    		if($this->view->xsname){
    			$sql .= " AND up.staffid='{$this->view->xsname}'";
    		}
    		$this->view->inqprod = $this->_repoService->inqRepo($sql);
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
    	$property_tmp = array('enduser'=>'终端用户','merchant'=>'贸易商');
    	if($_GET['sdata']){
    		$newname = "IC_sellorder_".$_GET['sdata']."_".$_GET['edata'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		$this->view->property = $_GET['property'];
    		$this->view->selectbrand = $_GET['brand'];
    		$this->view->xsname = $_GET['xsname'];
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']?$_GET['edata']:date("Y-m-d")." 23:59:59");
    		$sql = " AND inqd.created BETWEEN '$stmie' AND '$etmie'";
    		//客户性质
    		if($this->view->property){
    			$sql .= " AND up.property='{$this->view->property}'";
    		}
    		//产品线
    		if($this->view->selectbrand){
    			$sql .= " AND p.manufacturer='{$this->view->selectbrand}'";
    		}
    		//负责销售
    		if($this->view->xsname){
    			$sql .= " AND up.staffid='{$this->view->xsname}'";
    		}
    		$soprod = $this->_repoService->inqRepo($sql);
    		if($soprod){ 
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"Item");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"客户类型");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"客户名称");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"客户领域");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"产品线");	 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"产品分类");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"产品型号");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"MPQ");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"MOQ");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,1,"需求数量");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,1,"成交数量");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,1,"成交单价");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,1,"成交总价");
    			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,1,"询价日期");
    			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,1,"报价日期");
    			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(15,1,"跟进销售");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(16,1,"报价跟进");
    			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(50);
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:P1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			
    			$row1 = 2;
    			foreach($soprod as $k=>$v){
    			if(is_int($k)){
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,($k+1));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$property_tmp[$v['property']]);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['companyname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['appname']?$v['appname']:$v['personaldesc']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['brandname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['pcname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$v['mpq']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,$v['moq']?$v['moq']:$v['mpq']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,$row1,$v['number']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,$row1,$v['buynum']?$v['buynum']:'--');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,$row1,$v['buyprice']?$v['currency'].' '.$v['buyprice']:'--');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,$row1,$v['buyprice']?$v['currency'].' '.($v['buynum']*$v['buyprice']):'--');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,$row1,date('Y/m/d H:i',$v['created']));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,$row1,$v['modified']?date('Y/m/d H:i',$v['modified']):'');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(15,$row1,$v['lastname'].$v['firstname']);
    				
    				if($soprod['log'][$v['id']]){
    				   foreach($soprod['log'][$v['id']] as $log){
    					  if($log){
    						 $loginfo .=date('Y-m-d H:i',$log['created']).'：'.$log['description'].'。';
    					  }
    				   }
    				}else $loginfo = '--';
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(15,$row1,$loginfo);
    				$row1++;
    			}
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    		}
    	}else $this->_redirect('/icwebadmin/RepoSell');
    }
}