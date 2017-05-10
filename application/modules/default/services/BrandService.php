<?php
class Default_Service_BrandService
{
	private $_brandModer;

	public function __construct()
	{
		$this->_brnadModer = new Default_Model_DbTable_Model('brand');
		
		$this->fun = new MyFun();
	}
	/*
	 * 获取所有品牌
	 */
	public function getAllBrand(){
		return $this->_brnadModer->getAllByWhere("status=1");
	}
	/*
	 * 获取代理线品牌
	 */
	public function getProxyBrand(){
		return $this->_brnadModer->getAllByWhere("status= 1 and type=1");
	}
	/*
	 * 按照首
	 */
	public function firstcharBrand($arr){
		$re = array('A'=>array(),'B'=>array(),'C'=>array(),'D'=>array(),'E'=>array(),
				'F'=>array(),'G'=>array(),'H'=>array(),'I'=>array(),'J'=>array(),'K'=>array(),
				'L'=>array(),'M'=>array(),'N'=>array(),'O'=>array(),'P'=>array(),'Q'=>array(),
				'R'=>array(),'S'=>array(),'T'=>array(),'U'=>array(),'V'=>array(),'W'=>array(),
				'X'=>array(),'Y'=>array(),'Z'=>array(),'其它'=>array());
		foreach($arr as $v){
			$fc = $this->fun->getfirstchar($v['name']);
			if($fc) $re[$fc][] = $v;
			else $re['其它'][] = $v;
		}
		return $re;
	}
	/**
	 * 获取所有品牌按字母
	 */
	public function getAllFcBrand(){
		$arr = $this->getAllBrand();
		$re  = $this->firstcharBrand($arr);
		ksort($re);
		return $re;
	}
	/**
	 * 获取所有品牌按字母
	 */
	public function getProxyFcBrand(){
		$arr = $this->getProxyBrand();
		$re  = $this->firstcharBrand($arr);
		ksort($re);
		return $re;
	}
}