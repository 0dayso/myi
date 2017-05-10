<?php
class Default_Service_LabService
{
	public function __construct() {
		$this->labModel = new Default_Model_DbTable_Model('lab_place');
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
		$sqlstr = "SELECT count(la.id) as num FROM lab_apply as la WHERE la.uid='".$_SESSION['userInfo']['uidSession']."' {$sql}";
		$allnumarr = $this->labModel->QueryRow($sqlstr);
		return $allnumarr['num'];
	}
	/**
	 * 获取通过和不通过的申请
	 */
	public function getRecord($offset,$perpage,$sql)
	{
		$orderbystr = "ORDER BY la.created DESC";
		$sqlstr = "SELECT la.*,lp.city,lp.address
		FROM lab_apply as la
		LEFT JOIN lab_place as lp ON la.place=lp.id
		WHERE la.uid='".$_SESSION['userInfo']['uidSession']."' {$sql} {$orderbystr} LIMIT $offset,$perpage";
		return $this->labModel->getBySql($sqlstr);
	}
	//邮件通知
	public function mailalert($applyid,$place,$customers_bumen=''){
		$this->_fun = new MyFun();
		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
	
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有客户提交了开放实验室申请，请处理。详情请到“<a href="http://www.iceasy.com/icwebadmin/OlabAppl">申请管理</a>”查看。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
	   $mess .=$this->getApplyLabTable($applyid);
		$fromname = 'IC易站';
		$title    = '客户开放实验室申请，请处理';
	
		$emailtype = 'labapply_user';
		if($customers_bumen=='BMP'){
			$emailtype = 'labapply_user_BMP';
		}elseif($place){
			$emailtype = 'labapply_user_'.$place;
		}
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress($emailtype);
			
		$emailto = $emailcc = $emailbcc = array();
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
	/**
	 * 获取申请table
	 */
	public function getApplyLabTable($applyid){
		$sqlstr = "SELECT la.*,lp.city,lp.address
		FROM lab_apply as la
		LEFT JOIN lab_place as lp ON la.place=lp.id
		WHERE la.id='$applyid'";
		$applyinfo = $this->labModel->getRowBySql($sqlstr);
		$instrument = $this->getInst();

		$instruments_str= '';
		foreach($instrument as $v){
			if(in_array($v['id'],explode(',',$applyinfo['instruments']))) 
				$instruments_str .=$v['ins_name'].($v['model']?'('.$v['model'].')':'').'，';
		}
		$html = '<tr valign="top">
    <td >
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >    
        <tr>
           <td bgcolor="#f9f9f9">
            <table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                    <td valign="middle" colspan="2" align="left" height="40" >
                    <span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;申请信息&nbsp;&nbsp;&nbsp;</span>
                	</td>
                </tr>
                <tr>
                    <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                    <td valign="top" align="left" >
				       <table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;公司名</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['company'].'</strong></td>
                            </tr>
                            <tr>
                              <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系人</td>
                              <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['contact'].'</strong></td>
                              <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系电话</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['phone'].'</strong></td>
                            </tr>
                            
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;邮箱</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['email'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;申请地点</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['city'].'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;来访时间</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['vist_time'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;是否老客户</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.($applyinfo['customer']==1?'否':'是('.$applyinfo['follow'].')').'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;项目名称</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$applyinfo['project_name'].'</strong></td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;主要器件</td>
                              <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">'.str_replace('|',"<br/>",$applyinfo['project_device']).'</strong></td>
                            </tr>
                            <tr  bgcolor="#ffffff">
                              <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;申请实验仪器</td>
                              <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$instruments_str.'</strong></td>
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
		return $html;
	}
}