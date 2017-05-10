<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
require_once 'Iceaclib/common/tcpdf/mytcpdf.php';
class Icwebadmin_Service_InqOrderService
{
	private $_salesproductModel;
	private $_inqsalesorderModel;
	private $_emailService;
	private $_staffService;
	private $_dInqOrderService;
	public function __construct() {
		$this->_salesproductModel = new Icwebadmin_Model_DbTable_SalesProduct();
		$this->_inqsalesorderModel= new Icwebadmin_Model_DbTable_InqSalesOrder();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->_staffService=new Icwebadmin_Service_StaffService();
		$this->_dInqOrderService = new Default_Service_InqOrderService();
		$this->fun = new MyFun();
	}
	/**
	 * 检查询价单是否通过OA询价
	 */
	public function checkPmscInq($salesnumber)
	{
		$re = $this->_inqsalesorderModel->getBySql("SELECT inqd.oa_result_price
				FROM inquiry_detailed as inqd 
				LEFT JOIN inq_sales_order as io ON inqd.inq_id = io.inquiry_id
				WHERE io.salesnumber = '{$salesnumber}';");
		$cansqs = true;
		if($re){
			foreach($re as $v){
				if($v['oa_result_price']<=0 || !$v['oa_result_price']){
					$cansqs = false;break;
				}
			}
			if($cansqs) return true;
			else return false;
		}else return false;	
	}
	/**
	 * 是否允许走SQS code
	 */
	public function canSqsCode($salesnumber){
		//必须有pmsc询价记录
		$cansqs = $this->checkPmscInq($salesnumber);
		$soinfo = $this->getSoInfo($salesnumber);
		if($cansqs){
			//RMB5000，USD1000，HKD5000才能走SQS流程
			$canarr = array('USD'=>1000,'RMB'=>5000,'HKD'=>5000);
			if($soinfo['total']>$canarr[$soinfo['currency']]){
				$cansqs = false;
			}
			//必须预付全款
			if($soinfo['down_payment']!=$soinfo['total']){
				$cansqs = false;
			}
		}
		//购买产品数量必须小于包装数
		if($cansqs){
			$pordarr = $this->getSoPart($salesnumber);
			foreach($pordarr as $pv){
				if($pv['buynum']>$pv['mpq']){
					$cansqs = false;break;
				}
			}
		}
		return $cansqs;
	}
	/**
	 * 获取用户的所有询价订单，包括子订单
	 */
	public function getAllSo($offset,$perpage,$typestr,$orderbystr='')
	{
		$limit = '';
		if($offset || $perpage) $limit ="LIMIT $offset,$perpage";
		if(!$orderbystr) $orderbystr = "ORDER BY so.status ASC,so.created DESC";
		$sqlstr = "SELECT so.*,sapo.auart,sapo.order_no,sapo.kunnr,sapo.cname,sapo.bstnk,u.uname,up.companyname,up.annex1,up.annex2,st.lastname,st.firstname FROM inq_sales_order as so
		LEFT JOIN user as u ON u.uid = so.uid
		LEFT JOIN user_profile as up ON u.uid = up.uid
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
		LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
		WHERE  so.available!=0 {$typestr} {$orderbystr} {$limit}";
		
		$orderarr = $this->_inqsalesorderModel->getBySql($sqlstr);
		return $orderarr;
	}
	/**
	 * 获取count()行数
	 */
	public function getRowNum($str)
	{
		$sqlstr = "SELECT count(so.id) as num FROM inq_sales_order as so 
		LEFT JOIN user_profile as up ON so.uid = up.uid
		LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
		WHERE so.available!=0 {$str}";
		$allrel = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		return $allrel['num'];
	}
	/**
	 * 获取查询订单号
	 */
	public function ajaxtag($keyword)
	{
		$sell = $this->_staffService->checkSell();
		$sellsql = '';
		if($sell){
			$sellsql = " AND up.staffid = '".$_SESSION['staff_sess']['staff_id']."'";
		}
		$where="so.`salesnumber` LIKE '%".$keyword. "%'".$sellsql;
		$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
		$sqlstr ="SELECT so.`salesnumber`  FROM `inq_sales_order` as so
		LEFT JOIN user_profile as up ON so.uid = up.uid
		WHERE so.available!=0 AND {$where}";
		return $this->_inqsalesorderModel->getBySql($sqlstr);;
	}
	/**
	 * 更新订单状态
	 */
	public function updateStatus($salesnumber,$statusarr)
	{
		return $this->_inqsalesorderModel->update($statusarr, "salesnumber='{$salesnumber}'");
	}
	/*
	 * 更新订单by number
	 */
	public function updateByNum($data,$salesnumber){
		return $this->_inqsalesorderModel->update($data, "salesnumber='{$salesnumber}'");
	}
	/**
	 * 检查订单是否是货到付款
	 */
	public function checkCod($salesnumber)
	{
		$sqlstr = "SELECT paytype FROM inq_sales_order WHERE salesnumber='{$salesnumber}'";
		$re = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		if($re['paytype']=='cod') return true;
		else return false;
	}
	/**
	 * 检查订单预付款是否为0
	 */
	public function checkDownPayment($salesnumber)
	{
		$sqlstr = "SELECT down_payment FROM inq_sales_order WHERE salesnumber='{$salesnumber}'";
		$re = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		if($re['down_payment']>0) return $re['down_payment'];
		else false;
	}
	/**
	 * 检查订单是否发现货，全额支付
	 */
	public function checkShipments($salesnumber)
	{
		$sqlstr = "SELECT shipments,down_payment,total FROM inq_sales_order WHERE salesnumber='{$salesnumber}'";
		$re = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		if($re['shipments']=='spot' && $re['down_payment']==$re['total']) return true;
		else return false;
	}
	/**
	 * 检查订单是否全额支付
	 */
	public function checkAllpay($salesnumber)
	{
		$sqlstr = "SELECT down_payment,total FROM inq_sales_order WHERE salesnumber='{$salesnumber}'";
		$re = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		if($re['down_payment']==$re['total']) return true;
		else return false;
	}
	/**
	 * 检查订单是否发现货并且是货到付款，全额支付
	 */
	public function checkCodShipments($salesnumber)
	{
		$sqlstr = "SELECT paytype,shipments,total FROM inq_sales_order WHERE salesnumber='{$salesnumber}'";
		$re = $this->_inqsalesorderModel->getByOneSql($sqlstr);
		if($re['paytype']=='cod' && $re['shipments']=='spot' && $re['total']>0) return true;
		else return false;
	}
	/**
	 * 获取so简单信息
	 */
	public function getSoBase($where)
	{
		return $this->_inqsalesorderModel->getRowByWhere($where);
	}
	/**
	 * 根据salesnumber查询订单详细信息
	 */
	public function getSoInfo($salesnumber,$where='')
	{
		//订单详细
		$salesorderModel   = new Icwebadmin_Model_DbTable_SalesOrder();
		$sqlstr ="SELECT so.*,
		iv.type as ivtype,inq.inq_number,
        u.uid,u.uname,u.email,up.companyname as ucompanyname,up.annex1,up.annex2,
        p.province,c.city,e.area,
    	a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.express_account,a.express_name,a.warehousing,
    	pi.province as province_i,ci.city as city_i,ei.area as area_i,
		ai.name as name_i,ai.address as address_i,ai.zipcode as zipcode_i,ai.mobile as mobile_i,ai.tel as tel_i,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name
    	FROM inq_sales_order as so
    	LEFT JOIN inquiry as inq ON inq.id=so.inquiry_id
        LEFT JOIN user as u ON so.uid=u.uid
    	LEFT JOIN invoice as iv ON so.invoiceid=iv.id
    	LEFT JOIN user_profile as up ON so.uid=up.uid
    	LEFT JOIN order_address as a ON so.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	
    	LEFT JOIN order_address as ai ON so.invoiceaddress=ai.id
		LEFT JOIN province as pi ON ai.province=pi.provinceid
    	LEFT JOIN city as ci ON ai.city=ci.cityid
    	LEFT JOIN area as ei ON ai.area = ei.areaid
    	
    	LEFT JOIN invoice as i ON so.invoiceid=i.id
    	LEFT JOIN courier_history as coh ON so.courierid=coh.id
    	LEFT JOIN courier as co ON coh.cou_id=co.id
    	WHERE so.salesnumber=:sonum AND so.available='1' {$where}";
		return $this->_inqsalesorderModel->getByOneSql($sqlstr, array('sonum'=>$salesnumber));
	}
	/**
	 * 获取负责销售
	 */
	public function getOwnerByUid($uid)
	{
		$sql = "SELECT st.* FROM admin_staff as st
				LEFT JOIN user_profile as up ON st.staff_id = up.staffid
    			WHERE st.status=1 AND up.uid='{$uid}'";
		return $this->_inqsalesorderModel->getByOneSql($sql);
	}
	/**
	 * 更加sonumber获取订单详细产品
	 */
	public function getSoPart($salesnumber)
	{
		$sql = "SELECT sp.*,p.manufacturer,p.staged,p.mpq,p.datecode  FROM sales_product as sp
		LEFT JOIN product as p ON sp.prod_id=p.id
		WHERE  sp.salesnumber='{$salesnumber}' ";
		return $this->_salesproductModel->getBySql($sql);
	}
	/**
	 * 释放订单时发送邮件
	 */
	public function emancipateSendEmail($orderarr,$pordarr,$filearray)
	{
		$this->config = Zend_Registry::get('config');
    	$this->view->reviewer = $this->config->order->reviewer;
    	$this->view->tel = $this->config->order->tel;
    	$this->view->email = $this->config->order->email;

       //负责销售情况
       $sellinfo = $this->_staffService->sellbyuid($orderarr['uid']);

       $fromname = 'IC易站';
       //重要提示
       $important_info = '';
       $imp_item = 1;
       //是否需要盖章合同
       if($orderarr['paper_contract']==1){
		  $important_info .= $imp_item.'、此订单客户需要纸质盖章合同，请与负责销售确认。<br/>';
		  $imp_item++;
       }
       //合并订单发货
       if($orderarr['ship_salesnumber']){
       	  $important_info .= $imp_item.'、此订单与订单#： '.$orderarr['ship_salesnumber'].' 一起发货。<br/>';
          $imp_item++;
       }
       //如果支付余款，
       if($orderarr['down_payment']!=$orderarr['total']){
       	  $important_info .= $imp_item.'、此订单请收到余款后才能安排发货。<br/>';
       	  $imp_item++;
       }
       //如果已经在走线下合同评审流程
       if($orderarr['line_process']==1){
       	  $important_info .= $imp_item.'、此订单已经在走线下合同评审流程，注意不要重复下单。<br/>';
       	  $imp_item++;
       }
       if($orderarr['sqs_code']){
       	//如果需要开发票的
       	if($orderarr['invoiceid']>0){
       		$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_invoice',$orderarr['uid']);
       	}else{
       		if($orderarr['delivery_place']=='SZ'){
       			$important_info .= $imp_item.'、此订单客户没有选择开发票，请与负责销售确认。<br/>';
       			$imp_item++;
       		}
       		$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_noinvoice',$orderarr['uid']);
       	}
       	$title    = '#在线订单# - 订单号:'.$orderarr['salesnumber'].'，请处理';
       	$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的Jill Cen,</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">IC易站在线订单，请使用
                                                <b style="color:#fd2323; font-size:15px;"> SQS Customer Code </b>处理。</div>';
       										    if($important_info){
       										    	$hi_mess .='<div style="padding:3px 0;font-size:14px; font-weight:bold; color:#ff6600;font-family:\'微软雅黑\';">重要提示：'.$important_info.'</div>';
       										    }
                                                $hi_mess .= '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
       }else{
       	//如果需要开发票的
       	if($orderarr['invoiceid']>0){
       		$emailarr = $this->_emailService->getEmailAddress('order_release_invoice',$orderarr['uid']);
       	}else{
       		if($orderarr['delivery_place']=='SZ'){
       		   $important_info .= $imp_item.'、此订单客户没有选择开发票，请与负责销售确认。<br/>';
       		   $imp_item++;
       		}
       		$emailarr = $this->_emailService->getEmailAddress('order_release_noinvoice',$orderarr['uid']);
       	}
       	$title    = '#询价订单# - 订单号:'.$orderarr['salesnumber'].'，请处理';
       	$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$emailarr['cse_name'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">IC易站询价订单，请使用
                                                <b style="color:#fd2323; font-size:15px;"> SAP标准订单处理流程 </b>处理。</div>';
       										    if($important_info){
       										    	$hi_mess .='<div style="padding:3px 0;font-size:14px; font-weight:bold; color:#ff6600;font-family:\'微软雅黑\';">重要提示：'.$important_info.'</div>';
       										    }
                                                $hi_mess .= '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
       }
       //询价信息
       $inqServer = new Icwebadmin_Service_InquiryService();
       $inqinfo = $inqServer->getInquiryByID($orderarr['inquiry_id']);
		
  	   $mess .= $this->_dInqOrderService->getInqOrderTable($orderarr, $pordarr,$hi_mess,$sellinfo,$inqinfo);
	
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//中电品牌抄送给研发
		$staffservice = new Icwebadmin_Service_StaffService();
		$emailcc = $staffservice->ccToYafa($pordarr,$emailcc);
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,$filearray,$sellinfo,0);
	}
	/**
	 * 支付余款成功通知发货
	 */
	public function deliverySendEmail($orderarr,$pordarr,$filearray)
	{
		$this->config = Zend_Registry::get('config');
    	$this->view->reviewer = $this->config->order->reviewer;
    	$this->view->tel = $this->config->order->tel;
    	$this->view->email = $this->config->order->email;

       //负责销售情况
       $sellinfo = $this->_staffService->sellbyuid($orderarr['uid']);

       $fromname = 'IC易站';
       if($orderarr['sqs_code']){
       	//如果需要开发票的
       	if($orderarr['invoiceid']>0){
       		$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_invoice',$orderarr['uid']);
       	}else{
       		$emailarr = $this->_emailService->getEmailAddress('sqs_order_release_noinvoice',$orderarr['uid']);
       	}
       	$title    = '#在线订单# - 订单号:'.$orderarr['salesnumber'].'，请处理';
       	$hititle  = 'ivy.zeng';
       }else{
       	//如果需要开发票的
       	if($orderarr['invoiceid']>0){
       		$emailarr = $this->_emailService->getEmailAddress('order_release_invoice',$orderarr['uid']);
       	}else{
       		$emailarr = $this->_emailService->getEmailAddress('order_release_noinvoice',$orderarr['uid']);
       	}
       	$title    = '#询价订单# - 订单号:'.$orderarr['salesnumber'].'，请处理';
       	$hititle  = 'CSE';
       }
       $hi_mess   = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$hititle.',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">
       											此订单客户已经上传余款转账凭证，请确认已收到余款后处理此订单。
       											</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
       //询价信息
       $inqServer = new Icwebadmin_Service_InquiryService();
       $inqinfo = $inqServer->getInquiryByID($orderarr['inquiry_id']);

  	   $mess .= $this->_dInqOrderService->getInqOrderTable($orderarr, $pordarr,$hi_mess,$sellinfo,$inqinfo);
				
		
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//中电品牌抄送给研发
		$staffservice = new Icwebadmin_Service_StaffService();
		$emailcc = $staffservice->ccToYafa($pordarr,$emailcc);
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,$filearray,$sellinfo,0);
	}
	/**
	 * 反馈交货期发送邮件
	 */
	public function leadtimeEmail($orderarr,$pordarr)
	{
		//预付款
		if($orderarr['total'] != $orderarr['down_payment']){
			$fromname = 'IC易站';
			$title    = 'IC易站订单#：'.$orderarr['salesnumber'].'的交期已确定，请支付余款';
			$hi_mess ='<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$orderarr['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对IC易站的惠顾！</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您于&nbsp;<strong>'.date('Y/m/d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>已经确认交货时间。我们将于&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'; font-size:13px;">'.date('Y-m-d',$orderarr['delivery_time']).'</strong>&nbsp;为您发货，请您务必于发货前付清订单余款。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您可以随时进入 <a href="http://www.iceasy.com/center/inqorder" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的订单</b></a> 查看订单的后续处理情况。</div>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}else{ //全款
			$fromname = 'IC易站';
			$title    = 'IC易站订单#：'.$orderarr['salesnumber'].' 的交期已确认，请查看';
			$hi_mess ='<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$orderarr['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对IC易站的惠顾！</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您于&nbsp;<strong>'.date('Y/m/d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>已经确认交货时间。我们将于&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'; font-size:13px;">'.date('Y-m-d',$orderarr['delivery_time']).'</strong>&nbsp;为您发货。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您可以随时进入 <a href="http://www.iceasy.com/center/inqorder" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的订单</b></a> 查看订单的后续处理情况。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}
		
		$mess .= $this->_dInqOrderService->getInqOrderTable($orderarr, $pordarr,$hi_mess);
			
		$mess .= $this->getImportantNotice();

	    $emailarr = $this->_emailService->getEmailAddress('inquiry_order_feedback',$orderarr['uid']);
		$emailto = array('0'=>$orderarr['email']);
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/**
	 * 获取重要声明
	 */
	public function getImportantNotice(){
		$mess ='<!--重要信息-->
<tr><td height="10" style="line-height:1px; font-size:10px; margin:0; padding:0 "></td></tr> 
<tr><td height="1" bgcolor="ffffff" style="line-height:1px; font-size:0; height:1px; border-top:1px solid #f1f1f1 ">&nbsp;</td></tr>  
<tr>
    <td>
         <table cellspacing="0" border="0" cellpadding="0" width="730" style=" font-size:12px; color:#5b5b5b;font-family:\'微软雅黑\'">
            
            <tr>
                <td valign="bottom" align="left" >
                    <div style="margin: 0; padding:0; text-align:left; font-size:14px; font-weight:bold;color:#fd2323;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;※※&nbsp;重要声明&nbsp;※※</div>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;1. 如果因原厂原因和/或不可控的突发状况造成交期延后，IC易站不对您承担责任，您不得因此而取消订单。如因您未及时支付余款，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;或因您的其他原因造成货物发送延误和/或产生其他风险，后果由您承担。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 如果由于超出IC易站合理控制的任何原因（应包括但不限于政府行为、战争、火灾、广泛流行性疾病、爆炸、洪水、灾害性气候、进<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;出口管制或禁运、劳动争议、货品或劳动力无法得到供给等），IC易站不应就该等迟延履行或未能履行而对客户承担任何形式的责任<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;或被视作违约行为。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 因上述原因而使IC易站履行合同受阻，IC易站可以自主决定迟延履行或撤销合同的全部或部分，且不对此迟延、撤销或任何不能交<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;货承担责任。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p  style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;更多条款内容请见&nbsp;<a href="http://www.iceasy.com/help/index/type/clause" target="_blank" style="color:#0055aa">IC易站交易条款</a>，我们建议您定期阅读以获取最新条款信息。</p>
                </td>
            </tr>
         </table>
    </td>
</tr>';
		return $mess;
	}
	/**
	 * 反馈交货期发送邮件
	 */
	public function deliverychangeEmail($orderarr,$re)
	{
		if($re=='yes'){
			$remess = '通过';
		}else{
			$remess = '不通过';
		}

		$mess = '尊敬的'.$orderarr['uname'].',您好！感谢您对IC易站的惠顾！<br/>
		您对订单：'.$orderarr['salesnumber'].'提出的交期变更请求已经处理。申请结果：'.$remess.'<br/>
		您可以随时进入<a href="http://'.$_SERVER['HTTP_HOST'].'"/center/delivery" target="_blank">交期变更</a>查看详细情况。	<br/>';
	
		$fromname = 'IC易站';
		$title    = '来自IC易站交货期变更申请反馈邮件';
		
		return $this->fun->sendemail($orderarr['email'], $mess, $fromname, $title);
	}
	/**
	 * 用户询价订单统计
	 * 括号的代表是cod状态 101待付预付款（cod:等待审核） 102 需要订货，订单处理中(转账:已支付预付款) 
	 * 103已经订货并返回交货期（转账:等待付剩余货款）
	 * 201待发货（转账:已付剩余货款） 202已发货,待确认收货 
	 * 301已确认收货,待评价 302已确认收货 401客户取消订单
	 */
	public function inqSoInfo($uid){
		$sqlstr = "SELECT salesnumber,status,back_status FROM inq_sales_order WHERE uid='{$uid}'";
		$inqrr = $this->_inqsalesorderModel->getBySql($sqlstr);
		$num_0 = $num_1 =$num_2 =$num_3 =$num_4 =$num_5 =$num_6 =$num_7 =$num_8 =0;
		if(!empty($inqrr)) {
			foreach($inqrr as $inq){
				if($inq['back_status']==102) $num_0++;
				elseif($inq['status']==101) $num_1++;
				elseif($inq['status']==102) $num_2++;
				elseif($inq['status']==103) $num_3++;
				elseif($inq['status']==201) $num_4++;
				elseif($inq['status']==202) $num_5++;
				elseif($inq['status']==301) $num_6++;
				elseif($inq['status']==302) $num_7++;
				elseif($inq['status']==401) $num_8++;
			}
		}
		$rearr[0]= $num_0;
		$rearr[1]= $num_1;
		$rearr[2]= $num_2;
		$rearr[3]= $num_3;
		$rearr[4]= $num_4;
		$rearr[5]= $num_5;
		$rearr[6]= $num_6;
		$rearr[7]= $num_7;
		$rearr[8]= $num_8;
		return $rearr;
	}
	/**
	 * 事务开始
	 */
	public function beginTransaction()
	{
		$this->_inqsalesorderModel->beginTransaction();
	}
	/**
	 * 事务完成
	 */
	public function commit()
	{
		$this->_inqsalesorderModel->commit();
	}
	/**
	 * 事务回滚
	 */
	public function rollBack()
	{
		$this->_inqsalesorderModel->rollBack();
	}
	/**
	 * 获取货代公司资料
	 * @param unknown_type $ufid
	 */
	function getUserForwarder($ufid){
		$ufModerl = new Icwebadmin_Model_DbTable_Model('user_forwarder');
		return $ufModerl->getRowByWhere("id='{$ufid}' AND status=1");
	}
	function updateUserForwarder($ufid,$update){
		$ufModerl = new Icwebadmin_Model_DbTable_Model('user_forwarder');
		return $ufModerl->update($update, "id='{$ufid}'");
	}
	function addUserForwarder($data){
		$ufModerl = new Icwebadmin_Model_DbTable_Model('user_forwarder');
		return $ufModerl->addData($data);
	}
	/**
	 * 生成批pi pdf
	 */
	public function createPi($soarray,$userforwarder,$userinfo,$sellinfo,$handling_charge){
		$currency = array('RMB'=>'￥','USD'=>'$','HKD'=>'HK$');
		$c_name = array('HK'=>'CEAC TECHNOLOGY HK LIMITED','SZ'=>'中国电子器材深圳有限公司');
		$c_address = array('HK'=>'Unit B on 9/F Unison Industrial Centre Nos. 27-31 Au Pui Wan Street Shatin New Territories','SZ'=>'深圳市福田区侨香路裕和大厦八楼');
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		$html = '<style type="text/css">
h2{color:#000}
h3{color:#000}
.table100{ width:100%; font-size:1em; line-height:0.9em; font-family:Arial}
.table100 td{ font-size:0.8em;}

.fontred{ color:#f00}
.fontsizeh3{font-size:2em;}
.tabletd{ border:0.05em solid #ddd}
.tablefooter td{ font-size:0.7em;}
.tablefootertd{ font-size:0.6em;}
.table1{ border-collapse:collapse}

</style>


<table cellpadding="0" cellspacing="0" border="0" class="table100">
    <tr>
        <td width="200"><br/><br/><br/>
        <img src="images/admin/alogo.jpg" width="190" height="50">
        </td>
        <td width="450">
        	<h1>'.$c_name[$soarray['delivery_place']].'</h1>
            <p>'.$c_address[$soarray['delivery_place']].'</p>
            <p>Contact：'.$sellinfo['lastname'].$sellinfo['firstname'].'  Tel：'.$sellinfo['tel'].' Mobile：'.$sellinfo['phone'].'</p>
        </td>
        <td width="200" >
        	<h1>&nbsp;</h1>
            <p><strong>Invoice No：</strong>'.$soarray['invoice_no'].'</p>
            <p><strong>Date：</strong>'.date("Ymd",$soarray['created']).'</p>
        </td>
    </tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" class="table100">
	<tr><td height="30" >&nbsp;</td></tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" class="table100">
	<tr><td colspan="2" align="center" height="20" style="border-bottom:0.2em solid #ddd"><h2>PROFORMA  INVOICE </h2></td></tr>
    <tr>
        <td width="280" >
        	<h3>BILL TO :</h3>	
            <p>'.$userinfo['companyname'].'</p>
            <p>'.$this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']).'</p>
            <p>'.$userinfo['truename'].'</p>
            <p>';
	if($userinfo['tel'] && $userinfo['mobile'])
		 $html .=$userinfo['tel'].' , '.$userinfo['mobile'];
	else $html .=($userinfo['tel']?$userinfo['tel']:$userinfo['mobile']);	
       $html .='</p>
        </td>
        <td width="280" >
        	<h3>SHIP TO :</h3>	
            <p>'.$userforwarder['ufname'].'</p>
            <p>'.$userforwarder['ufaddress'].'</p>
            <p>'.$userforwarder['ufcontact'].'</p>
            <p>'.$userforwarder['uftel'].'</p>
        </td>
        <td width="225" >
        	<div style="border:0.2em solid #ddd">
                <p><strong>&nbsp;&nbsp;Customer ID：</strong>'.$soarray['customer_id'].'</p>
                <p><strong>&nbsp;&nbsp;Delivery Terms：</strong>'.$soarray['delivery_terms'].'</p>
                <p><strong>&nbsp;&nbsp;Payment Terms：</strong>'.$soarray['payment_terms'].'</p>
                <p><strong>&nbsp;&nbsp;Currency：</strong>'.$soarray['currency'].'</p>
            </div>
        </td>
    </tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" class="table100">
	<tr><td height="15" >&nbsp;</td></tr>
</table>
<table cellpadding="5" border="0" cellspacing="0" class="table1 table100" ><tbody>
  <tr bgcolor="#eeeeee"  align="center" valign="bottom">
    <th width="50px">Item</th>
    <th width="150px">MPN</th> 
    <th width="100px">So No/Item No</th>
    <th width="100px">CPN</th>
    <th width="100px">PO No</th>
    <th width="90px">Quantity</th>
    <th width="90px">Unit Price</th>
    <th width="105px">Amount</th>
  </tr>';
 foreach($soarray['pordarr'] as $k=>$pordarr){
    $html .='<tr align="center" >
    <td class="tabletd">'.($k+1).'</td>    
    <td class="tabletd">'.$pordarr['part_no'].'</td>
    <td class="tabletd">'.($pordarr['item_no']?$pordarr['item_no']:'--').'</td>	
    <td class="tabletd">'.($pordarr['cpn']?$pordarr['cpn']:'--').'</td>	
    <td class="tabletd">'.($pordarr['po_no']?$pordarr['po_no']:'--').'</td>	
    <td class="tabletd">'.$pordarr['buynum'].'</td>	
    <td class="fontred tabletd">'.$currency[$soarray['currency']].$pordarr['buyprice'].'</td>
    <td class="fontred tabletd">'.$currency[$soarray['currency']].($pordarr['buynum']*$pordarr['buyprice']).'</td>																																												
    </tr>';
  }
  $html .='
  <tr>
      <td colspan="8" align="right"><h3>';
  if($soarray['freight']>0){
       $html .='Shipping：<b class="fontred">'.$currency[$soarray['currency']].number_format($soarray['freight'],DECIMAL).'</b>
       		    + Sub-Total：<b class="fontred">'.$currency[$soarray['currency']].number_format($soarray['total']-$soarray['freight'],DECIMAL).'</b> 
                = Total：<b class="fontred">'.$soarray['currency'].' '.number_format($soarray['total'],DECIMAL).'</b>';
   }else{
      $html .= 'Sub-Total：<b class="fontred">'.$soarray['currency'].' '.number_format($soarray['total'],DECIMAL).'</b>';
   }
  	   $html .='</h3> </td>
  </tr>
  <tr>
      <td colspan="4" align="left" class="tablefootertd">
			<p><strong>Exchange Rate：</strong>USD：'.($soarray['delivery_place']=='HK'?'HKD':'RMB').'=1：'.$soarray['usdtohkd'].'</p>
      </td>
      <td colspan="4" align="right" class="tablefootertd">';
	  if($handling_charge=='down'){
	  	$html .='<p><strong>'.($soarray['percentage']?$soarray['percentage'].'% ':'').' Deposit：</strong>'.($soarray['currency'].' '.number_format($soarray['down_payment'],DECIMAL)).'</p>
            <p><strong>Bank Charge：</strong>'.($soarray['down_handling_charge']?$soarray['currency'].' '.number_format($soarray['down_handling_charge'],DECIMAL):'0.00').'</p>
            <p><strong>Total AmounT：</strong>'.($soarray['currency'].' '.number_format($soarray['down_payment']+$soarray['down_handling_charge'],DECIMAL)).'</p>';
	  }elseif($handling_charge=='surplus'){
	  	$html .='<p><strong>'.($soarray['percentage']?(100-$soarray['percentage']).'% ':'').' Balance：</strong>'.($soarray['currency'].' '.number_format($soarray['total']-$soarray['down_payment'],DECIMAL)).'</p>
            <p><strong>Bank Charge：</strong>'.($soarray['surplus_handling_charge']?$soarray['currency'].' '.number_format($soarray['surplus_handling_charge'],DECIMAL):'0.00').'</p>
            <p><strong>Total AmounT：</strong>'.($soarray['currency'].' '.number_format($soarray['total']-$soarray['down_payment']+$soarray['surplus_handling_charge'],DECIMAL)).'</p>';
	  }
      $html .='</td>
  </tr>
</tbody></table>     
<table cellpadding="0" cellspacing="0" border="0" class="table100 tablefooter">
	<tr><td colspan="2" align="center" style="border-bottom:0.2em solid #ddd">&nbsp;</td></tr>
    <tr>
        <td width="280" >
      	<p>Bank Information by Wire Transfer for Payment:</p>';
        $remark = 'Remark：';
        if($soarray['delivery_place']=='HK'){
        	$html .='<p>Bank Name   : '.BANK_HK_NAME.'</p>
        	<p>Account Name  : '.$c_name['HK'].'</p>
            <p>Account No  : '.BANK_HK_ACCOUNT.'</p>
            <p>Swift Code  : '.SWIFT_CODE_HK.'</p>
            <p>Bank Address: '.BANK_ADDRESS_HK.'</p>';
        }else{
        	$html .='<p>户名 : '.BANK_COM.'</p>
        	<p>开户行   : '.BANK_NAME.'</p>
            <p>帐号  : '.BANK_ACCOUNT.'</p>';
        	
        	$remark = "备注：";
        }
        
        $html .='</td>
        <td width="280" >
            <p>'.($soarray['admin_notes']?$remark.$soarray['admin_notes']:'').'</p>
            <br/>
            <br/>
            <br/>
        </td>';
        
    $html .='</tr>
</table>';

        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

		//$pdf->Output ();
		$pdf->Output (ORDER_PI.md5($handling_charge.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
}