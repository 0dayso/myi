<?php
class Zend_View_Helper_VendorSelect extends Zend_View_Helper_Abstract
{

    private $_model;
    public function VendorSelect($id,$default=null,$html=null)
    {
        $this->_model = new Icwebadmin_Model_DbTable_Vendor();
        $apps = $this->_model->getAllByWhere('1=1');
        $select_str = PHP_EOL.'<select id="'.$id.'" name="'.$id.'" '.$html.'>'.PHP_EOL;
        $select_str .="<option value=\"\">选择 -- 供应商</option>";
        foreach($apps as $val)
        {
            $selected = ($val['vendor_id']==$default) ? "selected" : "";
            $select_str .="<option value=\"{$val['vendor_id']}\"  $selected >".$val['vendor_name']."</option>";
        }
        $select_str .= "<select>";
        return $select_str;
    }
}