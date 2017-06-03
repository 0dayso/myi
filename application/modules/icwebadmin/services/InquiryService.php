<?php
class Icwebadmin_Service_InquiryService
{
	private $_inqModer;
	private $_inqdetailedModer;
	private $_emailService;
	public function __construct()
	{
		$this->_inqModer        = new Icwebadmin_Model_DbTable_Inquiry();
		$this->_inqdetailedModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
		$this->_emailService = new Default_Service_EmailtypeService();
	}
	/*
	 * 获取用户询价记录
	*/
	public function getInquiry($offset,$perpage)
	{
		$sqlstr = "SELECT iq.*
    	FROM inquiry as iq 
    	WHERE iq.status!='0' ORDER BY iq.status DESC,iq.created DESC LIMIT $offset,$perpage";
    	$inqArr = $this->_inqModer->getBySql($sqlstr);
    	if(!empty($inqArr)){
    		foreach($inqArr as $k=>$inq){
    			$detailedArr = array();
    			$sqlstr = "SELECT iqd.*,
    			pd.part_img,pd.lead_time
    			FROM inquiry_detailed as iqd LEFT JOIN product as pd
    			ON iqd.part_id=pd.id
    			WHERE iqd.inq_id='{$inq['id']}'";
    			$detailedArr = $this->_inqdetailedModer->getBySql($sqlstr);
    			$inqArr[$k]['detaile']=$detailedArr;
    		}
    	}
    	return $inqArr;
	}
	/**
	 * 根据number获得id
	 */
	public function getIdByNumber($inq_num){
		$sqlstr = "SELECT iq.id FROM inquiry as iq WHERE iq.inq_number='{$inq_num}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr);
		return $inqArr['id'];
	}
    /*
     * 更新byid
    */
    public function upInqByid($data,$id){
    	return $this->_inqModer->updateInquiry($id,$data);
    }
    /*
     * 获取inqid by oa_rfq
    */
    public function getInqByOarfq($oa_rfq,$oa_rdtype){
    	$sql = "SELECT iq.*,up.companyname FROM inquiry as iq
    			LEFT JOIN user_profile as up ON iq.uid = up.uid
    			WHERE oa_rfq='{$oa_rfq}' AND oa_rdtype='{$oa_rdtype}'";
    	$re1 = $this->_inqModer->getByOneSql($sql);
    	if(empty($re1)){
    		$sql2 = "SELECT iq.*,up.companyname FROM inquiry as iq
    		LEFT JOIN user_profile as up ON iq.uid = up.uid
    		WHERE oa_rfq_bpp='{$oa_rfq}'";
    		$re1 = $this->_inqModer->getByOneSql($sql2);
    	}
    	return $re1;
    }
    /*
     * 获取总数
    */
    public function getInqNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq
    			LEFT JOIN user_profile as up ON iq.uid = up.uid
    			WHERE iq.id !='' {$xswhere}");
    	}
    /*
     * 获取待询价总数
     */
    public function getWaitNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.status IN ('1','3') AND iq.oa_status=102 {$xswhere}");
    }
    /*
     * 获取所有已报价总数
    */
    public function getAlreadyNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.status='2' {$xswhere}");
    }
    /*
     * 获取所有已下过单的总数
    */
    public function getOrderNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.status IN ('5','6') {$xswhere}");
    }
    /*
     * 审核不通过
    */
    public function getNoNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.status='4' {$xswhere}");
    }
    /*
     * 已经删除
    */
    public function getCancelNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq
    			LEFT JOIN user_profile as up ON iq.uid = up.uid
    			WHERE iq.status='0' {$xswhere}");
    	}
    /*
     * 获取已删除总数
    */
    public function getDeleteNum($xswhere){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.status='0' {$xswhere}");
    }
    /**
     * 获取搜索总数
    */
    public function getSelectNum($xswhere,$keyword=''){
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq 
    			LEFT JOIN user_profile as up ON iq.uid = up.uid 
    			WHERE iq.id!='' {$xswhere}");
    }
    /**
     * 待向OA询价数量
     */
    public function getOainqNum($xswhere)
    {   
    	return $this->getNum("SELECT count(id) as num FROM inquiry as iq
    			LEFT JOIN user_profile as up ON iq.uid = up.uid
    			WHERE iq.status IN ('1','3') AND iq.oa_status IN ('100','101') {$xswhere}");
    }
    /**
     * 获取待向OA询价记录
     */
    public function getAllInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql($xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    /**
     * 获取搜索记录
     */
    public function getSelectInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql($xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    /**
     * 获取待向OA询价记录
     */
     public function getOainqInquiry($offset,$perpage,$xswhere)
     {
     	$sqlstr = $this->getInquirySql(" AND iq.status IN ('1','3') ".$xswhere,$offset,$perpage);
     	return $this->getInquiryBySql($sqlstr);
     }
    
    /*
     * 获取待询价记录
    */
    public function getWaitInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql(" AND iq.status IN ('1','3') ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    
    /*
     * 获取已询价记录
    */
    public function getAlreadyInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql(" AND iq.status = '2' ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    public function getOrderInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql(" AND iq.status IN ('5','6') ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    /**
     * 获取审核不通过记录
     * @param unknown_type $offset
     * @param unknown_type $perpage
     * @return Ambigous <boolean, unknown, multitype:, multitype:mixed Ambigous <string, boolean, mixed> >
     */
	public function getNoInquiry($offset,$perpage,$xswhere)
    {

    	$sqlstr = $this->getInquirySql(" AND iq.status=4 ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    /**
     * 获取审核被取消记录
     * @param unknown_type $offset
     * @param unknown_type $perpage
     * @return Ambigous <boolean, unknown, multitype:, multitype:mixed Ambigous <string, boolean, mixed> >
     */
    public function getCancelInquiry($offset,$perpage,$xswhere)
    {
    
    	$sqlstr = $this->getInquirySql(" AND iq.status=0 ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    /*
     * 获取待询价记录
    */
    public function getDeleteInquiry($offset,$perpage,$xswhere)
    {
    	$sqlstr = $this->getInquirySql(" AND iq.status=0 ".$xswhere,$offset,$perpage);
    	return $this->getInquiryBySql($sqlstr);
    }
    private function getInquirySql($where,$offset,$perpage){
    	$limit = '';
    	if($offset || $perpage) $limit = "LIMIT $offset,$perpage";
    	return "SELECT iq.*,up.companyname ,up.staffid as upstaffid,up.property,
    	sta.email,sta.lastname,sta.firstname,app1.name as appname1,app2.name as appname2
    	FROM inquiry as iq 
    	LEFT JOIN user_profile as up ON iq.uid = up.uid 
    	LEFT JOIN admin_staff as sta ON sta.staff_id = up.staffid
    	LEFT JOIN app_category as app1 ON app1.id = iq.app_1_id
    	LEFT JOIN app_category as app2 ON app2.id = iq.app_2_id
    	WHERE iq.id!='' {$where} {$limit}";
    }
    /*
     * 根据询价编号获取询价详细
    */
    public function getDetailedByID($id)
    {
    	$sqlstr = "SELECT iqd.*,
    	pd.part_img,pd.mpq,pd.lead_time,pd.lead_time,pd.datecode
    	FROM inquiry_detailed as iqd 
    	LEFT JOIN product as pd ON iqd.part_id=pd.id
    	WHERE iqd.inq_id='{$id}'";
       return $this->_inqdetailedModer->getBySql($sqlstr);;
    }
    /*
     * 更新询价记录
    */
    public function updateInquiry($id,$data){
    	return $this->_inqModer->updateInquiry($id, $data);
    }
    /*
     * 更新询价详细记录
     */
    public function updateDetailed($id,$data){ 
	   return $this->_inqdetailedModer->updateInquiry($id, $data);
	}
	public function updateDetWhere($data,$where){
		return $this->_inqdetailedModer->update($data, $where);
	}
	/*
	 * 获取用户询价详细记录
	*/
	public function getDetailedInquiry($inqid)
	{
		$sqlstr = "SELECT iqd.*,iqd.part_no as part_no2,pd.part_no,
		b.name as brand,pd.part_img,pd.mpq,pd.moq,pd.lead_time,pd.staged,pd.datecode
		FROM inquiry_detailed as iqd 
		LEFT JOIN product as pd ON iqd.part_id=pd.id
		LEFT JOIN  brand as b ON b.id=pd.manufacturer
		WHERE iqd.inq_id='{$inqid}'";
		return $this->_inqdetailedModer->getBySql($sqlstr);
	}
	/*
	 * 根据询价编号获取询价详细
	*/
	public function getInquiryByID($id,$type=0,$status='')
	{
		$wheresql = '';$statussql = '';
		if($type==1) $wheresql = " AND iq.expiration_time >='".strtotime(date('Y-m-d H:i'))."'";
		if($status) $statussql = " AND iq.status='{$status}'";
	
		$sqlstr = "SELECT iq.*,inqr.hi_mess,iq2.inq_number as father_inq_number,app1.name as appname1,app2.name as appname2
		FROM inquiry as iq
		LEFT JOIN inquiry_reasons as inqr ON inqr.id = iq.reasons
		LEFT JOIN inquiry as iq2 ON iq.father_inquiry = iq2.id
		LEFT JOIN app_category as app1 ON app1.id = iq.app_1_id
		LEFT JOIN app_category as app2 ON app2.id = iq.app_2_id
		WHERE iq.id='{$id}' {$statussql}";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr);
		if(!empty($inqArr)){
		    $sqlstr = "SELECT iqd.*,iqd.part_no as part_no2,pd.part_no,
		    b.name as brand,b.oa_name,pdbook.book_price_usd,pdbook.book_price_rmb,pdbook.status as pdbstatus,
		    pd.break_price,pd.break_price_rmb,pd.part_img,pd.mpq,pd.moq,pd.lead_time,pd.description,pd.staged,pd.datecode
		    FROM inquiry_detailed as iqd 
		    LEFT JOIN product as pd ON iqd.part_id=pd.id
		    LEFT JOIN product_bookprice as pdbook ON pdbook.part_id=pd.id
		    LEFT JOIN brand as b ON b.id=pd.manufacturer
		    WHERE iqd.inq_id='{$id}'".$wheresql;
		$inqArr['detaile'] = $this->_inqdetailedModer->getBySql($sqlstr);
		return $inqArr;
		}else return false;
	}
	/*
	 * 获取再议价历史记录
	*/
	public function getInquiryHistory($inqid,$type=0)
	{
		$all = array();
		$sqlstr = "SELECT id,son_inquiry,inq_number,father_inquiry FROM inquiry WHERE id='{$inqid}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr);
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
	//获取用户信息
	public function getUserById($inq){
		$sql = "select a.uid,app.name as appname,app1.name as appname1,app2.name as appname2,c.uname as user,c.backstage,c.email,up.*
			     from inquiry a
			     left join app_category app1 on app1.id = a.app_1_id
				 left join app_category app2 on app2.id = a.app_2_id
				 left join user c on c.uid = a.uid
			     left join user_profile up on a.uid = up.uid
			     left join app_category app  on  app.id = up.industry
				where a.id  = '{$inq}'";
		return $this->_inqModer->getByOneSql($sql);
	}
	/*
	 * 获取记录数
	*/
	private function getNum($sqlstr)
	{
		$allver = $this->_inqModer->getBySql($sqlstr,array());
		return $allver[0]['num'];
	}
	/*
	 * 获取记录bysql
	*/
	private function getInquiryBySql($sqlstr){
		$inqArr = $this->_inqModer->getBySql($sqlstr);
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
	 * 报价发送email通知
	*/
	public function emailAlertToUser($user,$inqinfo)
	{
		$statusArr = array (
				'1' => '<font color="#FF0000">待报价</font>',
				'2' => '<font color="#009944">已报价</font>',
				'3' => '<font color="#FF912F">议价审核中</font>',
				'4' => '<font color="#FF912F">审核不通过</font>',
				'5' => '<font color="#FF912F">已下单</font>',
				'6' => '<font color="#FF912F">成功下单</font>'
		);
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'RMB','USD'=>'USD','HKD'=>'HKD');
		$unitArr = array('RMB'=>'¥','USD'=>'$','HKD'=>'$');
		$emailFun = new MyFun();
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($user['uid']);
		$mess = '<!--hi-->
    <tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['user'].',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对盛芯电子的垂询！已确认收到您的询价单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['inq_number'].'</strong>。</div>
                      <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">盛芯电子致力于为每位客户提供最优惠的价格和最完善的服务，销售代表在报价前需要与您沟通以获得更多信息。</div>
                      <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">非常抱歉我们无法按照您提供的信息联系上您，请您尽快与我们的销售代表联系，以便我们为您提供最佳的报价。</div>
                      <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">负责为您服务的销售代表信息如下：</div>
                      </td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <!-------------------------------------------------------内容------------------------------------------------------->
    <!--销售相关-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;销售代表&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#d6d6d6" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6">
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6  ">&nbsp;&nbsp;姓名：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['lastname'].$sellinfo['firstname'].'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;邮箱：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;<a href="mailto:'.$sellinfo['email'].'" style="color:#000000;font-family:\'微软雅黑\'">'.$sellinfo['email'].'</a></strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ;">&nbsp;&nbsp;电话：</td>
                        <td width="250" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['tel'].'</strong></td>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ;">&nbsp;&nbsp;手机：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['phone'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>
    <!--订单详情-->
  <tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >   
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" >
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;报价详情&nbsp;&nbsp;&nbsp;</span>
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
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0; border-collapse:separate; border-spacing:0px" > 
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse"> 
                    <tr bgcolor="#f3f3f3">
                        <th width="35" height="30">项次</th>
                        <th>产品型号</th>
                        <th>品牌</th>
                        <th>采购数量</th>
                        <th>年用量</th>
                        <th>目标单价('.$inqinfo['currency'].')</th>
                    </tr>';
		foreach($inqinfo['detaile'] as $itm=>$v){
			$mess .= '<tr bgcolor="#FFFFFF" >
                        <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($itm+1).'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['brand'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;">'.$v['number'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;">'.($v['expected_amount']?$v['expected_amount']:'--').'</td>
                        <td style="border-top:1px solid #d6d6d6;color:#3fa156"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($v['price']?$unitArr[$inqinfo['currency']].' '.$v['price']:'--').'</strong></td>
                    </tr>';
		}
		$mess .= '</table>
        </td>  
            </tr>
        </table>
        </td>     
    </td>
</tr>
<!--询价说明-->';
if($inqinfo['remark']){                    
$mess .='<tr valign="top">
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
};
	
		$fromname = '盛芯电子';
		$title    = '您在盛芯电子的提交的询价单#：'.$inqinfo['inq_number'].'，提供的信息无法联系';
		$emailarr = $this->_emailService->getEmailAddress('quoted_price',$user['uid']);
		$emailto = array('0'=>$user['email']);
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $emailFun->sendemail($user['email'], $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/*
	 * 报价发送email通知
	*/
	public function sendReInqEmail($user,$inqinfo)
	{
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($user['uid']);
		$emailFun = new MyFun();
		//报价不通过
		if($inqinfo['status']==4){
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
			
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['user'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">感谢您使用盛芯电子询价！很抱歉您的询价我们未通过审核，详细信息如下。您可以进入 <a href="http://www.iceasy.com/center/inquiry" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的询价</b></a> 查看详情。</div>
                                        		</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}elseif($inqinfo['reasons']){
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
		
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['user'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">'.$inqinfo['hi_mess'].'</div>
                                                		<div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">您可以进入 <a href="http://www.iceasy.com/center/inquiry" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的询价</b></a> 查看详情。</div>
                                        		</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}else{
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
			
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['user'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">感谢您使用盛芯电子询价！您的询价我们已经处理，报价信息如下。您可以进入 <a href="http://www.iceasy.com/center/inquiry" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的询价</b></a> 查看详情并直接下单。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">为了确保您能以所报的优惠价格购买，请您在报价有效期内下单。</div>
                                        		</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}
		$mess = $this->getInqBackTable($inqinfo,$user,$hi_mess);
	
		$fromname = '盛芯电子';
		$title    = '您在盛芯电子的询价单#：'.$inqinfo['inq_number'].'已处理，请查看报价';
		$emailarr = $this->_emailService->getEmailAddress('quoted_price',$user['uid']);
		$emailto = array('0'=>$user['email']);
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
		
		return $emailFun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo,1);
	}
	
	/**
	 * 获取报价table
	 */
public function getInqBackTable($inqinfo,$user,$hi_mess){
		$this->fun = new MyFun();
		$statusArr = array (
				'1' => '<font color="#FF0000">待报价</font>',
				'2' => '<font color="#009944">已报价</font>',
				'3' => '<font color="#FF912F">议价审核中</font>',
				'4' => '<font color="#FF912F">审核不通过</font>',
				'5' => '<font color="#FF912F">已下单</font>',
				'6' => '<font color="#FF912F">成功下单</font>'
		);
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'人民币 RMB','USD'=>'美元 USD','HKD'=>'港币 HKD');
		$unitArr = array('RMB'=>'¥','USD'=>'$','HKD'=>'HK$');
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
        <div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">盛芯电子报价单</div>
    </td>
</tr>
  
<!--订单详情-->
<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >   
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" >
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;报价详情&nbsp;&nbsp;&nbsp;</span>
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
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0; border-collapse:separate; border-spacing:0px" > 
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse"> 
                    <tr bgcolor="#f3f3f3">
                        <th width="35" height="50">项次</th>
                        <th>产品型号</th>
                        <th>品牌</th>
                        <th>标准包装</th>
                        <th>采购数量</th>
                        <th bgcolor="#daefe0">标准交期</th>
                        <th bgcolor="#daefe0">最小起订量</th>
                        <th bgcolor="#daefe0" width="100">'.($inqinfo['currency']=='RMB'?'含税':'').'单价('.$inqinfo['currency'].')</th>
                        <th bgcolor="#daefe0">有效期</th>
                        <th bgcolor="#daefe0">备注</th>
                    </tr>';
		foreach($inqinfo['detaile'] as $itm=>$v){
			$mess .= '<tr bgcolor="#FFFFFF" >
                        <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($itm+1).'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['brand'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['mpq'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;">'.$v['number'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;color:#3fa156">'.($v['result_price']?($v['inq_lead_time']?$v['inq_lead_time']:$v['lead_time']):'--').'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;color:#3fa156">'.($v['result_price']?($v['pmpq']==0?'--':$v['pmpq']):'--').'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($v['result_price']==0?'--':$unitArr[$inqinfo['currency']].' '.$v['result_price']).'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;color:#3fa156">'.($v['result_price']>0?($v['expiration_time']?date('Y-m-d',$v['expiration_time']):'--'):'--').'</td>
                        <td style="border-top:1px solid #d6d6d6;color:#3fa156">'.($v['result_remark']?$v['result_remark']:'--').'</td>
                    </tr>';
		}
		$mess .= '</table>
        </td>  
            </tr>
        </table>
        </td>     
    </td>
</tr>';

if($inqinfo['status']==2 && !$inqinfo['reasons'])
{
$mess .= '<!--操作-->  
<tr>
    <td align="center" valign="middle" height="40">
    <a href="http://www.iceasy.com/inquiryorder/index/inqkey/'.$this->fun->encryptVerification($inqinfo['id']).'" target="_blank" style=" background:#fd2323; display:inline-block; padding:3px 0;font-weight:bold; color:#ffffff; font-size:18px; text-decoration:none;font-family:\'微软雅黑\'">
    	&nbsp;&nbsp;&nbsp;&nbsp;立即下单&nbsp;&nbsp;&nbsp;&nbsp;
    </a>  
    </td>
</tr>';
}

$mess .='<!--询价说明-->
<!--<tr valign="top">
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
</tr>-->';
if($inqinfo['result_remark']){                    
$mess .='<tr valign="top">
<td >
    <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" > 
    <tr>
       <td bgcolor="#f9f9f9" >
    
    <table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';" >
 
        <tr>
            <td valign="middle" colspan="2" align="left" height="40" >
            <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;报价说明&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
		<tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="35" colspan="4"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" >'.nl2br($inqinfo['result_remark']).'</td></tr></table></td>
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
$mess .='<!--重要信息-->
<tr><td height="10" style="line-height:1px; font-size:10px; margin:0; padding:0 "></td></tr> 
<tr><td height="1" bgcolor="ffffff" style="line-height:1px; font-size:0; height:1px; border-top:1px solid #f1f1f1 ">&nbsp;</td></tr>  
<tr>
    <td>
         <table cellspacing="0" border="0" cellpadding="0" width="730" style=" font-size:12px; color:#5b5b5b;font-family:\'微软雅黑\'; border-collapse:collapse; border-spacing:0">
            
            <tr>
                <td valign="bottom" align="left" >
                    <div style="margin: 0; padding:0; text-align:left; font-size:14px; font-weight:bold;color:#fd2323;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;※※&nbsp;重要声明&nbsp;※※</div>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\'; line-height:18px;">&nbsp;&nbsp;&nbsp;1. 如果以人民币结算, 则报价为含税价格。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;2. 报价单中的标准交期仅供参考，实际交期应在您下单之后，以原厂反馈的交期为准。</p>
                </td>
            </tr>
            <tr><td height="10" style="font-size:0; line-height:0; padding:0; margin:0">&nbsp;</td></tr>
            <tr>
                <td>
                    <p style=" padding:0; margin:0;font-family:\'微软雅黑\';line-height:18px;">&nbsp;&nbsp;&nbsp;3. 报价默认有效期为一个月，具体有效期请以报价单中的日期为准。如人民币与美元汇率发生重大波动和/或原厂价格有所调整，<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;和/或其他不可控因素引发价格变化时，盛芯电子保留改变已确定价格的权利。下单时的最终价格以下单当天的产品价格为准。</p>
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
</tr>';
		return $mess;
	}
	/**
	 * 用户询价记录
	 * 
	 */
	public function userInqInfo($uid){
		$sqlstr = "SELECT id,status FROM inquiry WHERE uid='{$uid}'";
		$inqrr = $this->_inqdetailedModer->getBySql($sqlstr);
		$num_0 = $num_1 =$num_2 =$num_3 =$num_4 =$num_5 =$num_6 =0;
		if(!empty($inqrr)) {
			foreach($inqrr as $inq){
				if($inq['status']==0) $num_0++;
				elseif($inq['status']==1) $num_1++;
				elseif($inq['status']==2) $num_2++;
				elseif($inq['status']==3) $num_3++;
				elseif($inq['status']==4) $num_4++;
				elseif($inq['status']==5) $num_5++;
				elseif($inq['status']==6) $num_6++;
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
	/*
	 * 获取再议价历史记录
	*/
	public function getHistoryById($inqid)
	{
		$all = array();
		$sqlstr = "SELECT * FROM inquiry WHERE id='{$inqid}'";
		$inqArr = $this->_inqModer->getByOneSql($sqlstr);
		$inqArr['detaile'] = $this->getDetailedInquiry($inqid);
		return $inqArr;
	}
	/**
	 * 获取已经下单的订单
	 */
	public function getInqOrder($inqid){
		return $this->_inqModer->getBySql("SELECT salesnumber,status,back_status FROM `inq_sales_order` WHERE inquiry_id='{$inqid}'");
	}
	/**
	 * 获取有效期内的报价历史
	 */
	public function getPriceHistory($part_id,$pmpq,$currency){
		$re = array();
		$rearray=$this->_inqModer->getBySql("SELECT inqd.*,inq.currency
				FROM inquiry_detailed as inqd
				LEFT JOIN inquiry as inq ON inqd.inq_id=inq.id
				WHERE inqd.part_id='{$part_id}' AND inqd.result_price>0 AND inqd.pmpq>0
		        AND inqd.pmpq<='$pmpq' AND inqd.expiration_time>='".time()."'");
		//汇率
		$rateModel = new Default_Model_DbTable_Rate();
		if($rearray){
			if($currency=='RMB'){
				$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
				$rate = $arr['rate_value'];
				foreach($rearray as $v){
					if($v['currency']=='RMB'){
						$re[] = $v;
					}elseif($v['currency']=='USD'){
						$v['result_price'] = $v['result_price']*$rate;
						$re[] = $v;
					}
				}
			}elseif($currency=='HKD'){
				$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='HKD' AND status='1'");
				$rate = $arr['rate_value'];
				foreach($rearray as $v){
					if($v['currency']=='HKD'){
						$re[] = $v;
					}elseif($v['currency']=='USD'){
						$v['result_price'] = $v['result_price']*$rate;
						$re[] = $v;
					}
				}
			}else{
				foreach($rearray as $v){
					if($v['currency']=='USD'){
						$re[] = $v;
					}
				}
			}
		}
		return $re;
	}
}