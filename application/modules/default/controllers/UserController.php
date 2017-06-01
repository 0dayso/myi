<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/filter.php';
class UserController extends Zend_Controller_Action
{
	private $_defaultlogService;
	private $_scoreService;
    public function init()
    {
    	$this->_defaultlogService = new Default_Service_DefaultlogService();
    	$_SESSION['menu'] = 'user';
        /* Initialize action controller here */
    	$this->view->fun =$this->fun = new MyFun();
    	$this->filter = new MyFilter();
    	$this->needcodenum = $this->view->needcodenum = 3;
    	$this->_scoreService = new Default_Service_ScoreService();
    }

    public function indexAction()
    {
        // action body
    	$this->_redirect('/user/login');
    }
    /*
     * 打开登录框
     */
    public function loginboxAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->parenturl = $this->filter->pregHtmlSql($_GET['parenturl']);
    }
    /*
     * ajax登录
     */
    public function ajaxloginAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		if(!isset($_SESSION['needcode'])) $_SESSION['needcode'] = 0;
    		$loginname = $this->filter->pregHtmlSql($formData['loginname']);
    		$password = $this->filter->pregHtmlSql($formData['password']);
    		$error = 0;$code=0;
    		$message ='';
    		if(empty($loginname)) {
    			$message = '*请输入用户名或者邮箱';$error++;
    			$code =100;
    		};
    		if(empty($password)) {
    			$message = '*请输入密码';$error++;
    			$code =200;
    		};
    		//登录信息错误超过3次需要验证码
    		if(isset($_SESSION['needcode']) && $_SESSION['needcode'] > $this->needcodenum){
    			$verifycode = $this->filter->pregHtmlSql($formData['verifycode']);
    			
    			if(!isset($_SESSION['verifycode']['code'])){
    				$description .=$this->view->verifycodeMess = '验证码错误！';
    				$error ++;
    			}elseif(strtolower($verifycode) != $_SESSION['verifycode']['code']){
    				$message = '验证码错误！';$error ++;
    				$code =300;
    			}
    		}
    		$user = new Default_Model_DbTable_User();
    		$reUser = $user->getByName($loginname);
    		//用户不用
    		if(!empty($reUser) && $reUser['enable'] != 1)
    		{
    			$message = '*该账户名已经被禁用';$error++;
    			$code =100;
    		}else{
    		  if(empty($reUser)){
    			$reUser = $user->getByEmail($loginname);
    			if(empty($reUser)){
    				$message = '*账户名不存在';$error++;
    				$code =100;
    			}else{
    				$inputpassword = md5(md5($password));
    				if($inputpassword != $reUser['pass']){
    					$message = '*密码错误';$error++;
    					$code =200;
    				}
    			}
    		  }else{
    			$inputpassword = md5(md5($password));
    			if($inputpassword != $reUser['pass']){
    				$message = '*密码错误';$error++;
    				$code =200;
    			}
    		  }
    		}
    		if(!$error){
    			unset($_SESSION['needcode']);
    			if($reUser['emailapprove'] != 1){
    				//注册session
    				$userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    				$userInfo->uidSession = $reUser['uid'];//设置值
    				$userInfo->unameSession = $reUser['uname'];//设置值
    				$userInfo->emailSession = $reUser['email'];//设置值
    				$userInfo->approveSession = 404;//设置值
    				echo Zend_Json_Encoder::encode(array("code"=>1, "message"=>'请通过邮箱验证'));
    				exit;
    			}else{
    				$user = new Default_Model_DbTable_User();
    				$ip = $this->fun->getIp();
    				$user->updateByUid(array('lasttime'=>time(),'lastip'=>$ip), $reUser['uid']);
    				//注册session
    				$userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    				$userInfo->uidSession = $reUser['uid'];//设置值
    				$userInfo->unameSession = $reUser['uname'];//设置值
    				$userInfo->emailSession = $reUser['email'];//设置值
    				$userInfo->approveSession = $reUser['enable'];//设置值1
    				$userService = new Default_Service_UserService();
    				$_SESSION['member_type'] = $userService->checkComapprove();
    				$this->_defaultlogService->addLog(array('log_id'=>'L','temp4'=>'登录成功'));
    				
    				//记录登陆用户
    				$this->recordLogin($reUser['uid']);
    				
    				echo Zend_Json_Encoder::encode(array("code"=>$code, "message"=>$message,'uname'=>$reUser['uname'],'uid'=>$reUser['uid']));
    				exit;
    			}
    		}else{
    			$description = $message .';'.$loginname.','.$verifycode;
    			$this->_defaultlogService->addLog(array('log_id'=>'L','temp1'=>400,'temp4'=>'登录失败','description'=>$description));
    			$_SESSION['needcode']++;
    			echo Zend_Json_Encoder::encode(array("code"=>$code, "message"=>$message,'needcode'=>$_SESSION['needcode'],));
    			exit;
    		}
    	}
    }
   /*
    * 登录
    */
    public function loginAction()
    {
    	if(isset($_SESSION['userInfo'])) $this->_redirect("/center");
    	if($this->_getParam('url')) $url = $this->filter->pregHtmlSql($this->_getParam('url'));
    	else $url = '/';
    	if($this->getRequest()->isPost()){
    		$formData   = $this->getRequest()->getPost();
    		if(!isset($_SESSION['needcode'])) $_SESSION['needcode'] = 0;
    		$loginname  = $this->filter->pregHtmlSql($formData['loginname']);
    		$password   = $this->filter->pregHtmlSql($formData['password']);
    		$error = 0;$description='';
    		$this->view->loginnameMess = $this->view->passwordMess =$this->view->verifycodeMess = '';
    		if(empty($loginname)) {
    			$description .= $this->view->loginnameMess = '*请输入用户名或者邮箱';$error++;
    		};
    		if(empty($password)) {
    			$description .= $this->view->passwordMess = '*请输入密码';$error++;
    		};
    		//登录信息错误超过3次需要验证码
    		if(isset($_SESSION['needcode']) && $_SESSION['needcode'] > $this->needcodenum){
    			$verifycode = $this->filter->pregHtmlSql($formData['verifycode']);
    			if(!isset($_SESSION['verifycode']['code'])){
    				$description .=$this->view->verifycodeMess = '验证码错误！';
    				$error ++;
    			}elseif(strtolower($verifycode) != $_SESSION['verifycode']['code'])
    			{
    				$description .= $this->view->verifycodeMess = '验证码错误！';$error ++;
    			}
    		}
    		$user = new Default_Model_DbTable_User();
    		$reUser = $user->getByName($loginname);
    		//用户不用
    		if(!empty($reUser) && $reUser['enable'] != 1)
    		{
    			$description .= $this->view->loginnameMess = '*该账户名已经被禁用';$error++;
    		}else{
    		  if(empty($reUser)){
    			$reUser = $user->getByEmail($loginname);
    			if(empty($reUser)){
    				$description .= $this->view->loginnameMess = '*账户名不存在';$error++;
    			}else{
    				$inputpassword = md5(md5($password));
    				if($inputpassword != $reUser['pass']){
    					$description .= $this->view->passwordMess = '*密码错误';$error++;
    				}
    			}
    		  }else{
    			$inputpassword = md5(md5($password));
    			if($inputpassword != $reUser['pass']){
    				$description .= $this->view->passwordMess = '*密码错误';$error++;
    			}
    		  }
    		}
    		if(!$error){
    			unset($_SESSION['needcode']);
    			//会员种类
    			if($reUser['emailapprove'] != 1){
    				//注册session
    				$userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    				$userInfo->uidSession = $reUser['uid'];//设置值
    				$userInfo->unameSession = $reUser['uname'];//设置值
    				$userInfo->emailSession = $reUser['email'];//设置值
    				$userInfo->approveSession = 404;//设置值
    				$this->_redirect('/user/verification');
    			}else{
    				$user = new Default_Model_DbTable_User();
    				$ip = $this->fun->getIp();
    				$user->updateByUid(array('lasttime'=>time(),'lastip'=>$ip), $reUser['uid']);
    				//注册session
    				$userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    				$userInfo->uidSession = $reUser['uid'];//设置值
    				$userInfo->unameSession = $reUser['uname'];//设置值
    				$userInfo->emailSession = $reUser['email'];//设置值
    				$userInfo->approveSession = $reUser['enable'];//设置值1
    				$userService = new Default_Service_UserService();
    				$_SESSION['member_type'] = $userService->checkComapprove();
    				//添加积分
    				$this->_scoreService->addScore('login');
    				
    				//记录登陆用户
    				$this->recordLogin($reUser['uid']);
    					
    				$this->_defaultlogService->addLog(array('log_id'=>'L','temp4'=>'登录成功'));
    				$this->_redirect("$url");
    			}
    		}else{
    			$description .=';'.$loginname.','.$verifycode;
    			$this->_defaultlogService->addLog(array('log_id'=>'L','temp1'=>400,'temp4'=>'登录失败','description'=>$description));
    			$this->view->loginname = $loginname;
    			$_SESSION['needcode']++;
    		}
    	}
    }
    /*
     * 用户注册
     */
    public function registerAction()
    {
    	if(isset($_SESSION['userInfo']) && $_SESSION['userInfo']['approveSession']==1) 
    		$this->_redirect('/');
    	$userModel = new Default_Model_DbTable_User();
    	
    	$this->view->appLevel1 = $appcModel->getAllByWhere("level = 1 AND status=1","displayorder ASC");
   
    	$this->view->invitekey = trim($_GET['invitekey']);
    	if($this->getRequest()->isPost()){
    		$this->view->formData = $formData = $this->getRequest()->getPost();
    		$uname = $this->filter->pregHtmlSql($formData['uname']);
    		$email = $this->filter->pregHtmlSql($formData['email']);
    		$password = $this->filter->pregHtmlSql($formData['password']);
    		$verifycode = $this->filter->pregHtmlSql($formData['verifycode']);
    		$this->view->verifycodeMess ='';
    		$this->view->userMess ='';
    		$this->view->emailMess ='';
    		$error = 0;$description='';
    		$invitekey =$formData['invitekey'];

    		if(!$uname)
    		{
    			$description .= $this->view->userMess = '用户名不能为空！';
    			$error ++;
    		}else{
    		    $reUser = $userModel->getByName($uname);
    			if($reUser){
    				$description .=$this->view->userMess = '用户名已经存在！';
    				$error ++;
    			}
    		}
    		if(!$email)
    		{
    			$description .=$this->view->emailMess = '邮箱不能为空！';
    			$error ++;
    		}elseif(!$this->filter->checkEmail($email)){
    			$description .=$this->view->emailMess = '请输入正确的邮箱地址！';
    			$error ++;
    		}else{
    			$reUser = $userModel->getByEmail($email);
    			if($reUser){
    				$description .=$this->view->emailMess = '邮箱已经存在！';
    				$error ++;
    			}
    		}
    		if(!isset($_SESSION['verifycode']['code'])){
    			$description .=$this->view->verifycodeMess = '验证码错误！';
    			$error ++;
    		}elseif(strtolower($verifycode) != $_SESSION['verifycode']['code'])
    		{
    			$description .=$this->view->verifycodeMess = '验证码错误！';
    			$error ++;
    		}
    		if($error){
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp4'=>'注册失败','description'=>$description));
    		}else{
    			   $password = md5(md5($password));
    			   $ip = $this->fun->getIp();
    			   $newid = $userModel->addUser(array('uname'=>$uname,
    			   		'pass'=>$password,
    			   		'email'=>$email,
    			   		'created'=>time(),
    	           		'lasttime'=>time(),
    			   		'ip'=>$ip,
    	           		'lastip'=>$ip));
    			   $userprofile = new Default_Model_DbTable_UserProfile();
    			   $uparr = array('uid'=>$newid,
    			   		'property'=>'1',
    			   		'industry'=>'2',
    			   		'staffid'=>'3',
    			   		'created'=>time());
    			   $userprofile->addUser($uparr);
    			   //当前时间。产生加密key
    			   $mycommon = new MyCommon();
    			   $keyone  = md5($uname.date("l dS F Y h:i:s A"));
    	           $hashkey = $mycommon->encryptVerification($uname,$keyone );
    	           $verificationcode =new Default_Model_DbTable_VerificationCode();
    	           $verificationcode->addCode(array('uid'=>$newid,
    	           		'uname'=>$uname,
    	           		'invitekey'=>$invitekey,
    	           		'code'=>$keyone,
    	           		'ecode'=>$hashkey,
    	           		'created'=>time()));
    	           //记录日志
    	           $this->_defaultlogService->addLog(array('log_id'=>'A','temp4'=>'注册成功'));
    	           
    			   //注册Session
    			   $userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    			   $userInfo->uidSession = $newid;//设置值
    			   $userInfo->unameSession = $uname;//设置值
    			   $userInfo->emailSession = $email;//设置值
    			   $userInfo->approveSession = 404;//设置值
    			   //建立文件夹
    			   $this->_MyCommon = new MyCommon();
    			   $this->_MyCommon->createUserFolder($newid);
    			   //异步请求开始
    			   $this->fun->asynchronousStarts();
    			   //发送验证email
    			   if($invitekey) $hashkey .='&invitekey='.$invitekey;
    			   $emailreturn = $this->fun->sendverification($hashkey,$email,$uname);
    			   //邮件日志
    			   if($emailreturn ){
    			   	   $this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp4'=>'发送注册邮件成功'));
    			   }else{
    			   	   $this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp4'=>'发送注册邮件失败'));
    			   }
    			   $this->_redirect('/user/verification');
    			   //异步请求开始
    			   $this->fun->asynchronousEnd();
    		}
    	}
    }
    /*
     * 验证第二步
     */
    public function verificationAction() {
    	//获取email地址
    	$mycommon = new MyCommon();
    	$this->view->emailurl = $mycommon->getEmailUrl($_SESSION['userInfo']['emailSession']);
    	$hashkey = trim($_GET['hashkey']);
    	$invitekey = trim($_GET['invitekey']);
    	if(!empty($hashkey))
    	{
    		$hashkey = $mycommon->decryptVerification($hashkey);
    	    $hasykeyArray = explode(',', $hashkey);
    	    $this->view->success ='';
    		//模拟登录，避免使用新开启的浏览器进行验证
    		if(empty($_SESSION['userInfo']) || !isset($_SESSION['userInfo'])){
    		    $user = new Default_Model_DbTable_User();
    		    $reUser = $user->getByName($hasykeyArray[1]);
    		    if(!empty($reUser) && $reUser['emailapprove'] != 1){
    		        //注册session
    		        $userInfo = new Zend_Session_Namespace('userInfo');//使用SESSION存储数据时要设置命名空间
    		        $userInfo->uidSession = $reUser['uid'];//设置值
    		        $userInfo->unameSession = $reUser['uname'];//设置值
    		        $userInfo->emailSession = $reUser['email'];//设置值
    		        $userInfo->approveSession = 404;//设置值
    		    }else $this->_redirect('/');
    		}
    		if($_SESSION['userInfo']['approveSession']!= 404) $this->_redirect('/');
    		$VerificationCode = new Default_Model_DbTable_VerificationCode();
    		$reCode = $VerificationCode->getBySql("uname='{$hasykeyArray[1]}' AND type='0'","created DESC");
    		if(!empty($reCode)){
    		   //验证码超过一小时过期
    		   if($reCode['created'] <= strtotime("-1 hours")){
    			   $this->view->success =false;
    			   //记录日志
    			   $this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp4'=>'注册验证失败','description'=>'验证码超过一小时过期'));
    		   }else{
    			   if($reCode['code']==$hasykeyArray[0]){
    			   	  //更新
    			   	  $user = new Default_Model_DbTable_User();
    			   	  $user->updateByUname(array('emailapprove'=>1,'lasttime'=>time()),$hasykeyArray[1]);
    			   	  $VerificationCode->updateById($reCode['id'],array('type'=>1,'modified'=>time()));
    			   	  //邀请送积分
    			   	  if($invitekey){
    			   	  	$adduid = $invitekey;
    			   	  	if($adduid){
    			   	  		$this->_scoreService->addScore('invite',1,$adduid,$adduid);
    			   	  	}
    			   	  }
    			   	  //记录日志
    			   	  $this->_defaultlogService->addLog(array('log_id'=>'E','temp4'=>'注册验证成功'));
    			   	  $this->_redirect('/user/success');
    			   }else{
    			   	$this->view->success =false;
    			   	//记录日志
    			   	$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp4'=>'注册验证失败','description'=>'验证码不相等'));
    			   }
    		   }
    		}else {
    		    $this->view->success =false;
    		    //记录日志
    		    $this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp4'=>'注册验证失败','description'=>'验证码为空'));
    		}
    	}else {
    		if($_SESSION['userInfo']['approveSession']!= 404) $this->_redirect('/');
    		$this->view->success =true;
    	}
    }
    /*
     * 重新发送验证码
    */
    public function resendAction(){
    	if(empty($_SESSION['userInfo']) || !isset($_SESSION['userInfo'])) $this->_redirect('/user/login');
    	if($_SESSION['userInfo']['approveSession']!= 404)$this->_redirect('/');
    	if($_SESSION['resendAction']==1) $this->_redirect('/user/verification');
    	//获取邀请码
    	$verificationcode =new Default_Model_DbTable_VerificationCode();
    	$invitekey = $verificationcode->QueryItem("SELECT invitekey FROM `verification_code` WHERE `uid` = '".$_SESSION['userInfo']['uidSession']."' ORDER BY id DESC LIMIT 1");
    	$mycommon = new MyCommon();
    	$keyone  = md5($_SESSION['userInfo']['unameSession'].date("l dS F Y h:i:s A"));
    	$hashkey = $mycommon->encryptVerification($_SESSION['userInfo']['unameSession'],$keyone );
    	
    	$verificationcode->addCode(array('uid'=>$_SESSION['userInfo']['uidSession'],
    			'uname'=>$_SESSION['userInfo']['unameSession'],
    			'invitekey'=>$invitekey,
    			'code'=>$keyone,
    			'ecode'=>$hashkey,
    			'created'=>time()));
    	//发送验证email
    	if($invitekey) $hashkey .='&invitekey='.$invitekey;
    	$emailreturn = $this->fun->sendverification($hashkey,$_SESSION['userInfo']['emailSession'],$_SESSION['userInfo']['unameSession']);
    	//邮件日志
    	if($emailreturn ){
    		$this->_defaultlogService->addLog(array('log_id'=>'M','temp4'=>'重新发送验证邮件成功'));
    	}else{
    		$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp4'=>'重新发送验证邮件失败'));
    	}
    	
    	$_SESSION['resendAction'] =1;
    	$this->_redirect('/user/verification');
    }
    /*
     * 验证成功页面
     */
    public function successAction() {
    	if(empty($_SESSION['userInfo']) || !isset($_SESSION['userInfo'])) $this->_redirect('/user/login');
    	if($_SESSION['userInfo']['approveSession']!= 404)$this->_redirect('/');
    	$_SESSION['userInfo']['approveSession'] = 1;
    }
    /*
     * 忘记密码
     */
    public function forgotpassAction(){
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$loginname = $this->filter->pregHtmlSql($formData['loginname']);
    		$verifycode = $this->filter->pregHtmlSql($formData['verifycode']);
    		$error = 0;$description = '';
    		$this->view->loginnameMess = $this->view->sendemailMess =$this->view->verifycodeMess = '';
    		if(empty($loginname)) {
    			$description .= $this->view->loginnameMess = '请输入用户名或者邮箱！';$error++;
    		};
    		$user = new Default_Model_DbTable_User();
    		$reUser = $user->getByName($loginname);
    		if(!empty($reUser) && $reUser['enable'] != 1){
    			   $description .=  $this->view->loginnameMess = '该用户名已经被禁用！';$error++;
    		}else{
    		   if(empty($reUser)){
    			  $reUser = $user->getByEmail($loginname);
    			  if(empty($reUser)){
    				$description .= $this->view->loginnameMess = '输入用户名或者邮箱不存在！';$error++;
    			  }
    		   }
    		}
    		if(strtolower($verifycode) != $_SESSION['verifycode']['code'])
    		{
    			$description .= $this->view->verifycodeMess = '验证码错误！';
    			$error ++;
    		}
    		if(!$error){
    			//注册session
    			$key = rand(10000,99999);
    			$forgotpass = new Zend_Session_Namespace('forgotpass');//使用SESSION存储数据时要设置命名空间
    			$forgotpass->key   = $key;//设置验证码
    			$forgotpass->email = $reUser['email'];//设置
    			$forgotpass->uid = $reUser['uid'];//设置值
    			$forgotpass->type  = 1;//设置值
    			//发邮件
    			$mycommon = new MyCommon();
    			$emailreturn = $this->fun->sendforgotpass($key,$reUser['uname'],$reUser['email']);
    			//邮件日志
    			if($emailreturn ){
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp3'=>$loginname,'temp4'=>'发送重置密码邮件成功'));
    			}else{
    				$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp3'=>$loginname,'temp4'=>'发送重置密码邮件失败'));
    			}
    			//记录日志
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp3'=>$loginname,'temp4'=>'提交重置密码成功'));
    			$this->_redirect('/user/resetpass');
    		}
    		//记录日志
    		$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp3'=>$loginname,'temp4'=>'提交重置密码失败','description'=>$description));
    		
    	}
    }
    /*
     * 重置密码
    */
    public function resetpassAction(){
    	if($_SESSION['forgotpass']['type']!= 1)$this->_redirect('/user/login');
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$password = $formData['password1'];
    		$password2= $formData['password2'];
    		$keycode  = $formData['keycode'];
    		$error = 0;$description='';
    		$this->view->passwordMess = $this->view->keycodeMess = '';
    		if($password != $password2) {
    			$description .= $this->view->passwordMess = '两次输入的密码不相同!';$error++;
    		};
    		if($_SESSION['forgotpass']['key']!=$keycode) {
    			$description .=$this->view->keycodeMess = '验证码错误!';$error++;
    		};
    		if(!$error){
    		    if($_SESSION['forgotpass']['uid'])
    		    {
    		    	$user = new Default_Model_DbTable_User();
    		    	$newpass  = md5(md5($password));
    		    	$data = array('pass'=>$newpass);
    		    	$user->updateByUid($data, $_SESSION['forgotpass']['uid']);
    		    	$_SESSION['forgotpass']['type'] = 2;
    		    	//记录日志
    		    	$this->_defaultlogService->addLog(array('log_id'=>'E','temp3'=>$keycode,'temp4'=>'重置密码成功'));
    		    	$this->_redirect('/user/resetsuccess');
    		    }else $this->_redirect('/user/forgotpass');
    		}
    		$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp3'=>$keycode,'temp4'=>'重置密码失败','description'=>$description));
    	}
    	//获取email地址
    	$mycommon = new MyCommon();
    	$this->view->email = $_SESSION['forgotpass']['email'];
    	$this->view->emailurl = $mycommon->getEmailUrl($_SESSION['forgotpass']['email']);
    	$this->view->key    = $_GET['key'];
    }
    /*
     * 修改密码成功
    */
    public function resetsuccessAction(){
    	if($_SESSION['forgotpass']['type']!= 2)$this->_redirect('/user/login');
    	unset($_SESSION['forgotpass']);//注销session
    }
    /*
     * 退出登录
     */
    public function logoutAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	
    	$snsuser = $_SESSION['userInfo']['uidSession'];
    	//用户id
    	$frontendOptions = array('lifeTime' => 0,'automatic_serialization' => true);
    	$backendOptions = array('cache_dir' => CACHE_PATH);
    	//$cache 在先前的例子中已经初始化了
    	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    	// 查看一个缓存是否存在:
    	$cache_key = 'sns_user_login_'.$snsuser;
    	$cache->save(array("snsuser"=>0),$cache_key);

    	unset($_SESSION['userInfo']);//注销session
    	session_destroy();
    	if($this->_getParam('url')) $url = $this->_getParam('url');
    	else $url = '/';
    	
    	$this->_redirect($url);
    }
    /*
     * ajax 检查email是否存在
     */
    public function emailcheckAction() {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	/* RETURN VALUE */
    	$validateValue=$_POST['validateValue'];
    	$validateId   =$_POST['validateId'];
    	$validateError=$_POST['validateError'];
    	/* RETURN VALUE */
    	$arrayToJs = array();
    	$arrayToJs[0] = $validateId;
    	$arrayToJs[1] = $validateError;
    	//用户名检查
    	$user = new Default_Model_DbTable_User();
    	$reUser = $user->getByEmail($validateValue);
    	if(!$this->filter->checkEmail($validateValue)){
    		$arrayToJs[1] = 'email';
    		$arrayToJs[2] = "false";			// RETURN TRUE
    		echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';
    	}else{
    	    if(!$reUser){		// validate??
    		    $arrayToJs[2] = "true";			// RETURN TRUE
    		    echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';			// RETURN ARRAY WITH success
    	    }else{
    		    for($x=0;$x<1000000;$x++){
    			    if($x == 990000){
    				    $arrayToJs[2] = "false";
    				    echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';		// RETURN ARRAY WITH ERROR
    			    }
    		    }
    	    }
    	}
    }
    /*
     * ajax 检查用户名是否存在
     */
    public function unamecheckAction() {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	/* RETURN VALUE */
    	$validateValue=$_POST['validateValue'];
    	$validateId   =$_POST['validateId'];
    	$validateError=$_POST['validateError'];
    	/* RETURN VALUE */
    	$arrayToJs = array();
    	$arrayToJs[0] = $validateId;
    	$arrayToJs[1] = $validateError;
    	//用户名检查
    	$user = new Default_Model_DbTable_User();
    	$reUser = $user->getByName($validateValue);
    	if(!$reUser){		// validate??
    		$arrayToJs[2] = "true";			// RETURN TRUE
    		echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';			// RETURN ARRAY WITH success
    	}else{
    		for($x=0;$x<1000000;$x++){
    			if($x == 990000){
    				$arrayToJs[2] = "false";
    				echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';		// RETURN ARRAY WITH ERROR
    			}
    		}
    	}
    }
    /*
     * ajax 检查验证码是否正确
     */
    public function verifycodeAction() {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	/* RETURN VALUE */
    	$validateValue=$_POST['validateValue'];
    	$validateId   =$_POST['validateId'];
    	$validateError=$_POST['validateError'];
    	/* RETURN VALUE */
    	$arrayToJs = array();
    	$arrayToJs[0] = $validateId;
    	$arrayToJs[1] = $validateError;
    	if(strtolower($validateValue) == $_SESSION['verifycode']['code']){		// validate??
    		$arrayToJs[2] = "true";			// RETURN TRUE
    		echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';			// RETURN ARRAY WITH success
    	}else{
    		for($x=0;$x<1000000;$x++){
    			if($x == 990000){
    				$arrayToJs[2] = "false";
    				echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';		// RETURN ARRAY WITH ERROR
    			}
    		}
    	}
    }
    /**
     * 检查是否注册企业信息
     */
    public function checkdetailedAction(){
    	$this->_userService = new Default_Service_UserService();
    	//检查用户企业资料是否完备
    	if($this->_userService->checkDetailed())
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'企业资料已经完备。'));
    		exit;
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'请提交相关企业资料。'));
    		exit;
    	}
    }
    /**
     * 获取用户积分
     */
    public function getuserscoreAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$score = $this->_scoreService->getScoreByUid();
    	echo Zend_Json_Encoder::encode(array("score"=>$score));
    	exit;
    }
    
    /**
     * 记录用户登录状态
     */
    private function recordLogin($uid){
    	//记录登陆用户
    	$snsuser = $uid;
    	//用户id
    	$frontendOptions = array('lifeTime' => 1800,'automatic_serialization' => true);
    	$backendOptions = array('cache_dir' => CACHE_PATH);
    	//$cache 在先前的例子中已经初始化了
    	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    	// 查看一个缓存是否存在:
    	$cache_key = 'sns_user_login_'.$snsuser;
    	$cache->save(array("snsuser"=>$snsuser),$cache_key);
    }
}

