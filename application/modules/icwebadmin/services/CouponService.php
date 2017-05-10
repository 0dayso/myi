<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_CouponService
{
	private $_couponModel;
	private $_emailService;
	public function __construct() {
		$this->_couponModel = new Icwebadmin_Model_DbTable_Model("coupon");
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
	}
	/**
	 * 获取发放过优惠券并下单的订单号
	 */
	public function getCouponSalesnumber($staffid){
		$sqlstr = "SELECT cp.salesnumber FROM coupon as cp
		WHERE cp.created_by='{$staffid}' AND cp.salesnumber IS NOT NULL";
		return $this->_couponModel->Query($sqlstr);
	}
	/**
	 * 获取优惠券
	 */
	public function getCouponByWhere($where)
	{
		$where = $where?$where:"cp.code!=''";
		$sqlstr = "SELECT cp.*,u.uname,u.email,up.companyname,st.lastname,st.firstname,
		p.part_no,p.manufacturer,b.name as bname,b2.name as bname2 FROM coupon as cp
		LEFT JOIN user as u ON cp.uid = u.uid
		LEFT JOIN user_profile as up ON cp.uid = up.uid
		LEFT JOIN product as p ON cp.part_id = p.id
		LEFT JOIN brand as b ON p.manufacturer = b.id
		LEFT JOIN brand as b2 ON cp.brand_id = b2.id
		LEFT JOIN admin_staff as st ON st.staff_id = cp.created_by
		WHERE $where";
		return $this->_couponModel->QueryRow($sqlstr);
	}
	/**
	 * 获取订单使用的优惠券
	 */
	public function getOrderCoupon($coupon_code){
		$re = array();
		if($coupon_code){
			$coupon_code = array_filter(explode(',',$coupon_code));
			foreach($coupon_code as $code){
				$re[] = $this->getCouponByWhere(" code='{$code}'");
			}
		}
		return $re;
	}
	/**
	 * 获取用户的所有优惠券
	 */
	public function getAllCoupon($offset,$perpage,$str='',$orderbystr='')
	{
		if(!$orderbystr){
			$orderbystr = "ORDER BY cp.status ASC,cp.created DESC";
		}
		$where = $where?$where:"cp.code!=''";
		$sqlstr = "SELECT cp.*,so.customer,so.efficiency,so.effective_time,
		u.uname,up.companyname,p.part_no,
		b.name as bname,b2.name as bname2,
		st1.lastname as lastname1,st1.firstname as firstname1,
		st2.lastname as lastname2,st2.firstname as firstname2
		FROM coupon as cp
		LEFT JOIN sales_order as so ON so.salesnumber = cp.salesnumber
		LEFT JOIN user as u ON cp.uid = u.uid
		LEFT JOIN user_profile as up ON cp.uid = up.uid
		LEFT JOIN product as p ON cp.part_id = p.id 
		LEFT JOIN brand as b ON p.manufacturer = b.id 
		LEFT JOIN brand as b2 ON cp.brand_id = b2.id
		LEFT JOIN admin_staff as st1 ON st1.staff_id = cp.created_by
		LEFT JOIN admin_staff as st2 ON st2.staff_id = cp.modified_by
		WHERE {$str} {$orderbystr} LIMIT $offset,$perpage";
		return $this->_couponModel->getBySql($sqlstr);
	}
	/**
	* 获取count()行数
	 */
	 public function getRowNum($str='')
	 {
	 	$where = $where?$where:"cp.code!=''";
	 	$sqlstr = "SELECT count(id) as num FROM coupon as cp
	 				WHERE {$str}";
	 	return $this->_couponModel->QueryItem($sqlstr);
	 }
	 /**
	  * 获取唯一优惠券号
	  */
	 public function getCoupon()
	 {
	 	$exclude_codes_array = array();
	 	$re = $this->_couponModel->Query("SELECT code FROM coupon");
	 	for($i=0;$i<count($re);$i++){
	 		$exclude_codes_array[]=$re[$i]['code'];
	 	}
	 	return $this->generate_promotion_code(1,$exclude_codes_array);
	 }
	 private function generate_promotion_code($no_of_codes,$exclude_codes_array=array(),$code_length = 7)
     {
          $characters = "123456789";
	      $promotion_codes = array();//这个数组用来接收生成的优惠码
	      for($j = 0 ; $j < $no_of_codes; $j++)
	      {
	          $code = "";
	          for ($i = 0; $i < $code_length; $i++)
	          {
	             $code .= $characters[mt_rand(0, strlen($characters)-1)];
	          }
	         //如果生成的4位随机数不再我们定义的$promotion_codes函数里面
	         if(!in_array($code,$promotion_codes))
	         {
	            if(is_array($exclude_codes_array))//
	            {
	               if(!in_array($code,$exclude_codes_array))//排除已经使用的优惠码
	               {
	                   $promotion_codes[$j] = $code;//将生成的新优惠码赋值给promotion_codes数组
	                }else{
	                    $j--;
	                }
	             }else{
	                   $promotion_codes[$j] = $code;//将优惠码赋值给数组
	             }
	          }else{
	              $j--;
	          }
	      }
	      return $promotion_codes;
    }
    /*
     * 通知用户邮件
    */
public function sendAlertEmail($id){
    	$data = $this->getCouponByWhere("cp.id='{$id}'");
    	if($data){
    	$typearr = array('1'=>'型号数量优惠券','2'=>'通用金额优惠券','3'=>'品牌金额优惠券');

     $mess ='</tbody>
      </table><tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$data['uname'].',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您好，IC易站为您提供一张优惠券，请在 <strong style="color:#fd2323;font-family:\'微软雅黑\'; font-size:13px; ">'.date('Y/m/d',$data['start_date']).' 至 '.date('Y/m/d',$data['end_date']).'</strong> 期间使用，谢谢。</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">优惠券信息如下：(仅供参考，具体优惠券信息请进入&nbsp;<a href="http://www.iceasy.com/center/coupon" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>我的优惠券</b></a>&nbsp;查看)</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>';
     if($data['type']==1){
     	$mess .='<!--优惠劵信息-->
    <tr valign="top" >
      <td  ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >  
          <tr >
            <td bgcolor="#f9f9f9" ><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;优惠劵信息&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                  
     			  <tr>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券类型：</td>
                        <td width="250" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$typearr[$data['type']].'</strong></td>
                        <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券号：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6; "><strong style="color:#ff6600;font-family:\'微软雅黑\';">&nbsp;&nbsp;'.$data['code'].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;有效日期：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.date('Y/m/d',$data['start_date']).' 至 '.date('Y/m/d',$data['end_date']).'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;品牌：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$data['bname'].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;产品型号：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#0055aa;font-family:\'微软雅黑\'">'.$data['part_no'].'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;抵扣数量：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$data['buy_number'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';
     }elseif($data['type']==2){
     	$mess .='<!--优惠劵信息-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >  
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;优惠劵信息&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
     			  <tr>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券类型：</td>
                        <td width="250" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$typearr[$data['type']].'</strong></td>
                        <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券号：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#ff6600;font-family:\'微软雅黑\';">'.$data['code'].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;有效日期：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.date('Y/m/d',$data['start_date']).' 至 '.date('Y/m/d',$data['end_date']).'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;抵扣金额：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB </span>'.$data['money_rmb'].' <span style="color:#000000">或者 USD </span>'.$data['money_usd'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';
     }elseif($data['type']==3){
     	$mess .='<!--优惠劵信息-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >  
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;优惠劵信息&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
     			<tr>
                        <td width="100" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券类型：</td>
                        <td width="250" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$typearr[$data['type']].'</strong></td>
                        <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;优惠券号：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#ff6600;font-family:\'微软雅黑\';">'.$data['code'].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;有效日期：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.date('Y/m/d',$data['start_date']).' 至 '.date('Y/m/d',$data['end_date']).'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;品牌：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\'; border-bottom:1px solid #d6d6d6; ">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$data['bname2'].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;">&nbsp;&nbsp;抵扣金额：</td>
                        <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';">&nbsp;&nbsp;<strong style="color:#fd2323;font-family:\'微软雅黑\'"><span style="color:#000000">RMB </span>'.$data['money_rmb'].' <span style="color:#000000">或者 USD </span>'.$data['money_usd'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>';
     }
    	$fromname = 'IC易站';
    	$title    = 'IC易站为您提供一张新优惠券，请及时使用';
    
    	$emailarr = $this->_emailService->getEmailAddress('new_coupon',$data['uid']);
    	$emailto = array($data['email']);
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
    	//更改脚本联系方式和email为销售
    	$staffservice = new Icwebadmin_Service_StaffService();
    	$sellinfo = $staffservice->sellbyuid($data['uid']);
    	return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
    	}
    }
}