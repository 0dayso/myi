<?php
class Icwebadmin_Service_StaffService
{
	private $_staffModer;
	private $_userfrofileModer;
	private $_userapplyModer;
	private $_emailService;
	public function __construct()
	{
		$this->_staffModer = new Icwebadmin_Model_DbTable_Staff();
		$this->_userfrofileModer = new Icwebadmin_Model_DbTable_UserProfile();
		$this->_userapplyModer = new Icwebadmin_Model_DbTable_UserApply();
		$this->_emailService = new Default_Service_EmailtypeService();
	}
	/**
	 * 检查是否是销售
	 */
	public function checkSell()
	{
		$sql = "SELECT st.id FROM admin_staff as st
    			WHERE st.status=1 AND st.level_id='XS' AND staff_id=:staff_idtmp";
		$xiaoshou = $this->_staffModer->getBySql($sql,array("staff_idtmp"=>$_SESSION['staff_sess']['staff_id']));
		if($xiaoshou) return true;
		else return false;
	}
	/**
	 * 获取销售列表
	 */
	public function getXiaoShou()
	{
		$sql = "SELECT st.*,dp.department FROM admin_staff as st
				LEFT JOIN admin_department as dp ON st.department_id = dp.department_id
    			WHERE st.status=1 AND st.level_id='XS' ORDER BY st.disroder";
		return $this->_staffModer->getBySql($sql);
	}
	/*
	 * 获取部门销售
	 */
	public function getDepXs($staffchose=array()){
		$arr = array();
		$xiaoshou = $this->getXiaoShou();
		foreach($xiaoshou as $v){
			if(in_array($v['department_id'],array('BMP','BNT'))){
				if($staffchose){
					if(in_array($v['staff_id'],$staffchose)){
						$arr[$v['department_id']][] = array($v['staff_id'],$v['lastname'].$v['firstname']);
					}
				}else{
			       $arr[$v['department_id']][] = array($v['staff_id'],$v['lastname'].$v['firstname']); 
				}
			}
		}
		return $arr;
	}
	/**
	 * 获取销售email
	 */
	public function sellemailbyuid($uid)
	{
		$sql = "SELECT st.email
				FROM admin_staff as st
				LEFT JOIN user_profile as up ON up.staffid = st.staff_id
				WHERE st.status=1 AND up.uid='{$uid}'";
		$re = $this->_staffModer->getByOneSql($sql);
		return $re;
	}
	/**
	 * 获取销售信息
	 */
	public function sellbyuid($uid)
	{
		$sql = "SELECT st.*,ad.department
		FROM admin_staff as st
		LEFT JOIN user_profile as up ON up.staffid = st.staff_id
		LEFT JOIN admin_department as ad ON ad.department_id = st.department_id
		WHERE st.status=1 AND up.uid='{$uid}'";
		$re = $this->_staffModer->getByOneSql($sql);
		return $re;
	}
	/**
	 * 获取信息
	 */
	public function getStaffInfo($staff_id)
	{
		return $this->_staffModer->getRowByWhere("status=1 AND staff_id='".$staff_id."'");
	}
	/**
	 * 获取需要发送的邮件
	 */
	public function mailtostaff($mail_to){
		$re = array();
		if($mail_to){
			$mail_to_arr = explode(',',$mail_to);
			foreach($mail_to_arr as $mail_staff){
				$staff = $this->getStaffInfo($mail_staff);
				if($staff['email']) $re[] = $staff['email'];
			}
		}
		return $re;
	}
	//中电品牌抄送给研发
	public function ccToYafa($prodarr,$emailcc)
	{
		//中电品牌抄送给研发
		$yafa = false;
		foreach($prodarr as $v){
			if($v['manufacturer']==42 && !$yafa) $yafa = true;
		}
		if($yafa){
			$ccarray = array('bony.fu','xiaoyan.guo');
			foreach($ccarray as $staffid){
				$info = $this->getStaffInfo($staffid);
				if($info) $emailcc = array_merge($emailcc,array($info['email']));
			}
		}
		return $emailcc;
	}
	/**
	 * 获取公司名称
	*/
	public function getCompany()
	{
		$sql = "SELECT u.uid,u.uname,up.companyname FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid 
    			WHERE up.companyname !='' AND u.enable=1";
		return $this->_userfrofileModer->getBySql($sql);
	}
	/*
	 * 获取审批人
	*/
	public function getSuperior($staff_id='')
	{	
		if(!$staff_id) $staff_id=$_SESSION['staff_sess']['staff_id'];
		$myinfoarray = $this->_staffModer->getByOneSql("SELECT s2.staff_id,s2.email,s2.lastname,s2.firstname
				FROM admin_staff as s 
				LEFT JOIN admin_staff as s2 ON s.superior = s2.staff_id 
				WHERE s.staff_id=:staff_idtmp",array('staff_idtmp'=>$staff_id));
		return $myinfoarray;
	}
	/**
	 * 获取待审核数量
	 */
	public function getAppTotal()
	{
		$sql = "SELECT count(uoa.id) as num FROM  user_oa_apply as uoa
				WHERE uoa.approval_staffid='".$_SESSION['staff_sess']['staff_id']."' AND uoa.status=100";
		$re = $this->_userapplyModer->getByOneSql($sql);
		return $re['num'];
	}
	/**
	 * 获取待审核和提交审核数量
	 */
	public function getAppNum()
	{
		$sql = "SELECT count(uoa.id) as num FROM  user_oa_apply as uoa
				WHERE (uoa.apply_staffid = '".$_SESSION['staff_sess']['staff_id']."' OR uoa.approval_staffid='".$_SESSION['staff_sess']['staff_id']."')";
		$re = $this->_userapplyModer->getByOneSql($sql);
		return $re['num'];
	}
	/**
	 * 获取待审核用户
	 */
	public function getApply($offset,$perpage)
	{
		$sqlstr = "SELECT uoa.*,u.uname,
				   st.lastname,st.firstname,st2.lastname as applastname,st2.firstname as appfirstname
    	           FROM  user_oa_apply as uoa
				   LEFT JOIN admin_staff as st ON uoa.apply_staffid =st.staff_id
				   LEFT JOIN admin_staff as st2 ON uoa.approval_staffid =st2.staff_id
				   LEFT JOIN user as u ON uoa.uid=u.uid
    	           WHERE (uoa.apply_staffid = '".$_SESSION['staff_sess']['staff_id']."' OR uoa.approval_staffid='".$_SESSION['staff_sess']['staff_id']."')
    	           ORDER BY uoa.status ASC,uoa.id DESC
    	           LIMIT $offset,$perpage ";
		return $this->_userapplyModer->getBySql($sqlstr);
	}
	/**
	 * 获取待审核用户by uid
	 */
	public function getApplyUser($uid)
	{
		$sqlstr = "SELECT uoa.*,up.oa_code,up.oa_sales as up_oa_sales,oac.name as cname,oap.name as pname,oaci.name as ciname FROM  user_oa_apply as uoa
		LEFT JOIN user_profile as up ON uoa.uid=up.uid
		LEFT JOIN oa_country as oac ON oac.id =uoa.country
		LEFT JOIN oa_province as oap ON oap.id =uoa.region
		LEFT JOIN oa_city as oaci ON oaci.id =uoa.city
		WHERE uoa.uid='{$uid}'";
		return $this->_userapplyModer->getByOneSql($sqlstr);
	}
	/**
	 * 获取联系人信息
	 */
	public function getApplyContact($apply_id)
	{
		$sqlstr = "SELECT uoac.* FROM  user_oa_apply_contact as uoac WHERE uoac.apply_id='{$apply_id}'";
		return $this->_userapplyModer->getByOneSql($sqlstr);
	}
	/**
	 * 获取数据字典名称
	 */
	public function getOadictionary(){
		$re = array();
		//oa数据字典
		$oa_dictionary_model = new Icwebadmin_Model_DbTable_Model('oa_dictionary');
		$dictionary = $oa_dictionary_model->getAllByWhere("status=1");
		foreach($dictionary as $v){
			$re[$v['type']][$v['oa_id']] = $v['name'];
		}
		return $re;
	}
	/**
	 * 更新审核状态
	 */
	public function updateApply($data, $id)
	{
		return $this->_userapplyModer->update($data, "id={$id}");
	}
	/**
	 * 更用户
	 */
	public function updateUserprofile($data, $uid)
	{
		return $this->_userfrofileModer->update($data, "uid={$uid}");
	}
	/**
	 * 更改分配销售，发邮件通知
	 */
public function emailResetxs($tomail,$xsname,$userinfo)
	{
		$compname = $userinfo['companyname']?$userinfo['companyname']:$userinfo['uname'];
		$emailFun = new MyFun();

		$mess ='</tbody>
        </table><tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.($userinfo['lastname'].$userinfo['firstname']).',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">IC易站已经将客户<strong style="color:000000">“'.$compname.'”</strong>分配给您负责，请跟进。您可以进入 <a href="http://www.iceasy.com/icwebadmin/UsUsgl" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>IC易站后台</b></a> 查看。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <!-------------------------------------------------------内容------------------------------------------------------->
    <!--用户信息-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" > 
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;客户信息&nbsp;&nbsp;&nbsp;</span><span style="color:#03b000">&nbsp;&nbsp;用户名：<b>'.$userinfo['uname'].'</b></span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" > <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                      <tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;公司名称：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['companyname']?$userinfo['companyname']:'--').'</strong></td>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;联系人：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['truename']?$userinfo['truename']:'--').'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;Email：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['email']?$userinfo['email']:'--').'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;电话：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['tel']?$userinfo['tel']:'--').'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;手机：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['mobile']?$userinfo['mobile']:'--').'</strong></td>
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;传真：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userinfo['fax']?$userinfo['fax']:'--').'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;详细地址：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; colspan="3"><strong>&nbsp;&nbsp;'.($userinfo['province']?$emailFun->createAddress($userinfo['province'],$userinfo['city'],$userinfo['area'],$userinfo['address']):'--').'</strong></td>

                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';
		$fromname = 'IC易站';
		$title    = '销售负责客户分配';
		$emailarr = $this->_emailService->getEmailAddress('email_resetxs');
		$emailto = array('0'=>$tomail);
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
		
		return $emailFun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	}
	/**
	 * 更改分配销售，发邮件通知
	 */
	public function emailOaclient($superior,$staff_sess,$formData)
	{
		$emailFun = new MyFun();
		$mess = '尊敬的'.$superior['lastname'].$superior['firstname'].',您好！<br/>';
		$mess .= $staff_sess['lastname'].$staff_sess['firstname'].'已经在IC易站提交新客户注册OA申请，请及时去审核。公司名称：“'.$formData['oa_apply']['client_cname'].'”。';
		$mess .= '<br/>详情，请登录<a href="http://'.$_SERVER['HTTP_HOST'].'/icwebadmin" target="_blank">IC易站后台</a>查看。谢谢！';
	
		$fromname = 'IC易站';
		$title    = 'IC易站新客户注册OA申请';
		$emailarr = $this->_emailService->getEmailAddress('email_oaclient');
		$emailto = array('0'=>$superior['email']);
		$emailcc = array('0'=>$staff_sess['email']);
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
		return $emailFun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * ，发邮件通知
	 */
	public function emailOaclientFeedback($userapply,$status)
	{
		$emailFun = new MyFun();
		$mess = '尊敬的'.$userapply['lastname'].$userapply['firstname'].',您好！<br/>';
		$mess .= $userapply['applastname'].$userapply['appfirstname'].'已经对你提交新客户注册OA的申请进行了审核，审核结果：'.($status==101?'通过':'不通过').'。公司名称：“'.$userapply['client_cname'].'”。';
		$mess .= '<br/>详情，请登录<a href="http://'.$_SERVER['HTTP_HOST'].'/icwebadmin" target="_blank">IC易站后台</a>查看。谢谢！';
	
		$fromname = 'IC易站';
		$title    = 'IC易站新客户注册OA审核结果';
		$emailarr = $this->_emailService->getEmailAddress('email_oaclientfeedback');
		$emailto = array('0'=>$userapply['applyemail']);
		$emailcc = array('0'=>$userapply['applastemail']);
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
		return $emailFun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * 添加新账号，发邮件通知客户
	 */
	public function emailToUser($userarr,$uparr)
	{
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($uparr['uid']);
		$compname = $uparr['companyname']?$uparr['companyname']:$userarr['uname'];
		$emailFun = new MyFun();
		
		$mess ='</tbody>
        </table><tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.($uparr['truename']).',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">IC易站为您添加了一个新账号。密码由IC易站自动生成，你随时可以进入<a href="http://www.iceasy.com/center/info" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>个人资料</b></a>进行修改，谢谢。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <!-------------------------------------------------------内容------------------------------------------------------->
    <!--用户信息-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;用户信息&nbsp;&nbsp;&nbsp;</span></td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" > <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                      <tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;用户名：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$userarr['uname'].'</strong></td>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;登录密码：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userarr['pass_back']).'</strong></td>
                      </tr>
                  		<tr  bgcolor="#ffffff">
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;公司名称：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($uparr['companyname']?$uparr['companyname']:'--').'</strong></td>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;联系人：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($uparr['truename']?$uparr['truename']:'--').'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;Email：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($userarr['email']?$userarr['email']:'--').'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;电话：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($uparr['tel']?$uparr['tel']:'--').'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;手机：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($uparr['mobile']?$uparr['mobile']:'--').'</strong></td>
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;传真：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($uparr['fax']?$uparr['fax']:'--').'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';
		$fromname = 'IC易站';
		$title    = '新IC易站账号创建成功';
		$emailarr = $this->_emailService->getEmailAddress('register');
		$emailto = array('0'=>$userarr['email']);
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $emailFun->sendemail($userarr['email'], $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
		
	}
}