<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
class JifenController extends Zend_Controller_Action {
	private $_jifenService;
	private $_defaultlogService;
	private $_scoreService;
	private $_giftservice;
	public function init() {
		/*
		 * Initialize action controller here
		 */
		//菜单选择
		$_SESSION['menu'] = 'jifen';
		
		//获取购物车寄存
		$cartService = new Default_Service_CartService();
		$cartService->getCartDeposit();
		
		$this->view->fun =$this->fun =new MyFun();
		
		//产品目录
		$prodService = new Default_Service_ProductService();
		$prodCategory = $prodService->getProdCategory();
		$this->view->first = $prodCategory['first'];
		$this->view->second = $prodCategory['second'];
		$this->view->third  = $prodCategory['third'];
		//目录推荐品牌
		$this->view->categorybarnd = $prodService->getCategoryBrand();
		$this->_jifenService = new Default_Service_JifenService();
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		$this->_giftservice = new Default_Service_GiftService();
		
		$this->_scoreService = new Default_Service_ScoreService();
		//获取是否已经签订
		$this->view->jifenview = $this->_scoreService->checkgetscore('jifenview',$_SESSION['userInfo']['uidSession']);
		//用户积分
		$this->view->myscore = $this->_jifenService->getSurplusScore();
		//自定义标题、关键字和描述
		$layout = $this->_helper->layout();
		$this->viewobj = $layout->getView();;
		$this->viewobj->headTitle('积分商城 - 盛芯电子，行业领先的一站式元器件电子商务平台。','SET');
		$this->viewobj->headMeta()->setName('description','盛芯电子是行业领先的一站式元器件电商平台，拥有海量的产品数据、设计方案、在线技术研讨会、产品资讯等，能满足客户从产品设计到批量生产的全程需求。盛芯电子提供便捷的在线订购、批量采购询价及难寻器件现货供应、样片申请，技术支持等服务，为你带来全新采购体验！盛芯电子传承中电器材30年品牌价值，100%正品保证，是您最佳的元器件在线采购平台。');
		$this->viewobj->headMeta()->setName('keywords','积分商城,积分活动,礼品兑换,积分换礼,元器件供应商,IC元器件电商平台,元器件网上商城,元器件交易网,电子商城,IC芯片网上采购,网上买元器件,IC,元器件,芯片,小批量采购,元器件询价,元器件在线购买,IC样片申请,免费样片,难寻器件,开发板,技术方案,在线研讨会,产品资讯,盛芯电子,飞思卡尔,Freescale代理,IDT代理,Intersil代理,AOS代理,Qualcomm代理,高通代理,CSR代理,EPSON代理,Microchip代理');
	}
	public function indexAction() {
		//自定义头部
		$_SESSION['no_logbar_event'] = 0;
		$_SESSION['no_menu_event']   = 1;
		$_SESSION['change_logo'] = '<div id="logo" ><a href="/" hidefocus="true"><img src="/images/default/logono.jpg"  alt="盛芯电子"></a></div>
        <div class="jflogo"><a href="/jifen"><img src="/css/jifen/img/ji_logo.jpg" class="png_ie6"/></a></div>';
		
		$_SESSION['jifenmenu']='index';
		//获取我的积分
		//礼品记录
		$this->view->gift1 = $this->_jifenService->getAllGift(0,6," AND gf.status=1 AND (gf.stock - gf.stock_cover)>0 AND gf.score<20000");
		$this->view->gift2 = $this->_jifenService->getAllGift(0,6," AND gf.status=1 AND (gf.stock - gf.stock_cover)>0 AND gf.score>=20000 AND  gf.score<50000");
		$this->view->gift3 = $this->_jifenService->getAllGift(0,6," AND gf.status=1 AND (gf.stock - gf.stock_cover)>0 AND gf.score>=50000");
		//积分排行榜
		$this->view->jifenlist = $this->_jifenService->getScorelist(0,10);
		//兑换排行榜
		//$this->view->giftlist = $this->_jifenService->getExchanglist(5);
		//最新兑换记录
		$this->view->exchangelist = $this->_jifenService->newexchange(0,10);
		//滚动图片
		//$this->view->topimageArr = $this->_jifenService->getTopimage();
	}
	/**
	 * 礼品详细页
	 */
	public function viewAction(){
		$_SESSION['jifenmenu']='view';
		if($this->_getParam('jifen')){
			$item = explode("-",$this->_getParam('jifen'));
			$id   = (int)$item[1];
			$this->view->gift = $this->_jifenService->getGiftById($id);
			if(!$this->view->gift) $this->_redirect('/jifen');
			//获取兑换过此礼品的记录
			$this->view->exchangelist = $this->_jifenService->newexchange(0,5," AND ge.giftid='{$id}'");
			//用户可以兑换的礼品
			$this->view->canexchange = $this->_jifenService->getAllGift(0,5," AND gf.score <='{$this->view->myscore}'");
		}else{
			$this->_redirect('/jifen');
		}
	}
	/**
	 * 礼品详细页
	 */
	public function listAction(){
		//自定义头部
		$_SESSION['no_logbar_event'] = 0;
		$_SESSION['no_menu_event']   = 1;
		$_SESSION['change_logo'] = '<div id="logo" ><a href="/" hidefocus="true"><img src="/images/default/logono.jpg"  alt="盛芯电子"></a></div>
        <div class="jflogo"><a href="/jifen"><img src="/css/jifen/img/ji_logo.jpg" class="png_ie6"/></a></div>';
		
		$this->viewobj->headTitle('积分礼品 - 积分商城 - 盛芯电子，行业领先的一站式元器件电子商务平台。','SET');
		$_SESSION['jifenmenu']='list';
		$typestr = $orderby = "";
		//分值区间
		$this->view->in = $_GET['in'];
		if($this->view->in){
			$inarr = explode('-',$this->view->in);
			if($inarr[0]) $typestr .= " AND gf.score >='".$inarr[0]."'";
			if($inarr[1]) $typestr .= " AND gf.score <='".$inarr[1]."'";
		}
		//全部
		$this->view->total = $total   =  $this->_jifenService->getNum($typestr);
		//排序
		$this->view->sortstr_1 = "2_0_0";
		$this->view->sortstr_2 = "0_2_0";
		$this->view->sortstr_3 = "0_0_2";
		if(isset($_GET['sort']) && $_GET['sort']){
			$sort = explode('_',$_GET['sort']);
			if($sort[0]==1){
				$orderby = 'ORDER BY gf.created ASC';
				$this->view->sortstr_1 = '2_0_0';
			}elseif($sort[1]==1){
				$orderby = 'ORDER BY gf.score ASC';
				$this->view->sortstr_2 = '0_2_0';
			}elseif($sort[2]==1){
				$orderby = 'ORDER BY (gf.stock-gf.stock_cover) ASC';
				$this->view->sortstr_3 = '0_0_2';
			}elseif($sort[0]==2){
				$orderby = 'ORDER BY gf.created DESC';
				$this->view->sortstr_1 = '1_0_0';
			}elseif($sort[1]==2){
				$orderby = 'ORDER BY gf.score DESC';
				$this->view->sortstr_2 = '0_1_0';
			}elseif($sort[2]==2){
				$orderby = 'ORDER BY (gf.stock-gf.stock_cover) DESC';
				$this->view->sortstr_3 = '0_0_1';
			}
		}
		//分页
		$perpage = 15;
		$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
		$offset  = $page_ob->offset();
		$this->view->page_bar= $page_ob->show(9);
		$this->view->allgift = $this->_jifenService->getAllGift($offset,$perpage,$typestr,$orderby);
	}
	/**
	 * 积分活动
	 */
	public function eventAction(){
		//自定义头部
		$_SESSION['no_logbar_event'] = 0;
		$_SESSION['no_menu_event']   = 1;
		$_SESSION['change_logo'] = '<div id="logo" ><a href="/" hidefocus="true"><img src="/images/default/logono.jpg"  alt="盛芯电子"></a></div>
        <div class="jflogo"><a href="/jifen"><img src="/css/jifen/img/ji_logo.jpg" class="png_ie6"/></a></div>';
		
		$this->viewobj->headTitle('积分活动 - 积分商城 - 盛芯电子，行业领先的一站式元器件电子商务平台。','SET');
		$_SESSION['jifenmenu']='event';
		//获取当前奖品
		$this->_prizeModel = new Default_Model_DbTable_Model("prize");
		$allprize = $this->_prizeModel->getAllByWhere("status=1","id DESC");
		$this->view->prize = $allprize[0];
		//参加人数
		$this->view->joinnum = $this->_giftservice->getjoinNum();
		$this->view->joinnum = str_pad($this->view->joinnum ,5,"0",STR_PAD_LEFT);
		//获奖名单
		$this->view->winners = $this->_giftservice->getWinners('AND temp5>0 AND temp5<4');
		$this->view->winners2 = $this->_giftservice->getWinners('AND temp5>3','ORDER BY sl.`id` DESC');
		//获取活跃度
		if(isset($_SESSION['userInfo']['unameSession'])){
			//活动结束时间
			$endtime = '1390406399';
			$slogModel   = new Default_Model_DbTable_Model('score_log');
			$userService = new Default_Service_UserService();
			$alllog = $slogModel->Query("SELECT distinct(sl.uid),sl.temp5 FROM `score_log` as sl WHERE sl.`temp2` = 'invite' AND sl.temp5!='' AND sl.created < '{$endtime}'");
			$temp = $topnum = $topnum2 = $top = array();
			foreach($alllog as $v){
				$temp[$v['temp5']][] = $v['uid'];
			}
			$jifenviewall = $viewpageall = $shareall = $clickall = $inviteall = 0;
			$this->view->jifenviewtop = $this->view->viewpagetop = $this->view->sharetop = $this->view->clicktop = $this->view->invitetop = 0;
			$this->view->alltop =$this->view->jlangtop = $this->view->slangtop=0;
			foreach($temp as $uid=>$ivarr){
				$top[$uid]['userinfo'] = $userService->getUserProfileByUid($uid);
				$t=array();
				foreach($ivarr as $ivuid){
					$t[] = $userService->getUserProfileByUid($ivuid);
				}
				$top[$uid]['invite'] = $t;
				$inviteall += count($t);
				if(count($t) > $this->view->invitetop)
					$this->view->invitetop = count($t);
				$email = explode('@',$top[$uid]['userinfo']['email']);
				
				//签订数量
				$top[$uid]['jifenviewnum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
						WHERE uid = '{$uid}' AND temp2='jifenview' AND temp3>0 AND created < '{$endtime}'");
						$jifenviewall +=$top[$uid]['jifenviewnum'];
								if($top[$uid]['jifenviewnum'] > $this->view->jifenviewtop)
									$this->view->jifenviewtop = $top[$uid]['jifenviewnum'];
    		    $top[$uid]['viewpagenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
			    				WHERE uid = '{$uid}' AND temp2='viewpage' AND temp3>0 AND created < '{$endtime}'");
			    				$viewpageall +=$top[$uid]['viewpagenum'];
			    						if($top[$uid]['viewpagenum'] > $this->view->viewpagetop)
			    							$this->view->viewpagetop = $top[$uid]['viewpagenum'];
			    									$top[$uid]['sharenum'] = $slogModel->QueryItem("SELECT count(id) FROM `score_log`
			    									WHERE uid = '{$uid}' AND temp2='share' AND temp3>0 AND created < '{$endtime}'");
			    									$shareall +=$top[$uid]['sharenum'];
			    									if($top[$uid]['sharenum'] > $this->view->sharetop)
			    									$this->view->sharetop = $top[$uid]['sharenum'];
				//$top[$uid]['loginnum'] = $slogModel->QueryItem("SELECT count(id) FROM `default_log`
				//WHERE uid = '{$uid}' AND (action='login' OR action='ajaxlogin') AND temp1 IS NULL");
				$top[$uid]['clicknum'] = $slogModel->QueryItem("SELECT count(id) FROM `default_view_log`
				WHERE uid = '{$uid}' AND created < '{$endtime}'");
				$clickall +=$top[$uid]['clicknum'];
				if($top[$uid]['clicknum'] > $this->view->clicktop)
					$this->view->clicktop = $top[$uid]['clicknum'];
				
				$topnum[$uid]  = count($ivarr);
				
			
				//最长连续签到
				$jvarr = $slogModel->Query("SELECT created FROM `score_log`
						WHERE uid = '{$uid}' AND temp2='jifenview' AND temp3>0 AND created < '{$endtime}'");
						$jlang = count($jvarr)>0?1:0;
						foreach($jvarr as $k=>$arr){
						$jvarr[$k] = date("ymd",$arr['created']);
						}
						foreach($jvarr as $k=>$arr){
						if((int)$jvarr[$k]==((int)$jvarr[$k+1]-1)) {$jlang ++;}
						}
						$top[$uid]['jlang'] = $jlang;
						if($jlang > $this->view->jlangtop)
							$this->view->jlangtop = $jlang;
			
							//最长分享
							$svarr = $slogModel->Query("SELECT created FROM `score_log`
							WHERE uid = '{$uid}' AND temp2='share' AND temp3>0 AND created < '{$endtime}'");
							$slang = count($svarr)>0?1:0;
							foreach($svarr as $k=>$arr){
							$svarr[$k] = date("ymd",$arr['created']);
						}
							foreach($svarr as $k=>$arr){
							if((int)$svarr[$k]==((int)$svarr[$k+1]-1)) {$slang ++;}
						}
							$top[$uid]['slang'] = $slang;
							if($slang > $this->view->slangtop)
								$this->view->slangtop = $slang;
			
						}
						foreach($top as $uid=>$t){
						$top[$uid]['jp'] = $top[$uid]['jifenviewnum']/$this->view->viewpagetop*100;
						$top[$uid]['jlangp'] = $top[$uid]['jlang']/$this->view->jlangtop*100;
						$top[$uid]['vp'] = $top[$uid]['viewpagenum']/$this->view->viewpagetop*100;
						$top[$uid]['sp'] = $top[$uid]['sharenum']/$this->view->sharetop*100;
						$top[$uid]['slangp'] = $top[$uid]['slang']/$this->view->slangtop*100;
						$top[$uid]['cp'] = $top[$uid]['clicknum']/$this->view->clicktop*100;
						$top[$uid]['ip'] = count($top[$uid]['invite'])/$this->view->invitetop*100;
								$top[$uid]['alltoptmp'] = ($top[$uid]['jp']+$top[$uid]['jlangp']+$top[$uid]['vp']+$top[$uid]['sp']+$top[$uid]['slangp']+$top[$uid]['cp']+$top[$uid]['ip'])/7;
								if($top[$uid]['alltoptmp'] > $this->view->alltop)
								$this->view->alltop = $top[$uid]['alltoptmp'];
						}
								$this->view->jifenviewall = $jifenviewall;
										$this->view->viewpageall = $viewpageall;
										$this->view->shareall= $shareall;
										$this->view->clickall = $clickall;
										$this->view->inviteall = $inviteall;
										$this->view->topnum = $topnum;
										$this->view->top = $top;
		}
	}
	/**
	 * 积分规则
	 */
    public function ruleAction(){
    	//自定义头部
    	$_SESSION['no_logbar_event'] = 0;
    	$_SESSION['no_menu_event']   = 1;
    	$_SESSION['change_logo'] = '<div id="logo" ><a href="/" hidefocus="true"><img src="/images/default/logono.jpg"  alt="盛芯电子"></a></div>
        <div class="jflogo"><a href="/jifen"><img src="/css/jifen/img/ji_logo.jpg" class="png_ie6"/></a></div>';
    	
    	$this->viewobj->headTitle('积分规则 - 积分商城 - 盛芯电子，行业领先的一站式元器件电子商务平台。','SET');
		$_SESSION['jifenmenu']='rule';
	}
	
	/*
	 * 兑换处理
	 */
	public function exchangeAction() {
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$this->_helper->layout->disableLayout();
		//处理提交
		if($this->getRequest()->isPost()){
			$formData  = $this->getRequest()->getPost();
			$giftid = (int)$formData['giftid'];
			$rearr = $this->_jifenService->checkexchang($giftid);
			//礼品信息
			$gift = $this->_jifenService->getGiftById($giftid);
			if(empty($gift)){
				$rearr['error']++;
				$rearr['mess'] = '礼品已经下架';
			}
			//扣除积分
			$su_score = (int)$gift['score'];
			if($su_score<=0){
				$rearr['error']++;
				$rearr['mess'] = '礼品兑换积分不能为0';
			}elseif($this->view->myscore < $su_score){
				$rearr['error']++;
				$rearr['mess'] = '您的积分不足'.$this->view->myscore;
			}
			if($gift['type']==1){
			  //收货地址
			  $addressModel = new Default_Model_DbTable_Address();
			  $addre = $addressModel->getRowByWhere("id='".$formData['addressid']."'");
			  if(empty($addre)){
				  $rearr['error']++;
				  $rearr['mess'] = '收货地址不存在，请重新选择。';
			  }
			}
			if($rearr['error']){
				//记录日志
				$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$giftid,'temp4'=>'兑换礼品失败','description'=>$rearr['mess']));
				echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>$rearr['mess']));
				exit;
			}else{
				if($gift['type']==1){
				   //添加订单收货地址
				   $soaddModel = new Default_Model_DbTable_OrderAddress();
				   $soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
						'name'=>$addre['name'],
						'companyname'=>$addre['companyname'],
						'province'=>$addre['province'],
						'city'=>$addre['city'],
						'area'=>$addre['area'],
						'address'=>$addre['address'],
						'mobile'=>$addre['mobile'],
						'tel'=>$addre['tel'],
						'zipcode'=>$addre['zipcode'],
						'created'=>time());
				   $addressid = $soaddModel->addData($soadd_data);
				}
				$exchangeModel = new Default_Model_DbTable_Model('gift_exchange');
				$newid = $exchangeModel->addData(array('uid'=>$_SESSION['userInfo']['uidSession'],
						'giftid'=>$giftid,
						'gifttype'=>$gift['type'],
						'giftname'=>$gift['name'],
						'number'=>1,
						'addressid'=>$addressid,
						'score'=>$su_score,
						'status'=>101,
						'remark'=>$formData['remark'],
						'created'=>time()));
				if($newid){
					//邮件通知
					$this->_giftservice->mailalert();
					//更新扣除库存
					$this->_giftModer = new Default_Model_DbTable_Model('gift');
					$this->_giftModer->updateBySql("UPDATE gift SET stock_cover =stock_cover + 1  WHERE id='{$giftid}'");
					//扣除积分
					$dservice = new Default_Service_ScoreService();
					$dservice->destore($su_score,$giftid,'兑换礼品扣除积分');
					//记录日志
					$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$giftid,'temp4'=>'兑换礼品成功','description'=>$newid));
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'兑换成功'));
					exit;
				}else{
					//记录日志
					$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$giftid,'temp4'=>'兑换礼品失败','description'=>'添加到数据库失败'));
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'兑换失败，系统错误'));
					exit;	
				}
			}
		}
		$giftid = (int)$_GET['id'];
		//礼品信息
		$this->view->gift = $this->_jifenService->getGiftById($giftid);
		$rearr = $this->_jifenService->checkexchang($giftid);
		$this->view->error = $rearr['error'];
		$this->view->mess = $rearr['mess'];
		if(!$this->view->error){
			//实物需要收货地址
			if($this->view->gift['type']==1){
			//收货地址	
			$addressService = new Default_Service_AddressService();
		    $this->view->addressArr = $addressService->getAddress();
			}
		}
	}
	/**
	 * 取消兑换
	 */
	public function cancelAction(){
		
		$this->_helper->layout->disableLayout();
		//处理提交
		if($this->getRequest()->isPost()){
			$formData  = $this->getRequest()->getPost();
			$id = (int)$formData['id'];
			$remark = trim($formData['remark']);
			$error = 0;$message='';
			$giftexchange = $this->_giftservice->getGiftExchangeByid($id);
			if(!$id){
				$message ='ID不存在。';
				$error++;
			}
			if(!$giftexchange){
				$message ='兑换记录不存在';
				$error++;
			}
			if($giftexchange['status']!=101){
				$message ='兑换记录已经处理';
				$error++;
			}
			if($error){
				$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'取消兑换失败','description'=>$message));
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				$model = new Icwebadmin_Model_DbTable_Model("gift_exchange");
				$re = $model->update(array('status'=>401,'remark'=>$remark,'modified'=>time()),"id = {$id}");
				
				//恢复库存
				$res = $this->_giftservice->resstockcover($giftexchange['giftid'],$giftexchange['number']);
				if($res){
					$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$giftexchange['giftid'],'temp4'=>'取消兑换，恢复礼品库存成功','description'=>$giftexchange['number']));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$giftexchange['giftid'],'temp4'=>'取消兑换，恢复礼品库存失败','description'=>$giftexchange['number']));
				}
				//恢复积分
				$dservice = new Default_Service_ScoreService();
				$dservice->restore($giftexchange['score'],$id,'用户取消兑换恢复积分');
				if($re){
					$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'取消兑换成功'));
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'取消兑换成功，并成功恢复消费积分：'.$giftexchange['score']));
					exit;
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'取消兑换失败','description'=>'更新数据库失败'));
					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'取消失败'));
					exit;
				}
			}
		}
		$this->view->giftexchange = $this->_giftservice->getGiftExchangeByid($_GET['id']);
	}
	/**
	 * 参加抽奖
	 */
	public function joinprizeAction(){
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		//处理提交
		if($this->getRequest()->isPost()){
			$formData  = $this->getRequest()->getPost();
			$id = (int)$formData['id'];
			$this->_prizeModel = new Default_Model_DbTable_Model("prize");
			$prize = $this->_prizeModel->getRowByWhere("id='{$id}'");
			$error = 0;$message='';
			if(!$prize['status']){
				$message ='此活动已经结束。';
				$error++;
			}
			if($prize['start_time']>time()){
				$message ='此活动还未开始。';
				$error++;
			}
			if(time()>$prize['end_time']){
				$message ='此活动已经结束。';
				$error++;
			}
			if($prize['score']>$this->view->myscore){
				$message ='您的积分不足。';
				$error++;
			}
			if($error){
				$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'参加抽奖失败','description'=>$message));
				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
				exit;
			}else{
				//记录参加抽奖
				$re = $this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'参加抽奖成功'));
				//记录参加人数
				$this->_prizeModel->updateBySql("UPDATE prize SET join_number =join_number + 1 WHERE id='$id'");
				//扣除积分
				$dservice = new Default_Service_ScoreService();
				$dservice->destore($prize['score'],$id,'用户参加抽奖扣除积分');
				if($re){
					echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'您已成功参加本次抽奖，抽奖获奖名单将会在活动结束后公布。'));
					exit;
				}else{
					echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'参加抽奖失败，系统繁忙'));
					exit;
				}	
			
			}
		}
	}
	/**
	 * 抽奖页面
	 */
	public function lotteryAction(){
		$_SESSION['jifenmenu']='lottery';
		//自定义头部
		$_SESSION['no_logbar_event'] = 0;
		$_SESSION['no_menu_event']   = 1;
		$_SESSION['change_logo'] = '<div id="logo" ><a href="/" hidefocus="true"><img src="/images/default/logono.jpg"  alt="盛芯电子"></a></div>
        <div class="jflogo"><a href="/jifen"><img src="/css/jifen/img/ji_logo.jpg" class="png_ie6"/></a></div>';
		//获取当前奖品
		$this->_prizeModel = new Default_Model_DbTable_Model("prize");
		$allprize = $this->_prizeModel->getAllByWhere("status=1","id DESC");
		$this->view->prize = $allprize[0];
		//参加人数
		$this->view->joinnum = $this->_giftservice->getjoinNum();
		$this->view->joinnum = str_pad($this->view->joinnum ,5,"0",STR_PAD_LEFT);
		//获奖名单
		$this->view->winners = $this->_giftservice->getWinners('AND temp5>0 AND temp5<4');
		$this->view->winners2 = $this->_giftservice->getWinners('AND temp5>3','ORDER BY sl.`id` DESC');
	}
	/**
	 * 积分抽奖方法：
	 *      确保对每个人获取奖品的概率是一样的
	 *      如果某件奖品没了，应该讲概率修改为0
	 *      考虑到高并发，在检测到用户中奖后，应该检查一下奖品是否存在，没了就直接返回没中奖或者次一级奖品
	 *      最后才将中奖结果返回
	 */
	public function jifenlotteryAction(){
		/*if(time()>'1390406399'){
			echo Zend_Json_Encoder::encode(array("code"=>500,"message"=>'活动已经结束，感谢参与！'));
		exit;
		}*/
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$dservice = new Default_Service_ScoreService();
		//一天只允许抽20次
		$joinnum = $this->_giftservice->getjoinNum(" AND uid='".$_SESSION['userInfo']['uidSession']."' AND (created BETWEEN '".(strtotime(date("Y-m-d 00:00:00")))."' AND '".(strtotime(date("Y-m-d 23:59:59")))."')");
		if($joinnum>=400){
			echo Zend_Json_Encoder::encode(array("code"=>101,"mess2"=>'每天抽奖次数最多400，请明天再来。'));
			exit;
		}
		//成功邀请5位，每天有一次机会
		$slogModel   = new Default_Model_DbTable_Model('score_log');
		$alllog = $slogModel->Query("SELECT sl.uid FROM `score_log` as sl WHERE sl.`temp2` = 'invite' AND sl.temp5='".$_SESSION['userInfo']['uidSession']."' ");
		if(count($alllog)>=5){
			$todytime = strtotime(date("Y-m-d 23:59:59")) - time();
			$re = $this->_scoreService->cachefactory($todytime, "ivmore5".$_SESSION['userInfo']['uidSession']);
			if(!$re){
				$dservice->restore(10,'ivmore5','邀请成功5位好友，每天免费抽奖一次');
			}
		}
	
		// 概率比例
		/* 接下来我们通过PHP配置奖项。 */
		$data =  $this->_giftservice->getPrizArray('jifen');
	
		foreach ($data as $key => $val) {
			$probability[$key] = $val["prob"];
		}
		//抽奖
		//print_r($data);exit;
		if($this->view->myscore<10){
			echo Zend_Json_Encoder::encode(array("code"=>100,"prizemessage"=>'',"mess2"=>'您的积分不足'));
			exit;
		}
		$n = $this->get_rand_new($probability);
		$logid = 0 ;
		//已经中奖
		$lotterylog = $slogModel->QueryItem("SELECT sl.uid FROM `score_log` as sl WHERE sl.action='lottery' AND sl.`temp5` IN ('1','2','3') AND sl.uid='".$_SESSION['userInfo']['uidSession']."' ");
		if($lotterylog && in_array($data[$n]["prize"],array('1','2','3'))) {
			$n = count($data)-1;
		}
		//内部不允许中奖
		$earr = explode("@",$_SESSION['userInfo']['emailSession']);
		if(($earr[1]=='ceacsz.com.cn' || $earr[1]=='CEACSZ.COM.CN') && in_array($data[$n]["prize"],array('1','2'))) {
			$n = count($data)-1;
		}
		//特定人
		if(in_array($_SESSION['userInfo']['unameSession'],array('lesley.liu','linlin','ytmfnckgu','double'))) {
			$n = count($data)-1;
		}
		if($data[$n]["prize"]!=7){
			//每天一次
			//$scoreService = new Default_Service_ScoreService();
			//$nocanflee = $scoreService->cachefactory(3600*24,'fleelottery'.$_SESSION['userInfo']['uidSession']);
			//if($nocanflee){}
			//扣除积分
			$logid = $dservice->destore(10,$data[$n]["prize"],$data[$n]["prizemessage"]);
			//如果抽中积分，添加
			if($data[$n]["prize"]==4){
				$logid = $dservice->restore(100,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
			if($data[$n]["prize"]==5){
				$logid = $dservice->restore(10,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
			if($data[$n]["prize"]==6){
				$logid = $dservice->restore(5,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
		}else{
			$logid = $dservice->destore(0,$data[$n]["prize"],$data[$n]["prizemessage"]);
		}
		//记录中奖记录
		if($data[$n]["prize"]>0){
			$scoreservice = new Default_Service_ScoreService();
			$scoreservice->addLog(array('log_id'=>'LO','temp2'=>'jifen_winners','temp3'=>$data[$n]["prizeid"],'temp4'=>$data[$n]["mess2"],'temp5'=>$data[$n]["prize"],'description'=>$data[$n]["prizemessage"]));
		}
		//记录中奖记录数据
		$this->_giftservice->updatePrizCover($data[$n]["prizeid"]);
		echo Zend_Json_Encoder::encode(array("code"=>$data[$n]["prize"],"logid"=>$logid,"prizemessage"=>$data[$n]["prizemessage"],"mess2"=>$data[$n]["mess2"]));
		exit;
	}
   /**
	* 活动抽奖方法：
    *      确保对每个人获取奖品的概率是一样的
	*      如果某件奖品没了，应该讲概率修改为0
	*      考虑到高并发，在检测到用户中奖后，应该检查一下奖品是否存在，没了就直接返回没中奖或者次一级奖品
	*      最后才将中奖结果返回
	*/
	public function lotteryactionAction(){
		/*if(time()>'1390406399'){
		echo Zend_Json_Encoder::encode(array("code"=>500,"message"=>'活动已经结束，感谢参与！'));
		exit;
		}*/
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$dservice = new Default_Service_ScoreService();
		//一天只允许抽20次
		$joinnum = $this->_giftservice->getjoinNum(" AND uid='".$_SESSION['userInfo']['uidSession']."' AND (created BETWEEN '".(strtotime(date("Y-m-d 00:00:00")))."' AND '".(strtotime(date("Y-m-d 23:59:59")))."')");
		if($joinnum>=400){
			echo Zend_Json_Encoder::encode(array("code"=>101,"mess2"=>'每天抽奖次数最多400，请明天再来。'));
			exit;
		}
		//成功邀请5位，每天有一次机会
		$slogModel   = new Default_Model_DbTable_Model('score_log');
		$alllog = $slogModel->Query("SELECT sl.uid FROM `score_log` as sl WHERE sl.`temp2` = 'invite' AND sl.temp5='".$_SESSION['userInfo']['uidSession']."' ");
		if(count($alllog)>=5){
			$todytime = strtotime(date("Y-m-d 23:59:59")) - time();
			$re = $this->_scoreService->cachefactory($todytime, "ivmore5".$_SESSION['userInfo']['uidSession']);
			if(!$re){
				$dservice->restore(10,'ivmore5','邀请成功5位好友，每天免费抽奖一次');
			}
		}
		
		// 概率比例
		/* 接下来我们通过PHP配置奖项。 */
		$data =  $this->_giftservice->getPrizArray('jifen');
	
		foreach ($data as $key => $val) {
			$probability[$key] = $val["prob"];
		}
		//抽奖
		//print_r($data);exit;
		if($this->view->myscore<10){
			echo Zend_Json_Encoder::encode(array("code"=>100,"prizemessage"=>'',"mess2"=>'您的积分不足'));
			exit;
		}
		$n = $this->get_rand_new($probability);
		$logid = 0 ;
		//已经中奖
		$lotterylog = $slogModel->QueryItem("SELECT sl.uid FROM `score_log` as sl WHERE sl.action='lottery' AND sl.`temp5` IN ('1','2','3') AND sl.uid='".$_SESSION['userInfo']['uidSession']."' ");
		if($lotterylog && in_array($data[$n]["prize"],array('1','2','3'))) {
			$n = count($data)-1;
		}
		//内部不允许中奖
		$earr = explode("@",$_SESSION['userInfo']['emailSession']);
		if(($earr[1]=='ceacsz.com.cn' || $earr[1]=='CEACSZ.COM.CN') && in_array($data[$n]["prize"],array('1','2'))) {
			$n = count($data)-1;
		}
		//特定人
		if(in_array($_SESSION['userInfo']['unameSession'],array('lesley.liu','linlin','ytmfnckgu','double'))) {
			$n = count($data)-1;
		}
		if($data[$n]["prize"]!=7){
			//每天一次
			//$scoreService = new Default_Service_ScoreService();
			//$nocanflee = $scoreService->cachefactory(3600*24,'fleelottery'.$_SESSION['userInfo']['uidSession']);
			//if($nocanflee){}
			//扣除积分
			$logid = $dservice->destore(10,$data[$n]["prize"],$data[$n]["prizemessage"]);
			//如果抽中积分，添加
			if($data[$n]["prize"]==4){
				$logid = $dservice->restore(100,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
			if($data[$n]["prize"]==5){
				$logid = $dservice->restore(10,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
			if($data[$n]["prize"]==6){
				$logid = $dservice->restore(5,$data[$n]["prize"],$data[$n]["prizemessage"]);
			}
		}else{
			$logid = $dservice->destore(0,$data[$n]["prize"],$data[$n]["prizemessage"]);
		}
		//记录中奖记录
		if($data[$n]["prize"]>0){
			$scoreservice = new Default_Service_ScoreService();
			$scoreservice->addLog(array('log_id'=>'LO','temp2'=>'jifen_winners','temp3'=>$data[$n]["prizeid"],'temp4'=>$data[$n]["mess2"],'temp5'=>$data[$n]["prize"],'description'=>$data[$n]["prizemessage"]));
		}
		//记录中奖记录数据
		$this->_giftservice->updatePrizCover($data[$n]["prizeid"]);
		echo Zend_Json_Encoder::encode(array("code"=>$data[$n]["prize"],"logid"=>$logid,"prizemessage"=>$data[$n]["prizemessage"],"mess2"=>$data[$n]["mess2"]));
		exit;
	}
	
	
	//提示窗口
	public function lotteryresultAction(){
		$this->_helper->layout->disableLayout();
		$this->view->mess = $_GET['mess'];
		$this->view->mess2 = $_GET['mess2'];
		$this->view->type = $_GET['type'];
		$this->view->id = $_GET['id'];
		$this->view->rf = $_GET['rf'];
		$this->view->t = (int)$_GET['t'];
		$model = new Default_Model_DbTable_Model('solution');
		//随机信息
		$this->view->show = rand(1,4);
		$this->view->showarr = array();
		if($this->view->show==1){
			$this->view->showarr = $model->Query("SELECT id,title FROM solution WHERE status=1 ORDER BY created DESC LIMIT 0 , 4");
		}elseif($this->view->show==2){
			$this->view->showarr = $model->Query("SELECT id,title FROM seminar WHERE status=1 ORDER BY created DESC LIMIT 0 , 4");
		}elseif($this->view->show==3){
			$this->view->showarr = $model->Query("SELECT id,title FROM news WHERE status=1 ORDER BY created DESC LIMIT 0 , 4");
		}else{
		   $frontendOptions = array('lifeTime' => 3600*24,'automatic_serialization' => true);
		   $backendOptions = array('cache_dir' => CACHE_PATH);
		   //$cache 在先前的例子中已经初始化了
		   $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		   // 查看一个缓存是否存在:
		   $cache_key = 'samples_prod_cache';
		   if(!$re = $cache->load($cache_key)) {
			   $sqlstr ="SELECT pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,
			b.name as brandname,pc2.name as cname2,pc3.name as cname3
			FROM product as pro
		    LEFT JOIN brand as b ON b.id=pro.manufacturer
			LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
			LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
			WHERE pro.samples=1 AND pro.status = 1 AND b.status = 1 ORDER BY id DESC LIMIT 0 , 20";
			$re = $model->Query($sqlstr);
			   $cache->save($re,$cache_key);
		    }
			if($re){
			$rand_keys = array_rand($re, 2);
			$this->view->showarr[0] = $re[$rand_keys[0]];
			$this->view->showarr[1] = $re[$rand_keys[1]];
			}
		}
	}
	 /**
     * 概率算法
	 * @param array $probability
	 * @return integer|string
	 */
	private	function get_rand($probability) {
		// 概率数组的总概率精度
		$max = array_sum($probability);
		foreach ($probability as $key => $val) {
			mt_srand(mktime());
			$rand_number = mt_rand(1, $max);//从1到max中随机一个值
			if ($rand_number <= $val) {//如果这个值小于等于当前中奖项的概率，我们就认为已经中奖
				return $key;
			} else {
				$max -= $val;//否则max减去当前中奖项的概率，然后继续参与运算
			}
		}
	}
	private function get_rand_new($probability) {
		// 概率数组的总概率精度
		asort($probability);
		$maxtmp = $probability;
		$max = array_pop($maxtmp);
		$larr = array();
		$old = 1;
		foreach ($probability as $key => $val) {
			$larr[$key] = array('min'=>$old,'max'=>$val);
			$old = $val+1;
		}
		mt_srand(mktime()*1000000);
		$rand_number = mt_rand(1, $max);//从1到max中随机一个值
	
		foreach ($larr as $key => $valarr) {
			if ($rand_number >=$valarr['min']  && $rand_number <= $valarr['max']){
				return $key;
			}
		}
		return count($probability)-1;
	}
	private function get_rand_new_2($probability) {
		// 概率数组的总概率精度
		asort($probability);
		$maxtmp = $probability;
		$max = array_pop($maxtmp);
		$larr = array();
		$old = 1;
		foreach ($probability as $key => $val) {
			$larr[$key] = array('min'=>$old,'max'=>$val);
			$old = $val+1;
		}
		mt_srand(mktime()*1000000);
		$rand_number = mt_rand(1, $max);//从1到max中随机一个值
	
		foreach ($larr as $key => $valarr) {
			if ($rand_number >=$valarr['min']  && $rand_number <= $valarr['max']){
				return array($key,$rand_number);
			}
		}
	}
	/**
	 * 邀请弹出框
	 */
	public function inviteboxAction(){
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$this->_helper->layout->disableLayout();
		$this->view->emailstr = $_GET['emailstr'];
	}
	/**
	 * 提示框
	 */
	public function alertboxAction(){
		$this->_helper->layout->disableLayout();
	}
	/**
	 * 中实物奖填写资料
	 */
	public function infoAction(){
		$this->_helper->layout->disableLayout();
		$this->view->id = $_GET['id'];
		if($this->getRequest()->isPost()){
			$formData  = $this->getRequest()->getPost();
			$id = (int)$formData['id'];
			$name = $formData['name'];
			$tel  = $formData['tel'];
			$add  = $formData['add'];
			$str = "；".$name."；".$tel."；".$add;
			$scorelogModel = new Default_Model_DbTable_Model('score_log');
			$scorelogModel->updateBySql("UPDATE `icweb`.`score_log` SET `description` =  concat(description,'$str') WHERE `score_log`.`id` ={$id};");
			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'添加成功'));
			exit;
		}
	}
	/**
	 * 邀请记录
	 */
	public function inviterecordsAction(){
		$this->_helper->layout->disableLayout();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$slogModel   = new Default_Model_DbTable_Model('score_log');
		$userService = new Default_Service_UserService();
		//成功邀请记录
		$alllog = $slogModel->Query("SELECT distinct(sl.uid) FROM `score_log` as sl WHERE sl.`temp2` = 'invite' AND sl.temp5='".$_SESSION['userInfo']['uidSession']."' ");
		$ivarr = $alllogtmp = array();
		foreach($alllog as $arr){
			$ivarr[] = $userService->getUserProfileByUid($arr['uid']);
			$alllogtmp[] = $arr['uid'];
		}
		$this->view->ivarr = $ivarr;
		//发送邮件但还没注册
		$emaillog = $slogModel->Query("SELECT description FROM `default_log` WHERE uid='".$_SESSION['userInfo']['uidSession']."' AND `action`='sendinvite' ");
		$emailarr = array();
		foreach($emaillog as $arr){
			$tmp = explode('||',$arr['description']);
			$tmp2= explode(',',$tmp[1]);
			foreach($tmp2 as $e){
				if($e) $emailarr[$e] = $e;
			}
		}
		foreach($ivarr as $iv){
			if(in_array($iv['email'],$emailarr)) unset($emailarr[$iv['email']]);
		}
		$this->view->emailarr = $emailarr;
		//还没认证
		$verificationlog = $slogModel->Query("SELECT uid,created FROM `verification_code` WHERE `invitekey`='".$_SESSION['userInfo']['uidSession']."' AND type=0 ORDER BY id ASC");
		$okarr = $noarr = $vtmp = array();
		foreach($verificationlog as $arr){
			if(!in_array($arr['uid'],$alllogtmp)) $vtmp[$arr['uid']] = $arr['created'];
		}
		$time = time();
		foreach($vtmp as $uid=>$created){
			if($time > ($created+3600)) $noarr[] = $userService->getUserProfileByUid($uid);
			else $okarr[] = $userService->getUserProfileByUid($uid);
		}
		$this->view->noarr = $noarr;
		$this->view->okarr = $okarr;
	}
	/**
	 * 在发邀请邮件
	 */
	public function smagainAction(){
		$this->_helper->layout->disableLayout();
		$this->view->emailstr = $_GET['emailstr'];
	}
	/**
	 * 提醒
	 */
	public function ivagainAction(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$userService = new Default_Service_UserService();
		//登录检查
		$this->common = new MyCommon();
		$this->common->loginCheck();
		$uidstr = $_POST['uidstr'];
		if(empty($uidstr)) return;
		$uidarr = explode(";", $uidstr);
		foreach($uidarr as $uid){
			$uid = (int)$this->fun->decryptVerification($uid);
			$userinfo = $userService->getUserProfileByUid($uid);
			if($userinfo){
				//获取邀请码
				$verificationcode =new Default_Model_DbTable_VerificationCode();
				$invitekey = $verificationcode->QueryItem("SELECT invitekey FROM `verification_code` WHERE `uid` = '".$userinfo['uid']."' ORDER BY id DESC LIMIT 1");
				$mycommon = new MyCommon();
				$keyone  = md5($userinfo['uname'].date("l dS F Y h:i:s A"));
				$hashkey = $mycommon->encryptVerification($userinfo['uname'],$keyone );
					
				$verificationcode->addCode(array('uid'=>$userinfo['uid'],
						'uname'=>$userinfo['uname'],
						'invitekey'=>$invitekey,
						'code'=>$keyone,
						'ecode'=>$hashkey,
						'created'=>time()));
				//发送验证email
				if($invitekey) $hashkey .='&invitekey='.$invitekey;
				$emailreturn = $this->fun->sendverification($hashkey,$userinfo['email'],$userinfo['uname']);
				//邮件日志
				if($emailreturn ){
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp4'=>'重新发送验证邮件成功'));
				}else{
					$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp4'=>'重新发送验证邮件失败'));
				}
			}
		}
	}
	
}

