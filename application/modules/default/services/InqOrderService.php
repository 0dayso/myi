<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
require_once 'Iceaclib/common/tcpdf/config/lang/eng.php';
require_once 'Iceaclib/common/tcpdf/mytcpdf.php';
class Default_Service_InqOrderService
{

	private $_salesproductModel;
	private $_inqsalesorderModel;
	private $_soaddModel;
	private $_emailService;
	public function __construct() {
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->_salesproductModel = new Default_Model_DbTable_SalesProduct();
		$this->_inqsalesorderModel= new Default_Model_DbTable_InqSalesOrder();
		$this->_soaddModel        = new Default_Model_DbTable_OrderAddress();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
		
		//人民币兑美元汇率
		$rateModel = new Default_Model_DbTable_Rate();
		$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
		$this->_USDTOCNY = $arr['rate_value'];
		$this->config = Zend_Registry::get('config');
	}
	/**
	 * 发送订单成功邮件
	 */
	/*public function sendinqsomail($email,$name,$orderarr)
	{
		//父亲订单
		$so_one = $this->geSoinfo($orderarr['salesnumber']);
		$re = $this->inqsotable($so_one,$email,$name);
		//孩子订单
		$so_son = array();$so_son_str = '';
		if($orderarr['son_salesnumber']){
			$so_son = $this->geSoinfo($orderarr['son_salesnumber']);
			$re = $this->inqsotable($so_son,$email,$name);
		}
		return $re;
	}*/
	public function sendinqsomail($email,$name,$salesnumber){
		$orderarr = $this->geSoinfo($salesnumber);
	
		//详细
		$pordarr = $orderarr['pordarr'];
	
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$name.',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您使用IC易站的在线采购服务！</div>
                                                <div style="height:5px;padding:0; margin:0;font-size:0; line-height:8px ">&nbsp;</div>
                                        		<div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您于&nbsp;<strong>'.date('Y/m/d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].'</strong>已确认收到，为保证您的货期，请您尽快支付货款，我们将在收到您的货款后尽快处理该订单。您也可以进入 <a href="http://www.iceasy.com/center/inqorder" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的订单</b></a> 随时查看订单的处理情况。</div>
                                                <div style="height:5px;padding:0; margin:0;font-size:0; line-height:8px ">&nbsp;</div>
                                        		<div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如有任何不明之处，请根据订单编号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>与我们确认相关细节。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess = $this->getInqOrderTable($orderarr,$pordarr,$hi_mess);
	
		$mess .= $this->getOrderMessTable();
		$fromname = 'IC易站';
		$title    = '您的IC易站订单#：'.$orderarr['salesnumber'].'已确认收到，请支付货款';
	
		$emailarr = $this->_emailService->getEmailAddress('inquiry_order',$orderarr['uid']);
	
		$emailto = array('0'=>$email);
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
		return $this->fun->sendemail($emailto, $mess, $fromname, $title, $emailcc, $emailbcc,array(),$sellinfo);
	}
	/**
	 * 获取订单信息table
	 */
public function getInqOrderTable($orderarr,$pordarr,$hi_mess,$sellinfo=array(),$inqinfo=array()){
		$this->config = Zend_Registry::get('config');
		//取货地址
		$this->delivery_add_sz = $this->config->order->delivery_add_sz;
		$this->delivery_tel_sz = $this->config->order->delivery_tel_sz;
		$this->delivery_workdate_sz = $this->config->order->delivery_workdate_sz;
		$this->delivery_des_sz = $this->config->order->delivery_des_sz;
			
		$this->delivery_add_hk = $this->config->order->delivery_add_hk;
		$this->delivery_tel_hk = $this->config->order->delivery_tel_hk;
		$this->delivery_workdate_hk = $this->config->order->delivery_workdate_hk;
		$this->delivery_des_hk = $this->config->order->delivery_des_hk;
		$statusarr = array('101'=>'<font color="#fd2323">待客户付款</font>',
				'102'=>'<font color="#fd2323">待处理</font>',
				'103'=>'<font color="#fd2323">待客户支付余款</font>',
				'201'=>'<font color="#fd2323">待发货</font>',
				'202'=>'<font color="#03b000">已发货</font>',
				'301'=>'<font color="#03b000">已完成</font>',
				'302'=>'<font color="#03b000">已评价</font>',
				'401'=>'<font color="#fd2323">订单被取消</font>');
		if($orderarr['total'] != $orderarr['down_payment']){
			$paystatus = array('101'=>'<font color="#fd2323">待付款</font>',
				'102'=>'<font color="#03b000">已付定金</font>',
				'103'=>'<font color="#03b000">已付定金</font>',
				'201'=>'<font color="#03b000">已付全款</font>',
				'202'=>'<font color="#03b000">已付全款</font>',
				'301'=>'<font color="#03b000">已付全款</font>',
				'302'=>'<font color="#03b000">已付全款</font>',
				'401'=>'<font color="#03b000">订单被取消</font>');
		}elseif($orderarr['paytype']=='cod'){
			$paystatus = array('101'=>'<font color="#fd2323">待付款</font>',
					'102'=>'<font color="#03b000">待付款</font>',
					'103'=>'<font color="#03b000">待付款</font>',
					'201'=>'<font color="#03b000">待付款</font>',
					'202'=>'<font color="#03b000">待付款</font>',
					'301'=>'<font color="#03b000">待付款</font>',
					'302'=>'<font color="#03b000">待付款</font>',
					'401'=>'<font color="#03b000">订单被取消</font>');
		}else{
			$paystatus = array('101'=>'<font color="#fd2323">待付款</font>',
					'102'=>'<font color="#03b000">已付全款</font>',
					'103'=>'<font color="#03b000">已付全款</font>',
					'201'=>'<font color="#03b000">已付全款</font>',
					'202'=>'<font color="#03b000">已付全款</font>',
					'301'=>'<font color="#03b000">已付全款</font>',
					'302'=>'<font color="#03b000">已付全款</font>',
					'401'=>'<font color="#03b000">订单被取消</font>');
		}
		if($orderarr['paytype']=='transfer')
		{
			$down_payment = number_format($orderarr['down_payment'],DECIMAL);
			$surplus = number_format($orderarr['total']-$orderarr['down_payment'],DECIMAL);
		}
		else{
			$down_payment = '--';
			$surplus = '--';
		}
		$contypeArr = array('1'=>'明细','2'=>'电子元件','3'=>'耗材');
		$paytypearr = array('transfer'=>'银行汇款','online'=>'在线支付','coupon'=>'优惠券兑换','cod'=>'货到付款(COD)','mts'=>'款到发货');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$type = array('spot'=>'现货','order'=>'订货');
		$expressarr = array('1'=>'国内快递','2'=>'公司配送','3'=>'客户自取');
		$unitArr = array('RMB'=>'￥','USD'=>'$','HKD'=>'HK$');
	$mess .= '<tr>
                    <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>

<!-------------------------------------------------------内容------------------------------------------------------->
<!--订单信息-->

<tr valign="top">
    <td valign="bottom"  align="center" height="40">
        <div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">IC易站订单</div>
    </td>
</tr>';
		if($sellinfo){
			$mess .='<!--销售相关-->
<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >       
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;销售相关&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                       <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\'; border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;销售负责人：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['lastname'].$sellinfo['firstname'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\'; border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;部门：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['department'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;手机：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6
									"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['phone'].'</strong></td>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['tel'].'-'.$sellinfo['ext'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ;
                              		">&nbsp;&nbsp;邮箱：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';">&nbsp;&nbsp;<a style="color:#000000;font-family:\'微软雅黑\'" href="mailto:'.$sellinfo['email'].'"><strong>'.$sellinfo['email'].'</strong></a></td>
                            </tr>
                            <!--<tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;是否需要纸质合同：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;是</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;是否需要转账水单：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;是</strong></td>
                            </tr>-->
                      </table>
                    </td>
                </tr>
            </table>
        </td>
        </tr>
        </table>
    </td>
</tr>';}
			if($inqinfo && $inqinfo['oa_rfqnumber']){
				$canshow = false;
				foreach($inqinfo['detaile'] as $k=>$inqdetaile){
					if($inqdetaile['oa_result_price']>0) {$canshow=true;break;}
				}
				if($canshow){
				$mess .='<!--PMSC报价相关-->
     <tr valign="top">
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;PMSC报价&nbsp;&nbsp;&nbsp;</span><span style="color:#03b000">&nbsp;&nbsp;<b>OA BQ#：'.$inqinfo['oa_rfqnumber'].'</b></span>
                	</td>
                </tr>

                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; text-align:center; border-collapse:collapse">  
                    <tr bgcolor="#f3f3f3">
                    	<th width="35" height="30">项次</th>
                        <th width="150" height="30">产品型号</th>
                    	<th width="100">品牌</th>
                        <th width="100">PMSC报价(RMB)</th>
                        <th width="80">有效期</th>
                        <th>报价备注</th>
                        <th width="60">报价专员</th>
                    </tr>';
			if($inqinfo['detaile']){
			  foreach($inqinfo['detaile'] as $k=>$inqdetaile){
			  	if($inqdetaile['oa_result_price']>0){
			     $mess .='<tr bgcolor="#FFFFFF" >
                    	<td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($k+1).'</td>
                        <td height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#0055aa; ">'.$inqdetaile['part_no'].'</strong></td>
                        <td width="100" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$inqdetaile['brand'].'</td>
                        <td width="100" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($inqdetaile['oa_result_price']>0?($unitArr[$orderarr['currency']].' '.$inqdetaile['oa_result_price']):'--').'</strong></td>
                        <td width="100" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($inqdetaile['expiration_time']?date("Y-m-d",$inqdetaile['expiration_time']):'--').'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($inqdetaile['oa_inqd_remark']?$inqdetaile['oa_inqd_remark']:'--').'</td>
                        <td width="100" style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($inqdetaile['oa_pmsc_name']?$inqdetaile['oa_pmsc_name']:'--').'</td>
                        </tr>';
		          }
			    }
			}
		        $mess .='</table>
                </td>
                </tr>
           </table>
    </td>
</tr>';
			}
		}
			
$mess .='<!--订单详情-->
                    		<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >     
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;订单详情&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;订单号#：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['salesnumber'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;订单状态：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$statusarr[$orderarr['status']].'</strong></td>
                            </tr>
                            <tr>                              
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;订单金额：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">'.$orderarr['currency'].'</span> '.number_format($orderarr['total'],DECIMAL).'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;下单时间：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.date('Y/m/d H:i:s',$orderarr['created']).'</strong></td>
                            </tr>';
                            if($orderarr['total'] != $orderarr['down_payment']){
                              $mess .='<tr>                              
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;预付款：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">'.$orderarr['currency'].' </span> '.number_format($orderarr['down_payment'],DECIMAL).'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;剩余款：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">'.$orderarr['currency'].' </span> '.number_format($orderarr['total']-$orderarr['down_payment'],DECIMAL).'</strong></td>
                              </tr>';
                           }
                           $mess .='
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;付款方式：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$paytypearr[$orderarr['paytype']].'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;付款状态：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($paystatus[$orderarr['status']]).'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;订单备注：</td>
                              <td  colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" ><strong style="color:#000000;font-family:\'微软雅黑\'">'.nl2br($orderarr['remark']).'</strong></td></tr></table></td>
                            </tr>
                      </table>
                    </td>
                </tr>
            </table>
        </td>
        </tr>
        </table>
    </td>
</tr>

<tr>
    <td valign="top" align="left" >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0;border-collapse:collapse" >
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">
                    <tr bgcolor="#f3f3f3">
                        <th width="35" height="30">项次</th>
                        <th>产品型号</th>
                        <th>客户物料号</th>
                        <th>品牌</th>
                        <th width="30">单位</th>
                        <th>数量</th>
                        <th>单价('.$orderarr['currency'].')</th>
                        <th>金额('.$orderarr['currency'].')</th>
                        <th>需求时间</th>
                    </tr>';
        if($pordarr){
		foreach($pordarr as $k=>$v){
			$mess .='<tr bgcolor="#FFFFFF" >
                        <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($k+1).'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['customer_material'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['brand'].'</td>
                        <td width="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">个</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['buynum'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.$unitArr[$orderarr['currency']].' '.($v['buyprice']).'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.$unitArr[$orderarr['currency']].' '.($v['buyprice']*$v['buynum']).'</strong></td>
                        <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($v['needs_time']?date('Y/m/d',$v['needs_time']):'--').'</td>
                    </tr>';
		}
        }
		$mess .='<tr  bgcolor="#FFFFFF">
                        <td height="30" colspan="9" align="right" style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">
			   商品金额：<strong style="color:#fd2323; margin-left:5px;">'.$unitArr[$orderarr['currency']].' '.number_format($orderarr['total']-$orderarr['freight'],DECIMAL).'</strong> 
              <strong style="font-size:16px; color:#000000">+</strong> 
                            运费及包装费：<strong style="color:#fd2323; margin-left:5px;">'.$unitArr[$orderarr['currency']].' '.number_format($orderarr['freight'],DECIMAL).'</strong> 
				<strong style="font-size:16px; color:#000000">=</strong>
                            合计：<b style="color:#fd2323; margin:0 5px;font-size:14px;"><span style="color:#000000">'.$orderarr['currency'].'</span> '.number_format($orderarr['total'],DECIMAL).'</b>&nbsp;&nbsp;
                        </td>
                    </tr>
            </table>
           
        </td>
                
                
            </tr>
        </table>
        </td>     
    </td>
</tr>
 
<!--收货信息-->
<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >      
        <tr>
           <td bgcolor="#f9f9f9">';
            if(in_array($orderarr['delivery_type'],array(1,2))){
				$mess .='<table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;收货信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['companyname'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;收货人：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['name'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;货运方式：</td>
                              <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$expressarr[$orderarr['delivery_type']].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;手机：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['mobile'].'</strong></td>
                            </tr>
                            
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;固定电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['tel'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;邮编：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['zipcode'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;详细地址：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$this->fun->createAddress($orderarr['province'],$orderarr['city'],$orderarr['area'],$orderarr['address']).'</strong></td>
							  <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;入仓号：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['warehousing'].'</strong></td>
                            </tr>
                      </table>
                    </td>
                </tr>
            </table>';
		   }elseif($orderarr['delivery_type']==3){
		   	$mess .='<table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;收货信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                              <td width="320" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 "><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['companyname'].'</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                            </tr>
                            <tr>
                              <td width="80" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;货运方式：</td>
                              <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;用户上门自取</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;取货时间：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['delivery_place']=='SZ'?$this->delivery_workdate_sz:$this->delivery_workdate_hk).'</strong></td>
                            </tr>
		   	
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;取货地址：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['delivery_place']=='SZ'?$this->delivery_add_sz:$this->delivery_add_hk).'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['delivery_place']=='SZ'?$this->delivery_tel_sz:$this->delivery_tel_hk).'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;备注：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['delivery_place']=='SZ'?$this->delivery_des_sz:$this->delivery_des_hk).'</strong></td>
                            </tr>
                      </table>
                    </td>
                </tr>
            </table>';
		   }elseif($orderarr['delivery_type']==4){
		   	$mess .='<table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;收货信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                              <td width="320" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 "><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['companyname'].'</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;快递商：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['express_name'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="80" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;货运方式：</td>
                              <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;客户提供账号</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;快递账号：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['express_account']).'</strong></td>
                            </tr>
		   	
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;收货人：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['name']).'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系方式：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['tel']).'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;收货地址：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($orderarr['address']).'</strong></td>
                            </tr>
                      </table>
                    </td>
                </tr>
            </table>';
		   }         		                         		              		
        $mess .='</td>
        </tr>
        </table>
    </td>
</tr>';
        if($orderarr['invoiceid']>0){
        	$mess .='<!--发票信息-->
<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >      
        <tr>
           <td bgcolor="#f9f9f9">
        	<table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;发票信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >';
        	        if($orderarr['itype']==1){
        	        	$mess .='<table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;发票类型：</td>
                               <td colspan="3" width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;普通发票</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;发票抬头：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['iname'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;发票内容：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$contypeArr[$orderarr['contype']].'</strong></td>
                            </tr>';
        	        }else{
        	        	$mess .='<table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;发票类型：</td>
                              <td colspan="3" width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;增值税发票(17%)</strong></td>
                             </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;单位名称：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['iname'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;纳税人识别号：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['identifier'].'</strong></td>
                            </tr>
                            <tr>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;注册地址：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['regaddress'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;注册电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['regphone'].'</strong></td>
                            </tr>		
                            <tr>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;开户银行：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['bank'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;银行账户：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['account'].'</strong></td>
                            </tr>';
        	        }
        	        if(in_array($orderarr['delivery_type'],array(1,2))){
        	        	if($orderarr['addressid']!=$orderarr['invoiceaddress'])
        	        	{
        	        		$mess .='<tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;收发票地址：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$this->fun->createAddress($orderarr['province_i'],$orderarr['city_i'],$orderarr['area_i'],$orderarr['address_i']).' ; '.$orderarr['name_i'].' ; '.$orderarr['tel_i'].'  '.$orderarr['mobile_i'].'</strong></td>
                            </tr>';
        	        	}else{
        	        		$mess .='<tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;收发票地址：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$this->fun->createAddress($orderarr['province_i'],$orderarr['city_i'],$orderarr['area_i'],$orderarr['address_i']).'</strong></td>
                            </tr>';
        	        	}
        	        }
                    $mess .='</table></td>
                </tr>
            </table>
        </td>
        </tr>
        </table>
    </td>
</tr>';
       }
		
		return $mess;
	}
	/**
	 * 获取订单说明
	 */
	public function getOrderMessTable(){
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
                        	<p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;1. 此邮件仅表明IC易站已经收到您的订单，只有当IC易站收到您的全款或按约定支付的定金时，IC易站和您之间的订购合同才成立。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 如果您选择在线支付，须在提交订单后24小时内完成支付，如选择转账，须在提交订单后2个工作日内完成汇款，否则订单将被取<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;消。与IC易站之间的合同成立后，您有义务完成与IC易站的交易，但法律或本用户协议禁止的交易除外，未经IC易站书面同意，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;任何订单都不可被取消。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 如在人民币与美元汇率发生重大波动和/或原厂价格有所调整，和/或其他不可控因素引发价格变化时，IC易站保留改变已确定价格<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;的权利。最终价格以收到货款或定金当天的产品价格为准。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;4. 货物交期以IC易站合同中的标准交期为准。您提交的需求时间仅作为IC易站为您备货的参考日期。如果因原厂原因和/或不可控的<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;突发状况（应包括但不限于政府行为、战争、火灾、广泛流行性疾病、爆炸、洪水、灾害性气候、进出口管制或禁运、劳动争议、<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;货品或劳动力无法得到供给等）造成交期延后，IC易站不应就该等迟延履行或未能履行而承担任何形式的责任或被视作违约行为，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;且您不得因此而取消订单。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;5. 产品可能受限于中国和货品可能的来源国的进出口管制法律、限制、条例和命令。您须同意遵守中国和其他外国机构或当局的所有应<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;适用的进出口法律、限制和条例，且应自担费用获取任何许可并遵守任何可适用的中国以及产品销往国的进出口条例。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                            <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;6. 在任何情况下严格禁止将产品使用于杀伤人员地雷、或用于与生物类、化学类或核类武器或运送该类武器的导弹相关的任何用途。产<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;品不得被用于航天或航空飞行器或其他空中运输应用、生命维持或供给设备、外科移植设备、或如货品发生故障将有理由认为会导致<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;人身伤害、死亡、环境破坏或财产严重损失的任何其他用途。同时严格禁止在此类设备、系统或应用中使用或加入任何产品。IC易站<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;在法律允许的最大限度内不对因产品用于该等用途而直接或间接地引致的任何损失和损害承担任何责任。</p>
                        </td>
                    </tr>
                     <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
                    <tr>
                    	<td>
                           <p  style=" padding:3px 0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;更多条款内容请见&nbsp;<a href="http://www.iceasy.com/help/index/type/clause" style="color:#0055aa">IC易站交易条款</a>，我们建议您定期阅读以获取最新条款信息。</p>
                        </td>
                    </tr>
         </table>
    </td>
</tr>';
		return $mess;
	}
	/**
	 * 发邮件给销售
	 */
	public function sendordermailsell($salesnumber){
		$orderarr = $this->geSoinfo($salesnumber);
		//销售信息
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
		$pordarr = $orderarr['pordarr'];
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有客户新提交了在线订单，订单号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>请在24小时之内跟进客户付款情况。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">详细资料和订单信息请登录 <a href="http://www.iceasy.com/icwebadmin/OrInqo"  target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>IC易站后台</b></a> 查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess  = $this->getInqOrderTable($orderarr,$pordarr,$hi_mess);
		$fromname = 'IC易站';
		$title    = '客户新建订单，订单号#：'.$orderarr['salesnumber'].'，请跟进付款 ';
	
		$emailarr = $this->_emailService->getEmailAddress('inquiry_order',$orderarr['uid']);
		$emailto = array('0'=>$sellinfo['email']);
		//如果有抄送人
		$staffService = new Icwebadmin_Service_StaffService();
		$emailcc = $staffService->mailtostaff($sellinfo['mail_to']);
		$emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailcc,$emailarr['cc']);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	}
	public function completemail($email,$name,$salesnumber){
		$orderarr = $this->geSoinfo($salesnumber);
		
		//详细
		$pordarr = $orderarr['pordarr'];
		
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$name.',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                        		 <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您在IC易站的订单<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;"> #'.$orderarr['salesnumber'].' </strong>已完成。再次感谢您对IC易站的支持！</div>
                                        		</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess = $this->getInqOrderTable($orderarr,$pordarr,$hi_mess);
		
		$fromname = 'IC易站';
		$title    = '您的IC易站订单#：'.$orderarr['salesnumber'].'已完成';
		
		$emailarr = $this->_emailService->getEmailAddress('inquiry_order',$orderarr['uid']);
		
		$emailto = array('0'=>$email);
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
		return $this->fun->sendemail($emailto, $mess, $fromname, $title, $emailcc, $emailbcc,array(),$sellinfo);
		
	}
	/**
	 * 一个用户最多添加maxnum个还没付款订单
	*/
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM inq_sales_order WHERE uid=:uidtmp AND status=101";
		$all = $this->_inqsalesorderModel->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/**
	 * 添加一条询价订单
	 */
	public function addinqOrder($buypart,$multiple,$addre,$addinfo,$invoiceaddre,$freight,$down_payment,$pricetotal,$shipoder)
	{
		//订单号
		$salesnumber = '1'.(intval(date('Y'))%10).date('m').date('d').substr(microtime(),2,4).'-'.substr(time(),-5);
		$addinfo['ship_salesnumber'] = $shipoder['salesnumber']?$shipoder['salesnumber']:'';
		//记录收货地址
		if(!empty($addre)){
		   $soadd_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
				'salesnumber'=>$salesnumber,
				'name'       =>$addre['name'],
				'companyname'=>$addre['companyname'],
				'province'   =>$addre['province'],
				'city'       =>$addre['city'],
				'area'       =>$addre['area'],
				'address'    =>$addre['address'],
				'mobile'     =>$addre['mobile'],
				'tel'        =>$addre['tel'],
				'zipcode'    =>$addre['zipcode'],
		   		'warehousing'    =>$addre['warehousing'],
		   		'express_name'=>$addre['express_name'],
		   		'express_account'=>$addre['express_account'],
				'created'    =>time());
		    $soaddid = $this->_soaddModel->addData($soadd_data);
		}elseif($shipoder){
			$soaddid = $shipoder['addressid'];
		}
		//记录发票地址
		if(!empty($invoiceaddre)){
			$invoice_data = array('uid'=>$_SESSION['userInfo']['uidSession'],
					'salesnumber'=>$salesnumber,
					'name'=>$invoiceaddre['name'],
					'companyname'=>$invoiceaddre['companyname'],
					'province'=>$invoiceaddre['province'],
					'city'=>$invoiceaddre['city'],
					'area'=>$invoiceaddre['area'],
					'address'=>$invoiceaddre['address'],
					'mobile'=>$invoiceaddre['mobile'],
					'tel'=>$invoiceaddre['tel'],
					'zipcode'=>$invoiceaddre['zipcode'],
					'created'=>time());
			$invoiceaddid = $this->_soaddModel->addData($invoice_data);
		}elseif($shipoder){
			$invoiceaddid = $shipoder['addressid'];
		}elseif($soaddid){
			$invoiceaddid = $soaddid;
		}
		//多个part_no
		$part_nos = '';$j=0;$itemdata = array();
		$quantitys = 0;$delivery_about_time = array();
		foreach($buypart as $item){
			if($item['lead_time']) $delivery_about_time[] = $item['lead_time'];
			$itemdata[]=array('salesnumber'=>$salesnumber,
					'prod_id' =>$item['part_id'],
					'needs_time'=>$addinfo['needs_time'],
					'lead_time'=>'',
					'shipments'=>$item['shipments'],
					'sz_cover'=>$item['sz_cover'],
					'hk_cover'=>$item['hk_cover'],
					'bpp_cover'=>$item['bpp_cover'],
					'inqdet_id'=>$item['id'],
					'part_no' =>$item['part_no'],
					'customer_material'=>$item['customer_material'],
					'brand'   =>$item['brand'],
					'buynum'  =>$item['buynum'],
					'buyprice'=>$item['result_price'],
					'needs_time'=>$item['needs_time'],
					'staged'=>$item['staged'],
					'created' =>time(),
					'type'=>2);
			if($j==0) $part_nos = $item['part_no'];
			else $part_nos .= ','.$item['part_no'];
			$quantitys +=$item['pmpq']*$multiple[$item['id']];
			$j++;
		}
		//排序，已最长交货期为订单大概交货期 ;shipments 发货种类，spot现货立即发送，order订货
		sort($delivery_about_time);
		if($addinfo['shipments']=='spot'){
			$delivery_about_time_str = '';
		}elseif($addinfo['shipments']=='order'){
			$delivery_about_time_str = $delivery_about_time[0];
		}
		$addinfo['addressid']      = $soaddid;
		$addinfo['invoiceaddress'] = $invoiceaddid;
		$addinfo['freight'] = $freight;
		$addinfo['quantitys'] = $quantitys;
		$addinfo['items'] =  count($buypart); 
		$addinfo['down_payment'] = ($pricetotal>$down_payment?$down_payment:$pricetotal);
		$addinfo['total'] = $pricetotal;
		$addinfo['delivery_about_time'] = $delivery_about_time_str;
		$addinfo['partnos'] = $part_nos;

		$orderdata = array('uid'=>$_SESSION['userInfo']['uidSession'],
				'salesnumber'   =>$salesnumber,
				'ship_salesnumber'=>$addinfo['ship_salesnumber'],
				'son_salesnumber'=>$addinfo['son_salesnumber'],
				'son_so'        =>$addinfo['son_so'],
				'inquiry_id'    =>$addinfo['inqid'],
				'delivery_type' =>$addinfo['delivery_type'],
				'addressid'     =>$addinfo['addressid'],
				'invoiceaddress'=>$addinfo['invoiceaddress'],
				'paytype'       =>$addinfo['paymenttype'],
				'percentage'    =>$addinfo['percentage'],
				'freight'       =>$addinfo['freight'],
				'quantitys'     =>$addinfo['quantitys'],
				'items'         =>$addinfo['items'],
				'down_payment'  =>$addinfo['down_payment'],
				'total'         =>$addinfo['total'],
				'shipments'     =>$addinfo['shipments'],
				'needs_time'    =>$addinfo['needs_time'],
				'remark'        =>$addinfo['remark'],
				'delivery_place'=>$addinfo['delivery'],
				'delivery_about_time'=>$addinfo['delivery_about_time'],
				'currency'      =>$addinfo['currency'],
				'consignee'     =>$addre['name'],
				'partnos'       =>$addinfo['partnos'],
				'paper_contract'=>$addinfo['paper_contract'],
				'status'        =>101,
				'available'     =>1,
				'ip'            =>$this->fun->getIp(),
				'created'=>time(),
				'modified'=>time());
		if(!empty($addinfo['needinvoice']))$orderdata['invoiceid']=$addinfo['invoiceid'];
		//订单记录
		$orderid = $this->_inqsalesorderModel->addData($orderdata);
		//记录产品销售详细
		$this->_salesproductModel->addDatas($itemdata);
		if($orderid) return array("orderid"=>$orderid,"salesnumber"=>$salesnumber); 
	    else return false;
	}
	/**
	 * 获取用户的所有询价订单，包括子订单
	 */
	public function getAllSo($offset,$perpage,$typestr)
	{
		$sqlstr = "SELECT * FROM inq_sales_order 
		WHERE available!=0 AND uid=:uidtmp {$typestr} ORDER BY status ASC,back_status ASC,created DESC LIMIT $offset,$perpage";
		$orderarr = $this->_inqsalesorderModel->getBySql($sqlstr, $this->sqlarr);
		for($i=0;$i<count($orderarr);$i++)
		{
			if(!empty($orderarr[$i]['son_salesnumber'])){
			$orderarr[$i]['son_so'] = $this->_inqsalesorderModel->getRowByWhere("salesnumber='{$orderarr[$i]['son_salesnumber']}'");
		   }
		}
		return $orderarr;
	}
	/**
	 * 根据订单号获取订单信息（包括子订单）
	 */
	public function getinqOrder($sonum){
		$sqlstr ="SELECT so.*,
		p.province,c.city,e.area,
		a.name,a.address,a.mobile,a.tel
    	FROM inq_sales_order as so 
		LEFT JOIN order_address as a ON so.addressid=a.id 
		LEFT JOIN province as p ON a.province=p.provinceid 
		LEFT JOIN city as c ON a.city=c.cityid 
		LEFT JOIN area as e ON a.area = e.areaid
    	WHERE so.salesnumber=:sonum AND so.uid=:uidtmp AND so.available='1'";
		$orderarr = $this->_inqsalesorderModel->getByOneSql($sqlstr, array('sonum'=>$sonum,'uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($orderarr['son_salesnumber'])){
			$orderarr['son_so'] = $this->_inqsalesorderModel->getRowByWhere("salesnumber='{$orderarr['son_salesnumber']}'");
		}
		return $orderarr;
	}
	/**
	 * 询价订单总数
	 */
	public function inqSoNum()
	{
		$sqlstr = "SELECT count(id) as allnum FROM inq_sales_order WHERE available!=0  AND uid=:uidtmp";
		$allnumarr = $this->_inqsalesorderModel->getBySql($sqlstr,$this->sqlarr);
		return $allnumarr[0]['allnum'];
	}
	/**
	 * 获取count()行数
	 */
	public function getRowNum($str)
	{
		$sqlstr = "SELECT count(id) as num FROM inq_sales_order WHERE available!=0 AND uid=:uidtmp {$str} ";
		$allrel = $this->_inqsalesorderModel->getByOneSql($sqlstr,$this->sqlarr);
		return $allrel['num'];
	}
	/**
	 * 更新询价为成功下单状态
	 */
	public function restoreInq($salesnumber){
		$allrel = $this->_inqsalesorderModel->getRowByWhere("salesnumber='{$salesnumber}'");
		if($allrel){
			$inqModel   = new Default_Model_DbTable_Inquiry();
			$re = $inqModel->update(array('status'=>6), "id='".$allrel['inquiry_id']."'");
			if($re) return true;
			else return false;
		}else return false;
	}
	/**
	 * 增加用户获得的积分
	 */
	function addScore($salesnumber,$adduid='')
	{
		$uid = $adduid?$adduid:$_SESSION['userInfo']['uidSession'];
		$sqlstr = "SELECT total,currency FROM inq_sales_order WHERE available!=0 AND status=301 AND salesnumber='{$salesnumber}' AND uid='{$uid}'";
	 	$rearr  = $this->_inqsalesorderModel->getByOneSql($sqlstr,$this->sqlarr);
	    $multiplier = $rearr['total'];
		if($rearr){
			if($rearr['currency']=='USD'){
				$multiplier = 6*$rearr['total'];
			}
		}
		$scoreservice = new Default_Service_ScoreService();
		return $scoreservice->addScore('inqorder',$multiplier,$salesnumber,$uid);
		//更新积分
		/*$udstr = "UPDATE user_profile SET score =score + {$score} WHERE uid=:uidtmp";
		$re = $this->_inqsalesorderModel->updateBySql($udstr,$this->sqlarr);
		if($re) return $score;
		else return 0;*/
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
	 * 获取so信息
	 */
	public function geSoinfo($salesnumber,$notuser=''){
		if($notuser==1){
			$wheresql = '';
			$wherearray = array('sonum'=>$salesnumber);
		}else{
			$wheresql = 'AND so.uid=:uidtmp';
			$wherearray = array('sonum'=>$salesnumber,'uidtmp'=>$_SESSION['userInfo']['uidSession']);
		}
		$sqlstr ="SELECT so.*,
        iv.type as ivtype,
		u.uname,u.email,up.companyname as ucompanyname,
        p.province,c.city,e.area,
    	a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.express_account,a.express_name,a.warehousing,
		pi.province as province_i,ci.city as city_i,ei.area as area_i,
		ai.name as name_i,ai.address as address_i,ai.zipcode as zipcode_i,ai.mobile as mobile_i,ai.tel as tel_i,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name,
		ia.addressid as iaaddressid,ia.invoiceid as iainvoiceid,ia.status as iastatus,ia.remark as iaremark
    	FROM inq_sales_order as so
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
		LEFT JOIN invoice_apply as ia ON ia.salesnumber=so.salesnumber
    	WHERE so.salesnumber=:sonum {$wheresql} AND so.available='1'";
		$orderarr = $this->_inqsalesorderModel->getByOneSql($sqlstr, $wherearray);
		if(!empty($orderarr)){
			$pordarr = $this->_salesproductModel->getAllByWhere("salesnumber='".$salesnumber."'");
			if(empty($pordarr)) return false;
			else{
				$orderarr['pordarr'] = $pordarr;
				return $orderarr;
			}
		}else return false;
	}
	/**
	 * 询价单
	 */
	public function inqpdf($inquiry,$user,$itemsArray,$multiple){
		$deliveryArr = array('SZ'=>'国内','HK'=>'港币');
		$currencyArr = array('RMB'=>'￥','USD'=>'$','HKD'=>'HK$');
		$unit = $currencyArr[$inquiry['currency']];
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'iceasy.com',
				'Reason' => 'Inquiry',
				'ContactInfo' => 'http://www.iceasy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
		
		$hight = 5;
		// Log
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );

		//标题
		$pdf->SetFont ( 'droidsansfallback', '', 20 );
		$pdf->Cell (280, 8, '报价单' ,0,0,'C');
		
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell (225, 8, ' 询价编号：'.$inquiry['inq_number'],0,0,"R");
		$pdf->Cell (50, 8, ' 询价时间：'.date('Y-m-d H:i:s',$inquiry['created']),0,0);
		
		//公司名
		$pdf->Ln ( );
		$pdf->Cell ( 25, $hight, '报价方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '询价方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $user['companyname'], 'TR', 1 );
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['province'].$user['city'].$user['area'].$user['address'], 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight,  $user['lastname'].$user['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['st_tel'], 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $user['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '手机：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $user['st_phone'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '手机：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $user['mobile'], 'RB', 1, 1);
		
		$pdf->Ln ();
		//详细
		// Header
		$w = array (15,50,30,30,30,30,30,30,30 );
		$header = array ('序号','Part No.','品牌','标准交期','最少起订量','购买数量',($inquiry['currency']=='RMB'?'含税':'').'单价('.$inquiry['currency'].')','小计','有效期');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] ,'LRT', 0, 'C' );
		}
		$pdf->Ln ();
		// Data
		$tmp=0;$pricetotal=0;
		foreach($inquiry['detaile'] as $item) {
			if(in_array($item['id'],$itemsArray) && $multiple[$item['id']]>0 && $item['expiration_time'] >= time()){
				$tmp++;
				$buynum = $item['pmpq']*$multiple[$item['id']];
				$pricetmp = $buynum*$item['result_price'];
				$pricetotal += $pricetmp;
					
				$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0, 'C' );

				$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [2], $hight, $item['brand'], 'LRTB', 0, 'C' );
					
				$pdf->Cell ( $w [3], $hight, $item['lead_time'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [4], $hight, $item['pmpq'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [5], $hight, $buynum, 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [6], $hight, $unit.$item['result_price'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [7], $hight, $unit.($pricetmp), 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [8], $hight, date('Y/m/d',$item['expiration_time']), 'LRTB', 1, 'C' );
			}
		}
		$pdf->SetFillColor (255, 255, 255 );
		$pdf->SetTextColor (0);
		$mate_tmp  = '交货地：'.$deliveryArr[$inquiry['delivery']].'    ';
		$mate_tmp .= '交易货币：'.$inquiry['currency'].'    ';
		$mate_tmp .= '金额总计：'.$unit.number_format($pricetotal,DECIMAL).' ' ;
		$pdf->Cell ( 275, $hight, $mate_tmp, 'LTRB', 1, 'R' );
		
	    //重要声明
		$pdf->Ln ();

		$html = '<font color="#FF0000">※※ 重要声明 ※※</font><br/>
		 1. 如果以人民币结算, 则报价为含税价格。<br/>			
         2. 报价单中的标准交期仅供参考，实际交期应在您下单之后，以原厂反馈的交期为准。<br/>								
         3. 报价默认有效期为一个月，具体有效期请以报价单中的日期为准。<br/>
		 4. 如人民币与美元汇率发生重大波动和/或原厂价格有所调整，和/或其他不可控因素引发价格变化时，IC易站保留改变已确定价格的权利。下单时的最终价格以下单当天的产品价格为准。	<br/>								
         5. 未尽事项请访问 <a href="http://www.iceasy.com" target="_blank">www.iceasy.com</a> 查看IC易站交易条款。';
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output ();
		exit ();
	}
	/**
	 * 下单后的询价单
	 */
	public function inqorderpdf($orderarr,$user,$inquiry){
		//print_r($orderarr);
		$deliveryArr = array('SZ'=>'国内','HK'=>'港币');
		$currencyArr = array('RMB'=>'￥','USD'=>'$','HKD'=>'HK$');
		$unit = $currencyArr[$orderarr['currency']];
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'iceasy.com',
				'Reason' => 'Inquiry',
				'ContactInfo' => 'http://www.iceasy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
		
		$hight = 5;
		// Log
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );

		//标题
		$pdf->SetFont ( 'droidsansfallback', '', 20 );
		$pdf->Cell (280, 8, '报价单' ,0,0,'C');
		
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell (230, 8, ' 询价编号：'.$inquiry['inq_number'],0,0,"R");
		$pdf->Cell (45, 8, ' 订单编号：'.$orderarr['salesnumber'],0,0,"R");
		//公司名
		$pdf->Ln ( );
		$pdf->Cell ( 25, $hight, '报价方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '询价方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $user['companyname'], 'TR', 1 );
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['province'].$user['city'].$user['area'].$user['address'], 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight,  $user['lastname'].$user['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $user['st_tel'], 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $user['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '手机：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $user['st_phone'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '手机：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $user['mobile'], 'RB', 1, 1);
		
		$pdf->Ln ();
		//详细
		// Header
		$w = array (15,40,40,30,30,30,30,30,30);
		$header = array ('序号','Part No.','客户物料号','品牌','标准交期','最少起订量','购买数量',($orderarr['currency']=='RMB'?'含税':'').'单价('.$orderarr['currency'].')','小计');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] ,'LRT', 0, 'C' );
		}
		$pdf->Ln ();
		// Data
		$pricetotal=0;
		//echo '<pre>';
		//print_r($orderarr);
		//print_r($inquiry);
		foreach($orderarr['pordarr'] as $key=>$item) {
				$pdf->Cell ( $w [0], $hight, ($key+1), 'LRTB', 0, 'C' );

				$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0, 'C' );
				
				$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0, 'C' );
				
				$pdf->Cell ( $w [3], $hight, $item['brand'], 'LRTB', 0, 'C' );
					
				$pdf->Cell ( $w [4], $hight, $item['lead_time'], 'LRTB', 0, 'C' );
				$pmpq='';
				foreach($inquiry['detaile'] as $inqd){
				    if($inqd['part_id']==$item['prod_id']){
				    	$pmpq = $inqd['pmpq'];
				    }
				}
				$pdf->Cell ( $w [5], $hight, $pmpq, 'LRTB', 0, 'C' );
				
				$pdf->Cell ( $w [6], $hight, $item['buynum'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [7], $hight, $unit.$item['buyprice'], 'LRTB', 0, 'C' );
		
				$pdf->Cell ( $w [8], $hight, $unit.($item['buynum']*$item['buyprice']), 'LRTB', 1, 'C' );

		}
		$pdf->SetFillColor (255, 255, 255 );
		$pdf->SetTextColor (0);
		$mate_tmp  = '交货地：'.$deliveryArr[$orderarr['delivery_place']].'    ';
		$mate_tmp .= '交易货币：'.$orderarr['currency'].'    ';
		$mate_tmp .= '运费及包装费：'.$unit.$orderarr['freight'].'    ';
		$mate_tmp .= '总计：'.$unit.number_format($orderarr['total'],DECIMAL).' ' ;
		$pdf->Cell ( 275, $hight, $mate_tmp, 'LTRB', 1, 'R' );
		
	    //重要声明
		$pdf->Ln ();

		$html = '<font color="#FF0000">※※ 重要声明 ※※</font><br/>
		 1. 如果以人民币结算, 则报价为含税价格。<br/>			
         2. 报价单中的标准交期仅供参考，实际交期应在您下单之后，以原厂反馈的交期为准。<br/>								
		 3. 如人民币与美元汇率发生重大波动和/或原厂价格有所调整，和/或其他不可控因素引发价格变化时，IC易站保留改变已确定价格的权利。下单时的最终价格以下单当天的产品价格为准。	<br/>								
         4. 未尽事项请访问 <a href="http://www.iceasy.com" target="_blank">www.iceasy.com</a> 查看IC易站交易条款。';
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output (ORDER_INQPDF.md5('inq'.$orderarr['salesnumber']).'.pdf','F');
		return true;
	}
	/**
	 * 获取能合并发货的订单
	 */
	public function getInqOrderShipments($delivery,$uid='')
	{
		if(!$uid) $uid = $_SESSION['userInfo']['uidSession'];
		//允许7天内下单的订单可以合并
		$ntime = time()-24*3600*7;
		$sqlstr ="SELECT so.*,
		p.province,c.city,e.area,
		a.name,a.address,a.mobile,a.tel
    	FROM inq_sales_order as so
		LEFT JOIN order_address as a ON so.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid
    	WHERE so.uid=:uidtmp AND so.delivery_place='$delivery' 
    	AND so.available='1' AND so.delivery_type IN ('1','2')
		AND so.status IN ('102','103','201') AND so.back_status IN ('201','202','301')
		AND so.created >= '$ntime' AND (so.ship_salesnumber='' or so.ship_salesnumber IS NULL)";
		return $this->_inqsalesorderModel->getBySql($sqlstr, array('uidtmp'=>$uid));
	}
	/**
	 * 获取能合并发货的订单
	 */
	public function getOneInqOrderShipments($salesnumber)
	{
		//允许3天内下单的订单可以合并
		$ntime = time()-24*3600*3;
		$sqlstr ="SELECT so.*,
		p.province,c.city,e.area,
		a.name,a.address,a.mobile,a.tel
		FROM inq_sales_order as so
		LEFT JOIN order_address as a ON so.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid
		WHERE so.salesnumber='$salesnumber' AND so.available='1'
		AND so.status IN ('102','103','201') AND so.back_status IN ('201','202','301')
		AND so.created >= '$ntime'  AND so.uid=:uidtmp AND (so.ship_salesnumber='' or so.ship_salesnumber IS NULL)";
		return $this->_inqsalesorderModel->getByOneSql($sqlstr, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
	}
	/**
	 * 国内合同
	 */
	public function szContract_back($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/iceac_logo.jpg', 10, 10, 12, 10 );
		$pdf->Image ( 'images/default/logo.jpg', 23, 10, 70, 11 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (280, 8, '销售合同' ,0,0,'C');
	
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 165, 8, '现货查询 400-626-1616');
		$pdf->Cell ( 50, 8, ' 订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y-m-d H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, ($userinfo['companyname']?$userinfo['companyname']:$userinfo['uname']), 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight,  '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight,  $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight,  '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_sz.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (10,40,70,20,15,20,25,20,20,20,15);
		else $w = array (15,40,40,25,15,20,25,20,20,25,30);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].'含税)','金额','需求时间','标准货期','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) {return false;};
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight, $item['brand'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight,'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight,  $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight, ($item['lead_time']?$item['lead_time']:''), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [10], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight,  $totalstr ,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['itype']==2){
			$title4='四.发票类型： [√]17%增值税票    [  ]普通发票   [  ]不需要发票 ';
		}elseif($soarray['itype']==1){
			$title4='四.发票类型： [  ]17%增值税票    [√]普通发票   [  ]不需要发票 ';
		}else{
			$title4='四.发票类型： [  ]17%增值税票    [  ]普通发票   [√]不需要发票 ';
		}
		$pdf->Cell (200,$hight, $title4,'', 1,1);
	
		if($soarray['exp_paytype']==1 || $soarray['freight']<=0){
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [√]供方付费   [  ]需方付费。';
		}else{
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [  ]供方付费   [√]需方付费。';
		}
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		$pdf->Cell ( 4, $hight, "");
		$pdf->Cell (45,$hight,  '交(提)货信息： （1）公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (26, $hight, "");
		$pdf->Cell (17,$hight, '（2）地址：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 26, $hight, "");
		$pdf->Cell (40,$hight, '（3）联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='六.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
	
	
		$title7='七.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight, $title7 ,'', 1,1);
	
		$title8='八.本合同一式贰份,供方壹份,需方壹份,本合同不可修改不可撤销,自双方签字盖章之日起生效.';
		$pdf->Cell (200,$hight,  $title8 ,'', 1,1);
			
		$title9='九.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
			
		$title10='十.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title10='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
	
		$title11='十一.供方银行信息：';
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$title11='开户银行：'.BANK_NAME;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$title11='账号：'.BANK_ACCOUNT.'              增值税纳税识别号：'.VAT_NUMBER;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	/**
	 * 香港合同
	 */
	public function hkContract_back($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/iceac_logo.jpg', 10, 10, 12, 10 );
		$pdf->Image ( 'images/default/logo.jpg', 23, 10, 70, 11 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (280, 8, '销售合同' ,0,0,'C');
	
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 165, 8, '现货查询 400-626-1616');
		$pdf->Cell ( 50, 8, ' 订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y-m-d H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight,'供方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $this->config->general->contract_com_hk, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['companyname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_hk.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (15,40,70,25,25,20,20,20,20,20,30);
		else $w = array (15,40,40,30,25,25,25,25,25,25,35);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].')','金额','需求时间','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) $this->_redirect('/center/inqorder');
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight, $item['brand'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight, "PCS", 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight, $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight,($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
	
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr ,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['delivery_type']==2){
			$title4='四.交付方式：[√]Local delivery HK  [  ]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}elseif($soarray['delivery_type']==3){
			$title4='四.交付方式：[  ]Local delivery HK  [√]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}else{
			$title4='四.交付方式：[  ]Local delivery HK  [  ]FOB HK  [√]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
	
		$pdf->Cell ( 4, $hight, "");
		$pdf->Cell (45,$hight, '交(提)货信息： （1）公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight,  $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (26, $hight, "");
		$pdf->Cell (17,$hight, '（2）地址：','', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 26, $hight, "");
		$pdf->Cell (40,$hight, '（3）联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='五.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
		$title6='六.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
	
		$title7='七.本合同一式贰份,供方壹份,需方壹份,自双方签字盖章之日起生效.若需修改，需签约双方确认。';
		$pdf->Cell (200,$hight,  $title7 ,'', 1,1);
	
		$title8='八.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title8 ,'', 1,1);
			
		$title9='九.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title9='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
	
	
		$title10='十.供方银行信息：Bank Name:'.BANK_HK_NAME;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$title10='Account No: '.BANK_HK_ACCOUNT.'              Swift Code: '.SWIFT_CODE_HK;
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$title10='Bank Address: '.BANK_ADDRESS_HK;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	
	/**
	 * 数字合同 ，国内
	 */
	public function szDigitalContract_back($soarray,$userinfo)
	{
		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		// create new PDF document
	
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// ---------------------------------------------------------
	
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		//$certificate = 'file://D:\www\web\test\tcpdf\config\cert\tcpdf.crt';
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'ic-easy.com',
				'Reason' => 'Order',
				'ContactInfo' => 'http://www.iceasy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		//$pdf->SetFont('helvetica', '', 12);
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
	
		// Log
	
		$pdf->Image ( 'images/default/iceac_logo.jpg', 10, 10, 12, 10 );
		$pdf->Image ( 'images/default/logo.jpg', 23, 10, 70, 11 );
	
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', '', 20 );
		$pdf->Cell (280, 8, '销售电子合同',0,0,'C');
	
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 165, 8, '现货查询 400-626-1616');
		$pdf->Cell ( 50, 8, ' 订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y-m-d H:i:s',$soarray['created']));
	
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['companyname']?$userinfo['companyname']:$userinfo['uname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_sz.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (10,40,70,20,15,20,25,20,20,20,15);
		else $w = array (15,40,40,25,15,20,25,20,20,25,30);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].'含税)','金额','需求时间','标准货期','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight,$header [$i], 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) {echo 'pordarr is null';exit;};
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	
	
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight, $item['brand'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight, 'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight, $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight,($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight, ($item['lead_time']?$item['lead_time']:''), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [10], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr ,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['itype']==2){
			$title4='四.发票类型： [√]17%增值税票    [  ]普通发票   [  ]不需要发票 ';
		}elseif($soarray['itype']==1){
			$title4='四.发票类型： [  ]17%增值税票    [√]普通发票   [  ]不需要发票 ';
		}else{
			$title4='四.发票类型： [  ]17%增值税票    [  ]普通发票   [√]不需要发票 ';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		if($soarray['exp_paytype']==1 || $soarray['freight']<=0){
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [√]供方付费   [  ]需方付费。';
		}else{
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [  ]供方付费   [√]需方付费。';
		}
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		$pdf->Cell ( 4, $hight, "");$title5='交(提)货信息： （1）公司名称：'.$soarray['companyname'];
		$pdf->Cell (200,$hight,$title5 ,'', 1,1);
		$pdf->Cell ( 26, $hight, "");$title5='（2）地址：'.$this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']);
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
		$pdf->Cell ( 26, $hight, "");$title5='（3）联系人以及联系方式：'.$soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'];
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='六.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
	
		$title7='七.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title7 ,'', 1,1);
	
		$title8='八.合同以供方的合同为准，需方不可修改不可撤销,自供方收到需方全部货款或预付定金时生效。';
		$pdf->Cell (200,$hight,  $title8 ,'', 1,1);
			
		$title9='九.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
			
		$title10='十.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title10='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
	
		$title11='十一.供方银行信息：';
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
		$title11='开户银行：'.BANK_NAME;
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
		$title11='账号：'.BANK_ACCOUNT.'              增值税纳税识别号：'.VAT_NUMBER;
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
	
		$pdf->Output (ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	
	public function hkDigitalContract_back($soarray,$userinfo){
		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		// create new PDF document
	
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// ---------------------------------------------------------
	
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		//$certificate = 'file://D:\www\web\test\tcpdf\config\cert\tcpdf.crt';
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'ic-easy.com',
				'Reason' => 'Order',
				'ContactInfo' => 'http://www.ic-easy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		//$pdf->SetFont('helvetica', '', 12);
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
	
		// Log
	
		$pdf->Image ( 'images/default/iceac_logo.jpg', 10, 10, 12, 10 );
		$pdf->Image ( 'images/default/logo.jpg', 23, 10, 70, 11 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (280, 8, '销售电子合同' ,0,0,'C');
	
		$pdf->Ln ( 10 );
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 165, 8, '现货查询 400-626-1616');
		$pdf->Cell ( 50, 8, ' 订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y-m-d H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_hk, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight,'需方：', 'LT', 0, 0 );
		$pdf->Cell ( 105, $hight,  $userinfo['companyname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0);
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_hk.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 0 );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight,'一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (15,40,70,25,25,20,20,20,20,20,30);
		else $w = array (15,40,40,30,25,25,25,25,25,25,35);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].')','金额','需求时间','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i], 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) $this->_redirect('/center/inqorder');
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight,$item['brand'] , 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight,'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight,$item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight,($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
	
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight,  $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight,$title3 ,'', 1,1);
			
		if($soarray['delivery_type']==2){
			$title4='四.交付方式：[√]Local delivery HK  [  ]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}elseif($soarray['delivery_type']==3){
			$title4='四.交付方式：[  ]Local delivery HK  [√]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}else{
			$title4='四.交付方式：[  ]Local delivery HK  [  ]FOB HK  [√]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='交付公司名称：'.$soarray['companyname'];
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='交付公司地址：'.$soarray['province'].$soarray['city'].$soarray['area'].$soarray['address'];
		$pdf->Cell (200,$hight,  $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='联系人以及联系方式：'.$soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'];
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
	
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='五.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
		$title6='六.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
	
		$title7='七.合同以供方的合同为准，需方不可修改不可撤销,自供方收到需方全部货款或预付定金时生效。';
		$pdf->Cell (200,$hight, $title7 ,'', 1,1);
	
		$title8='八.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title8 ,'', 1,1);
			
		$title9='九.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight,$title9 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title9='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
	
	
		$title10='十.供方银行信息：Bank Name:'.BANK_HK_NAME;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$title10='Account No: '.BANK_HK_ACCOUNT.'              Swift Code: '.SWIFT_CODE_HK;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$title10='Bank Address: '.BANK_ADDRESS_HK;
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
	
		$pdf->Output (ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	
	//////////////////////////////////////////////////////////////////////////v1.0

	/**
	 * 国内合同
	 */
	public function szContract($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, ($userinfo['companyname']?$userinfo['companyname']:$userinfo['uname']), 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight,  '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight,  $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight,  '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_sz.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.  产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (10,40,20,65,15,20,25,20,20,20,20);
		else $w = array (15,40,25,40,15,20,25,20,20,25,30);
		$header = array ('项次','产品名称','品牌','客户物料号','单位','数量','单价('.$unit[$soarray['currency']].'含税)','金额','需求时间','标准货期','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) {return false;};
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	        
			$pdf->Cell ( $w [2], $hight, $item['brand'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [3], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight,'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight,  $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight, ($item['lead_time']?$item['lead_time']:''), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [10], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr ,'LRTB', 1,1);
		//其它说明
		//$pdf->Ln();
		$pdf->Cell ( 2, 2,'','', 1,1);
		$title2='二.  质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.  产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
		if($soarray['itype']==2){
			$title4='四.  发票类型： [√]17%增值税票    [  ]普通发票 ';
		}elseif($soarray['itype']==1){
			$title4='四.  发票类型： [  ]17%增值税票    [√]普通发票 ';
		}else{
			$title4='四.  发票类型： [  ]17%增值税票    [  ]普通发票 ';
		}
		$pdf->Cell (200,$hight, $title4,'', 1,1);
	
		if($soarray['exp_paytype']==1){
			$title5='五.  交(提)货办法及运输方法： [√]国内快递  [√]供方付费   [  ]需方付费。  （备注：发票不随货）';
		}else{
			$title5='五.  交(提)货办法及运输方法：[√]国内快递  [  ]供方付费   [√]需方付费。  （备注：发票不随货）';
		}
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (45,$hight,  '交(提)货信息： （1）公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (28, $hight, "");
		$pdf->Cell (17,$hight, '（2）地址：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 28, $hight, "");
		$pdf->Cell (40,$hight, '（3）联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
	if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='六.  结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
	
	
		$title7='七.  因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight, $title7 ,'', 1,1);
	
		$title8='八.  本合同一式贰份,供方壹份,需方壹份,本合同不可修改不可撤销,自双方签字盖章之日起生效。';
		$pdf->Cell (200,$hight,  $title8 ,'', 1,1);
			
		$title9='九.  在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
			
		$title10='十.  未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。';
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='本销售条款及条件公示于CEAC网站，CEAC在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于CEAC网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
	
		$title11='十一.  供方银行信息：';
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title11='开户银行：'.BANK_NAME;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title11='账号：'.BANK_ACCOUNT.'              增值税纳税识别号：'.VAT_NUMBER;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	/**
	 * 香港合同
	 */
	public function hkContract($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight,'供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight,  $this->config->general->contract_com_hk, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0,'R' );
		$pdf->Cell ( 105, $hight, $userinfo['companyname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_hk.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.  产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (15,40,25,70,25,20,20,20,20,20,30);
		else $w = array (15,40,30,40,25,25,25,25,25,25,35);
		$header = array ('项次','产品名称','品牌','客户物料号','单位','数量','单价('.$unit[$soarray['currency']].')','金额','需求时间','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) $this->_redirect('/center/inqorder');
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [2], $hight, $item['brand'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [3], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight, "PCS", 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight, $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight,($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
	
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr,'LRTB', 1,1);
		//其它说明
		//$pdf->Ln();
		$pdf->Cell ( 2, 2,'','', 1,1);
		$title2='二.  质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.  产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['delivery_type']==2){
			$title4='四.  交付方式：[√]Local delivery HK  [  ]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}elseif($soarray['delivery_type']==3){
			$title4='四.  交付方式：[  ]Local delivery HK  [√]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}else{
			$title4='四.  交付方式：[  ]Local delivery HK  [  ]FOB HK  [√]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (25,$hight, '交付公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight,  $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (6, $hight, "");
		$pdf->Cell (25,$hight, '交付公司地址：','', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (33,$hight, '联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
	if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='五.  结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
		$title6='六.  因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
	
		$title7='七.  本合同一式贰份,供方壹份,需方壹份,自双方签字盖章之日起生效.若需修改，需签约双方确认。';
		$pdf->Cell (200,$hight,  $title7 ,'', 1,1);
	
		$title8='八.  在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title8 ,'', 1,1);
			
		$title9='九.  未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title9='本销售条款及条件公示于CEAC网站，CEAC在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于CEAC网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
	
	
		$title10='十.  供方银行信息：Bank Name:'.BANK_HK_NAME;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='Account No: '.BANK_HK_ACCOUNT.'              Swift Code: '.SWIFT_CODE_HK;
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='Bank Address: '.BANK_ADDRESS_HK;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	
	/**
	 * 数字合同 ，国内
	 */
	public function szDigitalContract($soarray,$userinfo)
	{
		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		// create new PDF document
	
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// ---------------------------------------------------------
	
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		//$certificate = 'file://D:\www\web\test\tcpdf\config\cert\tcpdf.crt';
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'ic-easy.com',
				'Reason' => 'Order',
				'ContactInfo' => 'http://www.iceasy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		//$pdf->SetFont('helvetica', '', 12);
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
	
		// Log
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/logo.jpg', 10, 10, 70, 11 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
	
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
	
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['companyname']?$userinfo['companyname']:$userinfo['uname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_sz.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (10,40,65,20,15,20,25,20,20,20,20);
		else $w = array (15,40,40,25,15,20,25,20,20,25,30);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].'含税)','金额','需求时间','标准货期','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight,$header [$i], 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) {echo 'pordarr is null';exit;};
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
				
	
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight, $item['brand'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight, 'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight, $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight,($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
				
				
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
				
			$pdf->Cell ( $w [9], $hight, ($item['lead_time']?$item['lead_time']:''), 'LRTB', 0,'C');
				
			$pdf->Cell ( $w [10], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr ,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['itype']==2){
			$title4='四.发票类型： [√]17%增值税票    [  ]普通发票 ';
		}elseif($soarray['itype']==1){
			$title4='四.发票类型： [  ]17%增值税票    [√]普通发票 ';
		}else{
			$title4='四.发票类型： [  ]17%增值税票    [  ]普通发票 ';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		if($soarray['exp_paytype']==1 || $soarray['freight']<=0){
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [√]供方付费   [  ]需方付费。  （备注：发票不随货）';
		}else{
			$title5='五.交(提)货办法及运输方法: [√]国内快递  [  ]供方付费   [√]需方付费。  （备注：发票不随货）';
		}
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		$pdf->Cell ( 4, $hight, "");$title5='交(提)货信息： （1）公司名称：'.$soarray['companyname'];
		$pdf->Cell (200,$hight,$title5 ,'', 1,1);
		$pdf->Cell ( 26, $hight, "");$title5='（2）地址：'.$this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']);
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
		$pdf->Cell ( 26, $hight, "");$title5='（3）联系人以及联系方式：'.$soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'];
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='六.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='六.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
	
		$title7='七.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title7 ,'', 1,1);
	
		$title8='八.合同以供方的合同为准，需方不可修改不可撤销,自供方收到需方全部货款或预付定金时生效。';
		$pdf->Cell (200,$hight,  $title8 ,'', 1,1);
			
		$title9='九.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
			
		$title10='十.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title10='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
	
		$title11='十一.供方银行信息：';
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
		$title11='开户银行：'.BANK_NAME;
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
		$title11='账号：'.BANK_ACCOUNT.'              增值税纳税识别号：'.VAT_NUMBER;
		$pdf->Cell (200,$hight, $title11 ,'', 1,1);
	
		$pdf->Output (ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	
	public function hkDigitalContract($soarray,$userinfo){
		$currencyArr = array('RMB'=>'人民币RMB','USD'=>'美元USD','HKD'=>'港币HKD');
		$unit = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		// create new PDF document
	
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// ---------------------------------------------------------
	
		// set certificate file
		$certificate = 'file://'.APPLICATION_PATH.'/../docs/cert/tcpdf.crt';
	
		//$certificate = 'file://D:\www\web\test\tcpdf\config\cert\tcpdf.crt';
		// set additional information
		$info = array(
				'Name' => 'ICEasy',
				'Location' => 'ic-easy.com',
				'Reason' => 'Order',
				'ContactInfo' => 'http://www.ic-easy.com',
		);
	
		// set document signature
		$pdf->setSignature($certificate, $certificate, 'iceasy886', '', 2, $info);
	
		// set font
		//$pdf->SetFont('helvetica', '', 12);
		$pdf->SetFont('droidsansfallback', '', 12);
		// add a page
		$pdf->AddPage('L');
	
		// Log
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/logo.jpg', 10, 10, 70, 11 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_hk, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight,'需方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight,  $userinfo['companyname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_hk.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight,'一.产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (15,40,70,25,25,20,20,20,20,20,30);
		else $w = array (15,40,40,30,25,25,25,25,25,25,35);
		$header = array ('次项','产品名称','客户物料号','品牌','单位','数量','单价('.$unit[$soarray['currency']].')','金额','需求时间','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i], 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) $this->_redirect('/center/inqorder');
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
				
			$pdf->Cell ( $w [2], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [3], $hight,$item['brand'] , 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight,'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight,$item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight,($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
				
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
				
			$pdf->Cell ( $w [9], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
				
		}
		$totalstr = '运费：'.$soarray['currency'].number_format($soarray['freight'],DECIMAL).'    合计: '.$soarray['currency'].number_format($soarray['total'],DECIMAL);
		$pdf->Cell (275,$hight, $totalstr,'LRTB', 1,1);
		//其它说明
		$pdf->Ln();
		$title2='二.质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight,  $title2 ,'', 1,1);
			
		$title3='三.产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight,$title3 ,'', 1,1);
			
		if($soarray['delivery_type']==2){
			$title4='四.交付方式：[√]Local delivery HK  [  ]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}elseif($soarray['delivery_type']==3){
			$title4='四.交付方式：[  ]Local delivery HK  [√]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}else{
			$title4='四.交付方式：[  ]Local delivery HK  [  ]FOB HK  [√]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='交付公司名称：'.$soarray['companyname'];
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='交付公司地址：'.$soarray['province'].$soarray['city'].$soarray['area'].$soarray['address'];
		$pdf->Cell (200,$hight,  $title4 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title4='联系人以及联系方式：'.$soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'];
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
	
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='五.结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='cod'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款，款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='五.结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿；';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]货到付款（COD）。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；需方须在发货前付清余款；若需方未能在到期日支付款项，供方有权没收定金，处理货物并且要求赔偿损失。 ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 28, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
		$title6='六.因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
	
		$title7='七.合同以供方的合同为准，需方不可修改不可撤销,自供方收到需方全部货款或预付定金时生效。';
		$pdf->Cell (200,$hight, $title7 ,'', 1,1);
	
		$title8='八.在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title8 ,'', 1,1);
			
		$title9='九.未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。合同内容和IC易站交易条款共同构成本合同之要约，需方确认在合同成立之前，已经完全理解并接受交易条款和IC易站用户协议。';
		$pdf->Cell (200,$hight,$title9 ,'', 1,1);
		$pdf->Cell ( 4, $hight, "");$title9='本销售条款及条件在公示于IC易站网站，供方在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于IC易站网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
	
	
		$title10='十.供方银行信息：Bank Name:'.BANK_HK_NAME;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$title10='Account No: '.BANK_HK_ACCOUNT.'              Swift Code: '.SWIFT_CODE_HK;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$title10='Bank Address: '.BANK_ADDRESS_HK;
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
	
		$pdf->Output (ORDER_ELECTRONIC.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	//////////////////////////////////////////////////////////////下线录入订单合同
	/**
	 * 国内合同
	 */
	public function szContract_Line($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight, '供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_com_sz, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight, ($userinfo['companyname']?$userinfo['companyname']:$userinfo['uname']), 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight,  '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight,  $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight,  '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_sz, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_sz.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.  产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (10,40,20,65,15,20,25,20,20,20,20);
		else $w = array (15,40,25,40,15,20,25,20,20,25,30);
		$header = array ('项次','产品名称','品牌','客户物料号','单位','数量','单价('.$unit[$soarray['currency']].'含税)','金额','需求时间','标准货期','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) {return false;};
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
	        
			$pdf->Cell ( $w [2], $hight, $item['brand'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [3], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight,'PCS', 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight,  $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight, ($item['lead_time']?$item['lead_time']:''), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [10], $hight, ($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
		}
		$totalstr = '合计(人民币小写)：'.number_format($soarray['total'],DECIMAL);
		$pdf->Cell ( 5, $hight,'','LTB', 0,0);$pdf->Cell (270,$hight,  $totalstr ,'RTB', 1,1);
		$totalstr = '       (人民币大写)：'.$this->fun->cny($soarray['total']);
		$pdf->Cell ( 5, $hight,'','LTB', 0,0);$pdf->Cell (270,$hight,  $totalstr ,'RTB', 1,1);
		//其它说明
		//$pdf->Ln();
		$pdf->Cell ( 2, 2,'','', 1,1);
		$title2='二.  质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.  产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
		if($soarray['itype']==2){
			$title4='四.  发票类型： [√]17%增值税票    [  ]普通发票 ';
		}elseif($soarray['itype']==1){
			$title4='四.  发票类型： [  ]17%增值税票    [√]普通发票 ';
		}else{
			$title4='四.  发票类型： [  ]17%增值税票    [  ]普通发票 ';
		}
		$pdf->Cell (200,$hight, $title4,'', 1,1);
	
		if($soarray['exp_paytype']==1){
			$title5='五.  交(提)货办法及运输方法： [√]国内快递  [√]供方付费   [  ]需方付费。  （备注：发票不随货）';
		}else{
			$title5='五.  交(提)货办法及运输方法：[√]国内快递  [  ]供方付费   [√]需方付费。  （备注：发票不随货）';
		}
		$pdf->Cell (200,$hight, $title5 ,'', 1,1);
	
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (45,$hight,  '交(提)货信息： （1）公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (28, $hight, "");
		$pdf->Cell (17,$hight, '（2）地址：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 28, $hight, "");
		$pdf->Cell (40,$hight, '（3）联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='六.  结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。   ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。   ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。    ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='六.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。    ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
	
	
		$title7='七.  因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight, $title7 ,'', 1,1);
	
		$title8='八.  本合同一式贰份,供方壹份,需方壹份,本合同不可修改不可撤销,自双方签字盖章之日起生效。';
		$pdf->Cell (200,$hight,  $title8 ,'', 1,1);
			
		$title9='九.  在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
			
		$title10='十.  未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》。';
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='本销售条款及条件公示于CEAC网站，CEAC在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于CEAC网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
	
		$title11='十一.  供方银行信息：';
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title11='开户银行：'.BANK_NAME;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title11='账号：'.BANK_ACCOUNT.'              增值税纳税识别号：'.VAT_NUMBER;
		$pdf->Cell (200,$hight,  $title11 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
	/**
	 * 香港合同
	 */
	public function hkContract_Line($soarray,$userinfo,$currencyArr,$unit){
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->Open ();
		$pdf->SetFont('droidsansfallback', '', 12);
		//横向
		$pdf->AddPage ('L');
			
		// Log
	
		$pdf->Image ( 'images/default/logo_12.jpg', 10, 10, 80, 15 );
		//$pdf->Image ( 'images/default/iceac_logo.jpg', 80, 11, 12, 10 );
			
		//印章
		//$pdf->Image ( 'images/default/seal_sz.jpg', 60, 25, 50, 50 );
			
		//标题
		$pdf->SetFont ( 'droidsansfallback', 'B', 20 );
		$pdf->Cell (155, 8, '销售合同' ,'',0,'R');
		
	    $pdf->SetFont ( 'droidsansfallback', '', 9 );
		$pdf->Cell ( 120, 8, '现货查询 400-626-1616','',0,'R');
		$pdf->Ln ( 10 );
		$pdf->Cell ( 180, 8, '');
		$pdf->Cell ( 52, 8, 'IC易站订单号：'.$soarray['salesnumber']);
		$pdf->Cell ( 40, 8, '下单时间：'.date('Y/n/j H:i:s',$soarray['created']));
			
		//双方资料
		$pdf->Ln ();
		$hight = 5;
			
		//公司名
		$pdf->Cell ( 25, $hight,'供方：', 'LT', 0, 'R' );
		$pdf->Cell ( 105, $hight,  $this->config->general->contract_com_hk, 'TR', 0);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '需方：', 'LT', 0,'R' );
		$pdf->Cell ( 105, $hight, $userinfo['companyname'], 'TR', 1 );
	
		//地址
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $this->config->general->contract_add_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '地址：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->fun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']), 'R', 1, 1 );
		//联系人
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['lastname'].$userinfo['firstname'], 'R', 0, 1);
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系人：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $userinfo['truename'], 'R', 1, 1);
		//联系电话
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_tel_hk, 'R', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '联系电话：', 'L', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['tel'], 'R', 1, 1);
		//传真
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R');
		$pdf->Cell ( 105, $hight, $this->config->general->contract_fax_hk.$userinfo['ext'], 'RB', 0, 1 );
		$pdf->Cell ( 15, $hight,'');
		$pdf->Cell ( 25, $hight, '传真：', 'LB', 0, 'R' );
		$pdf->Cell ( 105, $hight, $userinfo['fax'], 'RB', 1, 1);
			
		//产品详细
		$pdf->Ln ();
		$pdf->Cell (275,$hight, '一.  产品名称、品牌、数量、金额及需求时间' ,'', 1,1);
			
		// Header
		$w2 = false;
		foreach($soarray['pordarr'] as $item) {
			if($item['customer_material']) {$w2 = true;break;}
		}
		if($w2)
			$w = array (15,40,25,70,25,20,20,20,20,20,30);
		else $w = array (15,40,30,40,25,25,25,25,25,25,35);
		$header = array ('项次','产品名称','品牌','客户物料号','单位','数量','单价('.$unit[$soarray['currency']].')','金额','需求时间','备注');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], $hight, $header [$i] , 1, 0,'C');
		}
		$pdf->Ln();
		// Data
		$tmp=0;$pricetotal=0;
		if(empty($soarray['pordarr'])) $this->_redirect('/center/inqorder');
		foreach($soarray['pordarr'] as $item) {
			$tmp++;
			$pricetotal += $item['buynum']*$item['buyprice'];
			$pdf->Cell ( $w [0], $hight, $tmp, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [1], $hight, $item['part_no'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [2], $hight, $item['brand'], 'LRTB', 0,'C' );
			
			$pdf->Cell ( $w [3], $hight, $item['customer_material'], 'LRTB', 0,'C' );
	
			$pdf->Cell ( $w [4], $hight, "PCS", 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [5], $hight, $item['buynum'], 'LRTB', 0 ,'C');
	
			$pdf->Cell ( $w [6], $hight, ($item['buyprice']), 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [7], $hight, ($item['buynum']*$item['buyprice']), 'LRTB', 0,'C');
	
	
			$needs_time = $item['needs_time']?date('Y-m-d' ,$item['needs_time']):'';
			$pdf->Cell ( $w [8], $hight, $needs_time, 'LRTB', 0,'C');
	
			$pdf->Cell ( $w [9], $hight,($item['remark']?$item['remark']:''), 'LRTB', 1,'C');
	
		}
		$totalstr = ' 合计（'.$soarray['currency'].'）: '.number_format($soarray['total'],DECIMAL);
		$pdf->Cell ( 5, $hight,'','LTB', 0,0);$pdf->Cell (270,$hight, $totalstr ,'RTB', 1,1);
		//其它说明
		//$pdf->Ln();
		$pdf->Cell ( 2, 2,'','', 1,1);
		$title2='二.  质量检验、验收办法:供方保证所供产品为原厂正品，需方同意按生产商有关技术标准执行。货到一周提书面异议，否则视为合格交付及接受。';
		$pdf->Cell (200,$hight, $title2 ,'', 1,1);
			
		$title3='三.  产品保修方式及期限:依原厂保修方式及期限。';
		$pdf->Cell (200,$hight, $title3 ,'', 1,1);
			
		if($soarray['delivery_type']==2){
			$title4='四.  交付方式：[√]Local delivery HK  [  ]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}elseif($soarray['delivery_type']==3){
			$title4='四.  交付方式：[  ]Local delivery HK  [√]FOB HK  [  ]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}else{
			$title4='四.  交付方式：[  ]Local delivery HK  [  ]FOB HK  [√]其他  (货交需方指定地址，若单笔送货金额不足USD1000，需方支付运费或上门自提。)';
		}
		$pdf->Cell (200,$hight, $title4 ,'', 1,1);
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (25,$hight, '交付公司名称：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight,  $soarray['companyname'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell (6, $hight, "");
		$pdf->Cell (25,$hight, '交付公司地址：','', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $this->fun->createAddress($soarray['province'],$soarray['city'],$soarray['area'],$soarray['address']) ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		$pdf->Cell ( 6, $hight, "");
		$pdf->Cell (33,$hight, '联系人以及联系方式：' ,'', 0,1);
		$pdf->SetFont ( 'droidsansfallback', 'B', 9 );
		$pdf->Cell (200,$hight, $soarray['name'].'  '.$soarray['mobile'].'  '.$soarray['tel'] ,'', 1,1);
		$pdf->SetFont ( 'droidsansfallback', '', 9 );
	
		if($soarray['paytype']=='mts' || $soarray['down_payment']==0){
			$title6='五.  结算方式及期限:  [√]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.$soarray['percentage'].' %定金'.$soarray['currency'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。     ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['paytype']=='other'){
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。     ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]其他。'.$soarray['paytype_other'];
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}elseif($soarray['down_payment']==$soarray['total']){
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付     %定金'.$soarray['currency'].'              ，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。     ';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}else{
			$title6='五.  结算方式及期限:  [  ]款到发货，供方提货通知10日内买方不付款提货，供方有权转卖处理货物，损失由需方赔偿。';
			$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]预付全款,款到合同生效。';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[√]预付'.($soarray['percentage']>0?$soarray['percentage']:'').' %定金'.$soarray['currency'].$soarray['down_payment'].'，定金到帐后合同生效；若货到供方30天仍未付清货款，供方有权没收定金，处理货物并且要求赔偿损失。     ';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
			$pdf->Cell ( 30, $hight, "");$title6='[  ]其他';
			$pdf->Cell (200,$hight, $title6 ,'', 1,1);
		}
		$title6='六.  因履行本合同发生的争议，由当事人协商解决，协商不成的，依法向有管辖权的法院进行起诉。';
		$pdf->Cell (200,$hight,  $title6 ,'', 1,1);
	
		$title7='七.  本合同一式贰份,供方壹份,需方壹份,自双方签字盖章之日起生效.若需修改，需签约双方确认。';
		$pdf->Cell (200,$hight,  $title7 ,'', 1,1);
	
		$title8='八.  在合同执行之过程中，若遇不可抗力之因素，致使合同不能继续执行，双方协商解决。';
		$pdf->Cell (200,$hight, $title8 ,'', 1,1);
			
		$title9='九.  未尽事项，请登录WWW.ICEASY.COM 详阅《销售条款与条件》';
		$pdf->Cell (200,$hight,  $title9 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title9='本销售条款及条件公示于CEAC网站，CEAC在法律允许的范围内保留对本条款及条件的修改及解释的权利，如有修改直接于CEAC网站发布并适用，恕不另行通知。';
		$pdf->Cell (200,$hight, $title9 ,'', 1,1);
	
	
		$title10='十.  供方银行信息：Bank Name:'.BANK_HK_NAME;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='Account No: '.BANK_HK_ACCOUNT.'              Swift Code: '.SWIFT_CODE_HK;
		$pdf->Cell (200,$hight,  $title10 ,'', 1,1);
		$pdf->Cell ( 6, $hight, "");$title10='Bank Address: '.BANK_ADDRESS_HK;
		$pdf->Cell (200,$hight, $title10 ,'', 1,1);
	
		$pdf->Output (ORDER_PAPER.md5('order'.$soarray['salesnumber']).'.pdf','F');
		return true;
	}
}