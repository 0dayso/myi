<?php
class Zend_View_Helper_ATSBrandSelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function ATSBrandSelect($id,$default=null,$html=null) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_ProductAts();
		$sql = "select wgbez as brand from product_ats group by wgbez";
		$apps = $this->_model->getBySql($sql);
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">ATS - 产品线</option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['brand']==$default) ? "selected" : "";
			$select_str .="<option value=\"{$val['brand']}\"  $selected >".$val['brand']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}