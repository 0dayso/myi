<?php
class Icwebadmin_Service_VendorService {
    private $_model;
    public function __construct() {
        $this->_model = new Icwebadmin_Model_DbTable_Model("vendor");
    }


}

?>