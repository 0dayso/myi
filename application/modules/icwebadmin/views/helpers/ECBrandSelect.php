<?php
class Zend_View_Helper_ECBrandSelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function ECBrandSelect($id,$default=null,$html=null,$extra) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_ProductEc();
		$where =  ($extra == -1) ? ' where part_id =-1' : '';
		$sql = "SELECT  oa_mfr  as brand FROM `ec_inventory`  {$where} group by oa_mfr";
		$brands = $this->_model->getBySql($sql);
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">EC - 产品线</option>"; 
		foreach($brands as $val)
		{
			$selected = ($val['brand']==$default) ? "selected" : "";
			$select_str .="<option value=\"{$val['brand']}\"  $selected >".$val['brand']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}