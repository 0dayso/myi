<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_RepoOrController extends Zend_Controller_Action
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
    		$this->view->prodtype = $_GET['prodtype'];
    		$this->view->sdata = $_GET['sdata'];
    		$this->view->edata = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    		$salesproductModel = new Icwebadmin_Model_DbTable_Model('sales_product');
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($_GET['edata']." 23:59:59");
    		$sql = " AND sp.created BETWEEN '$stmie' AND '$etmie'";
    		//线上，线下
    		$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    		$back_order = 0;
    		if($this->view->ordertype=='online') $back_order = 1;
    		elseif($this->view->ordertype=='outline'){
    			$back_order = 2;
    		}
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
    		//产品类型
    		if($this->view->prodtype){
    			if($this->view->prodtype=='staged') 
    				$sql .= " AND sp.staged='1'";
    		}
    		$this->view->soprod = $this->_repoService->orderRepo($sql,$back_order);
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
    	$paytypearray = array('online'=>'在线支付','transfer'=>'银行转账','cod'=>'货到付款','mts'=>'款到发货');
    	$statusarr = array('101'=>'待客户支付货款',
    			'102'=>'待处理',
    			'103'=>'待支付余款',
    			'201'=>'待发货',
    			'202'=>'已发货',
    			'301'=>'已完成',
    			'302'=>'已评价',
    			'401'=>'订单被取消');
    	if($_GET['sdata']){
    		$newname = "IC_order_".$_GET['sdata']."_".$_GET['edata'].".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    		$this->view->property    = $_GET['property'];
    		$this->view->selectbrand = $_GET['brand'];
    		$this->view->xsname      = $_GET['xsname'];
    		$this->view->prodtype = $_GET['prodtype'];
    		$tmp = $_GET['edata']?$_GET['edata']:date("Y-m-d");
    		$stmie = strtotime($_GET['sdata']." 00:00:00");
    		$etmie = strtotime($tmp." 23:59:59");
    		$sql = " AND sp.created BETWEEN '$stmie' AND '$etmie'";
    		//线上，线下
    		$this->view->ordertype = $_GET['ordertype']?$_GET['ordertype']:'online';
    		$back_order = 0;
    		if($this->view->ordertype=='online') $back_order = 1;
    		elseif($this->view->ordertype=='outline'){
    			$back_order = 2;
    		}
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
    		//产品类型
    		if($this->view->prodtype){
    			if($this->view->prodtype=='staged')
    				$sql .= " AND sp.staged='1'";
    		}
    		$soprod = $this->_repoService->orderRepo($sql,$back_order);
    		if($soprod){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"部门");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"销售");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"公司名称");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(18);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"订单号");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(18);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"下单时间");
    			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(12);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"品牌");
    			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"型号");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"数量");
    			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"单价");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,1,"订单金额");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,1,"币种");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,1,"支付类型");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,1,"订单状态");
 
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,1,"类型");
    			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(18);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,1,"SAP订单号");

    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:O1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');

    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			
    			$row1 = 2;
    			foreach($soprod as $v){
    				if($v['companyname']) $companyname = $v['companyname'];
    				else $companyname = $v['uname'];
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$v['department']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['lastname'].$v['firstname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$companyname);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['salesnumber']);	
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,date('Y/m/d',$v['created']));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['brandname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$v['buynum']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,$v['buyprice']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(9,$row1,number_format($v['buynum']*$v['buyprice'],DECIMAL));
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(10,$row1,$v['currency']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(11,$row1,$paytypearray[$v['paytype']]);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(12,$row1,$statusarr[$v['sostatus']]);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(13,$row1,$v['back_order']?'线下':'线上');
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(14,$row1,$v['order_no']);
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
                $objWriter->save('php://output');  
    		}
    	}else $this->_redirect('/icwebadmin/RepoOr');
    }
}