<?php
class Zend_View_Helper_ProductCategorySelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function productCategorySelect($id,$level=1,$default=null,$html=null) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_ProdCategory();
		$apps = $this->_model->getAllByWhere('level='.$level);
		$select_str = PHP_EOL.'<select class="span2" id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">产品分类 </option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['id']==$default) ? "selected" : "";
			$class = ($level!=1) ? "class=".$val['parent_id'] : "";
			$select_str .="<option value=\"{$val['id']}\"  $selected $class>".$val['name']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}