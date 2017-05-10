<?php
class Icwebadmin_Service_CountService
{
	public function __construct()
	{
		
	}
	/*
	 * 获取产品分类
	*/
	public function prodCategoryNumber()
	{
		$prodService = new Default_Service_ProductService();
    	$prodCategory = $prodService->getProdCategory();
    	$second = $third = 0;
    	foreach($prodCategory['second'] as $sarray){
    		$second +=count($sarray);
    	}
    	foreach($prodCategory['third'] as $tarray){
    		$third +=count($tarray);
    	}
		return array(count($prodCategory['first']),$second,$third);
	}
	/**
	 * 获取产品统计
	 */
	public function prodNumber()
	{
		$this->_prodService = new Icwebadmin_Service_ProductService();
		$onnum = $this->_prodService->getOnNum('');
		$offnum = $this->_prodService->getOffNum('');
		$nstocknum = $this->_prodService->getNstockNum('');
		$hstocknum = $this->_prodService->getHstockNum('');
		$atsnum    = $this->_prodService->getAtsNum('');
		$bppnum    = $this->_prodService->getBppNum('');
		$sellnum    = $this->_prodService->getSellNum('');
		$pricenum    = $this->_prodService->getPriceNum('');
		$imgnum    = $this->_prodService->getImgNum('');
		$datasheetnum    = $this->_prodService->getDatasheetNum('');
		$notesnum   = $this->_prodService->getNotesNum('');
		return array('onnum'=>$onnum,
				'offnum'=>$offnum,
				'nstocknum'=>$nstocknum,
				'hstocknum'=>$hstocknum,
				'atsnum'=>$atsnum,
				'bppnum'=>$bppnum,
				'sellnum'=>$sellnum,
				'pricenum'=>$pricenum,
				'imgnum'=>$imgnum,
				'datasheetnum'=>$datasheetnum,
				'notesnum'=>$notesnum);
	}
	/**
	 * 获取品牌等统计
	 */
	public function commonNumber()
	{
		$bModer = new Default_Model_DbTable_Model("brand");
		$bandnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `brand` WHERE status='1'");
		$solutionnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `solution` WHERE status='1'");
		$seminarnnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `seminar` WHERE status='1'");
		//搜索产品数
		$searnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `search_inquiry`");
		//优惠券数
		$couponnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `coupon`");
		//BOM数
		$bomnumber = $bModer->QueryItem("SELECT COUNT(id) FROM `bom`");
		return array('bandnumber'=>$bandnumber,
				'solutionnumber'=>$solutionnumber,
				'seminarnnumber'=>$seminarnnumber,
				'searnumber'=>$searnumber,
				'couponnumber'=>$couponnumber,
				'bomnumber'=>$bomnumber);
	}
	/**
	 * 获取用户统计
	 */
	public function userNumber()
	{
		$uModer = new Default_Model_DbTable_Model("user");
		return $uModer->QueryItem("SELECT COUNT(uid) FROM `user` WHERE backstage='0' AND emailapprove=1 AND enable=1");
	}
	/**
	 * 询价统计
	 */
    public function inquiryNumber()
	{
		$this->_inqservice = new Icwebadmin_Service_InquiryService();
		$xswhere = " AND iq.back_inquiry=0 ";
		$waitnum     = $this->_inqservice->getWaitNum($xswhere);
    	$alreadynum  = $this->_inqservice->getAlreadyNum($xswhere);
    	$ordernum    = $this->_inqservice->getOrderNum($xswhere);
    	$nonum       = $this->_inqservice->getNoNum($xswhere);
		return array('waitnum'=>$waitnum,
				'alreadnum'=>$alreadynum,
				'ordernum'=>$ordernum,
				'nonum'=>$nonum);
	}
	/**
	 * 订单统计
	 * @return multitype:unknown Ambigous <boolean, string, mixed>
	 */
    public function orderNumber()
	{
		$orderModer = new Default_Model_DbTable_Model("sales_order");
		$onlinetotal = $orderModer->QueryItem("SELECT COUNT(id) FROM `sales_order`");
		$inquirytotal = $orderModer->QueryItem("SELECT COUNT(id) FROM `inq_sales_order` WHERE back_order=0");
		//营运统计
		$rateModel = new Default_Model_DbTable_Rate();
		$arr = $rateModel->getAllByWhere("status='1'");
		$usdtormb = $usdtohkd = $hkdtormb = 1;
		foreach($arr as $k=>$ratearr){
			if($ratearr['currency']=='USD' && $ratearr['to_currency']=='RMB') $usdtormb = $ratearr['rate_value'];
			if($ratearr['currency']=='USD' && $ratearr['to_currency']=='HKD') $usdtohkd = $ratearr['rate_value'];
			$hkdtormb = (1/$usdtohkd)*$usdtormb;
		}
		//在线订单
		$onlinearr = $orderModer->Query("SELECT total,total_back,deductions,currency FROM `sales_order` WHERE status IN('201','202','301','302') AND back_status IN('201','202')");
		$online = array('rmb'=>0,'usd'=>0,'hkd'=>0,'totalrmb'=>0);
		foreach($onlinearr as $oarr){
			if($oarr['currency']=='RMB') $online['rmb'] +=$oarr['total'];
			if($oarr['currency']=='USD') $online['usd'] +=$oarr['total'];
			if($oarr['currency']=='HKD') $online['hkd'] +=$oarr['total'];
		}
		$online['totalrmb'] = $online['rmb'] + $online['usd']*$usdtormb + $online['hkd']*$hkdtormb;
		//询价订单
		$inqorderModer = new Default_Model_DbTable_Model("inq_sales_order");
		$inquiryarr = $inqorderModer->Query("SELECT total,currency FROM `inq_sales_order` WHERE status IN('102','103','201','202','301','302') AND back_status IN('201','202') AND back_order=0");
		$inquiry = array('rmb'=>0,'usd'=>0,'hkd'=>0,'totalrmb'=>0);
		foreach($inquiryarr as $iarr){
			if($iarr['currency']=='RMB') $inquiry['rmb'] +=$iarr['total'];
			if($iarr['currency']=='USD') $inquiry['usd'] +=$iarr['total'];
			if($iarr['currency']=='HKD') $inquiry['hkd'] +=$iarr['total'];
		}
		$inquiry['totalrmb'] = $inquiry['rmb'] + $inquiry['usd']*$usdtormb + $inquiry['hkd']*$hkdtormb;
		return array('onlinetotal'=>$onlinetotal,
				'inquirytotal'=>$inquirytotal,
				'online'=>$online,
				'inquiry'=>$inquiry);
	}
	/**
	 * 订单统计back
	 * @return multitype:unknown Ambigous <boolean, string, mixed>
	 */
	public function orderNumberBack()
	{
		$sqlstr = " AND so.created <= 1364659200 AND up.staffid!='alina.shang'";
		//$sqlstr = " AND so.created > 1364659200";
		$orderModer = new Default_Model_DbTable_Model("sales_order");
		$onlinetotal = $orderModer->QueryItem("SELECT COUNT(so.id) FROM `sales_order` as so WHERE so.id!='' $sqlstr");
		$inquirytotal = $orderModer->QueryItem("SELECT COUNT(so.id) FROM `inq_sales_order` as so WHERE so.back_order=0 $sqlstr");
		//营运统计
		$rateModel = new Default_Model_DbTable_Rate();
		$arr = $rateModel->getAllByWhere("status='1'");
		$usdtormb = $usdtohkd = $hkdtormb = 1;
		foreach($arr as $k=>$ratearr){
			if($ratearr['currency']=='USD' && $ratearr['to_currency']=='RMB') $usdtormb = $ratearr['rate_value'];
			if($ratearr['currency']=='USD' && $ratearr['to_currency']=='HKD') $usdtohkd = $ratearr['rate_value'];
			$hkdtormb = (1/$usdtohkd)*$usdtormb;
		}
		//在线订单
		$onlinearr = $orderModer->Query("SELECT so.total,so.total_back,so.deductions,so.currency 
				FROM `sales_order` as so
				LEFT JOIN user_profile as up ON so.uid=up.uid
				WHERE so.status IN('201','202','301','302') AND so.back_status IN('201','202') $sqlstr");
		$online = array('rmb'=>0,'usd'=>0,'hkd'=>0,'totalrmb'=>0);
		foreach($onlinearr as $oarr){
			if($oarr['currency']=='RMB') $online['rmb'] +=$oarr['total'];
			if($oarr['currency']=='USD') $online['usd'] +=$oarr['total'];
			if($oarr['currency']=='HKD') $online['hkd'] +=$oarr['total'];
		}
		$online['totalrmb'] = $online['rmb'] + $online['usd']*$usdtormb + $online['hkd']*$hkdtormb;
		//询价订单
		$inqorderModer = new Default_Model_DbTable_Model("inq_sales_order");
		$inquiryarr = $inqorderModer->Query("SELECT so.total,so.currency 
				FROM `inq_sales_order` as so
				LEFT JOIN user_profile as up ON so.uid=up.uid
				WHERE so.status IN('102','103','201','202','301','302') 
				AND so.back_status IN('201','202') AND back_order=0 $sqlstr");
		$inquiry = array('rmb'=>0,'usd'=>0,'hkd'=>0,'totalrmb'=>0);
		foreach($inquiryarr as $iarr){
			if($iarr['currency']=='RMB') $inquiry['rmb'] +=$iarr['total'];
			if($iarr['currency']=='USD') $inquiry['usd'] +=$iarr['total'];
			if($iarr['currency']=='HKD') $inquiry['hkd'] +=$iarr['total'];
		}
		$inquiry['totalrmb'] = $inquiry['rmb'] + $inquiry['usd']*$usdtormb + $inquiry['hkd']*$hkdtormb;
		return array('onlinetotal'=>$onlinetotal,
				'inquirytotal'=>$inquirytotal,
				'online'=>$online,
				'inquiry'=>$inquiry);
	}
}