<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/cart.php';
class Default_Service_InquiryService
{
	private $_inqdetailedModer;
	private $_inqModer;
	private $_inquiry;
	private $_fun;
	private $_emailService;
	public function __construct()
	{
		$this->_inqModer = new Default_Model_DbTable_Inquiry();
		$this->_inqdetailedModer = new Default_Model_DbTable_InquiryDetailed();
		$this->_inquiry = new Iceaclib_default_inquiry();
		$this->_fun = new MyFun();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->_emailService = new Default_Service_EmailtypeService();
	}
	/**
	 * 获取数数量
	 */
	public function getNum($sql)
	{
		$sqlstr = "SELECT count(iq.id) as num FROM inquiry as iq  WHERE iq.uid=:uidtmp {$sql}";
		$allnumarr = $this->_inqModer->getByOneSql($sqlstr,$this->sqlarr);
		return $allnumarr['num'];
	}
	/*
	 * 检查part id是否已经存在有效的询价记录
	 */
	public function checkAvailable($inqModer,$partid)
	{
		$sqlstr = "SELECT iqd.* FROM inquiry as i,inquiry_detailed as iqd
    			WHERE i.part_id=:partid 
				AND i.id = iqd.inq_id 
				AND i.status=1 
				AND i.uid=:uidtmp
				ORDER BY iqd.created DESC";
		$myinfoarray = $inqModer->getBySql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession'],'partid'=>$partid));
		if(empty($myinfoarray)) return false;
		else return $myinfoarray;
	}
	/*
	 * 根据询价id检查是否存在
	*/
	public function checkInq($id)
	{
		$sqlstr = "SELECT iqd.* FROM inquiry as i,inquiry_detailed as iqd
    			WHERE i.id = '{$id}'
				AND i.id = iqd.inq_id
				AND i.status=1
				AND i.uid=:uidtmp
				AND iqd.expiration_time>0
				AND iqd.result_price>0
				ORDER BY iqd.created DESC";
		$inqArr = $this->_inqModer->getBySql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(empty($inqArr)) return false;
		else return $inqArr;
	}
	/*
	 * 获取用户询价记录
	*/
	public function getInquiry($offset,$perpage,$typestr='')
	{
		$sqlstr = "SELECT iq.*
    	FROM inquiry as iq 
    	WHERE iq.uid=:uidtmp  AND iq.status!='0' {$typestr}
		ORDER BY iq.created DESC LIMIT $offset,$perpage";
    	$inqArr = $this->_inqModer->getBySql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
    	if(!empty($inqArr)){
    		foreach($inqArr as $k=>$inq){
    			$inqArr[$k]['detaile']=$this->getDetailedInquiry($inq['id']);
    			//有再议价记录的
    			if(!empty($inq['son_inquiry']))
    			{
    				$inqArr[$k]['lastson'] = $this->getInquiryByID(end(explode(",",$inq['son_inquiry'])));
    			}
    			//如果有父亲询价
    			if(!empty($inq['father_inquiry']))
    			{
    				$inqArr[$k]['father'] = $this->getInquiryByID($inq['father_inquiry']);
    			}
    		}
    	}
    	return $inqArr;
	}
	/*
	 * 获取再议价历史记录
	*/
	public function getInquiryHistory($inqid,$type=0)
	{
		$all = array();
		$sqlstr = "SELECT id,son_inquiry,father_inquiry
		FROM inquiry WHERE uid=:uidtmp AND id='{$inqid}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$allinq = array_reverse(explode(",",$inqArr['id'].','.$inqArr['son_inquiry']));
		foreach($allinq as $v){
			if(!empty($v))
			{
				$all[] = $this->getInquiryByID($v,$type);
			}
		}
		//如果有父亲询价
		if(!empty($inqArr['father_inquiry']))
		{
			$all[] = $this->getInquiryByID($inqArr['father_inquiry']);
		}
		return $all;
	}
	/*
	 * 获取可下单的询价
	 */
	public function getOrderInq($inqid,$type=0)
	{
		$all = array();
		$sqlstr = "SELECT id,son_inquiry
		FROM inquiry WHERE uid=:uidtmp AND id='{$inqid}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$allinq = array_reverse(explode(",",$inqArr['id'].','.$inqArr['son_inquiry']));
		if($allinq[0]){
			$inqid = $allinq[0];
		}
		return $this->getInquiryByID($inqid,$type);;
	}
	/*
	 * 获取最新询价记录
	 */
	public function getLastInquiry($inqid){
		$sqlstr = "SELECT id,son_inquiry
		FROM inquiry WHERE uid=:uidtmp AND id='{$inqid}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$allinq = array_reverse(explode(",",$inqArr['id'].','.$inqArr['son_inquiry']));
		return $this->getInquiryByID($allinq[0]);
	}
	/*
	 * 获取用户询价详细记录
	*/
	public function getDetailedInquiry($inqid)
	{
		$sqlstr = "SELECT iqd.*,
		b.name as brand,pd.part_img,pd.mpq,pd.lead_time,
		pd.manufacturer,pd.part_level1,pd.part_level2,pd.part_level3
		FROM inquiry_detailed as iqd LEFT JOIN product as pd
		ON iqd.part_id=pd.id
		LEFT JOIN  brand as b
		ON b.id=pd.manufacturer
		WHERE iqd.inq_id='{$inqid}'";
		return $this->_inqdetailedModer->getBySql($sqlstr);
	}
	/*
	 * 根据询价编号获取询价详细
	*/
	public function getInquiryByID($id,$type=0,$status='')
	{   
		$wheresql = '';$statussql = '';
		if($type==1) $wheresql = " AND iqd.expiration_time >='".time()."'";
		if($status) $statussql = " AND iq.status='{$status}'";
		$sqlstr = "SELECT iq.*
    	FROM inquiry as iq
		WHERE iq.uid=:uidtmp AND iq.id='{$id}' {$statussql}";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($inqArr)){
			$sqlstr = "SELECT iqd.*,
			b.name as brand,pd.part_img,pd.mpq,pd.lead_time
			FROM inquiry_detailed as iqd 
			LEFT JOIN product as pd ON iqd.part_id = pd.id
			LEFT JOIN brand   as b  ON b.id = pd.manufacturer
			WHERE iqd.inq_id='{$id}'".$wheresql;
			$inqArr['detaile'] = $this->_inqdetailedModer->getBySql($sqlstr);
			return $inqArr;
		}else return false;
	}
	/*
	 * 获取状态
	 */
	public function getInquiryStatus($inq_number)
	{
		
		$sqlstr = "SELECT iq.status FROM inquiry as iq
		WHERE iq.uid=:uidtmp AND iq.inq_number='{$inq_number}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $inqArr['status'];
	}
	/**
	 * 根据询价编号获取负责的销售
	*/
	public function getInquiryStaff($id)
	{
		$sqlstr = "SELECT st.email,st.tel,st.phone,st.lastname,st.firstname
		FROM inquiry as iq
		LEFT JOIN admin_staff as st ON iq.staffid = st.staff_id
		WHERE iq.uid=:uidtmp AND iq.id='{$id}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $inqArr;
	}
	/*
	 * 删除询价记录
	 */
	public function deleteIqu($id){
		return $this->_inqModer->update(array('status'=>0), "id='{$id}' AND uid='".$_SESSION['userInfo']['uidSession']."'");
	}
	/*
	 * 加入询价列表
	 */
	public function addInquiry($part_id,$number,$price,$expected_amount,$product,$delivery='',$currency=''){
		$items = array(
				'id'          => $part_id,
				'part_no'     => $product['part_no'],
				'hope_number' => $number,
				'hope_price'  => $price,
				'expected_amount'  => $expected_amount,
				'delivery'    =>$delivery,
				'currency'    => $currency,
				'options'     => array(
						'mpq'    => $product['mpq'],
						'part_img'    => $product['part_img'],
						'brand'       => $product['manufacturer'],
						'lead_time'   => $product['lead_time']
				));
		//加入询价列表
		return $this->_inquiry->insert($items);
	}
	/*
	 * 返回条目数
	*/
	public function total_items(){
		return $this->_inquiry->total_items();
	}
	/*
	 * 一个用户最多添加$maxnum个还没回复的询价记录
	*/
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM inquiry WHERE uid=:uidtmp AND status=1";
		$all = $this->_inqModer->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/*
	 * 更新已下单状态 下单数量加1
	 */
	public function updateStatus($inqid,$status){
		$sqlstr = "UPDATE inquiry SET status = '{$status}',order_time='".time()."',order_number = order_number + 1 WHERE id ='{$inqid}' AND uid=:uidtmp";
		return $this->_inqModer->updateBySql($sqlstr, $this->sqlarr);
	}
	/*
	 * 检查再议价条件是否满足
	*/
	public function checkInqAgain($inqid,$num=1){
		$sqlstr = "SELECT son_inquiry FROM inquiry WHERE uid=:uidtmp AND id='{$inqid}'";
		$all = $this->_inqModer->getByOneSql($sqlstr,$this->sqlarr);
		$sonArr = array_filter(explode(',',$all['son_inquiry']));
		if(count($sonArr) < $num) return $all;
		else return false;
	}
	/*
	 * 询价发送email通知
	 */
	public function sendInqAlertEmail($xs_name,$new_inqid,$user,$inqinfo,$xs_email,$cc=array(),$bcc=array())
	{
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
		
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$xs_name.',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">有客户新提交了询价单，询价单号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['inq_number'].'</strong>&nbsp;，请在24小时之内完成报价。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">详细资料和询价信息请登录&nbsp;<a href="http://www.iceasy.com/icwebadmin" target="_blank"><b>IC易站后台</b></a>&nbsp;查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess =$this->getInqXsTable($inqinfo,$user,$hi_mess);
		
		$fromname = 'IC易站';
		$title    = '客户新建询价，询价单号#：'.$inqinfo['inq_number'].'，请处理';
		
		$emailarr = $this->_emailService->getEmailAddress('new_inquiry');
			
		$emailto = array('0'=>$xs_email);
		$emailcc = $cc;
		$emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	}
	public function sendInqEmailToUser($user,$inqinfo)
	{
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
                            
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['uname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">感谢您对IC易站的垂询！已确认收到您的如下询价，我们会尽快提供报价给您。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">如有任何不明之处，请根据询价编号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['inq_number'].'</strong>，与我们确认相关细节。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">您也可以进入&nbsp;<a href="http://www.iceasy.com/center/inquiry"  target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的询价</b></a>&nbsp;随时查看询价处理情况。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess =$this->getInqTable($inqinfo,$user,$hi_mess);
		$fromname = 'IC易站';
		$title    = '您的IC易站询价单#：'.$inqinfo['inq_number'].'已确认收到';
		$emailarr = $this->_emailService->getEmailAddress('inquiry_order',$user['uid']);
		$emailcc  = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($user['uid']);
		return $this->_fun->sendemail($user['email'], $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo,1);
	}
public function getInqXsTable($inqinfo,$user,$hi_mess){
	$mess .='<tr>
                    <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
<!--内容-->
<!--订单信息-->
<tr valign="top">
    <td valign="bottom"  align="center" height="40">
        <div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">IC易站询价单</div>
    </td>
</tr>
<!--用户信息-->';
 $mess .=$this->getUserTable($user);
 $mess .=$this->getInqInfoTable($inqinfo,$user,1);
 
 if($inqinfo['remark']){
 $mess .='
<!--询价说明-->
<tr valign="top">
<td >
    <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >    
    <tr>
       <td bgcolor="#f9f9f9" >
    
    <table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';" >
        <tr>
            <td valign="middle" colspan="2" align="left" height="40" >
            <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;询价说明&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
        <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="35" style="font-family:\'微软雅黑\'" colspan="4"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" >'.nl2br($inqinfo['remark']).'</td></tr></table></td>
                            </tr> 
                      </table>
                    </td>
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
	public function getInqTable($inqinfo,$user,$hi_mess)
	{
		$mess =' <tr>
                    <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
<!----------内容--------->
<!--订单信息-->
<tr valign="top">
    <td valign="bottom"  align="center" height="40">
        <div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">IC易站询价单</div>
    </td>
</tr>';
 $mess .=$this->getInqInfoTable($inqinfo,$user);
 
 if($inqinfo['remark']){
 $mess .='<!--询价说明-->
<tr valign="top">
<td >
    <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >     
    <tr>
       <td bgcolor="#f9f9f9" >
        <table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';" >
            <tr>
                <td valign="middle" colspan="2" align="left" height="40" >
                <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;询价说明&nbsp;&nbsp;&nbsp;</span>
                </td>
            </tr>
            <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="35" style="font-family:\'微软雅黑\'" colspan="4"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" >'.nl2br($inqinfo['remark']).'</td></tr></table></td>
                            </tr> 
                      </table>
                    </td>
                </tr>
        </table>
    </td>
    </tr>
    </table>
</td>
</tr>';}
		return $mess;
	}
	/**
	 * 获取询价详情table
	 */
	public function getInqInfoTable($inqinfo,$user,$xiaoshou=0){
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'人民币 RMB','USD'=>'美元 USD','HKD'=>'港币 HKD');
		$unitArr = array('RMB'=>'¥','USD'=>'$','HKD'=>'$');
		$mess ='<!--订单详情--><tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >    
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" >
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;询价详情&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
				       <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;询价方：</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['companyname'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;询价编号#：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$inqinfo['inq_number'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;询价日期：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.date('Y/m/d H:i:s',$inqinfo['created']).'</strong></td>
                            </tr>
                            
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;交货地：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$deliveryArr[$inqinfo['delivery']].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;交易货币：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$currencyArr[$inqinfo['currency']].'</strong></td>
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
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0; margin:0; border-collapse:separate; border-spacing:0px; padding:10px 0" >  
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">  
                    <tr bgcolor="#f3f3f3">
                        <th width="35" height="30">项次</th>
                        <th>产品型号</th>
                        <th>品牌</th>';
                        if($xiaoshou){
                        	$mess .='<th width="60">标准包装</th>
                                     <th width="60">标准交期</th>';
                        }
                        $mess .='<th>采购数量</th>
                        <th>年用量</th>
                        <th width="100">目标单价('.$inqinfo['currency'].')</th>
                    </tr>';
		foreach($inqinfo['detaile'] as $itm=>$v){
			$mess .= '<tr bgcolor="#FFFFFF" >
                        <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($itm+1).'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['brand'].'</td>';
                        if($xiaoshou){
                        	$mess .='<td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['mpq'].'</td>
                                     <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['lead_time'].'</td>';
                        }
                        $mess .='<td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['number'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($v['expected_amount']?$v['expected_amount']:'--').'</td>
                        <td style="border-top:1px solid #d6d6d6"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($v['price']?$unitArr[$inqinfo['currency']].' '.$v['price']:'--').'</strong></td>
                    </tr>';
		 }   
           $mess .='</table>
        </td>
            </tr>
        </table>
        </td>     
    </td>
</tr>';
		return $mess;
	}
	/**
	 * 获取用户table
	 */
	public function getUserTable($user){
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$mess = '<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >      
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;客户信息&nbsp;&nbsp;&nbsp;</span><span style="color:#03b000">&nbsp;&nbsp;用户名：<b>'.$user['uname'].'</b></span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['companyname'].'  <span style="color:#ff6600">('.$propertyArr[$user['property']].')</span></strong></td>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系人：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['truename'].'</strong></td>
                            </tr>
                            
                            <tr  bgcolor="#ffffff">
                              <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;Email：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;<a href="mailto:'.$user['email'].'" style="color:#0055aa">'.$user['email'].'</a></strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['tel'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;手机：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['mobile'].'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;传真：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['fax'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;详细地址：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong>&nbsp;&nbsp;'.$this->_fun->createAddress($user['province'],$user['city'],$user['area'],$user['address']).'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;邮编：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['zipcode'].'</strong></td>
                            </tr> 
                      </table>
                    </td>
                </tr>
            </table>
        </td>
        </tr>
        </table>
    </td>
</tr>';
		return $mess;
	}
}