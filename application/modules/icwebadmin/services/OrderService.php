<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
class Icwebadmin_Service_OrderService
{
	private $_orderModel;
	private $_soprodModel;
	private $_staffService;
	private $_emailService;
	public function __construct() {
		$this->_orderModel = new Icwebadmin_Model_DbTable_SalesOrder();
		$this->_soprodModel = new Icwebadmin_Model_DbTable_SalesProduct();
		$this->_staffService=new Icwebadmin_Service_StaffService();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
	}
	/**
	 * 获取用户的所有询价订单，包括子订单
	 */
	public function getAllSo($offset,$perpage,$typestr,$orderbystr='')
	{
		if(!$orderbystr){
			$orderbystr = "ORDER BY so.back_status ASC,so.created DESC,so.status DESC";
		}
		$limit = '';
		if($offset || $perpage) $limit = "LIMIT $offset,$perpage";
		$sqlstr = "SELECT so.*,
		u.uname,up.companyname,st.lastname,st.firstname
		FROM sales_order as so 
		LEFT JOIN user as u ON so.uid=u.uid
		LEFT JOIN user_profile as up ON u.uid = up.uid
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
		WHERE so.available!=0 {$typestr} {$orderbystr} {$limit}";

		
		return $this->_orderModel->getBySql($sqlstr);
	}
	/**
	* 获取count()行数
	 */
	 public function getRowNum($str)
	 {
	 	$sqlstr = "SELECT count(id) as num FROM sales_order as so
	 				LEFT JOIN user_profile as up ON so.uid = up.uid
	 				WHERE so.available!=0 {$str}";
	 	$allrel = $this->_orderModel->getByOneSql($sqlstr);
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
		$sqlstr ="SELECT so.`salesnumber`  FROM `sales_order` as so 
		LEFT JOIN user_profile as up ON so.uid = up.uid
		WHERE so.available!=0 AND {$where}";
	 	return $this->_orderModel->getBySql($sqlstr);;
	 }
	/**
	 * 用户在线订单统计
	 * 101待付款  201已付款待发货202已配送,待确认收货 301已确认收货,待评价302已确认收货,已评价401客户取消订单
	 */
	public function soInfo($uid){
		$sqlstr = "SELECT salesnumber,status,back_status FROM sales_order WHERE uid='{$uid}'";
		$inqrr = $this->_soprodModel->getBySql($sqlstr);
		$num_0 = $num_1 =$num_2 =$num_3 =$num_4 =$num_5 =$num_6 =0;
		if(!empty($inqrr)) {
			foreach($inqrr as $inq){
				if($inq['back_status']==102) $num_0++;
				elseif($inq['status']==101) $num_1++;
				elseif($inq['status']==201) $num_2++;
				elseif($inq['status']==202) $num_3++;
				elseif($inq['status']==301) $num_4++;
				elseif($inq['status']==302) $num_5++;
				elseif($inq['status']==401) $num_6++;
			}
			
		}
		$rearr[0]= $num_0;
		$rearr[1]= $num_1;
		$rearr[2]= $num_2;
		$rearr[3]= $num_3;
		$rearr[4]= $num_4;
		$rearr[5]= $num_5;
		$rearr[6]= $num_6;
		return $rearr;
	}
	/**
	 * 根据salesnumber查询订单详细信息
	 */
	public function getSoInfo($salesnumber,$where='')
	{
		//订单详细
		$sqlstr ="SELECT so.*,
        u.uid,u.uname,u.email,up.companyname as ucompanyname,up.annex1,up.annex2,
        p.province,c.city,e.area,
    	a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.express_name,a.express_account,a.warehousing,
		pi.province as province_i,ci.city as city_i,ei.area as area_i,
		ai.name as name_i,ai.address as address_i,ai.zipcode as zipcode_i,ai.mobile as mobile_i,ai.tel as tel_i,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name
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
    	WHERE so.salesnumber=:sonum AND so.available='1' AND so.back_status!=2";
		return $this->_orderModel->getByOneSql($sqlstr, array('sonum'=>$salesnumber));
	}
	/**
	 * 获取负责销售
	 */
	public function getOwnerByUid($uid)
	{
		$sql = "SELECT st.* FROM admin_staff as st
		LEFT JOIN user_profile as up ON st.staff_id = up.staffid
		WHERE st.status=1 AND up.uid='{$uid}'";
		return $this->_orderModel->getByOneSql($sql);
	}
	/**
	* 更加sonumber获取订单详细产品
	*/
	public function getSoPart($salesnumber)
	{
		$sql = "SELECT sp.*,p.manufacturer,p.staged,p.datecode FROM sales_product as sp 
				LEFT JOIN product as p ON sp.prod_id=p.id
				WHERE  sp.salesnumber='{$salesnumber}' ";
		return $this->_soprodModel->getBySql($sql);
	}
	/**
	 * 更新订单
	 */
	public function updateByNum($data,$salesnumber){
		return $this->_orderModel->update($data, "salesnumber = '{$salesnumber}'");
	}
	/**
	 * 更新sz_cover
	 */
	public function updateSzcover($pordarr)
	{
		for($i=0;$i<count($pordarr);$i++)
		{
		  $sz_cover = $pordarr[$i]['sz_cover']?$pordarr[$i]['sz_cover']:0;
		  $hk_cover = $pordarr[$i]['hk_cover']?$pordarr[$i]['hk_cover']:0;
		  $bpp_cover = $pordarr[$i]['bpp_cover']?$pordarr[$i]['bpp_cover']:0;
		  $udstr = "UPDATE product SET sz_stock = sz_stock - ".$sz_cover.",sz_cover =sz_cover - ".$sz_cover.",hk_stock = hk_stock - ".$hk_cover.",hk_cover =hk_cover - ".$hk_cover.",bpp_stock =bpp_stock - ".$bpp_cover.",bpp_cover =bpp_cover - ".$bpp_cover." WHERE id=".$pordarr[$i]['prod_id'];
		  $this->_soprodModel->updateBySql($udstr);
		}
	}
	/**
	 * 获取发票信息
	 */
	public function getInvoice($invoiceid)
	{
		return $this->_orderModel->getByOneSql("SELECT * FROM `invoice` WHERE id='{$invoiceid}'");
	}
	/**
	 * 反馈交货期发送邮件
	 */
	public function leadtimeEmail($orderarr,$pordarr)
	{
			$fromname = '盛芯电子';
			$title    = '盛芯电子订单#：'.$orderarr['salesnumber'].' 的交期已确认，请查看';
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
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对盛芯电子的惠顾！</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您于&nbsp;<strong>'.date('Y/m/d H:i',$orderarr['created']).'</strong>&nbsp;提交的订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].' </strong>已经确认交货时间。我们将于&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'; font-size:13px;">'.date('Y-m-d',$orderarr['delivery_time']).'</strong>&nbsp;为您发货。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您可以随时进入 <a href="http://www.iceasy.com/center/order" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的订单</b></a> 查看订单的后续处理情况。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$dforderService = new Default_Service_OrderService();
		$mess .= $dforderService->getOrderTable($orderarr, $pordarr,$hi_mess);
			
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
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;1. 如果因原厂原因和/或不可控的突发状况造成交期延后，盛芯电子不对您承担责任，您不得因此而取消订单。如因您未及时支付余款，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;或因您的其他原因造成货物发送延误和/或产生其他风险，后果由您承担。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 如果由于超出盛芯电子合理控制的任何原因（应包括但不限于政府行为、战争、火灾、广泛流行性疾病、爆炸、洪水、灾害性气候、进<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;出口管制或禁运、劳动争议、货品或劳动力无法得到供给等），盛芯电子不应就该等迟延履行或未能履行而对客户承担任何形式的责任<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;或被视作违约行为。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 因上述原因而使盛芯电子履行合同受阻，盛芯电子可以自主决定迟延履行或撤销合同的全部或部分，且不对此迟延、撤销或任何不能交<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;货承担责任。</p>
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