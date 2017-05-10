<?php
class Default_Service_CouponService
{
	private $_couponModel;
	public function __construct() {
		$this->_couponModel = new Icwebadmin_Model_DbTable_Model("coupon");
		$this->uidarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
	}
	/**
	 * 更加优惠券获取发放人email
	 */
	public function getEmailByCoup($coups){
		$mail = array();
		if($coups){
		   $couparr = explode(',',$coups);
		   foreach($couparr as $coup){
		   	  if($coup){
		   	  	$sqlstr = "SELECT st.email
		   	  	FROM coupon as cp
		   	  	LEFT JOIN admin_staff as st ON st.staff_id = cp.created_by
		   	  	WHERE cp.code='{$coup}'";
		   	  	$mail[] = $this->_couponModel->QueryItem($sqlstr);
		   	  }
		   }
		}
		return $mail;
	}
	/**
	 * 获取所有优惠券数
	 */
	public function getNum($sql)
	{
		$sqlstr = "SELECT count(cp.id) as num FROM coupon as cp WHERE cp.uid='".$_SESSION['userInfo']['uidSession']."' {$sql}";
		$allnumarr = $this->_couponModel->QueryRow($sqlstr);
		return $allnumarr['num'];
	}
	/**
	 * 获取通过和不通过的申请
	 */
	public function getRecord($offset,$perpage,$sql)
	{
		$orderbystr = "ORDER BY cp.created DESC";
		$sqlstr = "SELECT cp.*,p.manufacturer as brand_id2,p.part_level2,p.part_level3,p.part_no,b.name as bname,b2.name as bname2 FROM coupon as cp
		LEFT JOIN product as p ON cp.part_id = p.id 
		LEFT JOIN brand as b ON p.manufacturer = b.id 
		LEFT JOIN brand as b2 ON cp.brand_id = b2.id
		WHERE cp.uid=:uidtmp {$sql} {$orderbystr} LIMIT $offset,$perpage";
		return $this->_couponModel->getBySql($sqlstr,$this->uidarr);
	}
	/**
	 * 获取用户的所有优惠券
	 */
	public function getCanUsedCoupon()
	{
		$orderbystr = "ORDER BY cp.created DESC";
		$where = " AND cp.status=200 AND cp.start_date<='".time()."' AND cp.end_date >='".time()."'";
		$sqlstr = "SELECT cp.*,u.uname,up.companyname,p.part_no,b.name as bname,b2.name as bname2 FROM coupon as cp
		LEFT JOIN user as u ON cp.uid = u.uid
		LEFT JOIN user_profile as up ON cp.uid = up.uid
		LEFT JOIN product as p ON cp.part_id = p.id 
		LEFT JOIN brand as b ON p.manufacturer = b.id 
		LEFT JOIN brand as b2 ON cp.brand_id = b2.id
		WHERE cp.uid=:uidtmp {$where} {$orderbystr}";
		return $this->_couponModel->getBySql($sqlstr,$this->uidarr);
	}
	/**
	 * 获取用户的所有优惠券
	 */
	public function getCouponByCode($code,$where='')
	{
		$orderbystr = "ORDER BY cp.created DESC";
		$where .= "AND cp.code='{$code}' AND cp.status=200 AND cp.start_date<='".time()."' AND cp.end_date >='".time()."'";
		$sqlstr = "SELECT cp.*,u.uname,up.companyname,p.part_no,b.name as bname,b2.name as bname2 FROM coupon as cp
		LEFT JOIN user as u ON cp.uid = u.uid
		LEFT JOIN user_profile as up ON cp.uid = up.uid
		LEFT JOIN product as p ON cp.part_id = p.id
		LEFT JOIN brand as b ON p.manufacturer = b.id
		LEFT JOIN brand as b2 ON cp.brand_id = b2.id
		WHERE cp.uid=:uidtmp {$where} {$orderbystr}";
		return $this->_couponModel->getRowBySql($sqlstr,$this->uidarr);
	}
	/**
	 * 获取优惠券是否能抵扣
	 */
	public function checkCoupon($coupon,$items,$delivery)
	{
		if(!$items){
			return (array("code"=>100, "message"=>'产品数据错误'));
		}
		if($coupon['type']==1){
			foreach($items as $pord){
				if($pord['pord_id']==$coupon['part_id']){
					$number = $coupon['buy_number']>$pord['qty']?$pord['qty']:$coupon['buy_number'];
					$deductions = $number*$pord['byprice'];
					return (array("code"=>0, "message"=>'此优惠券使用','part_id'=>$coupon['part_id'],'deductions'=>$deductions));
				}
			}
			return (array("code"=>100, "message"=>'此优惠券不可以使用'));
		}elseif($coupon['type']==2){
			$allmoney = 0;
			foreach($items as $pord){
				$allmoney +=$pord['qty']*$pord['byprice'];
			}
			if($allmoney){
				if($delivery=='SZ') $deductions=$coupon['money_rmb'];
				elseif($delivery=='HK') $deductions=$coupon['money_usd'];
				$deductions = $deductions>$allmoney?$allmoney:$deductions;
				return (array("code"=>0, "message"=>'此优惠券使用','part_id'=>$coupon['part_id'],'deductions'=>$deductions));
			}else{
				return (array("code"=>100, "message"=>'此优惠券不可使用'));
			}
		}elseif($coupon['type']==3){
			$this->_productService = new Default_Service_ProductService();
			$allmoney = 0;
			foreach($items as $pord){
				$brandid = $this->_productService->getBrandId($pord['pord_id']);
				if($brandid==$coupon['brand_id']){
					$allmoney +=$pord['qty']*$pord['byprice'];
				}
			}
			if($allmoney){
				if($delivery=='SZ') $deductions=$coupon['money_rmb'];
				elseif($delivery=='HK') $deductions=$coupon['money_usd'];
				$deductions = $deductions>$allmoney?$allmoney:$deductions;
				return (array("code"=>0, "message"=>'此优惠券使用','part_id'=>$coupon['part_id'],'deductions'=>$deductions));
			}else{
				return (array("code"=>100, "message"=>'此优惠券不可使用'));
			}
		}
	}
}