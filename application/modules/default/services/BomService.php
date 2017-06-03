<?php
class Default_Service_BomService
{
	private $_bomModer;
	private $_emailService;
	public function __construct()
	{
		$this->_bomModer = new Default_Model_DbTable_Bom();
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->fun = new MyFun();
	}
	/*
	 * 一个用户最多添加100个还没付款订单
	*/
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM bom WHERE uid=:uidtmp AND status=0";
		$all = $this->_bomModer->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/**
	 * 获取数数量
	 */
	public function getNum($sql='')
	{
		$sqlstr = "SELECT count(bo.id) as num FROM bom as bo  WHERE bo.uid=:uidtmp {$sql}";
		$allnumarr = $this->_bomModer->getByOneSql($sqlstr,$this->sqlarr);
		return $allnumarr['num'];
	}
	/*
	 * 获取用户Bom采购记录
	*/
	public function getBom($offset,$perpage,$typestr='')
	{
		$sqlstr = "SELECT bo.*
		FROM bom as bo WHERE bo.uid=:uidtmp  AND bo.status!='0' {$typestr}
		ORDER BY bo.created DESC LIMIT $offset,$perpage";
		$bomArr = $this->_bomModer->getBySql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($bomArr)){
		foreach($bomArr as $k=>$bom){
			$bomArr[$k]['detaile']=$this->getDetailedBom($bom['id']);
			}
		}
		return $bomArr;
	}
	/*
	 * 获取用户Bom详细记录
	*/
	public function getDetailedBom($bomid)
	{
		$sqlstr = "SELECT bod.*,
		b.name as brand,pd.part_img,pd.mpq,pd.lead_time
		FROM bom_detailed as bod 
		LEFT JOIN product as pd ON bod.part_id=pd.id 
		LEFT JOIN brand as b ON b.id=pd.manufacturer
		WHERE bod.bom_id='{$bomid}'";
		return $this->_bomModer->getBySql($sqlstr);
	}
	/*
	 * 根据询价编号获取询价详细
	*/
	public function getBomByID($id,$type=0,$status='')
	{
		$wheresql = '';$statussql = '';
		if($type==1) $wheresql = " AND iqd.expiration_time >='".time()."'";
		if($status) $statussql = " AND iq.status='{$status}'";
	
		$sqlstr = "SELECT iq.* FROM bom as iq
		WHERE iq.uid=:uidtmp AND iq.id='{$id}' {$statussql}";
		$inqArr = $this->_bomModer->getByOneSql($sqlstr,array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($inqArr)){
			$sqlstr = "SELECT iqd.* FROM bom_detailed as iqd WHERE iqd.bom_id='{$id}'".$wheresql;
			$inqArr['detaile'] = $this->_bomModer->getBySql($sqlstr);
			return $inqArr;
		}else return false;
		}
	/*
	 * 询价发送email通知销售
	*/
	public function sendBomAlertEmail($xs_name,$new_inqid,$user,$inqinfo,$xs_email,$cc=array(),$bcc=array())
	{
		$hi_mess ='<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$xs_name.',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有客户提交了BOM采购单，单号#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['bom_number'].'</strong>。请在24小时之内与客户联系，了解客户需求并根据具体情况跟进。</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">详细资料和采购信息请登录&nbsp;<a "http://www.iceasy.com/icwebadmin/QuoBom" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>盛芯电子后台</b></a>&nbsp;查看。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table>';
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'人民币 RMB','USD'=>'美元 USD','HKD'=>'港币 HKD');
	
		$mess .= $this->getBomTable($user,$inqinfo,$hi_mess,1);
	
		$fromname = '盛芯电子';
		$title    = '客户新建BOM采购，单号#'.$inqinfo['bom_number'].'#，请及时处理';
		
		$emailarr = $this->_emailService->getEmailAddress('new_bom');
		$emailto = array('0'=>$xs_email);
		$emailcc = $cc;
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
		
		$this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * 发送邮件给客户
	 */
	public function sendBomEmail($user,$inqinfo){

		$hi_mess ='<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$user['uname'].',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对盛芯电子的惠顾！确认收到您的BOM采购单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$inqinfo['bom_number'].'</strong>，我们会尽快处理。</div>
                      <!--<div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>-->
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如有任何不明之处，请根据单号与我们确认相关细节。您可以进入&nbsp;<a href="http://www.iceasy.com/center/bom" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>BOM采购</b></a>&nbsp;随时查看处理情况。</div>
                      </td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table>';
		
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$currencyArr = array('RMB'=>'人民币 RMB','USD'=>'美元 USD','HKD'=>'港币 HKD');
		
		$mess .= $this->getBomTable($user,$inqinfo,$hi_mess);
		$fromname = '盛芯电子';
		$title    = '盛芯电子已收到您的BOM采购单，会尽快处理';
		$emailarr = $this->_emailService->getEmailAddress('new_bom',$user['uid']);
		$emailcc  = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		$this->fun->sendemail($user['email'], $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * 获取询价信息table
	 */
	public function getBomTable($user,$inqinfo,$hi_mess,$utype=0){
		$propertyArr = array('merchant'=>'贸易商','enduser'=>'终端用户');
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$mess ='<!--hi-->
    <tr>
      <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
    </tr>
    <!-------------------------------------------------------内容------------------------------------------------------->
    <!--订单信息-->
    <tr valign="top">
      <td valign="bottom"  align="center" height="40"><div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">BOM采购单</div></td>
    </tr>';
	if($utype==1){
	  $usertitle = '请在24小时之内为客户提供以下产品的报价，或根据客户具体情况为客户推荐更合适的产品。';
	  $buttitle  = '';
      $mess .='<!--用户信息-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:separate; border-spacing:0px" >
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;用户信息&nbsp;&nbsp;&nbsp;</span><span style="color:#03b000">&nbsp;&nbsp;用户名：<b>quoteshin</b></span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6">
                      <tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;公司名称：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['companyname'].'</strong></td>
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
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong>&nbsp;&nbsp;'.$this->fun->createAddress($user['province'],$user['city'],$user['area'],$user['address']).'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;邮编：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$user['zipcode'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';}else{
       $usertitle = '我们会尽快为您寻找以下产品，也会根据您的具体要求为您推荐更适合的产品。';
       $buttitle  = '<tr>
      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; margin:0; padding:0">
          <tr>
            <td><p style="padding:0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">&nbsp;再次感谢您对盛芯电子的支持!欲了解更多产品信息，请登录&nbsp;<a href="http://www.iceasy.com" style="color:#0055aa"><strong>www.iceasy.com</strong></a>。</p></td>
          </tr>
        </table></td>
    </tr>';
    }
    
    $mess .='<!--BOM采购详细-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:separate; border-spacing:0px" >
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;BOM采购详细&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6">
                      <tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;结算货币：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$inqinfo['currency'].'</strong></td>
                        <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;交货地：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$deliveryArr[$inqinfo['delivery']].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>
    <!--未上线产品-->
    <tr>
      <td valign="top" align="left" ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0">
          <tr>
            <td bgcolor="#ffffff" height="30" valign="bottom" colspan="2"><strong style="font-size:14px; color:#000000;">&nbsp;&nbsp;'.$usertitle.'</strong></td>
          </tr>
          <tr>
            <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
            <td bgcolor="#f9f9f9"><table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">
                <tr bgcolor="#f3f3f3">
                  <th width="35" height="30">项次</th>
                  <th>产品型号</th>
                  <th>品牌</th>
                  <th>需求数量</th>
                  <th>目标单价('.$inqinfo['currency'].')</th>
                  <th>需求交货日期</th>
                  <th>备注</th>
                </tr>';
    foreach($inqinfo['detaile'] as $itm=>$v){
    	$mess .= ' <tr bgcolor="#FFFFFF" >
                  <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($itm+1).'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#0055aa; ">'.$v['part_no'].'</strong></td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['brand_name'].'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$v['number'].'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.$inqinfo['currency'].$v['price'].'</strong></td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.date("Y/m/d",$v['deliverydate']).'</td>
                  <td style="border-top:1px solid #d6d6d6">'.$v['description'].'</td>
                </tr>';
    }
               
                
              $mess .= '</table></td>
          </tr>
        </table></td>
      </td>
    </tr>'.$buttitle;
		return $mess;
	}
}