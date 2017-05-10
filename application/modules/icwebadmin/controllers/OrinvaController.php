<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_OrInvaController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_invoiceapplyService;
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
    	$this->_invoiceapplyService = new Icwebadmin_Service_InvoiceApplyService();
    }
    public function indexAction(){
    	
    	//选择不同的类型
    	$typeArr =array('no','pass','nopass');
    	$this->view->type = $typetmp = $_GET['type']==''?'no':$_GET['type'];
    	$typestr    ='';
    	$onsql      = " AND ia.status='101'";
    	$passsql    = " AND ia.status='201'";
    	$nopasssql  = " AND ia.status='202'";
    	 
    	//没有申请发票的订单数
    	$this->view->nototal  = $this->_invoiceapplyService->getNum($onsql);
    	//待付款订单数
    	$this->view->passtotal = $this->_invoiceapplyService->getNum($passsql);
    	//处理中订单数
    	$this->view->nopasstotal = $this->_invoiceapplyService->getNum($nopasssql);
    	 
    	if($typetmp == 'no') {
    		$total = $this->view->nototal;
    	}elseif($typetmp == 'pass') {
    		$total = $this->view->passtotal;
    	}elseif($typetmp == 'nopass') {
    		$total = $this->view->nopasstotal;
    	}
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	if($typetmp == 'no') {
    		$this->view->invoiceapply = $this->_invoiceapplyService->getRecord($offset,$perpage,$onsql);
    	}elseif($typetmp == 'pass') {
    		$this->view->invoiceapply = $this->_invoiceapplyService->getRecord($offset,$perpage,$passsql);
    	}elseif($typetmp == 'nopass') {
    		$this->view->invoiceapply = $this->_invoiceapplyService->getRecord($offset,$perpage,$nopasssql);
    	}
    }
    /**
     *审核发票申请
     */
    public function applyinvoiceAction()
    {
    	$addressService = new Icwebadmin_Service_AddressService();
    	
    	//提交处理
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData   = $this->getRequest()->getPost();
    		$salesnumber= $this->_filter->pregHtmlSql($formData['salesnumber']);
    		$status     = (int)($formData['status']);
    		$so_type    = (int)($formData['so_type']);
    		$invoiceid  = (int)($formData['invoiceid']);
    		$invoiceaddress = (int)($formData['invoiceaddress']);
    		$notpass    = $this->_filter->pregHtmlSql($formData['notpass']);
    		//更新
    		$invModel = new Icwebadmin_Model_DbTable_InvoiceApply();
    		$invModel->update(array('status'=>$status,'remark'=>$notpass,'modified'=>time()), "salesnumber='{$salesnumber}'");
    		//通过
    		if($status == 201)
    		{
    			if($so_type==100)
    			{
    				$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
    				$soModel->update(array('invoiceid'=>$invoiceid,'invoiceaddress'=>$invoiceaddress), "salesnumber='{$salesnumber}'");
    			}elseif($so_type==110)
    			{
    				$soModel = new Icwebadmin_Model_DbTable_InqSalesOrder();
    				$soModel->update(array('invoiceid'=>$invoiceid,'invoiceaddress'=>$invoiceaddress), "salesnumber='{$salesnumber}'");
    			}
    		}
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'提交成功。'));
    		exit;
    	}
    	$salesnumber = $this->_getParam('salesnumber');
    	$this->view->sotype = (int)$this->_getParam('sotype');
    	if($this->view->sotype==100){
    		$this->view->orderarr = $this->_invoiceapplyService->geSoinfo($salesnumber);
    	}elseif($this->view->sotype==110){
    		$this->view->orderarr = $this->_invoiceapplyService->geInqSoinfo($salesnumber);
    	}
    	//收货地址
    	$this->view->addressFirst = $addressService->getSoAddress($this->view->orderarr['iaaddressid']);
    	//发票
    	$invoiceModel = new Icwebadmin_Model_DbTable_Invoice();
    	$this->view->invoiceFirst = $invoiceModel->getRowByWhere("id='".$this->view->orderarr['iainvoiceid']."'");
    }
    /**
     * 查看
     */
    public function viewAction()
    {
    	$addressService = new Icwebadmin_Service_AddressService();
    	$salesnumber = $this->_getParam('salesnumber');
    	$this->view->sotype = (int)$this->_getParam('sotype');
    	if($this->view->sotype==100){
    		$this->view->orderarr = $this->_invoiceapplyService->geSoinfo($salesnumber);
    	}elseif($this->view->sotype==110){
    		$this->view->orderarr = $this->_invoiceapplyService->geInqSoinfo($salesnumber);
    	}
    	//收货地址
    	$this->view->addressFirst = $addressService->getSoAddress($this->view->orderarr['iaaddressid']);
    	//发票
    	$invoiceModel = new Icwebadmin_Model_DbTable_Invoice();
    	$this->view->invoiceFirst = $invoiceModel->getRowByWhere("id='".$this->view->orderarr['iainvoiceid']."'");
    }
}