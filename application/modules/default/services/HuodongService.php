<?php
class Default_Service_HuodongService
{
	private $_model;
	public function __construct() {
		$this->_moder = new Default_Model_DbTable_Model('huodong_nxp_ms');
	}
	/**
	 * 获取nxp秒杀型号列表
	 */
	public function getNxpList(){
		$sql ="SELECT nxp.*,p.part_no,p.mpq,p.moq,p.part_img,p.description,b.name as bname 
				FROM huodong_nxp_ms as nxp
                LEFT JOIN product as p ON nxp.nxp_part_id=p.id
				LEFT JOIN brand as b ON p.manufacturer=b.id
    		    WHERE nxp.nxp_status=1";
		return $this->_moder->Query($sql);
	}
	
	/**
	 * 获取nxp产品详细
	 */
	public function getNxpProd($partId){
		$sqlstr ="SELECT nxp.*,po.*,pc1.name as cname1,pc2.name as cname2,pc3.name as cname3,
		br.id as bid,br.name as bname
		FROM huodong_nxp_ms as nxp
        LEFT JOIN product as po ON nxp.nxp_part_id=po.id
		LEFT JOIN prod_category as pc3 ON po.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON po.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON po.part_level1=pc1.id
		LEFT JOIN brand as br ON po.manufacturer=br.id
		WHERE nxp.nxp_part_id = '{$partId}' AND nxp.nxp_status=1";
		return $this->_moder->QueryRow($sqlstr);
	}
	
	/**
	 * 获取收货地址
	 */
	public function getAddress($uid){
		$sqlstr ="SELECT a.id,a.uid,a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,a.default,a.warehousing,
    	p.provinceid,p.province,c.cityid,c.city,e.areaid,e.area
    	FROM address as a LEFT JOIN province as p
        ON a.province=p.provinceid
        LEFT JOIN city as c
        ON a.city=c.cityid
        LEFT JOIN area as e
        ON a.area = e.areaid
    	WHERE a.uid='{$uid}' AND a.status=1 AND a.name!=''
    	ORDER BY `default` DESC";
		return $this->_moder->Query($sqlstr);
	}
	/**
	 * 判断用户是否已经抢购过
	 */
	public function checkBuy($uid){
		$sqlstr ="SELECT so.salesnumber
		FROM sales_order as so WHERE so.uid='{$uid}' AND so.so_type=102 AND so.status IN ('101','201','202','301','302') LIMIT 1";
		$num = $this->_moder->Query($sqlstr);
		if(!empty($num)) return true;
		else return false;
	}
	/**
	 * 判断是否售罄
	 */
	public function checkStock($data){
		if(empty($data)) return true;
		if($data['nxp_stock_cover'] >= $data['nxp_stock']){
			$this->_moder->update(array('nxp_show_dinghuo'=>1), " nxp_id = ".$data['nxp_id']);
			return true;
		}else  return false;
	}
}