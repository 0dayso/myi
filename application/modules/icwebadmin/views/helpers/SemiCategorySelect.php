<?php
class Zend_View_Helper_SemiCategorySelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function semiCategorySelect($id,$level=1,$default=null,$html=null) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_SemiCategory();
		$apps = $this->_model->getAllByWhere('level='.$level);
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">请选择</option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['id']==$default) ? "selected" : "";
			$select_str .="<option value=\"{$val['id']}\"  $selected >".$val['name']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}