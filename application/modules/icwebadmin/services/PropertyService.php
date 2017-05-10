<?php
class Icwebadmin_Service_PropertyService
{
	private $_prodmoder;
	private $_propertymoder;
	private $_propertyvaluemoder;
	private $_propertycategorymoder;
	private $_productpropertymoder;
	public function __construct()
	{
		$this->_prodmoder = new Icwebadmin_Model_DbTable_Product();
		$this->_propertymodel = new Icwebadmin_Model_DbTable_Model('property');
		$this->_propertyvaluemoder = new Icwebadmin_Model_DbTable_Model('property_value');
		$this->_propertycategorymoder = new Icwebadmin_Model_DbTable_Model('property_category');
		$this->_productpropertymoder  = new Icwebadmin_Model_DbTable_Model('product_property');
	}
	/**
	 * 获取属性
	 */	
	public function getProperty($where='')
	{
		return $this->_propertymodel->getAllByWhere("id!='' ".$where," displayorder ASC");
	}
	/**
	 * 获取属性值
	 */
	public function getPropertyValue($pid)
	{
		return $this->_propertyvaluemoder->getAllByWhere("property_id = '{$pid}'"," displayorder ASC");
	}
	/**
	 * 获取单个属性值
	 */
	public function getPropertyValueByid($pvid)
	{
		return $this->_propertyvaluemoder->getRowByWhere("id = '{$pvid}'");
	}
	/**
	 * 获取产品所有属性值
	 */
	public function getPropertyByPartid($part_id)
	{
		$sqlstr ="
			SELECT pp.*,pv.property_id FROM product_property as pp 
			LEFT JOIN property_value as pv ON pv.id = pp.value_id
			WHERE pp.part_id='{$part_id}'";
		return $this->_productpropertymoder->getBySql($sqlstr);
	}
	/**
	 * 获取分类与属性
	 */
	public function getPropertyCategoryByCid($cid,$where='')
	{
		$sqlstr ="SELECT distinct(pc.property_id),pc.id,pc.category_id,p.id as pid,p.cname,pc.status
		FROM property_category as pc
		LEFT JOIN property as p ON pc.property_id = p.id
	    WHERE pc.category_id = '{$cid}' {$where}
		ORDER BY pc.displayorder";
		$all_pc_arr = $this->_propertycategorymoder->getBySql($sqlstr);
		if(!$all_pc_arr) return false;
		foreach($all_pc_arr as $k=>$pc_arr){
			$all_pc_arr[$k]['property_value'] = $this->getPropertyValue($pc_arr['pid']);
		}
		return $all_pc_arr;
	}
	/**
	 * 获取分类与属性 byid
	 */
	public function getPropertyCategoryByPcid($pcid)
	{
		$sqlstr ="SELECT pc.*,p.cname
		FROM property_category as pc
		LEFT JOIN property as p ON pc.property_id = p.id
		WHERE pc.id = '{$pcid}'";
		return $this->_propertycategorymoder->QueryRow($sqlstr);
	}
	/**
	 * 选型获取产品数量
	 */
	public function getProdCount($cid,$clevel,$propertycategory)
	{
		if($clevel==3){
			$level_sql = " AND p.part_level3='$cid'";
		}elseif($clevel==2){
			$level_sql = " AND p.part_level2='$cid'";
		}else{
			$level_sql = " AND p.part_level1='$cid'";
		}
		if(!empty($propertycategory)){
			$where = $pc_sql = '';
			$i=0;
			foreach($propertycategory as $pv=>$pv_id){
				if(!$i){
					$where = " value_id = '{$pv_id}'";
					$i=1;
				}else{
					$pc_sql .=" AND pp.part_id IN (SELECT part_id FROM product_property WHERE value_id='$pv_id' AND status=1)";
				}
			}
			$sqlstr ="
			SELECT COUNT(DISTINCT(part_id)) FROM product_property as pp 
			LEFT JOIN product as p ON p.id = pp.part_id
			WHERE pp.status=1 AND pp.status=1 AND p.status=1 AND $where {$pc_sql} {$level_sql}";
		}else{
			$sqlstr ="SELECT COUNT(p.id)
			FROM product as p
			WHERE p.status=1 {$level_sql}";
		}
		return $this->_productpropertymoder->QueryItem($sqlstr);;
	}
	/**
	 * 选型获取产品
	 */
	public function getProd($cid,$clevel,$propertycategory,$offset,$perpage,$orderbystr='')
	{
		if($clevel==3){
			$level_sql = " AND p.part_level3='$cid'";
		}elseif($clevel==2){
			$level_sql = " AND p.part_level2='$cid'";
		}else{
			$level_sql = " AND p.part_level1='$cid'";
		}
		$prod_all = array();
		if($propertycategory){
		   $where = $pc_sql = '';
		   $i=0;
		   foreach($propertycategory as $pv=>$pv_id){
		   	  if(!$i){
		   	  	 $where = " value_id = '{$pv_id}'";
		   	  	 $i=1;
		   	  }else{
		   	     $pc_sql .=" AND pp.part_id IN (SELECT part_id FROM product_property WHERE value_id='$pv_id' AND status=1)";
		   	  }
		   }
		   $sqlstr ="SELECT DISTINCT(pp.part_id),p.* FROM product_property as pp
		   LEFT JOIN product as p ON p.id = part_id
		   WHERE pp.status=1 AND p.status=1 AND $where {$pc_sql} {$level_sql} LIMIT {$offset},{$perpage}";
		}else{
		   $sqlstr ="SELECT p.* FROM product as p
    	   WHERE p.status=1 {$level_sql} LIMIT {$offset},{$perpage}";
		}
		$prod_all = $this->_prodmoder->getBySql($sqlstr);
		//取出属性
		$re_prod_all = array();
		foreach($prod_all as $key=>$part_arr){
			$sql = "SELECT DISTINCT(pp.value_id),pv.property_id,pv.value FROM product_property as pp
					LEFT JOIN property_value as pv ON pv.id = pp.value_id
		            WHERE pv.status=1 AND pp.status=1 AND pp.part_id='{$part_arr['id']}'";
			$prod_all[$key]['property_array']=$this->_prodmoder->getBySql($sql);
		}
		return $prod_all;
	}
}