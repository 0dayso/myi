<?php
class Default_Service_EmailtypeService
{
	private $_emailtypeModel;
	private $_staffModel;
	public function __construct() {
		$this->_emailtypeModel = new Default_Model_DbTable_EmailType();
		$this->_staffModel=new Default_Model_DbTable_AdminStaff();
		if(isset($_SESSION['userInfo']['uidSession']))
		  $this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		else $this->sqlarr = array('uidtmp'=>'');
	}
	/**
	 * 获取发送邮件地址
	 */
	public function getEmailAddress($type,$uid=''){
		$re = $this->_emailtypeModel->getRowByWhere("type='{$type}' AND status=1");
		if($re){
			$toarr  = $ccarr = $bccarr = array();
			if(!empty($re['to'])){
				$toarr = explode('|',$re['to']);
			}
			if(!empty($re['cc'])){
				$ccarr = explode('|',$re['cc']);
			}
			if(!empty($re['bcc'])){
				$bccarr = explode('|',$re['bcc']);
			}
			
			if(!empty($re['to_staffid'])){
				$totmparr = explode('|',$re['to_staffid']);
				foreach($totmparr as $staffid){
					$retmp = $this->_staffModel->getRowByWhere("staff_id = '{$staffid}'");
					if($retmp['email']) $toarr[] = $retmp['email'];
				}
			}
			if(!empty($re['cc_staffid'])){
				$cctmparr = explode('|',$re['cc_staffid']);
				foreach($cctmparr as $staffid){
					$retmp = $this->_staffModel->getRowByWhere("staff_id = '{$staffid}'");
					if($retmp['email']) $ccarr[] = $retmp['email'];
				}
			}
			if(!empty($re['bcc_staffid'])){
				$bcctmparr = explode('|',$re['bcc_staffid']);
				foreach($bcctmparr as $staffid){
					$retmp = $this->_staffModel->getRowByWhere("staff_id = '{$staffid}'");
					if($retmp['email']) $bccarr[] = $retmp['email'];
				}
			}
			$staffservice = new Icwebadmin_Service_StaffService();
			$sellinfo = $staffservice->sellbyuid($uid);
			//释放订单时发送给不同负责人
			$cse_name = 'CSE';
		    if($re['other_mail'] && !$re['to_user']){
    		    $other_mail = explode('<>',$re['other_mail']);
    		    
    		    foreach($other_mail as $mail_str){
    			    $mailarray  = explode('|',$mail_str);
    			    $to_mail    = $mailarray[0];
    			    $staffarray = explode(',',$mailarray[1]);
    			    if(in_array($sellinfo['staff_id'],$staffarray)){
    				   if($to_mail) $toarr = array_merge($toarr,array($to_mail));
    				   $cse_email = explode("@",$to_mail);
    				   $cse_name = $cse_email[0];
    			    }
    		    }
    	    }
			//暗送给负责客户的销售
			if($re['bcc_sell'] && $uid){
				$sellinfo = $staffservice->sellbyuid($uid);
				$sellemail = array($sellinfo['email']);
				//如果有抄送人
				$email_to = $staffservice->mailtostaff($sellinfo['mail_to']);
				if($re['to_user']){
				    if($sellemail){
				    	$bccarr = array_merge($bccarr,$email_to);
				    	$bccarr = array_merge($bccarr,$sellemail);
				    }
				}else{
				    if($sellemail){
				    	$ccarr = array_merge($ccarr,$email_to);
				    	$ccarr = array_merge($ccarr,$sellemail);
				    }
				}
			}
			return array('to'=>$toarr,'cc'=>$ccarr,'bcc'=>$bccarr,'cse_name'=>$cse_name);
		}else return false;
	}
}