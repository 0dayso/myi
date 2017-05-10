<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
class InquiryController extends Zend_Controller_Action
{
	private $_fun;
    private $_inqService;
    private $_prodService;
    private $_filter;
    private $_inquiry;
    private $_userService;
    private $_defaultlogService;
    public function init()
    {
    	$_SESSION['menu'] = 'inquiry';
    	//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
    	$this->_fun =$this->view->fun= new MyFun();
    	$this->_filter = new MyFilter();
    	$this->_inqService = new Default_Service_InquiryService();
    	$this->_prodService = new Default_Service_ProductService();
    	$this->_userService = new Default_Service_UserService();
    	$this->_inquiry = new Iceaclib_default_inquiry();
    	$this->_defaultlogService = new Default_Service_DefaultlogService();
    	//产品目录
    	$prodService = new Default_Service_ProductService();
    	$prodCategory = $prodService->getProdCategory();
    	$this->view->first = $prodCategory['first'];
    	$this->view->second = $prodCategory['second'];
    	$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
    }

    public function indexAction()
    {
    	//$this->_inquiry->destroy();
    	$this->view->items = $this->_inquiry->contents();
        //echo '<pre>';
        //print_r($this->view->items);
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->_fun->changeView($this->view,$_SESSION['new_version']);
    	}
    }
    /*
     * 询价窗
    */
    public function inqboxAction()
    {
    	$this->_helper->layout->disableLayout();
    	$id = (int)$_GET['id'];
    	if($_GET['number'])
    	    $this->view->number   = (int)$_GET['number'];
    	$ids = $this->_inquiry->total_ids();
    	if(in_array($id,$ids)) $this->view->available = true;
    	else $this->view->available = false;
    	$this->view->product     = $this->_fun->filterProduct($this->_prodService->getInqProd($id));
    	$this->view->break_price = $this->_fun->showbreakprice($this->view->product['break_price']);
    	//用户信息
    	$this->view->user = $this->_userService->getUserProfile();
    	//交货地和币种
    	$this->view->del_cur = $this->_inquiry->getDelCur();
    	$Arr = array('RMB'=>'SZ','USD'=>'HK','HKD'=>'HK');
    	if($_GET['currency'] && $Arr[$_GET['currency']]){
    		$Arr = array('RMB'=>'SZ','USD'=>'HK','HKD'=>'HK');
    		$this->view->del_cur['currency'] = $_GET['currency'];
    		$this->view->del_cur['delivery'] = $Arr[$_GET['currency']];
    	}
    	$this->view->items = $this->_inquiry->contents();
    }
    /*
     * 再议价
     */
    public function againAction()
    {
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$formData    = $this->getRequest()->getPost();
    		$id          = $formData['id'];
    		$app_1_id    = (int)$formData['app_1_id'];
    		$app_2_id    = (int)$formData['app_2_id'];
    		$part_id     = $formData['part_id'];
    		$part_no     = $formData['part_no'];
    		$other       = $formData['other'];
    		$other_part  = $formData['other_part'];
    		$other_price = $formData['other_price'];
    		$num         = $formData['num'];
    		$price       = $formData['price'];
    		$expected_amount= $formData['amount'];
    		$project_name   = $this->_filter->pregHtmlSql($formData['project_name']);
    		$project_status = $this->_filter->pregHtmlSql($formData['project_status']);
    		$production_time = $formData['production_time'];
    		$description    = $this->_filter->pregHtmlSql($formData['description']);
    		$re = $this->_inqService->checkInqAgain($id);
    		if(!$re){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'不能再议价了。'));
    			exit;
    		}
    		if(empty($part_id) || empty($num)|| empty($price)|| empty($expected_amount)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'询价信息不能为空。'));
    			exit;
    		}
    		if(empty($project_name)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入项目名称'));
    			exit;
    		}
    		if(empty($project_status)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入项目状态'));
    			exit;
    		}
    		if(empty($production_time)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入量产时间'));
    			exit;
    		}
    		if(empty($description)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入再议价说明'));
    			exit;
    		}
    	try{
    		//获取父亲询价
    		$finq = $this->_inqService->getInquiryByID($id);
    		$data = array('uid'=>$_SESSION['userInfo']['uidSession'],
    				're_inquiry'=>1,
    				'father_inquiry'=>$finq['id'],
    				'delivery'=>$finq['delivery'],
    				'currency'=>$finq['currency'],
    				'app_1_id'=>$finq['app_1_id'],
    				'app_2_id'=>$finq['app_2_id'],
    				'project_name'=>$project_name,
    				'project_status'=>$project_status,
    				'production_time'=>strtotime($production_time),
    				'remark'=>$description,
    				'status'=>3,
    				'created'=>time(),
    				'staffid'=>$finq['staffid']
    		);
    		$inqModer = new Default_Model_DbTable_Inquiry();
    		$inqdetModer = new Default_Model_DbTable_InquiryDetailed();
    		$new_inqid = $inqModer->addData($data);
    		//更新询价编号
    		$inq_number = 'RFQ'.$new_inqid.substr(microtime(),2,4);
    		$inqModer->update(array("inq_number"=>$inq_number), "id='{$new_inqid}'");
    		 
    		//更新父询价记录 ，子询价记录
    		if(!$re) $son_inquiry = $re['son_inquiry'].','.$new_inqid;
    		else $son_inquiry = $new_inqid;
    		$inqModer->updateById(array("son_inquiry"=>$son_inquiry), $id);
    		
    		$datas = array();
    		for($i=0;$i<count($part_id);$i++){
    			$tmp = array('inq_id'=>$new_inqid,
    					'part_id'=>$this->_filter->pregHtmlSql($part_id[$i]),
    					'part_no'=>$this->_filter->pregHtmlSql($part_no[$i]),
    					'number' =>$this->_filter->pregHtmlSql($num[$i]),
    					'price'  =>$this->_filter->pregHtmlSql($price[$i]),
    					'other_vendors'  =>$this->_filter->pregHtmlSql($other[$i]).'|'.$this->_filter->pregHtmlSql($other_part[$i]).'|'.$this->_filter->pregHtmlSql($other_price[$i]),
    					'expected_amount'=>$this->_filter->pregHtmlSql($expected_amount[$i]));
    			$datas[] = $tmp;
    		}
    		$inqdetModer->addDatas($datas);
    		//记录日志
    		$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$inq_number,'temp4'=>'再议价提交成功'));
    		
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'再议价提交成功'));
    		exit;
    		}catch (Exception $e){
    			echo Zend_Json_Encoder::encode(array("code"=>100,"message"=>'系统繁忙'));
    			exit;
    		}
    	}else{
    	   $id = (int)$_GET['id'];
    	   $inq = $this->_inqService->getInquiryByID($id);
    	   if(!empty($inq)){
    		   $this->view->inquiry = $inq;
    	   }else $this->_redirect('/center/inquiry');
    	}
    }
    /*
     * 查看询价历史
     */
    public function viewAction()
    {
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_helper->layout->disableLayout();
    	$this->view->id = $_GET['id'];
    	$id = (int)$_GET['id'];
    	$this->view->inquiry = $this->_inqService->getInquiryHistory($id);
    	if(!empty($this->view->inquiry)){
    		sort($this->view->inquiry);
    	}else $this->_redirect('/center/inquiry');
    	
    }
    /*
     * 询价成功提示窗
     */
    public function showAction()
    {
    	$this->view->items = $this->_inquiry->contents();
    	$this->_helper->layout->disableLayout();
    }
    /*
     * 头部询价下拉
    */
    public function dropdownAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->view->items = $this->_inquiry->contents();
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->_fun->changeView($this->view,$_SESSION['new_version']);
    	}
    }
    /*
     * 加入询价列表
     */
    public function addinqlistAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$part_id  = (int)($formData['part_id']);
    		$number = $this->_filter->pregHtmlSql($formData['number']);
    		$delivery = $this->_filter->pregHtmlSql($formData['delivery']);
    		$currency = $this->_filter->pregHtmlSql($formData['currency']);
    		$price  = $this->_filter->pregHtmlSql($formData['price']);
    		$expected_amount  = $this->_filter->pregHtmlSql($formData['expected_amount']);
    		$product = $this->_prodService->getInqProd($part_id);
    		if(!empty($product))
    		{
    			if($product['noinquiry']){
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'很抱歉，此型号不支持询价。'));
    				exit;
    			}
    			//获取已经存在的询价列表的产品id
    			$ids = $this->_inquiry->total_ids();
    			//不能多于100条同时询价
    			if(count($ids) > 100){
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'每次询价不能超过100条记录，请分开询价！'));
    				exit;
    			}
    			if(empty($delivery)){
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'请选择交货地'));
    				exit;
    			}
    			if(empty($currency)){
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'请选择结算货币'));
    				exit;
    			}
    			if(!in_array($part_id,$ids))
    			{
    			  $re = $this->_inqService->addInquiry($part_id,$number,$price,$expected_amount,$product,$delivery,$currency);
    			  if($re){
    			  	//寄存
    			  	$cdModel = new Default_Model_DbTable_CartDeposit();
    			  	//客户端ip
    			  	$ip = $this->_fun->getIp();
    			  	$cdModel->insert(array('ip'=>$ip,
    			  			'prod_id'=>$product['id'],
    			  			'number'=>$number,
    			  			'hope_price'=>$price,
    			  			'expected_amount'=>$expected_amount,
    			  			'type'=>2));
    			  	
    				 //获取询价数
    				 $total_items = $this->_inqService->total_items(); 
    				 //购物车记录数，用于头部显示
    				 $_SESSION['inquirynumber']=$total_items;
    				 //记录日志
    				 $description = '交货地:'.$delivery.';结算货币:'.$currency.';采购数量:'.$number.';目标单价:'.$price.';年用量:'.$expected_amount;
    				 $this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$product['id'],'temp4'=>'加入询价篮成功','description'=>$description));
    				 echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'加入询价篮成功',"inquirynumber"=>$total_items));
    				 exit;
    			   }else{
    				 echo Zend_Json_Encoder::encode(array("code"=>4, "message"=>'加入询价失败'));
    				 exit;
    			   }
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'产品已经存在询价列表'));
    				exit;
    			}
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'产品不存在'));
    			exit;
    		}
    	}else $this->_redirect('/');
    }
    /**
     * 删除询价列表
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    function delectinqAction(){
    	//用户不能删除
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$rowid    = $formData['rowid'];
    		$ids = $this->_inquiry->total_ids();

    		$total_items = $this->_inquiry->delete($rowid);
    		$_SESSION['inquirynumber']=$total_items;
    		//删除购物车寄存
    		$cdModel = new Default_Model_DbTable_CartDeposit();
    		$cdModel->updateByWhere(array('status'=>0), "ip='".$this->_fun->getIp()."' AND prod_id='".$ids[$rowid]."' AND type=2");
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'删除成功',));
    		exit;
    	}else $this->_redirect('/');
    }
    /**
     * 删除询价记录
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    function delectrecordsAction(){
    	exit;
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$id    = $formData['id'];
    		$this->_inqService->deleteIqu($id);
    		$_SESSION['inqmessage'] = '删除询价记录成功';
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>$_SESSION['inqmessage']));
    		exit;
    	}
    }
    /**
     * 提交询价单处理
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    public function handleAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    			$formData = $this->getRequest()->getPost();
    			$num    = $formData['num'];
    			$app_1  = (int)$formData['app_1'];
    			$app_2  = (int)$formData['app_2'];
    			$delivery = $formData['delivery'];
    			$currency = $formData['currency'];
    			$price  = $formData['price'];
    			$expected_amount  = $formData['amount'];
    			$rowid  = $formData['rowid'];
    			$description  = $this->_filter->pregHtmlSql($formData['description']);
    			for($i=0;$i<count($rowid);$i++){
    				$num_tmp   = $this->_filter->pregHtmlSql($num[$i]);
    				$price_tmp = $this->_filter->pregHtmlSql($price[$i]);
    				$expected_amount_tmp = $this->_filter->pregHtmlSql($expected_amount[$i]);
    				if(empty($num_tmp)){
    					echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入数据，不能为空。'));
    					exit;
    				}else{
    					$num[$rowid[$i]]   = (int)$num_tmp;
    					$price[$rowid[$i]] = $price_tmp;
    					$amount[$rowid[$i]] = $expected_amount_tmp;
    				}
    			}
    			if(!in_array($delivery,array('SZ','HK'))) {
    				echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请选择交货地。'));
    				exit;
    			}
    			if(!in_array($currency,array('RMB','USD','HKD'))) {
    				echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请选择结算货币。'));
    				exit;
    			}
    			$items = $this->_inquiry->contents();
    			//每次最多提交100条产品询价，防止攻击
    			if(count($items)<1 ||count($items)>=100) {
    				echo Zend_Json_Encoder::encode(array("code"=>300,"message"=>'数据出错，每次询价最多提交100条产品。'));
    				exit;
    			}
    			//最多添加10个还没回复的询价，防止攻击
    			$inqre = $this->_inqService->checkNum(10);
    			if($inqre){
    				echo Zend_Json_Encoder::encode(array("code"=>111,"message"=>'很抱歉，你添加的询价单过多，请等待报价。报价后才能继续询价。'));
    				exit;
    			}
    			//检查用户企业资料是否完备
    			if(!$this->_userService->checkDetailed())
    			{
    				echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交报价必须要提交相关企业资料。'));
    				exit;
    			}
    		try{
    			$moder = new Default_Model_DbTable_Model('inquiry');
    			$moder->beginTransaction();
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
    					'app_1_id'=>$app_1,
    					'app_2_id'=>$app_2,
    					'remark'=>$description,
    					'status'=>1,
    					'created'=>time(),
    					'staffid'=>$xs_staffid
    			);
    			$inqModer = new Default_Model_DbTable_Inquiry();
    			$inqdetModer = new Default_Model_DbTable_InquiryDetailed();
    			$new_inqid = $inqModer->addData($data);
    			//更新询价编号
    			$inq_number = 'RFQ'.$new_inqid.substr(microtime(),2,4);
    			$inqModer->update(array("inq_number"=>$inq_number), "id='{$new_inqid}'");
    			
    			$datas = array();
    			foreach($items as $varr){
    				if(is_array($varr)){
    					$product = $this->_prodService->getInqProd($varr['id']);
    					if($product['noinquiry']){
    						echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'很抱歉，型号'.$varr['part_no'].'已经不支持询价。'));
    						exit;
    					}
    					$tmp = array('inq_id'=>$new_inqid,
    							'part_id'=>$varr['id'],
    							'part_no'=>$varr['part_no'],
    							'number' =>$num[$varr['rowid']],
    							'price'  =>$price[$varr['rowid']],
    							'expected_amount'=>$amount[$varr['rowid']],
    							'created'=>time());
    					$datas[] = $tmp;
    				}
    			}
    			if(empty($datas)){
    				$moder->rollBack();
    				echo Zend_Json_Encoder::encode(array("code"=>300,"message"=>'很抱歉，数据出错，请清空询价篮再继续选择产品询价。'));
    				exit;
    			}
    			$inqdetModer->addDatas($datas);
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$inq_number,'temp4'=>'询价单提交成功'));
    			//清空询价列表
    			unset($_SESSION['inquirynumber']);
    			$this->_inquiry->destroy();
    			//清空寄存
    			$cdModel = new Default_Model_DbTable_CartDeposit();
    			$cdModel->updateByWhere(array('status'=>0),"ip='".$this->_fun->getIp()."' AND type=2");
    			
    			$moder->commit();
    			//异步请求开始
    			$this->_fun->asynchronousStarts();
    			
    			//用户信息
    			$user = $this->_userService->getUserProfile();
    			//询价信息
    			$inqinfo = $this->_inqService->getInquiryByID($new_inqid);
    			
    			//发送email给销售
    			$emailreturn = $this->_inqService->sendInqAlertEmail($xs_name,$new_inqid,$user,$inqinfo,$xs_email,$cc);
    			//邮件日志
    			if($emailreturn){
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$inq_number,'temp4'=>'发送询价单提醒销售邮件成功'));
    			}else{
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$inq_number,'temp4'=>'发送询价单提醒销售邮件失败'));
    			}
    			
    			//发送邮件给客户 
    			$emailreturn = $this->_inqService->sendInqEmailToUser($user,$inqinfo);
    			//邮件日志
    			if($emailreturn){
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$inq_number,'temp4'=>'发送询价单邮件给用户成功','description'=>$user['email']));
    			}else{
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$inq_number,'temp4'=>'发送询价单邮件给用户失败'));
    			}
    			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'询价单提交成功','key'=>$this->_fun->encryptVerification($new_inqid)));
    			
    			//异步请求结束
    			$this->_fun->asynchronousEnd();
    			exit;
    			
    			}catch (Exception $e) {
    				$moder->rollBack();
    				//记录日志
    				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$inq_number,'temp4'=>'询价单提交失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
    				exit;
    		    }
    	}
    }
    /**
     * 询价单成功页面
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    public function successAction(){
    	$inqid= $this->_fun->decryptVerification($_GET['key']);
    	$inq = $this->_inqService->getInquiryByID($inqid);
    	
    	if(!empty($inq))
    	{
    		$this->view->inq=$inq;
    	}else $this->_redirect('/');
    }
    /**
     * 报价前修改询价单
     * @access 	public
     * @param
     */
    public function editAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->_fun->changeView($this->view,$_SESSION['new_version']);
    	}
    	if($this->getRequest()->isPost()){
    		$this->_helper->layout->disableLayout();
    	    $this->_helper->viewRenderer->setNoRender();
    	    $formData = $this->getRequest()->getPost();
    	    $inq_number = $formData['inq_number'];
    	    $num    = $formData['num'];
    	    $delivery = $formData['delivery'];
    	    $currency = $formData['currency'];
    	    $price  = $formData['price'];
    	    $expected_amount  = $formData['amount'];
    	    $rowid  = $formData['rowid'];
    	    $description  = $this->_filter->pregHtmlSql($formData['description']);
    	    for($i=0;$i<count($rowid);$i++){
    	    	$num_tmp   = $this->_filter->pregHtmlSql($num[$i]);
    	    	if(empty($num_tmp)){
    	    		echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请输入数据，不能为空。'));
    	    		exit;
    	    	}
    	    }
    	    if(!in_array($delivery,array('SZ','HK'))) {
    	    	echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请选择交货地。'));
    	    	exit;
    	    }
    	    if(!in_array($currency,array('RMB','USD','HKD'))) {
    	    	echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'请选择结算货币。'));
    	    	exit;
    	    }
    	    $inqstatus = $this->_inqService->getInquiryStatus($inq_number);
    	    if($inqstatus != 1) {
    	    	echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'参数错误。'));
    	    	exit;
    	    }
    	    //更新
    	    try{
    	    	$inqModer    = new Default_Model_DbTable_Inquiry();
    	    	$inqdetModer = new Default_Model_DbTable_InquiryDetailed();
    	        $inqdata = array('delivery'=>$delivery,'currency'=>$currency,'remark'=>$description);
    	        $inqModer->update($inqdata, "inq_number='{$inq_number}'  AND status=1 ");
    	        for($i=0;$i<count($rowid);$i++){
    	        	$data = array('number'=>(int)$num[$i],
    	        			'price'=>$this->_filter->pregHtmlSql($price[$i]),
    	        			'expected_amount'=>$this->_filter->pregHtmlSql($expected_amount[$i]));
    				$inqdetModer->update($data, "id='".$rowid[$i]."'");
    			}
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$inq_number,'temp4'=>'询价单修改成功'));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'修改成功'));
    			exit;
    	    }catch (Exception $e) {
    	    	//记录日志
    	    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$inq_number,'temp4'=>'询价单修改失败'));
    	    	echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'系统繁忙'));
    	    	exit;
    	    }
    	}else{
    	    $inqid= $this->_fun->decryptVerification($this->_getParam('inqkey'));
    	    $this->view->inq = $this->_inqService->getInquiryByID($inqid);
    	    if(empty($this->view->inq)) $this->_redirect('/center/inquiry');
    	}
    }
    
    /**
     * 检查是否有bpp满足
     */
    public function checkbppAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	/*$partId   = $_POST['part_id'];
    	$delivery = $_POST['delivery'];
    	$currency = $_POST['currency'];
    	$number   = $_POST['number'];
    	$bpp_stock_id = $_POST['bpp_stock_id'];
    	$mpq          = $_POST['mpq'];
    	$bppService = new Default_Service_BppService();
    	$pr = $bppService->getCanSell($partId,$number,$bpp_stock_id,$mpq);
    	if($pr){
    		echo Zend_Json_Encoder::encode(array("code"=>1,"bpp_stock_id"=>$pr['id'], "message"=>'有bpp满足。'));
    		exit;
    	}else{*/
    	   echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'没有bpp满足。'));
    	   exit;
    	//}
    }
    /**
     * 购买bpp库存弹出框
     */
    public function buybppAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->partId       = $partId = $_GET["part_id"];
    	$this->view->bpp_stock_id = $bpp_stock_id = $_GET["bpp_stock_id"];
    	$this->view->delivery     = $_GET['delivery'];
    	$this->view->currency     = $_GET['currency'];
    	$this->view->number       = $_GET['number'];
    	$prodarr = $this->_prodService->getPartById($partId);
    	$this->view->partInfo = $this->_fun->filterProduct($prodarr,$bpp_stock_id);
    	if(!$this->view->partInfo["id"] || $this->view->partInfo["f_show_price_sz"]!=1 || $this->view->partInfo["f_show_price_hk"]!=1){
    		$this->view->error = 1;
    	}
    }
}

