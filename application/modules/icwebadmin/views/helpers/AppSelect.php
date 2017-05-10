<?php
class Zend_View_Helper_AppSelect extends Zend_View_Helper_Abstract  
{
	
	private $_appModel;
	public function appSelect($id,$level=1,$default=null,$html=null) 
	{
		$this->_appModel = new Icwebadmin_Model_DbTable_AppCategory();
		$apps = $this->_appModel->getAllByWhere('status =1 and level='.$level,'displayorder');
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">请选择 -- 应用分类</option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['id']==$default) ? "selected" : "";
			$class = ($level==2) ? "class=".$val['parent_id'] : "";
			$select_str .="<option value=\"{$val['id']}\"  $selected $class >".$val['name']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}