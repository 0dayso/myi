<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_IceasyserviceController extends Zend_Controller_Action
{
    public function init(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    }
    public function indexAction(){
    	$this->_redirect('/icwebadmin');
    }
    /**
     * OA回复报价
     */
    public function feedbackinquiryAction(){
    	$wsdl_url = "../docs/wsdl/feedbackinquiry.wsdl";
    	ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
    	$server = new SoapServer($wsdl_url);
    	$server->setClass('Icwebadmin_Service_OainquiryService');
    	$server->handle();
    }
}