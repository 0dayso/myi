<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_FavoritesService
{
	public function __construct() {
		$this->favModel = new Default_Model_DbTable_Favorites();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
	}
	/*
	 * 收藏夹记录数
	 */
	public function getTotal()
	{
		$sqlstr = "SELECT count(id) as allnum FROM favorites WHERE status=1  AND uid=:uidtmp";
		$allnumarr = $this->favModel->getBySql($sqlstr,$this->sqlarr);
		return $allnumarr[0]['allnum'];
	}
	/*
	 * 获取记录
	 */
	public function getRecord($offset,$perpage)
	{
		$sqlstr = "SELECT f.id as fid,b.name as bname,pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.break_price,
			    pro.part_level1,pro.part_level2,pro.part_level3,pro.moq,pro.mpq,pro.noinquiry,
				pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,pro.bpp_stock,pro.bpp_cover,
                pro.mpq,pro.moq,pro.can_sell,pro.surplus_stock_sell,pro.special_break_prices,pro.show_price,
	            pro.price_valid,pro.price_valid_rmb,pro.description
		FROM favorites as f 
		LEFT JOIN product as pro ON f.prod_id=pro.id
        LEFT JOIN brand as b ON pro.manufacturer=b.id
		WHERE f.status=1 AND pro.status = 1 AND f.uid=:uidtmp ORDER BY f.created DESC LIMIT $offset,$perpage";
		$soArr = $this->favModel->getBySql($sqlstr,$this->sqlarr);
		return $soArr;
	}
	/*
	 * 更新询价数量
	 */
	public function updateInquiry($fid)
	{
		$sqlstr = "UPDATE favorites SET inquiry = inquiry+1 WHERE id='{$fid}' AND status=1  AND uid=:uidtmp";
		$this->favModel->updateBySql($sqlstr,$this->sqlarr);
		return true;
	}
	/*
	 * 更新购买数量
	*/
	public function updateCart($fid)
	{
		$sqlstr = "UPDATE favorites SET cart =cart+1 WHERE id='{$fid}' AND status=1  AND uid=:uidtmp";
		$this->favModel->updateBySql($sqlstr,$this->sqlarr);
		return true;
	}
	/*
	 * 删除
	*/
	public function deleteFov($fid)
	{
		$sqlstr = "UPDATE favorites SET status=0 WHERE id='{$fid}' AND status=1  AND uid=:uidtmp";
		$this->favModel->updateBySql($sqlstr,$this->sqlarr);
		return true;
	}
	/*
	 * 一个用户最多添加1000个记录
	 */
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM favorites WHERE uid=:uidtmp AND status=1";
		$all = $this->favModel->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
}