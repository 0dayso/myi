<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel5.php';
class Icwebadmin_CpCpcxController extends Zend_Controller_Action
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
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    }
    public function indexAction(){
    	$typeArr =array('ats','staged');
    	if(in_array($_GET['type'],$typeArr)) $this->view->type = $type = $_GET['type'];
    	else $this->view->type = $type='ats';
    	 
    	$wheresql = '';
    	//产品线
    	$this->view->selectbrand = $_GET['brand'];
    	if($this->view->selectbrand)
    		$wheresql .= " AND po.manufacturer = '{$this->view->selectbrand}'";
    	$this->view->partno = trim($_GET['partno']);
    	if($this->view->partno)
    		$wheresql .= " AND po.part_no LIKE '%".$this->view->partno."%'";
    	//ATS
    	$atssql    = " AND po.status=1 AND ((po.hk_stock)>0 OR (po.sz_stock)>0 OR (po.sample_stock)>0)";
    	//滞留仓
        $stagedsql = " AND po.staged=1 AND po.status=1 AND ((po.hk_stock)>0 OR (po.sz_stock)>0 OR (po.sample_stock)>0)";
    	
    	$this->view->atsnum = $this->_prodService->getTotalNum($atssql.$wheresql);
    	$this->view->stagednum = $this->_prodService->getTotalNum($stagedsql.$wheresql);

    	$sql = '';
    	if($type == 'ats'){
    		$sql = $atssql.$wheresql;
    		$this->view->total = $total =$this->view->atsnum;
    	}elseif($type == 'staged'){
    		$sql = $stagedsql.$wheresql;
    		$this->view->total = $total =$this->view->stagednum;
    	}else{
    		echo '参数错误';exit;
    	}
    	
    	
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	 
    	$sql .= " ORDER BY id DESC ";
    	if($type == 'ats'){
    		$product =  $this->_prodService->getOn($offset,$perpage,$sql);
    	}elseif($type == 'staged'){
    		$product =  $this->_prodService->getOn($offset,$perpage,$sql);
    	}
    	foreach($product as $k=>$parr){
    		$product[$k]['pdnpcn'] = $this->_prodService->checkpdnpcn($parr['id']);
    	}
    	$this->view->product = $product;
    	//获取品牌
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!=''"," name ASC");
    }
    /**
     * 导出Excel
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	
    		$newname = "IC_sellorder_".$_GET['type'].'_'.date('Y-m-d').".xls";
    		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$newname.'"');
    		header('Cache-Control: max-age=0');
    	    $wheresql = '';
    	    //产品线
    	    $this->view->selectbrand = $_GET['brand'];
    	    if($this->view->selectbrand)
    		    $wheresql .= " AND po.manufacturer = '{$this->view->selectbrand}'";
    	    $this->view->partno = trim($_GET['partno']);
    	    if($this->view->partno)
    		     $wheresql .= " AND po.part_no LIKE '%".$this->view->partno."%'";
    	    //ATS
    	    $atssql    = "  po.status=1 AND ((po.hk_stock)>0 OR (po.sz_stock)>0 OR (po.sample_stock)>0)";
    	    //滞留仓
            $stagedsql = "  po.staged=1 AND po.status=1 AND ((po.hk_stock)>0 OR (po.sz_stock)>0 OR (po.sample_stock)>0)";


    	    if($_GET['type'] == 'ats'){
    		    $this->product = $this->_prodService->getProductBySql($atssql.$wheresql." ORDER BY id DESC ");
    	    }elseif($_GET['type'] == 'staged'){
    		    $this->product = $this->_prodService->getProductBySql($stagedsql.$wheresql." ORDER BY id DESC ");
    	    }else{
    		    $this->_redirect('/icwebadmin/CpCpcx');
    	    }    	
    	if($this->product){
    		//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle($_GET['type']);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"型号");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"产品线");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"香港库存");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"国内库存");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"样片库存");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"总库存");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"订单占用（香港）");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,1,"订单占用（国内）");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,1,"减去占用后");
    			
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:I1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			 
    			$row1 = 2;
    			foreach($this->product as $k=>$data){
    				$hktotal = ($data['hk_stock']);
    				$sztotal = ($data['sz_stock']);
    				$sampletotal = ($data['sample_stock']);
    				$al = $hktotal+$sztotal+$sampletotal;
    				$totalAll = ($al-$data['hk_cover']-$data['sz_cover']);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$data['part_no']);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$data['bname']);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$hktotal);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$sztotal);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$sampletotal);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$al);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$data['hk_cover']);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(7,$row1,$data['sz_cover']);
    					$objExcel->getSheet(0)->setCellValueByColumnAndRow(8,$row1,$totalAll);
    					$row1++;
    			}
    			$num = $row1+1;
    			$objExcel->getActiveSheet()->mergeCells("A$num:I".($num+4));
    			$reark = '备注：									
1,表格中库存数量仅做参考，具体库存情况请上http://www.iceasy.com查询				
2,售价为不包含运费的销售单价.							
(订单满500元可免运费。对于总价款少于500元的订单，广东省内每单每次收取20元的运费及包装费，广东省以外收取30元的运费和包装费。)';
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$num,$reark);
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    	}else $this->_redirect('/icwebadmin/CpCpcx');
    }
}