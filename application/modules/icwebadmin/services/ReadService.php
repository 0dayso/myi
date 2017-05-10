<?php
class Icwebadmin_Service_ReadService
{
	private $_rModer;
	public function __construct()
	{
		$this->_rModer = new Icwebadmin_Model_DbTable_Recommend();
	}
	/**
	 * 获取热销产品
	 */
	public function getHotProd()
	{
		$sqlstr ="SELECT re.id,re.comid,re.status,re.displayorder,pro.part_no,pro.part_img,b.name as brandname
		     FROM recommend as re
    		 LEFT JOIN product as pro ON re.comid=pro.id
			 LEFT JOIN brand as b ON b.id=pro.manufacturer
		     WHERE re.type='hot_prod' AND re.part='home' ORDER BY re.displayorder ASC";
		return $this->_rModer->getBySql($sqlstr, array());
	}
	/**
	 * 获取精品 新品
	 */
	public function getHot()
	{
		$sqlstr ="SELECT re.id,re.cat_id,re.comid,pro.part_no,pro.part_img
		     FROM recommend as re
    		 LEFT JOIN product as pro ON re.comid=pro.id
		     WHERE re.type='hot' AND re.part='home'";
		return $this->_rModer->getBySql($sqlstr, array());
	}
	/**
	 * 根据品牌获取精品 新品
	 */
	public function getHotByBand($bandid)
	{
		$sqlstr ="SELECT re.id,re.cat_id,re.comid,pro.part_no,pro.part_img
		     FROM recommend as re
    		 LEFT JOIN product as pro ON re.comid=pro.id
		     WHERE re.type='hot' AND re.part='home' AND cat_id = {$bandid}";
		return $this->_rModer->getBySql($sqlstr);
	}
	/**
	 * 获取产品分类推荐品牌
	 */
	public function getRbBand($cat_id)
	{
		//热销产品
		$sqlstr ="SELECT re.id,re.cat_id,re.comid
		FROM recommend as re
		WHERE re.type='category_brand' AND re.part='home' AND cat_id = {$cat_id}";
		return $this->_rModer->getBySql($sqlstr);
	}
	/**
	 * 获取应用分类
	 */
	public function getAppById($appid)
	{
		//推荐应用方案
		$sqlstr ="SELECT id,comid FROM recommend  WHERE cat_id='{$appid}' AND type='solution' AND part='home'";
		$arr['solution'] = $this->_rModer->getBySql($sqlstr);
		//应用推荐产品
		$sqlstr ="SELECT id,comid FROM recommend  WHERE cat_id='{$appid}' AND type='app' AND part='home' ORDER BY displayorder";
		$arr['value'] = $this->_rModer->getBySql($sqlstr);
		//应用品牌
		$sqlstr ="SELECT id,comid FROM recommend WHERE cat_id='{$appid}' AND type='brand'  AND part='home' ORDER BY displayorder";
		$arr['brand'] = $this->_rModer->getBySql($sqlstr);
		return $arr;
	}
	
}