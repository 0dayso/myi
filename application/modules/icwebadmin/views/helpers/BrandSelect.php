<?php
class Zend_View_Helper_BrandSelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function BrandSelect($id,$default=null,$html=null) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_Brand();
		$apps = $this->_model->getAllByWhere('status=1');
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">选择 -- 品牌</option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['id']==$default) ? "selected" : "";
			$select_str .="<option value=\"{$val['id']}\"  $selected >".$val['name']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}