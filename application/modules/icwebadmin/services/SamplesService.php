<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_SamplesService
{
	private $_samplesModel;
	private $_samplesapplyModel;
	private $_samplesdetailedModel;
	public function __construct() {
		$this->_samplesModel = new Icwebadmin_Model_DbTable_Model("samples");
		$this->_samplesapplyModel = new Icwebadmin_Model_DbTable_Model("samples_apply");
		$this->_samplesdetailedModel = new Icwebadmin_Model_DbTable_Model("samples_detailed");
	}
	/**
	 * 根据型号获取ats sz 库存
	 */
	public function getAtsSzByPartno($partno){
		$oas = new Icwebadmin_Service_OainquiryService();
		$re = $oas->GetDwByPartNo($partno);
		if($re){
			return $re['SQS_SZ'];//+$re['TOOL_SZ']+$re['NORMAL_SZ'];
		}else return 0;
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getApplyNum($typestr='')
	{
		$sqlstr = "SELECT count(spa.id) as num  FROM samples_apply as spa
		LEFT JOIN user_profile as up ON up.uid = spa.uid
		WHERE spa.id!='' $typestr";
		return $this->_samplesapplyModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	*/
	public function getApply($offset=0,$perpage=0,$typestr='')
	{
		if($offset && $perpage) $ost = "ORDER BY spa.id DESC LIMIT $offset,$perpage";
		else $ost = '';
 		$sqlstr ="SELECT spa.*,u.uname,up.companyname,up.oa_code,
		p.province,c.city,e.area,
    	a.name as sname,a.address,a.zipcode,a.mobile,a.tel,
    	ch.cou_number,ch.track,cou.name as couname,
    	st.staff_id,st.email,st.tel as sttel,st.ext,st.phone,st.lastname,st.firstname,dp.department
    	FROM samples_apply as spa
		LEFT JOIN user as u ON u.uid = spa.uid
		LEFT JOIN user_profile as up ON up.uid = spa.uid
		
		LEFT JOIN order_address as a ON spa.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	
    	LEFT JOIN courier_history as ch ON ch.id = spa.courierid
    	LEFT JOIN courier as cou ON ch.cou_id = cou.id
    	
    	LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    	LEFT JOIN admin_department as dp ON st.department_id = dp.department_id
		WHERE spa.id!='' $typestr $ost";
		$re = $this->_samplesapplyModel->getBySql($sqlstr);
		foreach($re as $k=>$v){
			$sql = "SELECT sd.*,p.break_price,p.break_price_rmb FROM `samples_detailed` as sd 
					LEFT JOIN product as p ON p.id=sd.part_id
					WHERE sd.applyid='".$v['id']."'";
			
			$re[$k]['detailed'] = $this->_samplesdetailedModel->Query($sql);
		}
		return $re;
	}
	/**
	 * 获取记录
	 */
	public function getApplyById($id)
	{
		$sqlstr ="SELECT spa.*,u.email as useremail,u.uname,up.companyname,
		p.province,c.city,e.area,
		a.name as sname,a.address,a.zipcode,a.mobile,a.tel,
		ch.cou_number,ch.track,cou.name as couname,
		st.staff_id,st.email,st.tel as sttel,st.ext,st.phone,st.lastname,st.firstname,dp.department
		FROM samples_apply as spa
		LEFT JOIN user as u ON u.uid = spa.uid
		LEFT JOIN user_profile as up ON up.uid = spa.uid
	
		LEFT JOIN order_address as a ON spa.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid
		 
		LEFT JOIN courier_history as ch ON ch.id = spa.courierid
		LEFT JOIN courier as cou ON ch.cou_id = cou.id
		 
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    	LEFT JOIN admin_department as dp ON st.department_id = dp.department_id
    	
		WHERE spa.id ='{$id}' LIMIT 1";
		$re = $this->_samplesapplyModel->QueryRow($sqlstr);
		$re['detailed'] = $this->_samplesdetailedModel->getAllByWhere("applyid='".$id."'");
		return $re;
	}
	/**
	 * 获取count()行数
	 */
	public function getSamplesNum($str='')
	{
		$sqlstr = "SELECT count(sp.id) as num FROM samples as sp
		LEFT JOIN product as p ON sp.part_id = p.id
		WHERE p.status=1 {$str}";
		return $this->_samplesModel->QueryItem($sqlstr);
	}
	/**
	 * 获取所有样品
	 */
	public function getSamples($offset,$perpage,$typestr,$orderbystr='')
	{
		if(!$orderbystr){
			$orderbystr = "ORDER BY sp.hot_top DESC ,sp.created DESC";
		}
		$limit = '';
		if($offset || $perpage) $limit = "LIMIT $offset,$perpage";
		$sqlstr = "SELECT sp.*,p.samples,p.part_no,b.name as brandname
		FROM samples as sp
		LEFT JOIN product as p ON sp.part_id = p.id
		LEFT JOIN brand as b ON b.id = p.manufacturer
		WHERE p.status=1 AND sp.status=1 {$typestr} {$orderbystr} {$limit}";
		return $this->_samplesModel->getBySql($sqlstr);
	}
	/*
	 * 获取样片
	 */
	public function getSamplesById($id){
		$sqlstr = "SELECT sp.id,sp.part_id,sp.hot_top,p.part_no,b.name as brandname
		FROM samples as sp
		LEFT JOIN product as p ON sp.part_id = p.id
		LEFT JOIN brand as b ON b.id = p.manufacturer
		WHERE sp.id = '{$id}'";
		return $this->_samplesModel->getRowBySql($sqlstr);
	}
	/*
	 * 更新样片
	 */
	public function upsample($id,$arr){
		return $this->_samplesModel->update($arr, "id='{$id}'");
	}
	/*
	 * 添加样片
	*/
	public function addsample($data){
		return $this->_samplesModel->addData($data);
	}
	/*
	 * 删除
	*/
	public function deletesample($id){
		return $this->_samplesModel->update(array('status'=>0),"id='{$id}'");
	}
	/*
	 * 获取限制金额
	 */
	public function getSamplestotal(){
		return $this->_samplesModel->QueryItem("SELECT value FROM `dictionary` WHERE type='samplestotal'");
	}
	/**
	 * 释放样片订单邮件
	 */
	public function emailcse($apply){
		$fromname = '盛芯电子';
		$title    = '#样片订单# - 订单号:'.$apply['salesnumber'].'，请处理';
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('samples_order_release',$apply['uid']);
		$cse_name = 'Shirley Yang';
		if($emailarr['to'][0]){
			$cse_email = explode("@",$emailarr['to'][0]);
			$cse_name = $cse_email[0];
		}
		$hi_mess     = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$cse_name.',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">盛芯电子样片订单，请使用
                                                <b style="color:#fd2323; font-size:15px;"> EC SAMPLE CUSTOMER CODE </b>处理。</div>
                                               </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		
		$mess .= $this->getTable($apply,$hi_mess);
		
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
		$this->fun = new MyFun();
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
		
	}
	/**
	 * 释放样片订单通知客户邮件
	 */
	public function emailuser($apply){
		$fromname = '盛芯电子';
		$title    = '样片申请已经处理';
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('samples_order_release_user');

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
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; font-size:14px">感谢对盛芯电子的支持，您在盛芯电子申请的样片已经处理，我们会尽快安排发货。</div>
                                               </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
	
		$mess .= $this->getTable($apply,$hi_mess);
	
		$emailto = $apply['useremail'];
		$emailcc = $emailbcc = array();
		
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		$this->fun = new MyFun();
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),1);
	
	}
	/**
	 * 内容table
	 */
	public function getTable($apply,$hi_mess,$type=''){
	
		$mess ='<tr>
                    <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>';
if($type==0){
$mess .='<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40">
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;销售相关&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
                      <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;销售：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['lastname'].$apply['firstname'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;部门：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['department'].'</strong></td>
                            </tr>
                            <tr>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;手机：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'">'.$apply['phone'].'</strong></td>
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;固话：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['sttel'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;邮箱：</td>
                              <td  colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" ><strong style="color:#000000;font-family:\'微软雅黑\'"><a href="mailto:'.$apply['email'].'">'.$apply['email'].'</a></strong></td></tr></table></td>
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
$mess .='<!--订单-->    		
<tr valign="top">
  <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
      <tr>
        <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
            <tr>
              <td valign="middle" colspan="2" align="left" height="40"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;订单详情&nbsp;&nbsp;&nbsp;</span> </td>
            </tr>
            <tr>
              <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
              <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                  <tr  bgcolor="#ffffff">
                    <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;盛芯电子订单号：</td>
                    <td width="300" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($apply['salesnumber']?$apply['salesnumber']:'--').'</strong></td>
                    <td width="100" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;客户名称：</td>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($apply['companyname']?$apply['companyname']:$apply['uname']).'</strong></td>
                  </tr>
                  <tr>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;订单类型：</td>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'">样片订单</strong></td>
                    <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;提交时间：</td>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.date('Y-m-d H:i',$apply['created']).'</strong></td>
                  </tr>
                  <tr>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;订单金额：</td>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'">RMB 0</strong></td>
                    <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;交货地：</td>
                    <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;国内</strong></td>
                  </tr>
                </table></td>
            </tr>
          </table></td>
      </tr>
    </table></td>
</tr>';          		
$mess .='<tr>
    <td valign="top" align="left" >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0;border-collapse:collapse" >
            <tr>
                <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
                <td bgcolor="#f9f9f9">
                <table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">
                    <tr bgcolor="#f3f3f3">
                        <th width="35" height="30">项次</th>
                        <th>产品型号</th>
                        <th>品牌</th>
                        <th>数量</th>
                    </tr>';
		if($apply['detailed']){
			foreach($apply['detailed'] as $k=>$v){
				$mess .='<tr bgcolor="#FFFFFF" >
                        <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($k+1).'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$v['brandname'].'</td>
                        <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.($v['approvenum']?$v['approvenum']:$v['applynum']).'</td>
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
</tr>
		
<!--收货信息-->
<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
        <tr>
           <td bgcolor="#f9f9f9">
				<table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
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
                              <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;收货人：</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['sname'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;手机：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['mobile'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;固定电话：</td>
                              <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['tel'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;详细地址：</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$apply['province'].$apply['city'].$apply['area'].$apply['address'].'</strong></td>
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