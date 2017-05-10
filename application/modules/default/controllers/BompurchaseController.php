<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
require_once "Iceaclib/common/excel/PHPExcel.php";
require_once 'Iceaclib/common/excel/PHPExcel/Writer/Excel2007.php';
class BompurchaseController extends Zend_Controller_Action {
	private $_userService;
	private $_bomService;
	private $_productService;
	private $_filter;
	private $_defaultlogService;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'bom';
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		$this->view->fun =$this->fun =new MyFun();
		
		$this->_filter = new MyFilter();
		$this->_userService = new Default_Service_UserService();
		$this->_bomService = new Default_Service_BomService();
		$this->_productService = new Default_Service_ProductService();
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		
		$this->seoconfig = Zend_Registry::get('seoconfig');
		//重新设置headtitle 、 description和keywords等
		$layout = $this->_helper->layout();
		$viewobj = $layout->getView();
		$viewobj->headTitle($this->seoconfig->general->bom_title,'SET');
		$viewobj->headMeta()->setName('description',$this->seoconfig->general->bom_description);
		$viewobj->headMeta()->setName('keywords',$this->seoconfig->general->bom_keywords);
	}
	public function indexAction() {
		if($this->getRequest()->isPost()){
			//登录检查
			$this->common = new MyCommon();
			$this->common->loginCheck();
			$formData = $this->getRequest()->getPost();
			$delivery = $_SESSION['delivery'] = $formData['delivery'];
			$currency = $_SESSION['currency'] = $formData['currency'];
			$description  = $_SESSION['description'] = $this->_filter->pregHtmlSql($formData['description']);
			
			$_SESSION['valuenum'] = $theValueNum = (int)$formData['theValueNum'];

			if($theValueNum > 100){
				$_SESSION['message'] = '很抱歉，bom采购的产品每次不能超过100条，请分开提交。';
				$_SESSION['code']++;
			}
			for($i=1;$i<=$theValueNum;$i++){
				$_SESSION['mfr'][$i]     = $mfr     = $formData['mfr_'.$i];
				$_SESSION['partno'][$i]  = $partno  = $formData['partno_'.$i];
				$_SESSION['buynum'][$i]  = $buynum  = $formData['buynum_'.$i];
				$_SESSION['price'][$i]   = $price  = $formData['price_'.$i];
				$_SESSION['deliverydate'][$i]  = $deliverydate  = $formData['deliverydate_'.$i];
				$_SESSION['remarks'][$i] = $remarks = $formData['remarks_'.$i];
				if(empty($mfr)) {
					$_SESSION['code']++;
					$_SESSION['message']='数据错误';
					$_SESSION['m_mesg'][$i]='不能为空';
				}else $_SESSION['m_mesg'][$i]='';
				if(empty($partno)) {
					$_SESSION['code']++;
					$_SESSION['message']='数据错误';
					$_SESSION['p_mesg'][$i]='不能为空';
				}else $_SESSION['p_mesg'][$i]='';
				if(!is_numeric($buynum)) {
					$_SESSION['code']++;
					$_SESSION['message']='数据错误';
					$_SESSION['n_mesg'][$i]='输入数字';
				}else $_SESSION['n_mesg'][$i]='';
				$adddata = array($mfr,$partno,$buynum,$price,$deliverydate,$remarks);
				$alldata[]=$adddata;
			}
			if(!$_SESSION['code'] && !empty($alldata)){
				$adddatas = array();
				foreach($alldata as $prodall){
					$part_id = $this->_productService->getPid($prodall[1]);
					$adddata = array('bom_id'=>0,
								'part_no'=>$prodall[1] ,
							    'part_id'=>$part_id,
								'brand_name' =>$prodall[0],
								'number'=>$prodall[2],
								'price'=>$prodall[3],
								'deliverydate'=>strtotime($prodall[4]),
								'description'=>$prodall[5]);
					$adddatas[]=$adddata;
				}
				if($adddatas){
					//获取跟进销售
					$admin_staff = $this->_userService->getUserSales();
					$xs_email   = $admin_staff['email'];
					$xs_staffid = $admin_staff['staff_id'];
					$xs_name    = $admin_staff['lastname'].$admin_staff['firstname'];
					$cc = array();
					//如果有抄送人
					$staffService = new Icwebadmin_Service_StaffService();
					$cc = $staffService->mailtostaff($admin_staff['mail_to']);
				
					$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
							'currency'=>$currency,
							'delivery'=>$delivery,
							'remark'=>$description,
							'status'=>1,
							'created'=>time(),
							'staffid'=>$xs_staffid
					);
					//插入到数据库
					$bomModer = new Default_Model_DbTable_Bom();
					$bomdetModer = new Default_Model_DbTable_BomDetailed();
					$new_inqid = $bomModer->addData($data);
					$bom_number = 'BOM'.$new_inqid.substr(microtime(),2,4);
					//更新编号
					$bomModer->update(array("bom_number"=>$bom_number), "id='{$new_inqid}'");
					for($i=0;$i<count($adddatas);$i++){
						$adddatas[$i]['bom_id'] = $new_inqid;
					}
					$bomdetModer->addDatas($adddatas);
					//用户信息
					$user = $this->_userService->getUserProfile();
					//询价信息
					$bominfo = $this->_bomService->getBomByID($new_inqid);
					//发送email提醒销售
					$this->_bomService->sendBomAlertEmail($xs_name,$new_inqid,$user,$bominfo,$xs_email,$cc);
					
					//发送邮件给客户
					$this->_bomService->sendBomEmail($user,$bominfo);
					$_SESSION['code'] = 0;
					$_SESSION['message'] = '提交Bom采购成功';
					//记录日志
					$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$bom_number,'temp4'=>'BOM采购提交成功'));
					unset($_SESSION['mfr']);
					unset($_SESSION['partno']);
					unset($_SESSION['buynum']);
					unset($_SESSION['remarks']);
					unset($_SESSION['p_mesg']);
					unset($_SESSION['n_mesg']);
					unset($_SESSION['delivery']);
					unset($_SESSION['currency']);
					unset($_SESSION['description']);
					$this->_redirect('/bompurchase');
				}
			}
		}
		if(isset($_SESSION['new_version'])){
			$this->fun->changeView($this->view,$_SESSION['new_version']);
		}
	}
	//将提交的BOM采购分离
	public function separateAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$alldata = array();
		if($this->getRequest()->isPost()){
			$_SESSION['code'] = 0;$_SESSION['message']='';
			$bomre = $this->_bomService->checkNum(100);
			if($bomre){
				$_SESSION['code']++;
				$_SESSION['message'] = '很抱歉，提交的bom采购过多，提交不成功。';
			}
			if(!empty($_FILES['uploadtext']['tmp_name'])){
				$file = $_FILES['uploadtext'];
				$nametype = $this->_filter->extend($file['name']);
				if( $nametype=='xls' || $nametype=='xlsx'){
					$objPHPExcel = PHPExcel_IOFactory::load($file['tmp_name']);
					$objWorksheet = $objPHPExcel->getActiveSheet();
					$alldata   = $objPHPExcel->getActiveSheet()->toArray();
					//判断标题是否正确
					if($alldata[0][0]!='品牌' || $alldata[0][1]!='型号' || $alldata[0][2]!='需求数量' || $alldata[0][3]!='目标单价' || $alldata[0][4]!='要求交货日期' || $alldata[0][5]!='备注')
					{
						$_SESSION['code']++;
						$_SESSION['message'] = '您上传的Excel标题不正确，请下载样例Excel并填上数据再上传。';
					}
					//去掉标题
					unset($alldata[0]);
					if(count($alldata) > 100){
						$_SESSION['code']++;
						$_SESSION['message'] = '很抱歉，bom采购的产品每次不能超过100条，请分开提交。';
					}
				}else{
					$_SESSION['code']++;
					$_SESSION['message'] = '很抱歉，请上传Excel文档。';
				}
			}
			if($_SESSION['code'] || empty($alldata)){
				$this->_redirect('/bompurchase');
			}else{
				$_SESSION['alldata'] = $alldata;
				//保存bom excel
				$this->common = new MyCommon();
				$part = "upload/default/bomexcel/".$_SESSION['userInfo']['uidSession'].'/';
				$this->common->createFolderByPart($part);
				$file_name = time().'.'.$this->_filter->extend($file['name']);
				move_uploaded_file($file['tmp_name'],$part.$file_name);
			}
		}
		$this->_redirect('/bompurchase');
	}
	/**
	 * 接收不存在产品型号提交
	 */
	public function separateactionAction(){
		exit;
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		if($this->getRequest()->isPost()){
			$formData  = $this->getRequest()->getPost();
			$delivery = $formData['delivery'];
			$currency = $formData['currency'];
			$description  = $this->_filter->pregHtmlSql($formData['description']);
			
			$adddatas = array();
			foreach($_SESSION['alldata'] as $prodall){
				if(!$prodall['product'] && $prodall[1] && $prodall[0] && $prodall[2]){
					$adddata = array('bom_id'=>0,
							'part_no'=>$prodall[1] ,
							'brand_name' =>$prodall[0],
							'number'=>$prodall[2],
							'price'=>$prodall[3],
							'deliverydate'=>strtotime($prodall[4]),
							'description'=>$prodall[5]);
				    $adddatas[]=$adddata;
			    }
			}
			if($adddatas){
				//获取跟进销售
				$admin_staff = $this->_userService->getUserSales();
				$xs_email   = $admin_staff['email'];
				$xs_staffid = $admin_staff['staff_id'];
				$xs_name    = $admin_staff['lastname'].$admin_staff['firstname'];
				$cc = array();
				//如果有抄送人
				$staffService = new Icwebadmin_Service_StaffService();
				$cc = $staffService->mailtostaff($admin_staff['mail_to']);
				
				$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
							'currency'=>$currency,
							'delivery'=>$delivery,
							'remark'=>$description,
							'status'=>1,
							'created'=>time(),
							'staffid'=>$xs_staffid
				);
				//插入到数据库
				$bomModer = new Default_Model_DbTable_Bom();
				$bomdetModer = new Default_Model_DbTable_BomDetailed();
				$new_inqid = $bomModer->addData($data);
				//更新编号
				$bomModer->update(array("bom_number"=>'BOM'.$new_inqid.substr(microtime(),2,4)), "id='{$new_inqid}'");
				for($i=0;$i<count($adddatas);$i++){
					$adddatas[$i]['bom_id'] = $new_inqid;
				}
				$bomdetModer->addDatas($adddatas);
				//用户信息
				$user = $this->_userService->getUserProfile();
				//询价信息
				$bominfo = $this->_bomService->getBomByID($new_inqid);
				//发送email提醒销售
				$this->_bomService->sendBomAlertEmail($xs_name,$new_inqid,$user,$bominfo,$xs_email,$cc);
				
				//发送邮件给客户
				$this->_bomService->sendBomEmail($user,$bominfo);
				$_SESSION['code'] = 1;
				$_SESSION['message'] = '提交Bom采购成功';
			    foreach($_SESSION['alldata'] as $key=>$prodall){
				  if(!$prodall['product'] && $prodall[1] && $prodall[0] && $prodall[2]){
					unset($_SESSION['alldata'][$key]);
			      }
			    }
			}
			$this->_redirect('/bompurchase/separate');
		}else{
			$this->_redirect('/bompurchase/separate');
		}
	}
	public function checkuserAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//检查用户企业资料是否完备
		if(!$this->_userService->checkDetailed())
		{
			echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料'));
			exit;
		}else{
			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'成功'));
			exit;
		}
	}
}

