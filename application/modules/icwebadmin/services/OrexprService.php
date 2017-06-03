<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_OrexprService
{
	private $_salesproductModel;
	private $_salesorderModel;
	private $_inqsalesorderModel;
	private $_emailService;
	
	public function __construct() {
		$this->_salesproductModel = new Icwebadmin_Model_DbTable_SalesProduct();
		$this->_salesorderModel   = new Icwebadmin_Model_DbTable_SalesOrder();
		$this->_inqsalesorderModel= new Icwebadmin_Model_DbTable_InqSalesOrder();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
	}
	/**
	 * 检查订单是在线订单还是询价订单
	 */
	public function checkSoType($sonum)
	{

		$sore = $this->_salesorderModel->getRowByWhere("salesnumber='{$sonum}'");
		if($sore) {return 'online';}
		else{
			$inqsore = $this->_inqsalesorderModel->getRowByWhere("salesnumber='{$sonum}'");
			if($inqsore) return 'inq';
			else return 'samples';
		}
	}
	/**
	 * 获取等待发货订单数（包括在线和询价订单）
	 */
	public function getSendNum()
	{
		$sqlstr = "SELECT so.salesnumber  FROM sales_order as so 
				WHERE so.available!=0 AND so.status<202 AND so.back_status='202' 
				union all 
				SELECT inqso.salesnumber FROM inq_sales_order as inqso 
				WHERE  inqso.available!=0 AND inqso.status<202 AND inqso.back_status='202'";
		$allrel = $this->_salesorderModel->getBySql($sqlstr);
		return count($allrel);
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getSelectNum($keyword)
	{
		$sqlstr = "SELECT so.salesnumber  FROM sales_order as so
				WHERE so.available!=0 AND so.status<202 AND so.back_status='202' AND so.salesnumber LIKE '%{$keyword}%'
				union all
				SELECT inqso.salesnumber FROM inq_sales_order as inqso
				WHERE  inqso.available!=0 AND inqso.status<202 AND inqso.back_status='202' AND inqso.salesnumber LIKE '%{$keyword}%'";
		$allrel = $this->_salesorderModel->getBySql($sqlstr);
		return count($allrel);
	}
	/**
	 * 获取等待发货订单信息（包括在线和询价订单）
	 */
	public function getAllSend($offset,$perpage)
	{
		$sqlstr ="SELECT sotmp.*,u.uname,p.province,c.city,e.area,a.name as sname,a.address,a.mobile,a.tel 
		FROM
		(SELECT so.id,so.delivery_type,so.salesnumber,so.uid,so.addressid,so.partnos,so.receipt,so.paytype,so.total,so.quantitys,
    	so.items,so.status,so.back_status,so.created FROM sales_order as so 
		WHERE so.available!=0 AND so.status<202  AND so.back_status='202'
		union all 
		SELECT inqso.id,inqso.delivery_type,inqso.salesnumber,inqso.uid,inqso.addressid,inqso.partnos,inqso.receipt,inqso.paytype,inqso.total,inqso.quantitys,
    	inqso.items,inqso.status,inqso.back_status,inqso.created FROM inq_sales_order as inqso 
		WHERE inqso.available!=0 AND inqso.status<202 AND inqso.back_status='202') as sotmp
		
		LEFT JOIN user as u ON sotmp.uid=u.uid
		LEFT JOIN order_address as a ON sotmp.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid  
		ORDER BY sotmp.created DESC
		LIMIT $offset,$perpage";
		return $this->_salesorderModel->getBySql($sqlstr);
	}
	/**
	 * 获取等待发货订单信息（包括在线和询价订单）
	 */
	public function getSelectSend($keyword,$offset,$perpage)
	{
		$sqlstr ="SELECT sotmp.*,u.uname,p.province,c.city,e.area,a.name as sname,a.address,a.mobile,a.tel
		FROM
		(SELECT so.id,so.delivery_type,so.salesnumber,so.uid,so.addressid,so.partnos,so.receipt,so.paytype,so.total,so.quantitys,
		so.items,so.status,so.back_status,so.created FROM sales_order as so
		WHERE so.available!=0 AND so.status<202  AND so.back_status='202' AND so.salesnumber LIKE '%{$keyword}%'
		union all
		SELECT inqso.id,inqso.delivery_type,inqso.salesnumber,inqso.uid,inqso.addressid,inqso.partnos,inqso.receipt,inqso.paytype,inqso.total,inqso.quantitys,
		inqso.items,inqso.status,inqso.back_status,inqso.created FROM inq_sales_order as inqso
		WHERE inqso.available!=0 AND inqso.status<202 AND inqso.back_status='202' AND inqso.salesnumber LIKE '%{$keyword}%') as sotmp
	
		LEFT JOIN user as u ON sotmp.uid=u.uid
		LEFT JOIN order_address as a ON sotmp.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid LIMIT $offset,$perpage";
		return $this->_salesorderModel->getBySql($sqlstr);
	}
	/**
	 * 获取搜索订单号（包括在线和询价订单）
	 */
	public function getSearchSo($keyword)
	{
		$soModel = new Icwebadmin_Model_DbTable_SalesOrder();
		$sqlstr ="SELECT sotmp.salesnumber FROM
		(SELECT so.id,so.salesnumber,so.delivery_type,so.uid,so.addressid,so.partnos,so.receipt,so.paytype,so.total,so.quantitys,
    	so.items,so.status,so.back_status,so.created FROM sales_order as so 
		WHERE so.available!=0 AND so.status<202 AND so.back_status='202' AND so.salesnumber LIKE '%{$keyword}%'
		union all 
		SELECT inqso.id,inqso.salesnumber,inqso.delivery_type,inqso.uid,inqso.addressid,inqso.partnos,inqso.receipt,inqso.paytype,inqso.total,inqso.quantitys,
    	inqso.items,inqso.status,inqso.back_status,inqso.created FROM inq_sales_order as inqso 
		WHERE inqso.available!=0 AND inqso.status<202 AND inqso.back_status='202' AND inqso.salesnumber LIKE '%{$keyword}%') as sotmp";
		return $this->_salesorderModel->getBySql($sqlstr);
	}
	/**
	 * 更新订单状态
	 */
	public function updateStatus($sonum,$newid)
	{
		$sore = $this->_salesorderModel->update(array('courierid'=>$newid,'status'=>202,'send_time'=>time(),'modified'=>time()), "salesnumber='{$sonum}'");
		if($sore) {return true;}
		else{
			$inqsore = $this->_inqsalesorderModel->update(array('courierid'=>$newid,'status'=>202,'send_time'=>time(),'modified'=>time()), "salesnumber='{$sonum}'");
			if($inqsore) return true;
			else return false;
		}
	}
	/**
	 * 发货提醒邮件
	 */
	public function shipmentsEmail($orderarr,$pordarr,$sotype)
	{
		$fromname = '盛芯电子';
	    $title = '盛芯电子订单#：'.$orderarr['salesnumber'].'已发货，请注意查收';
	    $hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
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
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对盛芯电子的惠顾！您于&nbsp;<strong>'.date('Y-m-d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;"> '.$orderarr['salesnumber'].' </strong>，已经发货，请注意查收。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您可以随时进入&nbsp;<a href="http://www.iceasy.com/center/order" style="color:#fd2323;font-family:\'微软雅黑\'"><b>我的订单</b></a>&nbsp;查看订单的后续处理情况和物流跟踪情况。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		if($sotype=='inq'){
			$dInqOrderService = new Default_Service_InqOrderService();
		    $mess =$dInqOrderService->getInqOrderTable($orderarr, $pordarr,$hi_mess);
		}else{
			$dforderService = new Default_Service_OrderService();
		    $mess = $dforderService->getOrderTable($orderarr,$pordarr,$hi_mess);
		}
		$mess .= $this->getImportantNotice();

		$emailarr = $this->_emailService->getEmailAddress('shipping_notice',$orderarr['uid']);
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
		//bcc 给发放优惠券的人
		if($orderarr['coupon_code']){
			$couponService = new Default_Service_CouponService();
			$coupbcc = $couponService->getEmailByCoup($orderarr['coupon_code']);
			$emailbcc = array_merge($emailbcc,$coupbcc);
		}
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/**
	 * 发给用户邮件
	 */
	public function shipmentsSamplesEmail($so_id){
		$this->_samplesservice = new Icwebadmin_Service_SamplesService();
		$apply = $this->_samplesservice->getApplyById($so_id);
		$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$apply['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">您在盛芯电子申请的样片已经发货，快递信息可到<a href="http://www.iceasy.com/center/samples" target="_blank"> 我的样片申请</a>查看。</div>
                                               </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';

		$mess .= $this->_samplesservice->getTable($apply,$hi_mess,1);
		
		$fromname = '盛芯电子';
		$title    = '样片申请已经发货';
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('samples_order_touser');
		$emailto = array($apply['useremail']);
		$emailcc = $emailbcc = array();
		
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),1);
		
	}
	/**
	 * 获取重要声明
	 */
	public function getImportantNotice()
	{
		$mess = '<!--重要信息-->
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
                        	<p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;1. 盛芯电子交货后，您须登录我的盛芯电子确认收到并在尽可能合理的时间内检验产品，如果产品在合理检验中发现显著瑕疵及不合其用<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;途，或与订单列明产品的种类、规格或数量不符，或客户有与产品相关的任何其它异议，应在交货后一周内给予盛芯电子详细的书面通<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;知并须随附交货单。如果您未给予此类通知，则产品应被最终认为各方面均符合合同规定，不存在任何经合理检验即能发现的瑕疵，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;而您因此被视为已完全接受产品。
                    </p>
                        </td>
                    </tr>
                    <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 如果您接收了损坏的包裹，应对该包裹拍照以确认损坏并在打开包裹前立即通知盛芯电子。</p>
                        </td>
                    </tr>
                    <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0;margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 在您能令盛芯电子合理满意地充分证明产品与合同不符或有瑕疵的情况下，盛芯电子有权在合理的时间内酌情选择替换产品、补发产品<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;或退还就该等产品当时已支付的价款。</p>
                        </td>
                    </tr>
                    <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
				    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;4. 货品损坏或灭失的风险将在货品从盛芯电子之承运人处卸载到您的场所时转移至您。盛芯电子自产品交付后不再对产品的毁损、灭失<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;承担任何责任。</p>
                        </td>
                    </tr>
                    <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                           <p  style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;更多条款内容请见&nbsp;<a href="http://www.iceasy.com/help/index/type/clause" target="_blank" style="color:#0055aa">盛芯电子交易条款</a>，我们建议您定期阅读以获取最新条款信息。</p>
                        </td>
                    </tr>
         </table>
    </td>
</tr>';
		return $mess;
	}
}