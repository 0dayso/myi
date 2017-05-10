<?php
class Zend_View_Helper_PressTypeSelect extends Zend_View_Helper_Abstract  
{
	
	private $_model;
	public function pressTypeSelect($id,$default=null,$html=null) 
	{
		$this->_model = new Icwebadmin_Model_DbTable_NewsType();
		$apps = $this->_model->getAll(0,10,array('id =3'));
		$select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL; 
		$select_str .="<option value=\"\">选择 -- 新闻类型</option>"; 
		foreach($apps as $val)
		{
			$selected = ($val['id']==$default) ? "selected" : "";
			$select_str .="<option value=\"{$val['id']}\"  $selected >".$val['news_type']."</option>";
		}
		$select_str .= "<select>";
		return $select_str;
	}
}