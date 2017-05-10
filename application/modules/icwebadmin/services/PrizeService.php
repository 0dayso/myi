<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_PrizeService
{
	private $_prizeModel;
	public function __construct() {
		$this->_prizeModel = new Icwebadmin_Model_DbTable_Model("prize");
	}
	/**
	 * 获取总数
	 */
	public function getPrizeNum($typestr='')
	{
		$sqlstr = "SELECT count(pr.id) as num  FROM prize as pr
		WHERE pr.id!='' $typestr";
		return $this->_prizeModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	*/
	public function getPrize($offset,$perpage,$typestr='')
	{
		$sqlstr ="SELECT pr.* FROM prize as pr
		WHERE pr.id!='' $typestr
		ORDER BY pr.type DESC,pr.awards ASC LIMIT $offset,$perpage";
		return $this->_prizeModel->getBySql($sqlstr);
	}
}