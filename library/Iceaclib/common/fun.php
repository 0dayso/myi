<?php
require_once 'Iceaclib/common/filter.php';
class MyFun
{
	/*
	 * 发送邮件
	 * 
	 */
	//人民币兑美元汇率
	 private $_USDTOCNY;
	 private $_emailService;
	 public function __construct() {
	 	//人民币兑美元汇率
		$rateModel = new Default_Model_DbTable_Rate();
		$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='RMB' AND status='1'");
		$this->_USDTOCNY = $arr['rate_value'];
		$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='HKD' AND status='1'");
		$this->_USDTOHKD = $arr['rate_value'];
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->commonconfig = Zend_Registry::get('commonconfig');
	}
	public function getUSDToRMB(){
		return $this->_USDTOCNY;
	}
	public function getUSDToHKD(){
		return $this->_USDTOHKD;
	}
	/**
	 * 发送Email
	 * @param unknown_type $email
	 * @param unknown_type $mess
	 * @param unknown_type $fromname
	 * @param unknown_type $title
	 * @param unknown_type $cc
	 * @param unknown_type $bcc
	 * @param unknown_type $filearray
	 * @param unknown_type $xiaoshou 销售信息
	 * @param unknown_type $mailtype 类型1客户，0内部
	 * @return boolean
	 */
	function sendemail($email,$mess,$fromname,$title,$cc=array(),$bcc=array(),$filearray=array(),$xiaoshou=array(),$mailtype=1)
	{
		set_time_limit(0);
		Zend_Loader::loadClass('Zend_Mail');
		Zend_Loader::loadClass('Zend_Mail_Transport_Smtp');
		$mail= new Zend_Mail('utf-8');
		$smtpTest = new Zend_Mail_Transport_Smtp('smtpcom.263xmail.com',
				array('name'=>'smtpcom.263xmail.com',
						'username'=>'iceac@ceacsz.com.cn',
						'password'=>'iceac@123*',
						'auth'=>'login'));
		if(!empty($filearray)){
		  $this->filter = new MyFilter();
		  $mimename = array('营业执照','税务登记证','转账水单','余额转账水单','订单电子版合同','项目框图','BOM单');
		  foreach($filearray as $key=>$file){
		  	if($file && file_exists($file)){
		  	   $mimetype = $this->filter->getMimeType($file);
		  	   if($mimetype){
		  	   	//处理中文命名乱码
		  	   	 $newname = "=?UTF-8?B?".base64_encode(($mimename[$key]).".".$mimetype['extend'])."?=";
		  	     $mail->createAttachment(file_get_contents($file),$mimetype['mimetype'], Zend_Mime::DISPOSITION_INLINE  , Zend_Mime::ENCODING_BASE64,$newname);	
		  	   }
		  	}
		  }
		}
		
		$mail->setFrom('iceac@ceacsz.com.cn',$fromname);
		$mail->setSubject($title);
		//加上底部
		$foot_email = $xiaoshou['email']?$xiaoshou['email']:$this->commonconfig->email->foot_email;
		if($xiaoshou['tel'] || $xiaoshou['phone']){
			$foot_tel   = '电话：'.($xiaoshou['tel']?$xiaoshou['tel'].'&nbsp;&nbsp;&nbsp;&nbsp;手机：'.$xiaoshou['phone']:$xiaoshou['phone']);
			$foot_name  = $xiaoshou['lastname'].$xiaoshou['firstname'];
		}else{
			$foot_name = '尚玉';
			$foot_tel   = '客服热线：'.$this->commonconfig->email->foot_tel.'&nbsp;&nbsp;&nbsp;&nbsp;手机：'.$this->commonconfig->email->foot_model;
		}
		//客户邮件
		if($mailtype ==1){
		//头部
		$mess_html =  '<table cellspacing="10" border="0" cellpadding="0" width="770" bgcolor="#ffffff"  style=" padding:0; margin:0; text-align:left;font-family:\'微软雅黑\';border:10px solid #ededed;border-collapse:separate;border-spacing:10px;">
<tbody>
<!-------------------------------------------------------头部------------------------------------------------------->
<tr valign="top">
    <td>
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#ffffff" style="font-family:\'微软雅黑\'; font-size:12px;color:#5b5b5b; ">
            <tbody>
                <tr>
                    <td valign="middle" align="right" height="30" bgcolor="#f9f9f9" style="font-family:\'微软雅黑\';  background:#f9f9f9" >
                    <span>温馨提示：</span>为了确保能正常收到IC易站的邮件，请将&nbsp;<a href="#" style="color:#ff6600; margin:0 2px; text-decoration:none"><b>IC易站&lt;iceac@ceacsz.com.cn&gt;</b></a>添加为您的邮件联系人。
                    </td>
                </tr>
                <!--logo-->
                <tr>
                    <td valign="top" bgcolor="#ffffff" align="center">
                        <table cellspacing="0" border="0" cellpadding="0" width="730">
                            <tbody>
                                <tr>
                                    <td valign="middle" height="80" >
                                         <a href="http://www.iceasy.com" target="_blank"><img src="http://www.iceasy.com/images/default/brand/logo_t.jpg" height="58" alt="IC易站" style="border:0; font-size:18px; font-weight:bold; color:#fd2323" /></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <!--menu-->
                <tr>
                    <td >
                        <table align="center" valigh="middle" border="0" cellpadding="0" cellspacing="0" ><tbody>
                            <tr>
                                <td align="left" valigh="middle" bgcolor="#f1f1f1" height="34" width="610" style="color:#999999;font-family:\'微软雅黑\';">
                                    <a href="http://www.iceasy.com" style="text-decoration:none;font-family:\'微软雅黑\';color:#5b5b5b; font-size:14px;" target="_blank">&nbsp;&nbsp;&nbsp;IC易站首页</a>
                                    │
                                    <a href="http://www.iceasy.com/category" style="text-decoration:none;font-family:\'微软雅黑\';color:#5b5b5b;font-size:14px;" target="_blank">产品目录</a>
                                    │
                                    <a href="http://www.iceasy.com/solutionlist" style="text-decoration:none;font-family:\'微软雅黑\';color:#5b5b5b;font-size:14px;" target="_blank">应用方案</a>
                                    │
                                    <a href="http://www.iceasy.com/brand-14.html" style="text-decoration:none;font-family:\'微软雅黑\';color:#5b5b5b;font-size:14px;" target="_blank">热卖品牌</a>
                                    │
                                    <a href="http://www.iceasy.com/webinarlist" style="text-decoration:none;font-family:\'微软雅黑\';color:#5b5b5b;font-size:14px;" target="_blank">技术研讨会</a>
                                </td>
				                <td align="center" valigh="middle" bgcolor="#fd2323" height="34" width="120" >
				                    <a href="http://www.iceasy.com/center" style="text-decoration:none;font-family:\'微软雅黑\'; color:#ffffff;font-size:14px;" target="_blank">登录我的易站</a> </td>
                            </tr>
                        </tbody></table>
                    </td>               
                </tr>
           <tr><td height="10" style="line-height:1px; font-size:10px; margin:0; padding:0 "></td></tr>
		   <!--hi-->'.$mess;
		
		//底部签名
		$mess_html .='<!--签名-->
	<tr><td height="10" style="line-height:1px; font-size:10px; margin:0; padding:0 "></td></tr>
    <tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="10" border="0" cellpadding="0" width="730" style="font-size:12px; line-height:20px; font-family:\'微软雅黑\'; border-collapse:separate; border-spacing:10px; border:1px dotted #ddd; border-top:2px solid #fd2323;text-align:left">
          <tbody>
            <tr>
              <td height="30" align="left" valign="middle" style="width:110px;"><img src="http://www.iceasy.com/images/default/brand/logo_f.jpg" height="28" width="100" alt="IC易站"  style="border:0; font-size:14px; font-weight:bold"/> </td>
              <td style="text-align:left; width:620px; font-size:14px; font-weight:bold; color:#555555; font-family:\'微软雅黑\'; ">中电器材旗下元器件电商平台</td>
            </tr>
            <tr>
              <td colspan="2" ><div style="padding:0px 0 2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px"><b>销售代表：'.$foot_name.'</b></div>
                <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">Email：<a href="mailto:'.$foot_email.'" style="color:#0055aa">'.$foot_email.'</a></div>
                <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">'.$foot_tel.'</div>
                <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">地 址：'.$this->commonconfig->email->foot_address.'</div>
                <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">IC易站（<a href="'.$this->commonconfig->email->foot_url.'" style="color:#0055aa;font-family:\'微软雅黑\'">www.iceasy.com</a>）'.$this->commonconfig->email->foot_remark.'</div></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
  </tbody>
</table>';
		}else{ //内部邮件
			//头部
		$mess_html =  '<table cellspacing="10" border="0" cellpadding="0" width="770" bgcolor="#ffffff"  style=" padding:0; margin:0; text-align:left;font-family:\'微软雅黑\';border:10px solid #ededed;border-collapse:separate;border-spacing:10px;">
<tbody>
<!-------------------------------------------------------头部------------------------------------------------------->
<tr valign="top">
    <td>
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#ffffff" style="font-family:\'微软雅黑\'; font-size:12px;color:#5b5b5b; ">
            <tbody>
                <tr>
                    <td valign="middle" align="right" height="30" bgcolor="#f9f9f9" style="font-family:\'微软雅黑\';  background:#f9f9f9" >
                    <span>温馨提示：</span>为了确保能正常收到IC易站的邮件，请将&nbsp;<a href="#" style="color:#ff6600; margin:0 2px; text-decoration:none"><b>IC易站&lt;iceac@ceacsz.com.cn&gt;</b></a>添加为您的邮件联系人。
                    </td>
                </tr>
                <!--logo-->
                <tr>
                    <td valign="top" bgcolor="#ffffff" align="center">
                        <table cellspacing="0" border="0" cellpadding="0" width="730">
                            <tbody>
                                <tr>
                                    <td valign="middle" height="80" >
                                         <a href="http://www.iceasy.com" target="_blank"><img src="http://www.iceasy.com/images/default/brand/logo_t.jpg" height="58" alt="IC易站" style="border:0; font-size:18px; font-weight:bold; color:#fd2323" /></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <!--menu-->
                <tr><td height="1" bgcolor="f1f1f1" style="line-height:1px; font-size:0; ">&nbsp;</td></tr>
                <tr><td height="20"></td></tr><!--hi-->'.$mess;
		
		//底部签名
		$mess_html .='<!--签名-->
	<tr><td height="10" style="line-height:1px; font-size:10px; margin:0; padding:0 "></td></tr>
    <tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="10" border="0" cellpadding="0" width="730" style="font-size:12px; line-height:20px; font-family:\'微软雅黑\'; border-collapse:separate; border-spacing:10px; border:1px dotted #ddd; border-top:2px solid #fd2323;text-align:left">
          <tbody>
            <tr>
              <td height="30" align="left" valign="middle" style="width:110px;"><img src="http://www.iceasy.com/images/default/brand/logo_f.jpg" height="28" width="100" alt="IC易站"  style="border:0; font-size:14px; font-weight:bold"/> </td>
              <td style="text-align:left; width:620px; font-size:14px; font-weight:bold; color:#555555; font-family:\'微软雅黑\'; ">中电器材旗下元器件电商平台</td>
            </tr>
			<tr>
                 	<td colspan="2" >
                    <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">Email：<a href="mailto:'.$foot_email.'" style="color:#0055aa">'.$foot_email.'</a></div>
                    <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">'.$foot_tel.'</div>
                    <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">地 址：'.$this->commonconfig->email->foot_address.'</div>
                    <div style="padding:2px 0; margin:0;color:#5b5b5b;font-family:\'微软雅黑\'; line-height:20px">IC易站（<a href="'.$this->commonconfig->email->foot_url.'" style="color:#0055aa;font-family:\'微软雅黑\'">www.iceasy.com</a>）'.$this->commonconfig->email->foot_remark.'</div>
                    
                    </td>
            </tr>
          </tbody>
        </table></td>
    </tr>
  </tbody>
</table>';
		}
		/*$mail->addCc('bella.chen@ceacsz.com.cn');
		$mail->addCc('314442481@qq.com');
		//$mail->addCc('jiaoli.liu@ceacsz.com.cn');
		$mail->addCc('iceasytest2013@126.com');
		$mail->addCc('iceasytest2013@gmail.com');*/
		//echo $mess_html;exit;
		$mail->setBodyHtml($mess_html);

		//增加一个收件人到邮件头“Cc”（抄送）
		if(is_array($cc))
		{
			  $cc = array_unique($cc);
			  foreach($cc as $ccvalue){
				 if($ccvalue) $mail->addCc($ccvalue);
			  }
		}elseif(!empty($cc)){
			$mail->addCc($cc);
		}
		//增加一个收件人到邮件头“Bcc”（暗送）
		if(is_array($bcc))
		{
			 $bcc = array_unique($bcc);
			 foreach($bcc as $bccvalue){
			    if($bcc)  $mail->addBcc($bccvalue);
			 }
		}elseif(!empty($bcc)){
			   $mail->addBcc($bcc);
		}
		//发送人
		
		if(is_array($email))
		{
			  $email = array_unique($email);
			  foreach($email as $emailvalue){
				if($emailvalue) $mail->addTo($emailvalue); //被发送的邮件地址
			  }
		}elseif($email){
			$mail->addTo($email); //被发送的邮件地址
		}
		//try {
			$mail->send($smtpTest);
			return true;
// 		} catch (Exception $e) {
// 			$adminlogService = new Icwebadmin_Service_AdminlogService();
// 			$adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>'mail_error','temp4'=>'发送邮件失败','description'=>$e->getMessage()));
// 			//发送提醒邮件
// 			$mail= new Zend_Mail('utf-8');
// 			$staffService = new Icwebadmin_Service_StaffService();
// 			$mail->setFrom('iceac@ceacsz.com.cn','IC易站邮件系统');
// 			$mail->setSubject('IC易站发送邮件失败');
// 			$errormess = 'IC易站发送邮件出现错误，请及时解决。错误：'.$e->getMessage().'。<br/><br/>邮件内容：<br/>'.$mess_html;
// 			$mail->setBodyHtml($errormess);
// 			$to = $staffService->getStaffInfo('jamie.feng');
// 			if($to && $to['email']){
// 				$mail->addTo($to['email']);
// 			}
// 			$cc = $staffService->getStaffInfo('andyxian');
// 			if($cc && $cc['email']){
// 				$mail->addCc($cc['email']);
// 			}
// 		    try {
// 		    	$mail->send($smtpTest);
// 		    	return false;
// 		    }catch (Exception $e) {
// 		    	return false;
// 		    }
// 		}
		
	}
	/**
	 * 读取邮件
	 */
	public function getmailAction(){
		set_time_limit(0);
		$mail = new Zend_Mail_Storage_Pop3(array('host'=> 'pop.ceacsz.com.cn',
				'user'     => 'iceac@ceacsz.com.cn',
				'password' => 'iceac@123*'));
		echo $mail->countMessages() . " messages found<br>";
		foreach ($mail as $messageNum=>$message) {
			$frommail = '';
			$fromarr = explode('<',$message->from);
			if($fromarr[1]) $frommail=trim(str_replace('>','',$fromarr[1]));
			if(!$frommail){
				//防备获取发信箱
			    $fromarr = explode(' ', $message->received);
			    if($fromarr[1]){
			       $from_mail = trim(str_replace('?','@',$fromarr[1]));
			    }
			}
			echo "<br>Mail from: ".$from_mail;
			
			$subjectarr  = explode('?', $message->subject);
			echo "<br>subject: ".$message->subject.' '.base64_decode($subjectarr[3]);
			
			echo "<br>date: ".date("Y-m-d H:i:s",strtotime($message->date));

			echo '<br/>';			
			echo quoted_printable_decode($mail->getMessage($messageNum));
		}
	}
	/*
	 * 发注册验证码邮件
	*/
	function sendverification($hashkey,$toemail,$uname){
		$activationLink = 'http://'.$_SERVER['HTTP_HOST'].'/user/verification?hashkey='.$hashkey;
		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
                    <tr>
                      <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$uname.',</div></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您注册IC易站，请点击以下链接激活账号。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="10" border="0" width="730" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b; border:1px #c4d1d7 solid;background:#f7fdff;border-collapse:separate;border-spacing:10px;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;font-family:\'微软雅黑\';"><a href="'.$activationLink.'" target="_blank" style="color:#0055aa;">'.$activationLink.'</a></div>
                              <div style="height:10px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如果您无法点击此链接，请将它复制到浏览器地址后访问。</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">此邮件由系统自动发出，请勿直接回复。</div>
                              <div style="padding:3px 0;margin:0;color:#ff6600;font-family:\'微软雅黑\';"><strong>为了保障您账号的安全性，请在1小时内完成激活，1小时之后链接地址失效。</strong></div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
		
		$fromname = 'IC易站';
		$title    = '欢迎注册IC易站，请激活您的账号';
		$emailarr = $this->_emailService->getEmailAddress('register');
		$emailto = array('0'=>$toemail);
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
		return $this->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/*
	 * 新注册用户感谢函及IC易站介绍
	*/
	function newuserEmail($toemail,$uname){
		$mess ='</tbody>
        </table></td>
    </tr>
	<tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
                    <tr>
                      <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$uname.',</div></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您已成功注册为IC易站用户，再次感谢您对IC易站的支持！</div>
                              <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';"><a href="http://www.iceasy.com" target="_blank" style="color:#0055aa">IC易站</a>&nbsp;是&nbsp;<a href="http://www.ceacsz.com.cn" target="_blank" style="color:#0055aa">中电器材</a>&nbsp;旗下领先的一站式元器件电商平台，专注于服务电子制造企业的设计链和供应链，满足其从产品设计到批量生产的全程需求！无论是小批量样片还是大批量采购,都是您最佳的一站式元器件在线采购平台。</div>
                              <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">IC易站不仅拥有海量的元器件产品可供您在线采购和询价，还有应用于各个领域的&nbsp;<a href="http://www.iceasy.com/solutionlist" target="_blank" style="color:#0055aa;font-family:\'微软雅黑\';">设计方案</a>&nbsp;和&nbsp;<a href="http://www.iceasy.com/webinarlist" target="_blank" style="color:#0055aa;font-family:\'微软雅黑\';">研讨会</a>&nbsp;信息，同时满足您对产品和技术的需求。IC易站传承中电器材30年品牌价值，汇聚众多国内外领先品牌，100%正品保证，及时的询价响应,便捷的在线下单流程，快速的物流服务，无论是小批量样片还是大批量采购,都是您最佳的一站式元器件在线采购平台。 </div></td>
                          </tr>
                        </table></td>
                    </tr>
                  </tbody>
                </table></td>
            </tr>
    <!-------------------------------------------------------内容------------------------------------------------------->
    <tr valign="top">
      <td><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#ffffff" >
          <tbody>
            <tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="border-top:1px solid #f1f1f1; margin-top:10px;">
                  <tbody>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:18px;">
                          <tr>
                            <td align="center" colspan="2"><img src="http://www.iceasy.com/upload/default/adpice/home/email_01.jpg"  width="730"/></td>
                          </tr>
                          <tr>
                            <td height="40" valign="middle"  colspan="2"><h4 style="font-size:16px; color:#333; font-family:\'微软雅黑\';  padding:0; margin:0">汇聚众多国内外领先品牌</h4></td>
                          </tr>
                          <tr>
                            <td align="center" colspan="2"><a href="http://www.iceasy.com/brand-14.html"  target="_blank"><img src="http://www.iceasy.com/upload/default/adpice/home/email_02.jpg"  width="730"/></a></td>
                          <tr>
                            <td width="500"><h4 style="font-size:16px; color:#333; font-family:\'微软雅黑\'; padding:0; margin:0">IC易站 — 您的电子元器件在线服务专家</h4>
                              <ul style="font-size:14px; line-height:24px; color:#333; font-family:\'微软雅黑\'; list-style-type:disc;">
                                <li>海量元器件产品，热卖新品，每日更新 </li>
                                <li>最新技术解决方案及参考设计</li>
                                <li>样片及小批量元器件在线下单</li>
                                <li>大批量元器件询价及在线采购</li>
                                <li>专业的元器件仓储、拆包、再包装服务</li>
                                <li>专业技术工程师提供在线支持</li>
                              </ul></td>
                            <td><img src="http://www.iceasy.com/upload/default/adpice/home/email_03.jpg"  width="180"/></td>
                          </tr>
                          <tr>
                            <td height="10"></td>
                          </tr>
                          <tr>
                            <td align="center" colspan="2" height="60"  valign="middle" width="730px" bgcolor="#f1f1f1" ><table cellpadding="0" cellspacing="0" style="font-family:\'微软雅黑\'; font-size:13px; margin:0 auto; width:730px;">
                                <tr>
                                  <td align="left" style="font-family:\'微软雅黑\'; color:#535353; line-height:24px; margin:0; text-align:left">&nbsp;&nbsp;立即登录&nbsp;<a href="http://www.iceasy.com"  target="_blank" style="color:#f00"><strong>www.iceasy.com</strong></a>，寻找您需要的产品和方案，体验IC易站的全方位在线服务！ </td>
                                  <td valign="bottom" align="right"><a href="http://www.iceasy.com/user/login"  target="_blank" style="background:#fd2323; padding:5px; color:#fff; text-decoration:none; font-size:14px;" >&nbsp;&nbsp;立即登录&nbsp;&nbsp;</a> </td>
                                </tr>
                                <tr>
                                  <td align="left"   valign="top" style="color:#535353;line-height:20px; margin:0;text-align:left">&nbsp;&nbsp;再次感谢您对IC易站的支持！ </td>
                                </tr>
                              </table></td>
                          </tr>
                        </table></td>
                    </tr>
                  </tbody>
                </table></td>
            </tr>
           </tbody>
        </table></td>
    </tr>';
	
		$fromname = 'IC易站';
		$title    = 'IC易站账号注册成功，欢迎使用IC易站服务';
		$emailarr = $this->_emailService->getEmailAddress('register');
		$emailto = array('0'=>$toemail);
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
		return $this->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * 后台添加用户发邮件
	 */
	function senduseremail($toemail,$uname,$pass,$uid){
		$link = '<a href="http://www.iceasy.com" target="_blank">IC易站</a>';
		$mess ='<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" style="background:#fff;line-height:20px; font-family:12px; font-family:\'微软雅黑\'">
	<tr>
    	<td align="left" style="padding-bottom:24px; padding-left:10px">
        	<table algin="center" width="698" border="0" cellpadding="0" cellspacing="0" style="background:#fff;">
                <tr>
                	<td align="left">
                    	<table width="698" style="border:1px solid #ddd;border-top:2px solid #f00">
                        	<tr>
                            	<td align="center">
                                	<table style="width:636px;padding:0px 16px; margin-top:10px;text-align:left;">
                                    	<tr>
                                            <td style=" font-size:14px;padding-bottom:10px; font-weight:bold; color:#f00;">尊敬的'.$uname.'，您好!</td>
                                        </tr>
                                    	<tr>
                                            <td style="color:#666; padding-bottom:8px;font-size:12px;">感谢注册IC易站，您的用户信息如下（请妥善保管）。请到'.$link.'进行登录。</td>
                                        </tr>
                                        <tr>
                                            <td style=" background:#EEF8FF; overflow:hidden; zoom:1; padding:16px; border:1px solid #CEE4F6;font-size:12px;">
                                                <p>用户名:'.$uname.'</p>
                                                <p>密码:'.$pass.'</p>
                                                <p style="color:#999;">此邮件由系统自动发出，请勿直接回复。</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr><td height="10"></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>';
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($uid);
		
		$emailarr = $this->_emailService->getEmailAddress('admin_user');
		$emailto = array('0'=>$toemail);
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
		$fromname = 'IC易站';
		$title    = '欢迎注册IC易站[您的账号注册邮件]';
		return $this->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/*
	 * 发找回密码验证邮件
	*/
	function sendforgotpass($key,$uname,$toemail){
		$activationLink = 'http://'.$_SERVER['HTTP_HOST'].'/user/resetpass?key='.$key;
		$mess ='</tbody>
        </table>
				<tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
                    <tr>
                      <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$uname.',</div></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对IC易站的支持！请您点击以下链接获取验证码，并在密码重置页面设置新密码。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="10" border="0" width="730" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b; border:1px #c4d1d7 solid;background:#f7fdff;border-collapse:separate;border-spacing:10px;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;font-family:\'微软雅黑\';"><span style="color:#0055aa;"><a href="'.$activationLink.'" target="_blank" style="color:#0055aa;">'.$activationLink.'</a></span></div>
                              <div style="height:10px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如果您无法点击此链接，请将它复制到浏览器地址后访问。</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">此邮件由系统自动发出，请勿直接回复。</div>
                              <div style="padding:3px 0;margin:0;color:#ff6600;font-family:\'微软雅黑\';"><strong>为了保障您的账号安全，请在1小时内完成密码重设，1小时后链接地址失效。</strong></div></td>
                          </tr>
                        </table></td>
                    </tr>
                  </tbody>
                </table></td>
            </tr>';
		$fromname = 'IC易站';
		$title    = 'IC易站账号密码重置提示';
		
		$emailarr = $this->_emailService->getEmailAddress('register');
		$emailto = array('0'=>$toemail);
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
		return $this->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc);
	}
	/**
	 * 获取客户端IP
	 *
	 * @param array
	 */
	public function getIp(){
		$onlineip="";
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$onlineip = getenv('HTTP_CLIENT_IP');
		} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$onlineip = getenv('REMOTE_ADDR');
		} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}
		return $onlineip;
	}
	/**
	 * 加密
	 *
	 * @param array
	 */
	function encrypt_aes($input){
		if (!$input){
			return;
		}
		/* Data */
		$key='IC secert key, web team. strong with dfgdfgd 32 bit';
		$iv='m3bmwasiv4200909m3bmwasiv4200909';
		/* Open module, and create IV */
		$td = mcrypt_module_open('rijndael-256','','cfb','');
		$key = substr($key,0,mcrypt_enc_get_key_size($td));
	
		/* Reinitialize buffers for decryption*/
		mcrypt_generic_init($td,$key,$iv);
	
		/* Encrypt data */
		$c_t = mcrypt_generic($td,$input);
	
		/*  Clean up */
		mcrypt_generic_deinit($td);
		mcrypt_module_close ($td);
	
		/*conversion  store data */
		return base64_encode($c_t);
	}
	/**
	 * 解密
	 *
	 * @param array
	 */
	function decrypt_aes($input){
		if (!$input){
			return;
		}
		/* Data */
		$key='IC secert key, web team. strong with dfgdfgd 32 bit';
		$iv='m3bmwasiv4200909m3bmwasiv4200909';
		/* Open module, and create IV */
		$td = mcrypt_module_open('rijndael-256','','cfb','');
		$key = substr($key,0,mcrypt_enc_get_key_size($td));
	
		/* Reinitialize buffers for decryption*/
		mcrypt_generic_init($td,$key,$iv);
	
		/* Decrypt data */
		$p_t = mdecrypt_generic($td,base64_decode($input));
	
		/* Clean up */
		mcrypt_generic_deinit($td);
		mcrypt_module_close ($td);
		return trim($p_t);
	}
	/**
	 * 加密转义
	 *
	 * @param array
	 */
	public function encryptVerification($str){
		//加密并转意
		$hashkey = rawurlencode($this->encrypt_aes($str));
		return $hashkey;
	}
	/**
	 * 转义解密
	 *
	 * @param array
	 */
	public function decryptVerification($hashkey){
		$str = $this->decrypt_aes(rawurldecode($hashkey));
		return $str;
	}
	/**
	 * 加入汇率后的价格
	 *
	 * @param array
	 */
	public function Rate($price){
		return $price*$this->_USDTOCNY;
	}
	/*
	 * 显示阶梯价格
	*/
	public function showbreakprice($break_price)
	{
		$str = '';
		if(!empty($break_price)){
		$pricearr = array_filter(explode(';',$break_price));
		$str='<table cellspacing="0" cellpadding="0" border="0" class="tiptable">';
		for($i=0;$i<count($pricearr);$i++)
		{
		$price = explode('|',$pricearr[$i]);
		//价格美元换人民币
	    $showprice = $this->usdtocny($price[1]);
		
		$str .= '<tr><td class="fontgreen" style="text-align:right">'.trim((int)$price[0]).'+</td><td class="fontred">￥'.$this->formnum($showprice,5).'</td></tr>';
		}
		$str.='</table>';
		}
		return $str;
	}
	public function showbreakprice2($break_price)
	{
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			$str='<table cellspacing="0" cellpadding="0" border="0" class="tiptable">
					<tr><th>数量</th><th>价格</th></tr>';
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			//价格美元换人民币
			$showprice = $this->usdtocny($price[1]);
			$str .= '<tr><td  class="fontgreen" >'.trim((int)$price[0]).'+</td><td class="fontred">￥'.$this->formnum($showprice,5).'</td></tr>';
			}
					$str.='</table>';
		}
		return $str;
	}
	public function showbreakprice3($break_price)
	{
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			  //价格美元换人民币
			  $showprice = $this->usdtocny($price[1]);
			  $str .= '('.$price[0].','.$showprice.')';
			}
		}
		return $str;
	}
	/**
	 * 显示阶梯价格
	 */
	public function getbreakprice($break_price,$sign,$formnum='')
	{
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			$str='<table cellspacing="0" cellpadding="0" border="0" class="tiptable"><tr><th>数量</th><th>价格</th></tr>';
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			$showprice = $this->formnum(trim($price[1]),5);
			$str .= '<tr><td  class="fontgreen" >'.trim((int)$price[0]).'+</td><td class="fontred">'.$sign.$showprice.'</td></tr>';
			}
			$str.='</table>';
		}
		return $str;
	}
	/**
	 * 显示阶梯价格
	 */
	public function getbreakprice_notitle($break_price,$sign,$formnum='')
	{
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			$str='<table cellspacing="0" cellpadding="0" border="0" class="tiptable">';
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			$showprice = $this->formnum(trim($price[1]),5);
			$str .= '<tr><td  class="fontgreen" >'.trim((int)$price[0]).'+</td><td class="fontred">'.$sign.$showprice.'</td></tr>';
			}
			$str.='</table>';
		}
			return $str;
	}
	/**
	 * 显示阶梯价格
	 */
	public function getbreakprice_notitle_email($break_price,$sign,$formnum='')
	{
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			$str='<table cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;"><tbody>';
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			$showprice = $this->formnum(trim($price[1]),5);
			$str .= '<tr><td  style="color:#009944;font-size:12px;font-family:\'微软雅黑\'">'.trim($price[0]).'+</td><td style="color:#fd2323;font-size:12px;font-family:\'微软雅黑\'">'.$sign.$showprice.'</td></tr>';
			}
			$str.='</tbody></table>';
		}
			return $str;
	}
	/*
	 * 新版详细页阶梯价格
	 */
	public function getbreakprice_new_d($break_price,$sign,$formnum='')
	{           
		$str = '';
		if(!empty($break_price)){
			$pricearr = array_filter(explode(';',$break_price));
			$str='<div class="LadderPrice_c0">';
			for($i=0;$i<count($pricearr);$i++)
			{
			$price = explode('|',$pricearr[$i]);
			$showprice = $this->formnum(trim($price[1]),5);
			$str .= '<ul><li class="w30">'.trim((int)$price[0]).'+</li><li class="w70">'.$sign.$showprice.'</li></ul>';
			}
			$str.='</div>';
		}
			return $str;
	}
	/*
	 * 获取阶真实价格
	*/
	public function getPrice($break_price,$num)
	{
	  $sell_price = 0;
	  $pricearr = array_filter(explode(';',$break_price));
	  for($i=0;$i<count($pricearr);$i++)
      {
        $price = explode('|',$pricearr[$i]);
        if(!empty($pricearr[$i+1]))
    	{ 	
    		  $price2 = explode('|',$pricearr[$i+1]);
    		  if($num >= $price[0] && $num < $price2[0])
    		  {
    		    $sell_price = $price[1];break;
    		  }elseif($num < $price[0]){
			  	$sell_price = $price[1];break;
			  }
    	}else{
    		  $sell_price = $price[1];
    	}
      }
      return $sell_price;
	}
	/*
	 * 获取书本价
	*/
	public function getBookPrice($break_price,$num)
	{
		$sell_price = 0;
		$pricearr = array_filter(explode(';',$break_price));
		for($i=0;$i<count($pricearr);$i++)
		{
		  $price = explode('|',$pricearr[$i]);
		  if(!empty($pricearr[$i+1]))
		  {
		    $price2 = explode('|',$pricearr[$i+1]);
		    if($num >= $price[0] && $num < $price2[0])
		    {
		        $sell_price = $price[1];break;
		    }elseif($num < $price2[0]){
		       $sell_price = $price[1];break;
		    }
		  }else{
		  	if($num >= $price[0]) $sell_price = $price[1];
		  }
		}
		return $sell_price;
		}
	/*
	 * 获取阶阶梯价格第一个
	*/
	public function getFirstPriceRomOrUsd($break_price_rmb,$break_price_usd)
	{
		if($break_price_rmb) $break_price=$break_price_rmb;
		elseif($break_price_usd) $break_price=$break_price_usd;
		else return 0;
		$price = array();
		$pricearr = array_filter(explode(';',$break_price));
		if($pricearr){
		   $price = explode('|',$pricearr[0]);
		   if($break_price_rmb){
		   	   return $price[1];
		   }elseif($break_price_usd){
		   	   return $this->rmbtousd($price[1]);
		   }
		}else return 0;
		}
	/*
	 * 获取阶梯价格最后的价格
	 */
	public function getbplast($break_price)
	{
		$price = array();
		$pricearr = array_filter(explode(';',$break_price));
		if($pricearr){
		   $price = explode('|',$pricearr[count($pricearr)-1]);
		   return $price[1];
		}else return false;
	}
	/*
	 * 美元阶梯价格转人民币
	 */
	public function breakpriceUsdtormb($break_price)
	{
		$re = '';
		if(!$break_price) return false;
		$pricearr = array_filter(explode(';',$break_price));
		for($i=0;$i<count($pricearr);$i++)
		{
		   $price = explode('|',$pricearr[$i]);
		   if($i==0)
		     $re .= $price[0].'|'.$this->usdtocny($price[1]);
		   else 
		   	 $re .= ';'.$price[0].'|'.$this->usdtocny($price[1]);
		}
		return $re;
	}
	/*
	 * 美元阶梯价格转港币
	*/
	public function breakpriceUsdtohkd($break_price)
	{
		$re = '';
		if(!$break_price) return false;
		$pricearr = array_filter(explode(';',$break_price));
		for($i=0;$i<count($pricearr);$i++)
		{
		$price = explode('|',$pricearr[$i]);
		if($i==0)
			$re .= $price[0].'|'.$this->usdtohkd($price[1]);
			else
			$re .= ';'.$price[0].'|'.$this->usdtohkd($price[1]);
		}
		return $re;
		}
	/*
	 * 计算价格美元换人民币并加上汇率 ,和0.01的运费
	 */
	public function usdtocny($price){
		return round($price*$this->_USDTOCNY*RATE*1.01,5);
	}
	/*
	 * 计算价格美元换港币
	*/
	public function usdtohkd($price){
		return round($price*$this->_USDTOHKD,5);
	}
	/*
	 * 计算价格人民币换美元
	 * @parms $formnum 保留小数位
	 * const RATE 增值税
	*/
	public function rmbtousd($price,$formnum=''){
		return round($price/($this->_USDTOCNY*RATE),5);
	}
	
	/*
	 * 人民币价格加上税率
	*/
	public function tariffprice($price){
		return round($price*RATE,5);
	}
	/*
	 * 格式化售价
	 */
	public function formnum($price,$formnum=''){
		if($formnum=='')  $formnum = DECIMAL;
		if($formnum==0) return $price;
		else return number_format($price,$formnum);
	}
	/** 
	 * 处理特殊url字符
	 */
	public function filterUrl($str){
		$re = array('+',' ','/','?','%','#','&','=');
		return str_replace($re,'_',$str);
	}
	/**
	 * 过滤产品
	 * @param array $prod_arr
	 */
	public function filterProduct($prodarr,$bpp_stock_id=0){
		$arr_tmp = array();
		$nowtime = time();
		//价格符号
		$arr_tmp['f_usd'] = '$';
		$arr_tmp['f_rmb'] ='￥';
		//产品链接url
		$arr_tmp['f_produrl'] = "/item-b".$prodarr['manufacturer']."-".($prodarr['part_level3']?$prodarr['part_level3']:$prodarr['part_level2'])."-".($prodarr['part_id']?$prodarr['part_id']:$prodarr['id']).'-'.$this->filterUrl($prodarr['part_no']).'.html';
		/* 库存规则
		 * 1、SZ只能在国内销售，HK可在两地销售
		 * 2、如果国内购买优先使用SZ库存
		 * 是否显示价格规则 (都是可显示价格，价格在有效期内)
		 * 1、指定可销售，有阶梯价格。(库存不考虑)
		 * 2、没指定可销售，需要有库存，有阶梯价格
		 * 3、rmb阶梯价格优先显示
		 */
		
		//深圳库存
		$arr_tmp['f_stock_sz_tmp'] = $prodarr['sz_stock'] - $prodarr['sz_cover'];
		//bpp库存
		$arr_tmp['f_stock_bpp']    = 0;//$prodarr['bpp_stock'] - $prodarr['bpp_cover'];
		//香港库存
		$arr_tmp['f_stock_hk_tmp'] = $prodarr['hk_stock'] - $prodarr['hk_cover'];
		//香港可销售库存
		$arr_tmp['f_stock_hk']     = $arr_tmp['f_stock_hk_tmp'];// + $arr_tmp['f_stock_bpp'];
		//国内可销售库存
		$arr_tmp['f_stock_sz']     = $arr_tmp['f_stock_sz_tmp'] + $arr_tmp['f_stock_hk_tmp'];// + $arr_tmp['f_stock_bpp'];

		//处理最少起订量为0
		$prodarr['moq'] = ($prodarr['moq']?$prodarr['moq']:$prodarr['mpq']);
		
		//如何整包购买，阶梯价格改变
		$break_price_rmb = $break_price = '';
		if($prodarr['show_price']){
			//国内
		    if($prodarr['can_sell'] || $arr_tmp['f_stock_sz']>=$prodarr['moq']){
		    	if($prodarr['break_price_rmb'] && (!$prodarr['price_valid_rmb'] || $prodarr['price_valid_rmb'] >=$nowtime)){
		    	   $break_price_rmb= $prodarr['break_price_rmb'];
		    	   $arr_tmp['f_show_price_sz'] = 1;
		        }else $arr_tmp['f_show_price_sz'] = 0; 
		    }elseif($prodarr['break_price_rmb'] && (!$prodarr['price_valid_rmb'] || $prodarr['price_valid_rmb'] >=$nowtime)){
		    	$break_price_rmb= $prodarr['break_price_rmb'];
		    	$arr_tmp['f_show_price_sz'] = 0;
		    }else $arr_tmp['f_show_price_sz'] = 0;
		    //香港
		    if($prodarr['can_sell'] || $arr_tmp['f_stock_hk']>=$prodarr['moq']){
		    	if($prodarr['break_price'] && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    		$break_price= $prodarr['break_price'];
		    		$arr_tmp['f_show_price_hk'] = 1;
		    	}else $arr_tmp['f_show_price_hk'] = 0;
		    }elseif($prodarr['break_price'] && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    	$break_price= $prodarr['break_price'];
		    	$arr_tmp['f_show_price_hk'] = 0;
		    }else $arr_tmp['f_show_price_hk'] = 0;
		    //如果没rmb阶梯价格，有usd阶梯价格
		    if($prodarr['can_sell']){
		    	if(!$break_price_rmb && $prodarr['break_price'] && (!$prodarr['price_valid_rmb'] || $prodarr['price_valid_rmb'] >=$nowtime)){
		    		$break_price_rmb = $prodarr['break_price_rmb'] = $this->breakpriceUsdtormb($prodarr['break_price']);
		    		$arr_tmp['f_show_price_sz'] = 1;
		    	}
		    }else{
		      if(!$break_price_rmb && $prodarr['break_price'] && ($arr_tmp['f_stock_hk']>=$prodarr['moq'] || $arr_tmp['f_stock_sz']>=$prodarr['moq']) && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    	  $break_price_rmb = $prodarr['break_price_rmb'] = $this->breakpriceUsdtormb($prodarr['break_price']);
		    	  $arr_tmp['f_show_price_sz'] = 1;
		      }else{
		      	    if($prodarr['break_price']){
		    		$break_price_rmb = $prodarr['break_price_rmb'] = $this->breakpriceUsdtormb($prodarr['break_price']);
		      	    }
		    		//$arr_tmp['f_show_price_sz'] = 0;
		    	}
		    }
		    //如果是整包购买 ,显示阶梯价
		    $break_price_rmb_show = $break_price_rmb;
		    $break_price_show     = $break_price;
			//如果是整包购买
		    if($prodarr['mpq']!=0 && ($prodarr['moq']%$prodarr['mpq'])==0){
		    	$break_price_rmb = $prodarr['moq'].'|'.$this->getPrice($break_price_rmb,$prodarr['moq']);
		    	$break_price     = $prodarr['moq'].'|'.$this->getPrice($break_price,$prodarr['moq']);
		    }
		    /* 处理库存小于moq的情况
		     * 1、如果surplus_stock_sell为1,special_break_prices为空，将剩余库存设为moq，使用原来阶梯价。
		     * 2、如果surplus_stock_sell为1,special_break_prices有值，moq和价格都使用special_break_prices。
		     */
		    if($prodarr['surplus_stock_sell']){
		    	//标志改变moq是否改变起作用
		    	$surplus_zs = $surplus_hk = 0;
		    	//国内
		    	if(!$arr_tmp['f_show_price_sz'] && $arr_tmp['f_stock_sz']>0 && $arr_tmp['f_stock_sz']<=$prodarr['moq']){
		    		$nwemoq = $arr_tmp['f_stock_hk']?$arr_tmp['f_stock_hk']:$arr_tmp['f_stock_sz'];
		    		if($prodarr['break_price_rmb'] && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    			$break_price_rmb= $prodarr['break_price_rmb'];
		    			$prodarr['moq'] = $nwemoq;
		    			$arr_tmp['f_show_price_sz'] = $surplus_zs = 1;
		    		}elseif($prodarr['break_price'] && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    			$break_price_rmb = $prodarr['break_price_rmb'] = $this->breakpriceUsdtormb($prodarr['break_price']);
		    			$prodarr['moq'] = $nwemoq;
		    			$arr_tmp['f_show_price_sz'] = $surplus_zs = 1;
		    		}else $arr_tmp['f_show_price_hk'] = 0;
		    	}
		    	//香港  因为moq没分国内和香港，所以只能将香港库存设为moq
		    	if(!$arr_tmp['f_show_price_hk'] && $arr_tmp['f_stock_hk']>0 && $arr_tmp['f_stock_hk']<=$prodarr['moq']){
		    		if($prodarr['break_price'] && (!$prodarr['price_valid'] || $prodarr['price_valid'] >=$nowtime)){
		    			$break_price= $prodarr['break_price'];
		    			$prodarr['moq'] = $arr_tmp['f_stock_hk'];
		    			$arr_tmp['f_show_price_hk'] = $surplus_hk = 1;
		    		}else $arr_tmp['f_show_price_hk'] = 0;
		    	}
		    	//有特殊阶梯价并且改变了moq
		    	if($prodarr['special_break_prices']){
		    		$sbp_arr = explode('|',$prodarr['special_break_prices']);
		    		//特殊moq
		    		if((int)$sbp_arr[0]>0){
		    			$prodarr['moq'] = (int)$sbp_arr[0];
		    		}
		    		//特殊价格，rmb
		    		if($sbp_arr[1] && $sbp_arr[1]>0){
		    			$break_price_rmb= $prodarr['break_price_rmb'] = $prodarr['moq'].'|'.$sbp_arr[1];
		    		}
		    		if($sbp_arr[2] && $sbp_arr[2]>0){
		    			$break_price= $prodarr['break_price'] = $prodarr['moq'].'|'.$sbp_arr[2];
		    			//如果没有rmb
		    			if(!$sbp_arr[1]){
		    				$break_price_rmb= $prodarr['break_price_rmb'] = $prodarr['moq'].'|'.$this->usdtocny($sbp_arr[2]);
		    			}
		    		}
		    		//可以销售
		    		if($sbp_arr[1] && $sbp_arr[1]>0 && $arr_tmp['f_stock_sz']){
		    			$arr_tmp['f_show_price_sz']  = 1;
		    		}
		    		if($sbp_arr[2] && $sbp_arr[2]>0 && $arr_tmp['f_stock_hk']){
		    			$arr_tmp['f_show_price_hk']  = 1;
		    			//如果没有rmb
		    			if(!$sbp_arr[1]){
		    				$arr_tmp['f_show_price_sz']  = 1;
		    			}
		    		}
		    	}
		    	$break_price_rmb_show = $break_price_rmb;
		    	$break_price_show     = $break_price;
		    }
		}else{
			$arr_tmp['f_show_price_sz'] = 0;
			$arr_tmp['f_show_price_hk'] = 0;
		}
		//没有自有库存销售合作伙伴库存
		if($arr_tmp['f_stock_hk']<=0 && $arr_tmp['f_stock_sz']<=0 && $bpp_stock_id==0){
			$partId = ($prodarr['part_id']?$prodarr['part_id']:$prodarr['id']);
			$bppService = new Default_Service_BppService();
			$pr = $bppService->getBestPrice($partId);
			if(!empty($pr)){
				$prodarr["moq"] = $pr["moq"];
				if($pr["mpq"] >0 ) $prodarr["mpq"] = $pr["mpq"];
				
				//香港可销售库存
				$arr_tmp['f_stock_hk_tmp'] = $arr_tmp['f_stock_hk']  = $pr['stock'] - $pr['bpp_stock_cover'];
				//国内可销售库存
				$arr_tmp['f_stock_sz_tmp'] = $arr_tmp['f_stock_sz']  = $pr['stock'] - $pr['bpp_stock_cover'];
				if($pr['break_price']){
					if($pr['location']=="Asia"){
						$arr_tmp['f_show_price_sz'] = 1;
						$arr_tmp['f_show_price_hk'] = 1;
					}else{
					    $arr_tmp['f_show_price_sz'] = 0;
					    $arr_tmp['f_show_price_hk'] = 0;
					}
					$prodarr["lead_time_cn"]    = $pr['lead_time_cn'];
					$prodarr["lead_time_hk"]    = $pr['lead_time_hk'];
					$prodarr['break_price']     =$break_price_show= $break_price    = $pr['break_price'];
					$prodarr['break_price_rmb'] =$break_price_rmb_show=$break_price_rmb = $this->breakpriceUsdtormb($pr['break_price']);
				
					//记录供应商信息
					$arr_tmp['f_bpp_stock_id']  = $pr['id'];
					$arr_tmp['f_vendor_name']   = $pr['vendor_name'];
					$arr_tmp['f_location']      = $pr['location'];
					$arr_tmp['f_location_code'] = $pr['location_code'];

				}
			}
		}elseif($bpp_stock_id>0){ //特定bpp
			$bppService = new Default_Service_BppService();
			$pr = $bppService->getBppById($bpp_stock_id);
			if(!empty($pr)){
				$prodarr["moq"] = $pr["moq"];
				if($pr["mpq"] >0 ) $prodarr["mpq"] = $pr["mpq"];
			
				//香港可销售库存
				$arr_tmp['f_stock_hk_tmp'] = $arr_tmp['f_stock_hk']  = $pr['stock'] - $pr['bpp_stock_cover'];
				//国内可销售库存
				$arr_tmp['f_stock_sz_tmp'] = $arr_tmp['f_stock_sz']  = $pr['stock'] - $pr['bpp_stock_cover'];
				if($pr['break_price']){
				    if($pr['location']=="Asia"){
						$arr_tmp['f_show_price_sz'] = 1;
						$arr_tmp['f_show_price_hk'] = 1;
					}else{
					    $arr_tmp['f_show_price_sz'] = 0;
					    $arr_tmp['f_show_price_hk'] = 0;
					}
					$prodarr["lead_time_cn"]    = $pr['lead_time_cn'];
					$prodarr["lead_time_hk"]    = $pr['lead_time_hk'];
					$prodarr['break_price']     = $break_price_show=$break_price    = $pr['break_price'];
					$prodarr['break_price_rmb'] =$break_price_rmb_show=$break_price_rmb = $this->breakpriceUsdtormb($pr['break_price']);
			
					//记录供应商信息
					$arr_tmp['f_bpp_stock_id']  = $pr['id'];
					$arr_tmp['f_vendor_name']   = $pr['vendor_name'];
					$arr_tmp['f_location']      = $pr['location'];
					$arr_tmp['f_location_code'] = $pr['location_code'];
			
				}
			}
		}
		//从阶梯价格获取售价
		$arr_tmp['f_sell_price_hk'] = $this->getPrice($break_price,$prodarr['moq']);
		$arr_tmp['f_sell_price_sz'] = $this->getPrice($break_price_rmb,$prodarr['moq']);
		//从阶梯价格获取最低售价
		$arr_tmp['f_lowest_price_hk'] = $this->getbplast($break_price);
		$arr_tmp['f_lowest_price_sz'] = $this->getbplast($break_price_rmb);
		//从阶梯价格获取最后价格
		$arr_tmp['f_last_price_hk'] = $this->getbplast($prodarr['break_price']);
		$sz_tmp = $prodarr['break_price_rmb']?$prodarr['break_price_rmb']:$this->breakpriceUsdtormb($prodarr['break_price']);
		$arr_tmp['f_last_price_sz'] = $this->getbplast($sz_tmp);
		//阶梯价格
		$arr_tmp['f_break_price_hk'] = $this->getbreakprice($break_price_show,$arr_tmp['f_usd']);
		$arr_tmp['f_break_price_sz'] = $this->getbreakprice($break_price_rmb_show,$arr_tmp['f_rmb']);
		//没有title阶梯价格
		$arr_tmp['f_break_price_notitle_hk'] = $this->getbreakprice_notitle($break_price_show,$arr_tmp['f_usd']);
		$arr_tmp['f_break_price_notitle_sz'] = $this->getbreakprice_notitle($break_price_rmb_show,$arr_tmp['f_rmb']);
		//新版详细页阶梯价格
		$arr_tmp['f_break_price_new_d_hk'] = $this->getbreakprice_new_d($break_price_show,$arr_tmp['f_usd']);
		$arr_tmp['f_break_price_new_d_sz'] = $this->getbreakprice_new_d($break_price_rmb_show,$arr_tmp['f_rmb']);
		
		//交期
		if(isset($prodarr['lead_time_hk'])){
		    $arr_tmp['f_lead_time_hk'] = $prodarr['lead_time_hk']?$prodarr['lead_time_hk']:$prodarr['lead_time'];
		}elseif(isset($prodarr['lead_time'])){
			$arr_tmp['f_lead_time_hk'] = $prodarr['lead_time'];
		}else $arr_tmp['f_lead_time_hk'] = '';
		if(isset($prodarr['lead_time_cn'])){
		    $arr_tmp['f_lead_time_cn'] = $prodarr['lead_time_cn']?$prodarr['lead_time_cn']:$prodarr['lead_time'];
		}elseif(isset($prodarr['lead_time'])){
			$arr_tmp['f_lead_time_cn'] = $prodarr['lead_time'];
		}else $arr_tmp['f_lead_time_cn'] = '';
		
		
		return array_merge($prodarr,$arr_tmp);
	}
	/*
	 * 获取url内容
	*/
	public function getByFile($url,$newcod='UTF-8',$oldcod='UTF-8')
	{
		$content = mb_convert_encoding(file_get_contents($url),$newcod,$oldcod);
		return  $content;
	}
	/*
	 * 获取url内容
	*/
	public function getByCurl($url,$newcod='UTF-8',$oldcod='UTF-8',$post='')
	{
		$header = array('Accept-Language: zh-cn','Connection: Keep-Alive','Cache-Control: no-cache');
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $post);//提交查询信息
		$content = mb_convert_encoding(curl_exec($ch),$newcod,$oldcod);
		curl_close($ch);
		return  $content;
		//return preg_replace('/\r|\n/', '', $content);
	}
	/*
	 * 截取顺丰快递信息
	 */
	//快递之家
	public function sfContents($str,$start_str,$end_str){
		$str = str_replace('\\','',$str);
		$str = str_replace('iv id="ali-iu-wl-resul" class="ali-iu-wl-resul">','',$str);
		$str = str_replace('v id="ali-itu-wl-result" class="ali-itu-wl-result">','',$str);
		$str = str_replace('2 class="logisTile">','',$str);
		$start_pos = strpos($str,$start_str)+strlen($start_str);
		$end_pos   = strpos($str,$end_str);
		$c_str_l   = $end_pos - $start_pos;
		$contents  = substr($str,$start_pos,$c_str_l);
		//处理查不到结果信息
		$is_exist = strpos($contents,'name="description"');
		if($is_exist>1) return false;
		return $contents;
	}
	/*
	 * 截取宅急送信息
	*/
	//快递之家
	function zjsContents($str,$start_str,$end_str){
	
		$start_pos = strpos($str,$start_str)+strlen($start_str);
		$end_pos   = strpos($str,$end_str);
		$c_str_l   = $end_pos - $start_pos;
		$contents  = substr($str,$start_pos,$c_str_l);
		//再截取
		$start_pos2 = strpos($contents,'>');
		$end_pos2   = strpos($contents,$end_str);
		$c_str_2   = $end_pos2 - $start_pos2;
		$contents   = substr($contents,$start_pos2+1,$c_str_2-1);
		//处理查不到结果信息
		$is_exist = strpos($contents,'name="description"');
		if($is_exist>1) return false;
		else return $contents;
	}
	//快递100
	public function contents100($string){
		$html='';$k=0;
		$arr = json_decode($string);
		if(empty($arr)){
			$string = strtr($string,"'",'"');
			$arr = json_decode($string);
		}
		if(empty($arr->data)) return false;
		else{
			foreach(array_reverse($arr->data) as $v)
			{
				if(!empty($v->ftime))
				{
					$k=1;
					$html .='<li><span class="ime">'.$v->ftime.'</span><span class="info">'.$v->context.'</span></li>';
				}
			}
		}
		if($k==1) $html = '<ul>'.$html.'</ul>';
		return $html;
	}
	/*
	 * 获取快递信息
	*/
	public function getkdContents($cou_number,$courier=1,$type='curl'){
		if($courier==1){ //顺丰
			//快递之家
		    //$url = "http://www.kiees.cn/sf.php?wen=".$cou_number."&channel=''&rnd=".rand(1,1000);
			//快递100
			$url = 'http://www.kuaidi100.com/query?type=shunfeng&postid='.$cou_number.'&id=1&valicode=&temp='.rand(1,100).'&sessionid=&tmp='.rand(1,100);
		    $newcod ='UTF-8';
		    $oldcod ='UTF-8';
		}elseif($courier==2){ //宅急送
			//$url = "http://www.kiees.cn/zjs.php?wen=".$cou_number;
			$url = 'http://www.kuaidi100.com/query?type=zhaijisong&postid='.$cou_number.'&id=1&valicode=&temp='.rand(1,100).'&sessionid=&tmp='.rand(1,100);
			$newcod ='UTF-8';
			$oldcod ='UTF-8';
		}else return '暂时不支持此快递公司物流信息查询';
		//获取网页内容
		if($type=='curl')
		   $string = $this->getByCurl($url,$newcod,$oldcod);
		elseif($type=='file')
		   $string = $this->getByFile($url,$newcod,$oldcod);
		else return '参数错误';
		//截取快递信息
		if($courier==1){ //顺丰
			//快递之家
			//$string = $this->sfContents($string,'<div class="race_resul">','<div>');
			//快递100
			$string = $this->contents100($string);
			if(empty($string)) $string = "很抱歉，暂时没有查询物流信息。你可以选择去顺丰官网查询<p><a href='http://www.sf-express.com' target='_blank'>www.sf-express.com</a></p>";
		}elseif($courier==2){ //宅急送
			//$string = $this->zjsContents($string,'<table class="data_info"','</table>');
			$string = $this->contents100($string);
			if(empty($string)) $string = "很抱歉，暂时没有查询物流信息。你可以选择去宅急送官网查询<p><a href='http://www.zjs.com.cn' target='_blank'>www.zjs.com.cn</a></p>";
		}
		return $string;
	}
	/**
	 * php中解释escape函数传了的值
	 */
	function phpEscape($str) {
	   $ret = ''; 
       $len = strlen($str); 
       for ($i = 0; $i < $len; $i++){ 
         if ($str[$i] == '%' && $str[$i+1] == 'u'){ 
          $val = hexdec(substr($str, $i+2, 4)); 
          if ($val < 0x7f) $ret .= chr($val); 
          else if($val < 0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f)); 
          else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f)); 
         $i += 5; 
         } 
         else if ($str[$i] == '%'){ 
          $ret .= urldecode(substr($str, $i, 3)); 
          $i += 2; 
          } 
          else $ret .= $str[$i]; 
        } 
       return $ret; 
	}
	/******************************************************************
	 * PHP截取UTF-8字符串，解决半字符问题。
	* 英文、数字（半角）为1字节（8位），中文（全角）为3字节
	* @return 取出的字符串, 当$len小于等于0时, 会返回整个字符串
	* @param $str 源字符串
	* @param $len 左边的子串的长度
	****************************************************************/
	function utf_substr($str,$len,$endstr='...')
	{
	  for($i=0;$i<$len;$i++)
	  {
			$temp_str=substr($str,0,1);
			if(ord($temp_str) > 127)
				{
				$i++;
				if($i<$len)
				{
				$new_str[]=substr($str,0,3);
				$str=substr($str,3);
				}
		}
		else
		{
			$new_str[]=substr($str,0,1);
			$str=substr($str,1);
	     }
	   }
	   if(strlen($str) > $str) $new_str[] = $endstr;
	   return join($new_str);
	}
	/******************************************************************
	 * PHP截取UTF-8字符串，解决半字符问题。
	* 英文、数字（半角）为1字节（8位），中文（全角）为3字节
	* @return 取出的字符串, 当$len小于等于0时, 会返回整个字符串
	* @param $str 源字符串
	* @param $len 左边的子串的长度
	****************************************************************/
	function subMiddle($str,$len,$endstr='...')
	{
		for($i=0;$i<$len;$i++)
		{
		$temp_str=substr($str,0,1);
		if(ord($temp_str) > 127)
		{
		$i++;
		if($i<$len)
			{
			$new_str[]=substr($str,0,3);
			$str=substr($str,3);
			}
			}else{
			$new_str[]=substr($str,0,1);
			$str=substr($str,1);
			}
			}
			$new_str = array_filter($new_str);
			$new_str[count($new_str)/2] = $endstr;
			if(count($new_str)>4){
			if($new_str[count($new_str)/2-1]) $new_str[count($new_str)/2-1] = '';
			if($new_str[count($new_str)/2+1]) $new_str[count($new_str)/2+1] = '';
			}
			return join($new_str);
			}
	/**
	 * 异步请求开始,只能用于ajax请求
	 */
	function asynchronousStarts()
	{
		while(ob_get_level()) ob_end_clean();
		header('Connection: close');
		ignore_user_abort();
		ob_start();
	}
	/**
	 * 异步请求结束,只能用于ajax请求
	 */
	function asynchronousEnd()
	{
		$size = ob_get_length();
    	header("Content-Length: $size");
    	ob_end_flush();
    	flush();
	}
	function iconv($str,$old='GBK',$new='utf-8'){
		return iconv($old, $new, $str);
	}
	/**
	 * 检查文件是否存在
	 */
	function checkFile($file)
	{
		return file_exists($file);
	}
	/**
	 * XML转数组
	 */
	function xml_to_array( $xml )
	{
		$reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
		if(preg_match_all($reg, $xml, $matches))
		{
			$count = count($matches[0]);
			$arr = array();
			for($i = 0; $i < $count; $i++)
			{
			$key = $matches[1][$i];
			$val = $this->xml_to_array( $matches[2][$i] );  // 递归
			if(array_key_exists($key, $arr))
			{
			if(is_array($arr[$key]))
			{
			if(!array_key_exists(0,$arr[$key]))
			{
			$arr[$key] = array($arr[$key]);
			}
			}else{
				$arr[$key] = array($arr[$key]);
			}
			$arr[$key][] = $val;
				}else{
					$arr[$key] = $val;
				}
				}
				return $arr;
				}else{
				return $xml;
			}
	}
	/**
	 * 生成地址
	 */
	public function createAddress($province,$city,$area,$address){
		
		if($province==$city) {
			if($city==$area) return $city.$address;
			else return $city.$area.$address;
		}
		else return $province.$city.$area.$address;
	}
	/**
	 * 记录在线人数
	 */
	public function setOnline(){
		$user_online = "../docs/count/online.txt"; //保存人数的文件
		$timeout = 30;//30秒内没动作者,认为掉线
		$user_arr = file_get_contents($user_online);
		$user_arr = explode('#',rtrim($user_arr,'#'));
		$temp = array();
		foreach($user_arr as $value){
			$user = explode(",",trim($value));
			if (($user[0] != getenv('REMOTE_ADDR')) && ($user[2] > time())) {//如果不是本用户IP并时间没有超时则放入到数组中
				array_push($temp,$user[0].",".$user[1].",".$user[2]);
			}
		}
		if(isset($_SESSION['userInfo']['uidSession'])) $uid = $_SESSION['userInfo']['uidSession'];
		else $uid = '';
		array_push($temp,getenv('REMOTE_ADDR').",".$uid.",".(time() + ($timeout)).'#'); //保存本用户的信息
		$user_str = implode("#",$temp);
		//写入文件
		$fp = fopen($user_online,"w");
		flock($fp,LOCK_EX); //flock() 不能在NFS以及其他的一些网络文件系统中正常工作
		fputs($fp,$user_str);
		flock($fp,LOCK_UN);
		fclose($fp);
		$re_array=array();
		$login_total = 0;
		if($user_arr){
			foreach($user_arr as $v){
				$arr = explode(',',$v);
				if($arr[1]) $login_total++;
			}
		}
		$re_array['user_arr'] = $user_arr;
		$re_array['total']    = count($user_arr);
		$re_array['login_total'] = $login_total;
 		return $re_array;
	}
	/*
	 * 获取全年季度开始时间和结束时间
	 */
	public function getQuarterly($year=''){
		$quarterly = array();
		$year = $year?$year:date("Y");
		$date_q = array(2, 5, 8, 11);
		foreach($date_q as $k=>$q){
			//季度未最后一月天数
			$getMonthDays = date("t",mktime(0, 0, 0,$q+($q-1)%3,1,$year));
		    $quarterly[$k]['s'] = mktime(0, 0, 0,$q-($q-1)%3,1,$year);
		    $quarterly[$k]['e'] = mktime(23,59,59,$q+($q-1)%3,$getMonthDays,$year);
		}
		return $quarterly;
	}
	//小数转大写
	function cny($ns) {
		static $cnums=array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"),
		$cnyunits=array("圆","角","分"),
		$grees=array("拾","佰","仟","万","拾","佰","仟","亿");
		list($ns1,$ns2)=explode(".",$ns,2);
		$ns2=array_filter(array($ns2[1],$ns2[0]));
		$ret=array_merge($ns2,array(implode("",$this->_cny_map_unit(str_split($ns1),$grees)),""));
		$ret=implode("",array_reverse($this->_cny_map_unit($ret,$cnyunits)));
		return str_replace(array_keys($cnums),$cnums,$ret);
	}
	function _cny_map_unit($list,$units) {
		$ul=count($units);
		$xs=array();
		foreach (array_reverse($list) as $x) {
			$l=count($xs);
			if ($x!="0" || !($l%4)) $n=($x=='0'?'':$x).($units[($l-1)%$ul]);
			else $n=is_numeric($xs[0][0])?$x:'';
			array_unshift($xs,$n);
		}
		return $xs;
	}
	/**
	 * 提供文件下载页面
	 */
	public function filedownloadpage($docpart,$docname){
		// We'll be outputting a file
		@header('Accept-Ranges: bytes');
		@header('Accept-Length: ' . filesize($docpart));
		// It will be called
		@header('Content-Transfer-Encoding: binary');
		@header('Content-type: application/octet-stream');
		@header('Content-Disposition: attachment; filename=' . $docname);
		@header('Content-Type: application/octet-stream; name=' . $docname);
		// The source is in filename
		$file = @fopen($docpart, "r");
		echo @fread($file, @filesize($docpart));
		@fclose($file);
		exit;
	}
	/**
	 * 转文件大小名称
	 * @param unknown_type $size
	 * @return string
	 */
	public function format_bytes($size,$f=0) {
		if(!$size) return "0 KB";
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
		return round($size, $f).' '.$units[$i];
	}
	/**
	 * 更改view路径
	 */
	public function changeView($view,$part=''){
		if($part!=''){
		   $arr = $view->getScriptPaths();
		   $view->setScriptPath($arr[0].$part);
		}
	}
	/*
	 * 获取中英文首字母
	 */
	public function getfirstchar($str){
		 if(empty($str)){return '';}
    $fchar=ord($str{0});
    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
    $s1=iconv('UTF-8','gb2312',$str);
    $s2=iconv('gb2312','UTF-8',$s1);
    $s=$s2==$str?$s1:$str;
    $asc=ord($s{0})*256+ord($s{1})-65536;
    if($asc>=-20319&&$asc<=-20284) return 'A';
    if($asc>=-20283&&$asc<=-19776) return 'B';
    if($asc>=-19775&&$asc<=-19219) return 'C';
    if($asc>=-19218&&$asc<=-18711) return 'D';
    if($asc>=-18710&&$asc<=-18527) return 'E';
    if($asc>=-18526&&$asc<=-18240) return 'F';
    if($asc>=-18239&&$asc<=-17923) return 'G';
    if($asc>=-17922&&$asc<=-17418) return 'H';
    if($asc>=-17417&&$asc<=-16475) return 'J';
    if($asc>=-16474&&$asc<=-16213) return 'K';
    if($asc>=-16212&&$asc<=-15641) return 'L';
    if($asc>=-15640&&$asc<=-15166) return 'M';
    if($asc>=-15165&&$asc<=-14923) return 'N';
    if($asc>=-14922&&$asc<=-14915) return 'O';
    if($asc>=-14914&&$asc<=-14631) return 'P';
    if($asc>=-14630&&$asc<=-14150) return 'Q';
    if($asc>=-14149&&$asc<=-14091) return 'R';
    if($asc>=-14090&&$asc<=-13319) return 'S';
    if($asc>=-13318&&$asc<=-12839) return 'T';
    if($asc>=-12838&&$asc<=-12557) return 'W';
    if($asc>=-12556&&$asc<=-11848) return 'X';
    if($asc>=-11847&&$asc<=-11056) return 'Y';
    if($asc>=-11055&&$asc<=-10247) return 'Z';
    return null;
	}
}