<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_BomService
{
	private $_bomModer;
	private $_bomdetModer;
	private $_emailService;
	public function __construct()
	{
		$this->_bomModer = new Icwebadmin_Model_DbTable_Bom();
		$this->_bomdetModer = new Icwebadmin_Model_DbTable_BomDetailed();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
	}
	/*
	 * 获取bom采购记录
	*/
	public function getAllBoms($offset,$perpage)
	{
		$sqlstr = "SELECT b.*,u.uname,up.companyname,up.truename,up.mobile,up.tel
		FROM bom as b
		LEFT JOIN user as u ON b.uid = u.uid
		LEFT JOIN user_profile as up ON b.uid = up.uid
		LIMIT $offset,$perpage";
		return $this->_bomModer->getBySql($sqlstr);
	}
	/**
	 * 根据条件，获取询价总数
	 */
	public function getNumber($sql){
		return $this->getNum("SELECT count(id) as num FROM bom as bo
		LEFT JOIN user_profile as up ON bo.uid = up.uid
		WHERE bo.id!='' {$sql}");
	}
	/*
	 * 获取待询价总数
	*/
	public function getWaitNum($xswhere){
		return $this->getNum("SELECT count(id) as num FROM bom as bo 
				LEFT JOIN user_profile as up ON bo.uid = up.uid 
				WHERE bo.status=1 {$xswhere}");
	}
	/*
	 * 获取所有已报价总数
	*/
	public function getAlreadyNum($xswhere){
		return $this->getNum("SELECT count(id) as num FROM bom as bo
				LEFT JOIN user_profile as up ON bo.uid = up.uid 
				 WHERE bo.status='2' {$xswhere}");
	}
	/**
	 * 获取搜索总数
	 */
	public function getSelectNum($xswhere,$keyword){
		return $this->getNum("SELECT count(id) as num FROM bom as bo 
				LEFT JOIN user_profile as up ON bo.uid = up.uid 
				WHERE bo.bom_number LIKE '%".$keyword. "%' {$xswhere}");
	}
	/*
	 * 根据条件获取待询价记录
	*/
	public function getBom($offset,$perpage,$sql)
	{
		$sqlstr = $this->getBomSql($sql,$offset,$perpage);
		return $this->getBomBySql($sqlstr);
	}
	/*
	 * 获取待询价记录
	*/
	public function getWaitBom($offset,$perpage,$xswhere)
	{
		$sqlstr = $this->getBomSql(" AND bo.status=1 ".$xswhere,$offset,$perpage);
		return $this->getBomBySql($sqlstr);
	}
	/*
	 * 获取已询价记录
	*/
	public function getAlreadyBom($offset,$perpage,$xswhere)
	{
		$sqlstr = $this->getBomSql(" AND bo.status=2 ".$xswhere,$offset,$perpage);
		return $this->getBomBySql($sqlstr);
	}
	/**
	 * 获取搜索
	 */
	public function getSelectBom($offset,$perpage,$xswhere,$keyword)
	{
		$sqlstr = $this->getBomSql(" AND bo.bom_number LIKE '%".$keyword."%' ".$xswhere,$offset,$perpage);
		return $this->getBomBySql($sqlstr);
	}
	/*
	 * 获取用户询价详细记录
	*/
	public function getDetailedBom($inqid)
	{
		$sqlstr = "SELECT bod.*,
		b.name as brand,pd.part_img,pd.mpq,pd.lead_time
		FROM bom_detailed as bod 
		LEFT JOIN product as pd ON bod.part_id=pd.id
		LEFT JOIN  brand as b ON b.id=pd.manufacturer
		WHERE bod.bom_id='{$inqid}'";
		return $this->_bomdetModer->getBySql($sqlstr);
	}
	/**
	 * 用户询价记录
	 *
	 */
	public function userInqInfo($uid){
		$sqlstr = "SELECT id,status FROM bom WHERE uid='{$uid}'";
		$inqrr = $this->_bomdetModer->getBySql($sqlstr);
		$num_0 = $num_1 =$num_2 =$num_3 =$num_4 =$num_5 =$num_6 =0;
		if(!empty($inqrr)) {
			foreach($inqrr as $inq){
				if($inq['status']==0) $num_0++;
				elseif($inq['status']==1) $num_1++;
				elseif($inq['status']==2) $num_2++;
			}
		}
		$rearr[0]= $num_0;
		$rearr[1]= $num_1;
		$rearr[2]= $num_2;
		return $rearr;
	}
	//获取用户信息
	public function getUserById($inq){
		$sql = "select a.uid,app1.name as appname1,app2.name as appname2,c.uname as user,c.email,up.*
		from bom a
		left join app_category app1 on app1.id = a.app_1_id
		left join app_category app2 on app2.id = a.app_2_id
		left join user c on c.uid = a.uid
		left join user_profile up on a.uid = up.uid
		where a.id  = '{$inq}'";
		return $this->_bomModer->getByOneSql($sql);
	}
	private function getBomSql($where,$offset,$perpage){
		return "SELECT bo.*,up.companyname ,up.staffid as upstaffid,up.property,
		sta.email,sta.lastname,sta.firstname,app1.name as appname1,app2.name as appname2
		FROM bom as bo
		LEFT JOIN user_profile as up ON bo.uid = up.uid 
		LEFT JOIN admin_staff as sta ON sta.staff_id = up.staffid
		LEFT JOIN app_category as app1 ON app1.id = bo.app_1_id
		LEFT JOIN app_category as app2 ON app2.id = bo.app_2_id
		WHERE bo.id!='' {$where}
		ORDER BY bo.created DESC LIMIT $offset,$perpage";
	}
	/*
	 * 获取记录bysql
	*/
	private function getBomBySql($sqlstr){
		$inqArr = $this->_bomModer->getBySql($sqlstr);
		if(!empty($inqArr)){
			foreach($inqArr as $k=>$inq){
				$inqArr[$k]['detaile']=$this->getDetailedBom($inq['id']);
				//有再议价记录的
				if(!empty($inq['son_inquiry']))
				{
					$inqArr[$k]['lastson'] = $this->getDetailedBom(end(explode(",",$inq['son_inquiry'])));
				}
				//如果有父亲询价
				if(!empty($inq['father_inquiry']))
				{
					$inqArr[$k]['father'] = $this->getDetailedBom($inq['father_inquiry']);
				}
			}
		}
		return $inqArr;
	}
	/*
	 * 根据询价编号获取询价详细
	*/
	public function getBomByID($id,$status='')
	{
		$statussql = '';
		if($status) $statussql = ' AND status = {$status}';
		$sqlstr = "SELECT bo.*
		FROM bom as bo WHERE bo.id='{$id}' {$statussql}";
		$inqArr = $this->_bomModer->getByOneSql($sqlstr);
		if(!empty($inqArr)){
		  $sqlstr = "SELECT bod.*,
		   b.name as brand,pd.part_img,pd.mpq,pd.lead_time
		FROM bom_detailed as bod 
		LEFT JOIN product as pd ON bod.part_id=pd.id
		LEFT JOIN  brand  as b ON b.id=pd.manufacturer
		WHERE bod.bom_id='{$id}'";
		$inqArr['detaile'] = $this->_bomdetModer->getBySql($sqlstr);
		return $inqArr;
		}else return false;
		}
		/*
		 * 根据询价编号获取询价详细
		*/
		public function getBomByNumber($inq_number,$status='')
		{
			$statussql = '';
			if($status) $statussql = ' AND status = {$status}';
			$sqlstr = "SELECT bo.*
			FROM bom as bo WHERE bo.bom_number='{$inq_number}' {$statussql}";
			$inqArr = $this->_bomModer->getByOneSql($sqlstr);
			if(!empty($inqArr)){
			$sqlstr = "SELECT bod.*,
			b.name as brand,pd.part_img,pd.mpq,pd.lead_time
			FROM bom_detailed as bod
			LEFT JOIN product as pd ON bod.part_id=pd.id
			LEFT JOIN  brand  as b ON b.id=pd.manufacturer
			WHERE bod.bom_id='".$inqArr['id']."'";
			$inqArr['detaile'] = $this->_bomdetModer->getBySql($sqlstr);
			return $inqArr;
			}else return false;
		}
	/*
	 * 获取记录数
	*/
	private function getNum($sqlstr)
	{
		$allver = $this->_bomModer->getBySql($sqlstr,array());
		return $allver[0]['num'];
	}
	/**
	 * 获取负责销售
	 */
	public function getSellByBomid($id){
		$sql = "SELECT st.lastname,st.firstname,st.tel,st.ext,st.phone,st.email FROM bom as bo
		        LEFT JOIN user_profile as up ON up.uid = bo.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE bo.id = '{$id}'";
		return $this->_bomModer->getByOneSql($sql);
	}
	/**
	 * 发邮件通知需要添加型号
	 */
	public function emailaddprod($bom,$sellinfo,$changeinq)
	{
		$emailarr = $this->_emailService->getEmailAddress('bom_inquiry_add_pord',$bom['uid']);
		if($emailarr['to'][0]){
		  $toname = explode("@",$emailarr['to'][0]);
		  $toname = $toname[0];
		}
		$hi_mess ='<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$toname.',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有销售提交了BOM采购转询价单请求，请及时添加型号并生成询价单。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table>';
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'人民币 RMB','USD'=>'美元 USD','HKD'=>'港币 HKD');
		
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
                              <td style="background:#ffffff;font-family:\'微软雅黑\'; border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;邮箱：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;<a style="color:#000000;font-family:\'微软雅黑\'" href="mailto:'.$sellinfo['email'].'"><strong>'.$sellinfo['email'].'</strong></a></strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;手机：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6
									"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['phone'].'</strong></td>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6 ; border-bottom:1px solid #d6d6d6">&nbsp;&nbsp;电话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$sellinfo['tel'].'-'.$sellinfo['ext'].'</strong></td>
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

   $mess .='
<tr>
    <td valign="top" align="left" >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0;border-collapse:collapse" >
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #fd2323; text-align:center; border-collapse:collapse">
                    <tr bgcolor="#f3f3f3">
                        <th>型号</th>
                        <th>品牌</th>
                        <th>需求数量</th>
                    </tr>';
		foreach($bom['detaile'] as $k=>$v){
			if(in_array($v[id],$changeinq)){
			$mess .='<tr bgcolor="#FFFFFF" >
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['brand_name'].'</td>
                        <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['number'].'</td>
                    </tr>';
			}
		}
		$mess .='
            </table>
        </td>   
            </tr>
        </table>
        </td>     
    </td>
</tr>';
		$fromname = '盛芯电子';
		$title    = 'BOM采购转询价单，单号#'.$bom['bom_number'].'#，请及时处理';
		
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailcc,$emailarr['cc']);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/**
	 * 成功转询价邮件通知销售
	 */
	public function emailinqtosell($inqinfo,$user,$staffinfo,$bom){
		$inqService = new Default_Service_InquiryService();
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
		
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$staffinfo['lastname'].$staffinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">你提交的BOM采购#：'.$bom['bom_number'].' 转询价单已经生成，询价单号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['inq_number'].'</strong>&nbsp;，请在进入客户询价模块继续处理。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">详细资料请登录&nbsp;<a href="http://www.iceasy.com/icwebadmin" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;" target="_blank"><b>盛芯电子后台</b></a>&nbsp;查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess = $inqService->getInqXsTable($inqinfo,$user,$hi_mess);
		$fromname = '盛芯电子';
		$title    = 'BOM采购转询价已经产生，询价单号#：'.$inqinfo['inq_number'].'，请处理';
		
		$emailarr = $this->_emailService->getEmailAddress('new_inquiry');
			
		$emailto = array('0'=>$staffinfo['email']);
		$emailcc = array('0'=>$_SESSION['staff_sess']['email']);
		$emailbcc = array();

		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
}