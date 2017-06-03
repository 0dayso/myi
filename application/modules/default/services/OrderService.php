<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
class Default_Service_OrderService
{
	private $_orderModel;
	private $_soprodModel;
	private $_emailService;
	private $_USDTOCNY;
	private $_couponModer;
	public function __construct() {
		$this->_orderModel = new Default_Model_DbTable_SalesOrder();
		$this->_soprodModel = new Default_Model_DbTable_SalesProduct();
		$this->_couponModer = new Default_Model_DbTable_Model('coupon');
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->fun = new MyFun();
		$this->_emailService = new Default_Service_EmailtypeService();
		
		//人民币兑美元汇率
		$rateModel = new Default_Model_DbTable_Rate();
		$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
		$this->_USDTOCNY = $arr['rate_value'];
	}
	public function sendordermail($email,$name,$orderarr){
		
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
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您使用盛芯电子的在线采购服务！</div>
                                                <div style="height:5px;padding:0; margin:0;font-size:0; line-height:8px ">&nbsp;</div>
                                        		<div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您于&nbsp;<strong>'.date('Y/m/d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].'</strong>已确认收到，请您在提交订单后24小时内完成在线支付或2个工作日内完成汇款，否则订单将被取消。我们将在收到您的货款后安排发货。您也可以进入 <a href="http://www.iceasy.com/center/order" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的订单</b></a> 随时查看订单的处理情况。</div>
                                                <div style="height:5px;padding:0; margin:0;font-size:0; line-height:8px ">&nbsp;</div>
                                        		<div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如有任何不明之处，请根据订单编号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>与我们确认相关细节。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess  = $this->getOrderTable($orderarr,$pordarr,$hi_mess);
		$mess .= $this->getOrderMessTable();
		$fromname = '盛芯电子';
		$title    = '您的盛芯电子订单#：'.$orderarr['salesnumber'].'已确认收到，请支付货款';
		$emailarr = $this->_emailService->getEmailAddress('online_order',$orderarr['uid']);
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
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/**
	 * 获取订单信息table
	 */
 public function getOrderTable($orderarr,$pordarr,$hi_mess,$sellinfo=array()){
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
		$paystatus = array('101'=>'<font color="#fd2323">待付款</font>',
				'102'=>'<font color="#03b000">已付全款</font>',
				'103'=>'<font color="#03b000">已付全款</font>',
				'201'=>'<font color="#03b000">已付全款</font>',
				'202'=>'<font color="#03b000">已付全款</font>',
				'301'=>'<font color="#03b000">已付全款</font>',
				'302'=>'<font color="#03b000">已付全款</font>',
				'401'=>'<font color="#03b000">订单被取消</font>');
		$contypeArr = array('1'=>'明细','2'=>'电子元件','3'=>'耗材');
		$paytypearr = array('transfer'=>'银行汇款','online'=>'在线支付','coupon'=>'优惠券兑换');
		$expressarr = array('1'=>'国内快递','2'=>'公司配送');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
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
        <div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">盛芯电子订单</div>
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
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 "><strong style="color:#fd2323;font-family:\'微软雅黑\'">&nbsp;&nbsp;<span style="color:#000000">'.$orderarr['currency'].'</span> '.$orderarr['total'].'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;下单时间：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.date('Y/m/d H:i:s',$orderarr['created']).'</strong></td>
                              
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;付款方式：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$paytypearr[$orderarr['paytype']].'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;付款状态：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$paystatus[$orderarr['status']].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;订单备注：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" ><strong style="color:#000000;font-family:\'微软雅黑\'">'.nl2br($orderarr['remark']).'</strong></td></tr></table></td>
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
                <td width="10" style="font-size:10px; width:10px;">&nbsp;</td>
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
                        <th>金额('.$orderarr['currency'].')</th><th>';
			if($sellinfo) $mess .='供应商信息';
			else  $mess .='备注';
            $mess .='</th></tr>';
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
                        <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">';
                        if($v['vendor_name']) $mess .=$v['vendor_name'].':'.$v['location'].'_'.$v['location_code'];
                        else "--";
                        $mess .='</td>
                    </tr>';
		}
		}
		$mess .='<tr  bgcolor="#FFFFFF">
                        <td height="30" colspan="9" align="right" style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">
                            商品金额：<strong style="color:#fd2323; margin-left:5px;">'.$unitArr[$orderarr['currency']].' '.number_format($orderarr['total_back']-$orderarr['freight'],DECIMAL).'</strong> 
                            <strong style="font-size:16px; color:#000000">+</strong> 
                            运费及包装费：<strong style="color:#fd2323; margin-left:5px;">'.$unitArr[$orderarr['currency']].' '.$orderarr['freight'].'</strong>';
         if(($orderarr['total_back']-$orderarr['total'])>0){           
            $mess .='<strong style="font-size:16px; color:#000000">-</strong> <span style="color:#03b000">
                            优惠券：</span><strong style="color:#03b000; margin-left:5px;">'.$unitArr[$orderarr['currency']].' '.number_format(($orderarr['total_back']-$orderarr['total']),DECIMAL).'</strong>';
         }           
         $mess .='<strong style="font-size:16px; color:#000000"> =</strong>
                            合计：<b style="color:#fd2323; margin:0 5px;font-size:14px;"><span style="color:#000000">'.$orderarr['currency'].'</span> '.number_format($orderarr['total'],DECIMAL).'</b>&nbsp;&nbsp;
                        </td>
                    </tr>
            </table>
           
        </td>
                
                
            </tr>
        </table>
        </td>     
    </td>
</tr>';
         //优化券
         $couponService = new Icwebadmin_Service_CouponService();
         $coupon_code = $couponService->getOrderCoupon($orderarr['coupon_code']);
         if($coupon_code){
         	$mess .='<!-- 优惠券-->
     <tr valign="top">
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;优惠券信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
         	
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; text-align:center; border-collapse:collapse">
                    <tr bgcolor="#f3f3f3">
         			   <th width="30">项次</th>
                        <th width="100">优惠券号</th>
                        <th width="200">优惠项目</th>
                        <th width="80">颁发者</th>
                        <th width="200">说明</th>
                    </tr>';
         	foreach($coupon_code as $k=>$v){
         		$tmpstr = '';
         		if($v['type']==1){
         			$tmpstr = '可抵扣'.$v['buy_number'].'个产品'.$v['part_no'];
         		}else{
         			if($orderarr['currency']=='RMB')
         				$tmpstr = '可抵扣'.$orderarr['currency'].$v['money_rmb'];
         			elseif($this->orderarr['currency']=='USD')
         			$tmpstr = '可抵扣'.$orderarr['currency'].$v['money_usd'];
         		}
         		$mess .='<tr bgcolor="#FFFFFF" >
                    	<td height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($k+1).'</td>
                        <td height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['code'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$tmpstr.'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['lastname'].$v['firstname'].'</td>
                        <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['remark'].'</td>
                        </tr>';
         	}
         	$mess .='</table>
                </td>
                </tr>
           </table>
    </td>
   </tr>';
         }
$mess .='<!--收货信息-->
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
                    <td style="font-size:34px; ">&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 "><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$orderarr['companyname'].'</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                            </tr>
                            <tr>
                              <td width="80" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;货运方式：</td>
                              <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;用户上门自取</strong></td>
                              <td width="80" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;可取货时间：</td>
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
                               <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;普通发票</strong></td>
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
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;1. 此邮件仅表明盛芯电子已经收到您的订单，只有当确认收到您的货款后，盛芯电子和您之间的订购合同才成立。
                    </p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 如果您选择在线支付，须在提交订单后24小时内完成支付，如选择转账，须在提交订单后2个工作日内完成汇款，否则订单将被取<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;消。与盛芯电子之间的合同成立后，您有义务完成与盛芯电子的交易，但法律或本用户协议禁止的交易除外，未经盛芯电子书面同意，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;任何订单都不可被取消。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0;margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 产品可能受限于中国和货品可能的来源国的进出口管制法律、限制、条例和命令。您须同意遵守中国和其他外国机构或当局的所有应<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;适用的进出口法律、限制和条例，且应自担费用获取任何许可并遵守任何可适用的中国以及产品销往国的进出口条例。 </p>
                </td>
            
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;4. 在任何情况下严格禁止将产品使用于杀伤人员地雷、或用于与生物类、化学类或核类武器或运送该类武器的导弹相关的任何用途。产<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;品不得被用于航天或航空飞行器或其他空中运输应用、生命维持或供给设备、外科移植设备、或如货品发生故障将有理由认为会导致<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;人身伤害、死亡、环境破坏或财产严重损失的任何其他用途。同时严格禁止在此类设备、系统或应用中使用或加入任何产品。盛芯电子<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;在法律允许的最大限度内不对因产品用于该等用途而直接或间接地引致的任何损失和损害承担任何责任。 </p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                   <p  style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;更多条款内容请见&nbsp;<a href="http://www.iceasy.com/help/index/type/clause" style="color:#0055aa">盛芯电子交易条款</a>，我们建议您定期阅读以获取最新条款信息。</p>
                </td>
            </tr>
         </table>
    </td>
</tr>
	';
		return $mess;
	}
	/**
	 * 发邮件给销售
	 */
	public function sendordermailsell($orderarr){
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
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">详细资料和订单信息请登录 <a href="http://www.iceasy.com/icwebadmin/OrOrgl"  target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>盛芯电子后台</b></a> 查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess  = $this->getOrderTable($orderarr,$pordarr,$hi_mess);
		$fromname = '盛芯电子';
		$title    = '客户新建在线订单，订单号#：'.$orderarr['salesnumber'].'，请跟进付款 ';
		
		$emailarr = $this->_emailService->getEmailAddress('online_order',$orderarr['uid']);
		$emailto = array('0'=>$sellinfo['email']);
		//如果有抄送人
		$staffService = new Icwebadmin_Service_StaffService();
		$emailcc = $staffService->mailtostaff($sellinfo['mail_to']);
		//抄送给优惠券发放人
		if($orderarr['coupon_code']){
			$couponService = new Default_Service_CouponService();
			$coupcc = $couponService->getEmailByCoup($orderarr['coupon_code']);
			$emailcc = array_merge($emailcc,$coupcc);
			
			//优惠券发送给刘倩
			$staff = $staffService->getStaffInfo('anny.liu');
			if($staff['email']){
				$emailto = array('0'=>$staff['email']);
				$emailcc = array_merge($emailcc,array('0'=>$sellinfo['email']));
			}
		}
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
	public function completemail($email,$name,$orderarr){
	
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
                                        		 <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您在盛芯电子的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;"> '.$orderarr['salesnumber'].' </strong>已完成。再次感谢您对盛芯电子的支持！</div>
                                        		</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess  = $this->getOrderTable($orderarr,$pordarr,$hi_mess);
		$fromname = '盛芯电子';
		$title    = '您的盛芯电子订单#：'.$orderarr['salesnumber'].'已完成';
		$emailarr = $this->_emailService->getEmailAddress('online_order',$orderarr['uid']);
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
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/*
	 * 一个用户最多添加100个还没付款订单
	*/
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM sales_order WHERE uid=:uidtmp AND status=101";
		$all = $this->_orderModel->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/*
	 * 获取在线Order的总数
	 */
	public function onlineSoNum()
	{
		$sqlstr = "SELECT count(id) as allnum FROM sales_order WHERE available!=0  AND uid=:uidtmp";
		$allnumarr = $this->_orderModel->getBySql($sqlstr,$this->sqlarr);
		return $allnumarr[0]['allnum'];
	}
	/**
	 * 获取so信息
	 */
	public function geSoinfo($salesnumber,$notuser=''){
		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
		if($notuser==1){
			$wheresql = '';
			$wherearray = array('sonum'=>$salesnumber);
		}else{
			$wheresql = 'AND so.uid=:uidtmp';
			$wherearray = array('sonum'=>$salesnumber,'uidtmp'=>$_SESSION['userInfo']['uidSession']);
		}
		$sqlstr ="SELECT so.*,
        u.uname,u.email,up.companyname as ucompanyname,
        p.province,c.city,e.area,
    	a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.express_name,a.express_account,a.warehousing,
		pi.province as province_i,ci.city as city_i,ei.area as area_i,
		ai.name as name_i,ai.address as address_i,ai.zipcode as zipcode_i,ai.mobile as mobile_i,ai.tel as tel_i,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name,
		ia.addressid as iaaddressid,ia.invoiceid as iainvoiceid,ia.status as iastatus,ia.remark as iaremark
    	FROM sales_order as so
        LEFT JOIN user as u ON so.uid=u.uid
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
		$orderarr = $this->_orderModel->getByOneSql($sqlstr, $wherearray);	
		if(!empty($orderarr)){
			$pordarr = $this->_soprodModel->getAllByWhere("salesnumber='".$salesnumber."'");
			if(empty($pordarr)) return false;
			else{
			   $orderarr['pordarr'] = $pordarr;
			   return $orderarr;
			}
		}else return false;
	}
	/**
	 * 增加用户获得的积分
	 */
	function addScore($salesnumber,$adduid='')
	{
		$uid = $adduid?$adduid:$_SESSION['userInfo']['uidSession'];
		$sqlstr = "SELECT total,currency FROM sales_order WHERE available!=0 AND status=301 AND salesnumber='{$salesnumber}' AND uid='{$uid}'";
		$rearr  = $this->_orderModel->getByOneSql($sqlstr);
		$multiplier = $rearr['total'];
		if($rearr){
			if($rearr['currency']=='USD'){
				$multiplier = $rearr['total']*6;
			}
		}
		$scoreservice = new Default_Service_ScoreService();
		return $scoreservice->addScore('onorder',$multiplier,$salesnumber,$uid);
		//更新积分
		/*$udstr = "UPDATE user_profile SET score =score + {$score} WHERE uid=:uidtmp";
		$re = $this->_orderModel->updateBySql($udstr,$this->sqlarr);
		if($re) return $score;
		else return 0;
		*/
	}
	/**
	 * 检查优惠券准确性
	*/
	public function checkCoupon($coupon,$buyproduct){
		if(!isset($_SESSION['coupon_number'])){
			$_SESSION['coupon_number'] = 1;
		}else{
			$_SESSION['coupon_number']++;
			if($_SESSION['coupon_number']>5){
				return array('code'=>100,'message'=>'很抱歉，错误输入优惠券超过5次，系统暂时锁定不能继续输入！');
			}
		}
		$couponArr = $this->_couponModer->getRowByWhere("code='$coupon' AND uid='".$_SESSION['userInfo']['uidSession']."'");
		if(!$couponArr) return array('code'=>102,'message'=>'此优惠券不存在，请输入正确的优惠券！');
		if(time() < $couponArr['start_date']) return array('code'=>103,'message'=>'很抱歉，此优惠券还没开始使用，请于'.date("Y-m-d",$couponArr['start_date']).'再来使用！');
		if(time() > $couponArr['end_date']) return array('code'=>104,'message'=>'很抱歉，此优惠券已经过期！');
		if($couponArr['status']!=200) return array('code'=>105,'message'=>'很抱歉，此优惠券已经不可使用！');
		if(count($buyproduct)!=1 || !$buyproduct[0]) return array('code'=>101,'message'=>'此优惠券只能兑换一件产品，请分开购买！');
		if($couponArr['part_id']!=$buyproduct[0]['pord_id']){
			$ps = new Default_Service_ProductService();
			$part_no = $ps->getPartNo($couponArr['part_id']);
			return array('code'=>106,'message'=>'很抱歉，此优惠券只能用于购买产品：'.$part_no.'，请重新选择！');
		}
		if($couponArr['type']==1){
			if($couponArr['buy_number']!=$buyproduct[0]['qty']) 
			   return array('code'=>107,'message'=>'很抱歉，此优惠券只能用于购买'.$couponArr['buy_number'].'个产品，请重新选择！');
		}elseif($couponArr['type']==2){
			return array('code'=>108,'message'=>'很抱歉，优惠券抵扣金额功能还没开通！');
		}
		return array('code'=>0,'message'=>'优惠券号正确！');
	}
	/**
	 * 更新优惠券
	 */
	public function updateCoupon($coupon,$data){
		return $this->_couponModer->update($data, "code='$coupon' AND uid='".$_SESSION['userInfo']['uidSession']."'");
	}
}