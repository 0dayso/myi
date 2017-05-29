<?php
class Default_Service_UserService
{
	private $_userModel;
	private $_userporModel;
	private $_defaultlogService;
	public function __construct()
	{
		$this->_userModel   = new Default_Model_DbTable_User();
		$this->_userporModel= new Default_Model_DbTable_UserProfile();
		$this->_defaultlogService  = new Default_Service_DefaultlogService();
	}
	
	/*
	 * 获取用户详细资料
	 */
	public function getUserProfile()
	{
		$myinfoarray = $this->_userModel->getBySql("SELECT u.*,up.* ,st.tel as st_tel,st.phone as st_phone,st.ext,st.lastname,st.firstname,
				app.name as appname,ud.department,p.province,c.city,a.area FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN province as p ON up.province = p.provinceid
				LEFT JOIN city as c ON up.city = c.cityid
				LEFT JOIN area as a ON up.area = a.areaid
				LEFT JOIN app_category as app ON app.id = up.industry
				LEFT JOIN user_department as ud ON ud.id = up.department_id
    			WHERE u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $myinfoarray[0];
	}
	/*
	 * 获取用户详细资料
	*/
	public function getUserProfileByUid($uid)
	{
		$myinfoarray = $this->_userModel->getBySql("SELECT u.*,up.* ,st.tel as st_tel,st.ext,st.lastname,st.firstname,
				p.province,c.city,a.area FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN province as p ON up.province = p.provinceid
				LEFT JOIN city as c ON up.city = c.cityid
				LEFT JOIN area as a ON up.area = a.areaid
    			WHERE u.uid=:uidtmp",array('uidtmp'=>$uid));
		return $myinfoarray[0];
	}
	/**
	 * 获取跟进用户的销售信息
	*/
	public function getUserSales()
	{
		$myinfoarray = $this->_userModel->getBySql("SELECT up.uid,up.staffid,sa.staff_id,sa.department_id,sa.level_id,
				sa.head,sa.email,sa.tel,sa.phone,sa.lastname,sa.firstname,sa.mail_to
				FROM user_profile as up
				LEFT JOIN admin_staff as sa ON up.staffid = sa.staff_id
    			WHERE up.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		return $myinfoarray[0];
	}
	/*
	 * 检查用户是否通过企业认证
	*/
	public function checkComapprove()
	{
		$companyapprove = $this->_userporModel->getBySql("SELECT companyapprove FROM user_profile
    			WHERE uid=:uidtmp AND companyapprove=1",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($companyapprove)) return true;
		else return false;
	}
	/*
	 * 检查用户企业资料是否完备
	*/
	public function checkDetailed()
	{
		$detailed = $this->_userporModel->getBySql("SELECT detailed FROM user_profile
    			WHERE uid=:uidtmp AND detailed=1",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($detailed)) return true;
		else return false;
	}
	/*
	 * 检查用户是否具备cod资格
	*/
	public function checkCod()
	{
		$detailed = $this->_userporModel->getBySql("SELECT cod FROM user_profile
    			WHERE uid=:uidtmp AND cod=1",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		if(!empty($detailed)) return true;
		else return false;
	}
	/**
	 * 个人中心获取治资料
	 */
	public function getCenterUserInfo()
	{
		return $this->_userModel->getRowBySql("SELECT u.uname,u.lasttime,u.lastip,up.* 
				FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid
    			WHERE u.uid=:uidtmp",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
	}
	/**
	 * 个人中心在线订单相关
	 * 101待付款201已付款待发货202已配送,
	 * 待确认收货301已确认收货,待评价302已确认收货,
	 * 已评价401客户取消订单,501退款,502退货退款
	 */
	public function getOnlineSoInfo()
	{
		$onlineso = $this->_userporModel->getBySql("SELECT total,currency,status FROM sales_order
    			WHERE uid=:uidtmp AND available=1 AND back_status!=102",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$currencytotal = array();
		$wpay = $rec = 0;
		 for($i=0;$i<count($onlineso);$i++)
		 {
		 	if(in_array($onlineso[$i]['status'],array('301','302'))){
		 	  $currencytotal[$onlineso[$i]['currency']] +=$onlineso[$i]['total'];
		 	}
			if($onlineso[$i]['status']==101) $wpay++;
			if($onlineso[$i]['status']==202) $rec++;
		 }
		 $onlineso['total'] = $currencytotal;
		 $onlineso['currency']  = $onlineso[0]['currency'];
		 $onlineso['wpay']  = $wpay;
		 $onlineso['rec']   = $rec;
		 return $onlineso;
		
	}
	/**
	 * 个人中心询价订单相关
	 * 前台状态;括号的代表是cod状态 101待付预付款（cod:等待审核） 102 需要订货，
	 * 订单处理中(转账:已支付预付款) 103已经订货并返回交货期（转账:等待付剩余货款）
	 * 201待发货（转账:已付剩余货款） 202已发货,待确认收货 
	 * 301已确认收货,待评价 302已确认收货 
	 * 401客户取消订单 ，501退款，502退货退款
	 */
	public function getInqSoInfo()
	{
		$inqso = $this->_userporModel->getBySql("SELECT total,currency,status FROM inq_sales_order
    			WHERE uid=:uidtmp AND available=1 AND back_status!=102",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		 $currencytotal = array();
		 $wpay = $over = $rec = 0;
		 for($i=0;$i<count($inqso);$i++)
		 {
		    if(in_array($inqso[$i]['status'],array('301','302'))){
		 	   $currencytotal[$inqso[$i]['currency']] +=$inqso[$i]['total'];
		 	}
			if($inqso[$i]['status']==101) $wpay++;
			if($inqso[$i]['status']==103) $over++;
			if($inqso[$i]['status']==202) $rec++;
		 }
		 $inqso['total'] = $currencytotal;
		 $inqso['wpay']  = $wpay;
		 $inqso['over']  = $over;
		 $inqso['rec']   = $rec;
		 return $inqso;
	}
	/**
	 * 个人中心询价相关
	 * 状态，0被删除，1等待回复，2已报价，3议价审核中，4审核不通过，5已经下单等处理，6成功下单
	 */
	public function getInqInfo()
	{
		$inq = $this->_userporModel->getBySql("SELECT delivery,currency,status FROM inquiry
    			WHERE uid=:uidtmp ",array('uidtmp'=>$_SESSION['userInfo']['uidSession']));
		$wait = $already = 0;
		for($i=0;$i<count($inq);$i++)
		{
		  if($inq[$i]['status']==1) $wait++;
		  if($inq[$i]['status']==2) $already++;
	    }
		$inq['wait']    = $wait;
		$inq['already'] = $already;
		return $inq;
	}
	/**
	 * 个人中心热推商品
	 */
	public function getHotPord()
	{
		$sqlstr ="SELECT re.cat_id,pro.id,
				pro.part_no,pro.description,pro.part_img,
				b.name as pname,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,
				pro.mpq_price,pro.break_price,pro.hk_stock,pro.sz_stock,pro.bpp_stock,pro.bpp_cover,pro.sz_cover,pc.name
		FROM recommend as re 
		LEFT JOIN product as pro ON re.comid=pro.id
		LEFT JOIN brand as b ON pro.manufacturer=b.id
		LEFT JOIN prod_category as pc ON pro.part_level3=pc.id
		WHERE re.type='hot' AND re.part='home' AND re.status = 1 AND b.status = 1 ORDER BY re.displayorder ASC";
		$allhotArr = $this->_userporModel->getBySql($sqlstr);
		//随机打乱数组
		shuffle($allhotArr);
		return $allhotArr;
	}
	/**
	 * 用户下订单时，更新用户详细记录
	 */
	public function upInfoByOrder($data,$uid)
	{
		try{	
		   $re = $this->_userporModel->getByOneSql("SELECT uid FROM user_profile WHERE uid='{$uid}' AND companyname!=''");
		   if(!$re){
			   return $this->_userporModel->updateByUid($data,$uid);
		   }else return false;
		}catch (Exception $e) {
		     return false;
		}
	}
	/**
	 * 客户上传汇款凭证邮件通知
	 */
	public function transferuplodeEamil($orderarr,$orderService,$ordertype,$receipt,$tablerow){
		$this->fun = new MyFun();
		$this->emailService = new Default_Service_EmailtypeService();
		//销售信息
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
		if($ordertype=='online'){
			$ordertitle = '在线';
		}elseif($ordertype=='inq'){
			$ordertitle = '询价';
		}
		if($tablerow == 2){
			$fromname = 'IC易站';
			$title    = $ordertitle.'订单#：'.$orderarr['salesnumber'].'的余款银行汇款凭证上传成功，请确认是否到账';
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                               '.$ordertitle.'订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].'</strong>，客户已经上传余款转账凭证。详细见附件。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">请确认凭证是否真实并到到IC易站后台 <b style="font-size:14px; color:#fd2323">进行下步处理</b>，谢谢！</div></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}else{
			$fromname = 'IC易站';
			$title    = $ordertitle.'订单#：'.$orderarr['salesnumber'].'的银行汇款凭证上传成功，请确认是否到账';
			$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">
                                               '.$ordertitle.'订单#：<strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px;">'.$orderarr['salesnumber'].'</strong>，客户已经上传转账凭证。详细见附件。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">请确认凭证是否真实并到到IC易站后台 <b style="font-size:14px; color:#fd2323">进行下步处理</b>，谢谢！</div></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		}
		if($ordertype=='online'){
    	  $mess .= $orderService->getOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
    	}elseif($ordertype=='inq'){
    	  $mess .= $orderService->getInqOrderTable($orderarr,$orderarr['pordarr'],$hi_mess);
    	}
		//负责销售
    	$staffservice = new Icwebadmin_Service_StaffService();
    	$sellinfo = $staffservice->sellbyuid($orderarr['uid']);
    	
		$emailarr = $this->emailService->getEmailAddress('transfer_uplode',$orderarr['uid']);
		$emailto = array($sellinfo['email']);
		//如果有抄送人
		$staffService = new Icwebadmin_Service_StaffService();
		$emailcc = $staffService->mailtostaff($sellinfo['mail_to']);
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
		$re = $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array('2'=>$receipt),array(),0);
		if($re){
			//记录日志
			$this->_defaultlogService->addLog(array('log_id'=>'M','temp2'=>$orderarr['salesnumber'],'temp4'=>'上传了转账凭证提醒邮件成功'));
		}else{
			//记录日志
			$this->_defaultlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$orderarr['salesnumber'],'temp4'=>'上传了转账凭证提醒邮件失败'));
		}
	}
	
	/*
	 * 检查登陆web service
	 */
	public function chackLogin($string) {
		//$string->inputJosn.
		$arr = json_decode($string->arg0);
		$uarr = array();
		$snsuser = $arr->snsuserid;
		if(!empty($snsuser)){
			//用户id
			$frontendOptions = array('lifeTime' => 1800,'automatic_serialization' => true);
			$backendOptions = array('cache_dir' => CACHE_PATH);
			//$cache 在先前的例子中已经初始化了
			$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
			// 查看一个缓存是否存在:
			$cache_key = 'sns_user_login_'.$snsuser;
			$uarr = $cache->load($cache_key);
		}
		if(!empty($uarr) && $uarr['snsuser']==$arr->snsuserid){
			$reArray['responseCode'] = 0;
			$reArray['responseMsg']  = iconv("gbk", "UTF-8//IGNORE","已经登陆");
		}else{
			$reArray['responseCode'] = 401;
			$reArray['responseMsg']  = iconv("gbk", "UTF-8//IGNORE","登陆失败");
		}
		return array("return"=>iconv("gbk", "UTF-8//IGNORE",json_encode($reArray)));
	}
}