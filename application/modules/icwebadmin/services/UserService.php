<?php
class Icwebadmin_Service_UserService
{
	private $_userModel;
	private $_userporModel;
	private $_staffService;
	public function __construct()
	{
		$this->_userModel   = new Icwebadmin_Model_DbTable_User();
		$this->_userporModel= new Icwebadmin_Model_DbTable_UserProfile();
		$this->_staffService=new Icwebadmin_Service_StaffService();
	}
	
	/*
	 * 获取用户详细资料
	 */
	public function getUserProfile($uid)
	{
		$myinfoarray = $this->_userModel->getBySql("SELECT u.*,u.created as utime,up.*,
				up.province as provinceid,up.city as cityid,up.area as areaid,ud.department,
				up.oa_code,up.oa_sales as up_oa_sales,p.province,c.city,a.area,
				ac.name as appname,
				st.tel as st_tel,st.ext,st.lastname,st.firstname,
				p.province,c.city,a.area FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN app_category as ac ON ac.id = up.industry
				LEFT JOIN province as p ON up.province = p.provinceid
				LEFT JOIN city as c ON up.city = c.cityid
				LEFT JOIN area as a ON up.area = a.areaid
				LEFT JOIN user_department as ud ON ud.id = up.department_id
    			WHERE u.uid=:uidtmp",array('uidtmp'=>$uid));
		return $myinfoarray[0];
	}
	/*
	 * 获取OA用户资料相关
	*/
	public function getOaUser($uid)
	{
		$myinfoarray = $this->_userModel->getBySql("SELECT u.uid,u.uname,up.companyname,up.oa_code,
				up.staffid,uoa.id as uoaid,uoa.status
				FROM user as u
				LEFT JOIN user_profile as up ON u.uid = up.uid
				LEFT JOIN user_oa_apply as uoa ON u.uid = uoa.uid
    			WHERE u.uid=:uidtmp",array('uidtmp'=>$uid));
		return $myinfoarray[0];
	}
	/**
	 * 获取用户收货地址
	 */
	public function getUserAddress($uid)
	{
		//收货地址
		$sqlstr ="SELECT a.id,a.uid,a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.default,a.warehousing,
    	p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area
    	FROM address as a LEFT JOIN province as p
        ON a.province=p.provinceid
        LEFT JOIN city as c
        ON a.city=c.cityid
        LEFT JOIN area as e
        ON a.area = e.areaid
    	WHERE a.uid=:uidtmp AND a.status=1
    	ORDER BY `default` DESC";
		return $this->_userModel->getBySql($sqlstr, array('uidtmp'=>$uid));
	}
	/*
	 * 获取销售负责用户详细资料
	*/
	public function getUserSell()
	{
		$sell = $this->_staffService->checkSell();
		$sellsql = '';
		if($sell){
			$sellsql = " AND up.staffid = '".$_SESSION['staff_sess']['staff_id']."'";
		}
		$myinfoarray = $this->_userModel->getBySql("SELECT up.uid,up.companyname,up.staffid	
				FROM user_profile as up
				LEFT JOIN user as u ON u.uid = up.uid 
    			WHERE up.companyname!='' AND u.enable=1 $sellsql");
		return $myinfoarray;
	}
	/**
	 * 更新OAid到客户
	 */
	public function updateOaCode($oacode,$uid){
		return $this->_userporModel->update(array('oa_code'=>$oacode), "uid = '$uid'");
	}
	/**
	 * 获取OAid到客户
	 */
	public function getOaCode($uid){
		$ure = $this->_userporModel->getRowByWhere("uid = '$uid'");
		return $ure['oa_code'];
	}
	/**
	 * 获取uid by uname
	 */
	public function getUid($uname){
		$ure = $this->_userModel->getRowByWhere("uname = '$uname'");
		return $ure['uid'];
	}
	/**
	 * 获取uid by uname
	 */
	public function getUidBycompanyname($companyname){
		$ure = $this->_userporModel->getRowByWhere("companyname = '$companyname'");
		return $ure['uid'];
	}
	/**
	 * 获取uid by Email
	 */
	public function getUidByEmail($email){
		$ure = $this->_userModel->getRowByWhere("email = '$email'");
		return $ure['uid'];
	}
	/**
	 * 获取uname by uid
	 */
	public function getUname($uid){
		$ure = $this->_userModel->getRowByWhere("uid = '$uid'");
		return $ure['uname'];
	}
	/**
	 * 获取已经存储的用户提供快递账号
	 */
	public function getExpressAddress($uid)
	{
		$sqlstr ="SELECT a.*
		FROM order_address as a
		WHERE a.uid='{$uid}' AND express_account IS NOT NULL AND express_account!='' ORDER BY created DESC LIMIT 0 , 1";
		return $this->_userModel->getByOneSql($sqlstr);
	}
	/**
	 * 注册用户走势
	 */
	public function userstrend($sql,$sdate,$edate,$type){
		$or_tmp = array();
		$array = $this->_userModel->getBySql($sql);

		if($edate>=$sdate){
			$rs = new Icwebadmin_Service_RepoService();
			$tmp = $rs->count_days($sdate,$edate)+1;
			
			$dtime = 60*60*24;
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$weekarr[date('W', $ftime)][] = date('y/m/d',$ftime);
			}
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$montharr[date('m', $ftime)][] = date('y/m/d',$ftime);
			}
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$ltime = $sdate+(($i+1)*$dtime);
				
				if($type=='day'){
				   //$or_tmp[date('y/m/d',$ftime)] = array('all'=>0,'num'=>0);
				   foreach($array as $oarr){
					  if($oarr['created']<=$ltime){
						  $or_tmp[date('y/m/d',$ftime)]['all']++;
					  }
					  if($oarr['created']>$ftime && $oarr['created']<=$ltime){
					  	$or_tmp[date('y/m/d',$ftime)]['num']++;
					  }
				   }
				}elseif($type=='week'){
					$key = date('y', $ltime).'年'.date('W', $ltime).' 周'.'('.($weekarr[date('W', $ltime)][0].'-'.$weekarr[date('W', $ltime)][count($weekarr[date('W', $ltime)])-1]).')';
					$or_tmp2[$key] = array('all'=>0);
					foreach($array as $oarr){
						if($oarr['created']<=$ltime){
							$or_tmp2[$key]['all']++;
						}
						if($oarr['created']>$ftime && $oarr['created']<=$ltime){
	
							$or_tmp[$key]['num']++;
						}
					}
					$or_tmp[$key]['all'] = $or_tmp2[$key]['all'];
				}elseif($type=='month'){
					$key = date('y', $ltime).'年'.date('m', $ltime).'月'.'('.($montharr[date('m', $ltime)][0].'-'.$montharr[date('m', $ltime)][count($montharr[date('m', $ltime)])-1]).')';
					$or_tmp2[$key] = array('all'=>0);
					foreach($array as $oarr){
						if($oarr['created']<=$ltime){
							$or_tmp2[$key]['all']++;
						}
						if($oarr['created']>$ftime && $oarr['created']<=$ltime){
							$or_tmp[$key]['num']++;
						}
					}
					$or_tmp[$key]['all'] = $or_tmp2[$key]['all'];
					
				}
			}
		}
		return $or_tmp;
	}
}