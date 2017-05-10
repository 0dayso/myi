<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_LabService
{
	public function __construct() {
		$this->labModel = new Icwebadmin_Model_DbTable_Model('lab_place');
		$this->_fun = new MyFun();
	}
	/*
	 * 获取地区
	 */
	public function getPlace()
	{
		return $this->labModel->getBySql("SELECT * FROM lab_place WHERE status=1");
	}
	/**
	 * 获取实验室
	 */
	public function getRoom(){
		return $this->labModel->getBySql("SELECT * FROM lab_room WHERE status=1");
	}
	/**
	 * 获取实验器材
	 */
	public function getInstrument($where)
	{
		$sqlstr = "SELECT li.*,lr.name as roomname  FROM lab_instrument_place as lip
				LEFT JOIN lab_place as lp ON lip.place=lp.id
				LEFT JOIN lab_instrument as li ON lip.instrument=li.id
				LEFT JOIN lab_room as lr ON lr.id=li.room
				WHERE lip.status=1 AND lp.status=1 AND li.status=1 AND lr.status=1 $where
				ORDER BY lp.id , lr.id , li.id";
		return $this->labModel->getBySql($sqlstr);
	}
	/**
	 * 获取实验器材
	 */
	public function getInst()
	{
		$sqlstr = "SELECT * FROM lab_instrument WHERE status=1";
		return $this->labModel->getBySql($sqlstr);
	}
	/**
	 * 以地区+实验室 获取仪器
	 */
	public function getInstrumentByPlace($place){
		$rearray =array();
		$room  = $this->getRoom();
		$ins   = $this->getInstrument(" AND lip.place='{$place}' ");
		if(!empty($room) && !empty($ins)){
		   	 foreach($room as $rv){
		   	 	foreach($ins as $iv){
		   	 		if($rv['id']==$iv['room']) $rearray[$iv['room']][] = $iv;
			    }
		   	 }
		}
		return $rearray;
	}
	/**
	 * 获取所有优惠券数
	 */
	public function getNum($sql)
	{
		$sqlstr = "SELECT count(la.id) as num FROM lab_apply as la WHERE la.id!='' {$sql}";
		$allnumarr = $this->labModel->QueryRow($sqlstr);
		return $allnumarr['num'];
	}
	/**
	 * 获取通过和不通过的申请
	 */
	public function getRecord($offset,$perpage,$sql)
	{
		$orderbystr = "ORDER BY la.created DESC";
		$sqlstr = "SELECT la.*,lp.city,lp.address,u.uname,up.companyname
		FROM lab_apply as la
		LEFT JOIN user as u ON la.uid=u.uid
		LEFT JOIN user_profile as up ON la.uid = up.uid
		LEFT JOIN lab_place as lp ON la.place=lp.id
		WHERE la.id!='' {$sql} {$orderbystr} LIMIT $offset,$perpage";
		return $this->labModel->getBySql($sqlstr);
	}
	
	/**
	 * 获取实验器材使用情况
	 */
	public function getInstrumentRecord($sql)
	{
		$return = array();
		$sqlstr = "SELECT la.*,lp.city,lp.address,u.uname,up.companyname
		FROM lab_apply as la
		LEFT JOIN user as u ON la.uid=u.uid
		LEFT JOIN user_profile as up ON la.uid = up.uid
		LEFT JOIN lab_place as lp ON la.place=lp.id
		WHERE la.id!='' {$sql}";
		$re = $this->labModel->getBySql($sqlstr);
		foreach($re as $v){
			$instIds = explode(",",$v['instruments']);
			if($instIds){
				foreach($instIds as $instid){
					$v['inst'] = $this->labModel->QueryRow("SELECT li.ins_name,li.model,li.brand,lr.name as roomname FROM `lab_instrument` as li 
							LEFT JOIN  lab_room as lr ON li.room = lr.id
							WHERE li.id = '{$instid}'");
					$return[] = $v;
				}
			}
		}
		return $return;
	}
	//邮件通知
	public function mailalert($applyid,$help_name,$help_email){
		$filearray=array();//附件
		$sqlstr = "SELECT project_images,project_bom FROM lab_apply as la WHERE la.id='$applyid'";
		$applyinfo = $this->labModel->getRowBySql($sqlstr);
		if($applyinfo['project_images']) $filearray[5] = $applyinfo['project_images'];
		if($applyinfo['project_bom'])    $filearray[6] = $applyinfo['project_bom'];

		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
	
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">'.$help_name.'，有客户提交了开放实验室申请，请跟进。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
		$dflab = new Default_Service_LabService();
		$mess .=$dflab->getApplyLabTable($applyid);
		$mess .='<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >    
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" >
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;协助测试人&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
				       <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;是否在OA注册</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">OA注册项目名称</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;预计生产时间</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['expected_time'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;项目简介</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['project_des'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;项目框图</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;BOM单</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;</strong></td>
                            </tr>
				           <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;测试情况</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;<br/><br/></strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;后续安排</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;<br/><br/></strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                             <td colspan="4" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">量产后产品是否愿意在实验平台上展示，获取合作机会？</td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td colspan="4" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">&nbsp;<br/><br/><br/><br/></td>
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
		$fromname = 'IC易站';
		$title    = '客户开放实验室申请已通过，请跟进';
	
		$emailtype = 'labapply_user_app';
		
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress($emailtype);
			
		$emailto = $help_email;
		$emailcc = array($_SESSION['staff_sess']['email']);
		$emailbcc = array();
		
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,$filearray,array(),0);
	
	}
	/**
	 * 发邮件通知用户
	 * 
	 */
	public function mailalertToUser($applyid,$type=''){
		$usrservice = new Icwebadmin_Service_UserService();
		$sqlstr = "SELECT la.*,lp.city,lp.address
		FROM lab_apply as la
		LEFT JOIN lab_place as lp ON la.place=lp.id
		WHERE la.id='$applyid'";
		$applyinfo = $this->labModel->getRowBySql($sqlstr);
		$sellinfo = $usrservice->getUserProfile($applyinfo['uid']);
		if($type=='pass'){
			$title    = '您的开放实验室申请已通过';
		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
		
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您好，您的开放实验室申请已通过。请确认以下信息，准时进行测试。 
				            '.$applyinfo['help_name'].'（跟进人员）是您的专属协同测试人员，任何问题，请及时联系（'.$applyinfo['help_email'].'邮箱）。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
		}else{
			$title    = '您的开放实验室申请已被拒绝';
			$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
			
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">抱歉，您的开放实验室申请未能通过审核。
				            '.$applyinfo['remark'].'</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
		}
		$dflab = new Default_Service_LabService();
		$mess .=$dflab->getApplyLabTable($applyid);
		$fromname = 'IC易站';
		
		
		$emailtype = 'labapply_to_user';
		
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress($emailtype);
			
		$emailto = $sellinfo['email'];
		$emailcc = array();
		$emailbcc = array();
		
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),1);
		
	}
	
}