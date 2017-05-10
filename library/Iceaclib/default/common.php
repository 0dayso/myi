<?php
class MyCommon
{
	 //加密
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
	//解密
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
	//获得加密后的hashkey，用于发邮件
	public function encryptVerification($uname,$keyone){
		//加密并转意
		$hashkey = rawurlencode($this->encrypt_aes($keyone.','.$uname));
		return $hashkey;
	}
	//解密hashkey
	public function decryptVerification($hashkey){
		$hashkey = $this->decrypt_aes(rawurldecode($hashkey));
		return $hashkey;
	}
	public function getEmailUrl($email){
         if(empty($email)) return false;
		 $email_arr=explode("@",$email);
		 $email_b=$email_arr[1];
		 $url = false;
		 if($email_b=='qq.com'){
			$url="http://mail.qq.com/";
		 }else if($email_b=='163.com'){
			$url="http://mail.163.com/";
		 }else if($email_b=='vip.163.com'){
			$url="http://vip.163.com/";
		 }else if($email_b=='sina.com'){
			$url="http://mail.sina.com.cn/";
		 }else if($email_b=='vip.sina.com'){
			$url="http://mail.sina.com.cn/cgi-bin/viplogin.php";
		 }else if($email_b=='sohu.com'){
			$url="http://login.mail.sohu.com/";
		 }else if($email_b=='tom.com'){
			$url='http://pass.tom.com/login.php/';
		 }else if($email_b=='163.net'){
			$url="http://mail.163.net/";
		 }else if($email_b=='263.net'){
			$url="http://mail.263.net/";
		}else if($email_b=='21cn.com'){
			$url="http://public.webmail.21cn.com/";
		}else if($email_b=='yahoo.com.cn' || $email_b=='yahoo.cn' || $email_b=='yahoo.com'){
			$url="http://mail.cn.yahoo.com/";
		}else if($email_b=='126.com'){
			$url="http://mail.126.com/";
		}else if($email_b=='eyou.com'){
			$url="http://www.eyou.com/";
		}else if($email_b=='xinhuanet.com'){
			$url="http://mail.xinhuanet.com/portal/mail.xinhuanet.com/index.jsp?locale=zh_cn";
		}else if($email_b=='hotmail.com' || $email_b=='msn.com'){
			$url="https://login.live.com/";
		}else if($email_b=='msn.com'){
			$url="https://login.live.com/";
		}else if($email_b=='gmail.com'){
			$url="https://mail.google.com/";
		}
		return $url;
	}
	/*
	 * 登录验证
	 * 
	 */
	public function loginCheck()
	{
		$actionname = rawurlencode($_SERVER['REQUEST_URI']);
		$user = new Default_Model_DbTable_User();
		$sql = "SELECT u.emailapprove,up.companyapprove FROM user as u,user_profile as up 
    			WHERE u.uid = up.uid AND u.uid=:uidtmp AND u.enable='1'";
		$reUser = $user->getRowBySql($sql, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(empty($reUser))
		{
			unset($_SESSION['userInfo']);//注销session
			header("Location: /user/login?url={$actionname}");
			exit();
		}
		if($reUser['emailapprove']==0)
		{
			$verification = new Default_Model_DbTable_VerificationCode();
			$ver = $verification->getRowByWhere("uid='".$_SESSION['userInfo']['uidSession']."'","created DESC");
			$_SESSION['userInfo']['approveSession'] = 404;
			header("Location: /user/verification");
			exit();
		}
		//更新会员类型
		$_SESSION['member_type'] = $reUser['companyapprove'];
	}
	/*
	 * 判断是否登录
	*
	*/
	public function isLoginAndEmail()
	{
		$user = new Default_Model_DbTable_User();
		$sql = "SELECT u.uid FROM user as u
    			WHERE u.uid=:uidtmp AND u.enable='1' AND u.emailapprove=1";
		$reUser = $user->getRowBySql($sql, array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(empty($reUser)) return false;
		else return true;
	}
	//创建文件夹
	public function createUserFolder($uid)
	{
		$folder = COM_ANNEX.$uid.'/';
		$this->createFolder($folder);
		//建立文件
		$inserttxt ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>IC易站 - 为您提供电子元器件设计链、供应链全程服务，行业领先的一站式电子元器件电子商务交易平台！</title><link rel="shortcut icon" href="/images/default/favicon.ico" />
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<link rel="stylesheet" type="text/css" href="/css/default/base.css"/>
<script language="javascript">
window.location.href ="/";
</script>
</head>
<body>
<!--end mainmenu--><div class="w mt10 clearfix">
<div class="w errorbox">
    <div class="fl"><img src="/images/default/notfound.gif"  alt="error"/></div>
    <div class="fr errorcon">
        <h1>很抱歉，访问出现错误！</h1>
        <p>您访问的页面不存在或链接错误。</p>
        <p class="errorpa"><a href="/">首页</a> | <a href="javascript:;" onclick="javascript:history.go(-1);">返回</a></p>
    </div>
    <div class="clr"></div>
    <div class="blank20"></div>
</div>
  </div> 
</body>
</html>';
		$this->createFile($folder,"index.html",$inserttxt);
	}
	//创建文件夹
	public function createFolderByPart($folder)
	{
		$this->createFolder($folder);
		//建立文件
		$inserttxt ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>IC易站 - 为您提供电子元器件设计链、供应链全程服务，行业领先的一站式电子元器件电子商务交易平台！</title><link rel="shortcut icon" href="/images/default/favicon.ico" />
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<link rel="stylesheet" type="text/css" href="/css/default/base.css"/>
<script language="javascript">
window.location.href ="/";
</script>
</head>
<body>
<!--end mainmenu--><div class="w mt10 clearfix">
<div class="w errorbox">
    <div class="fl"><img src="/images/default/notfound.gif"  alt="error"/></div>
    <div class="fr errorcon">
        <h1>很抱歉，访问出现错误！</h1>
        <p>您访问的页面不存在或链接错误。</p>
        <p class="errorpa"><a href="/">首页</a> | <a href="javascript:;" onclick="javascript:history.go(-1);">返回</a></p>
    </div>
    <div class="clr"></div>
    <div class="blank20"></div>
</div>
  </div>
</body>
</html>';
		$this->createFile($folder,"index.html",$inserttxt);
	}
	//创建文件夹
	public function createFolder($folder)
	{
		$ok=0;
		$success=$folder;
		if (!empty($folder))
		{
			if(!is_dir($folder)) //判断是否存在
			{
				if(mkdir($folder,0777))//创建
					$ok=$success;
			}else $ok=$success;
		}
		return $ok;
	}
	//添加文件
	public function createFile($folder,$filename,$inserttxt)
	{
		$filename=$folder.$filename;
		if(!file_exists($filename))
		{
			$handle=fopen($filename,"w+");
			fwrite($handle,$inserttxt);
			fclose($handle);
			return TRUE;
		}
	}
}