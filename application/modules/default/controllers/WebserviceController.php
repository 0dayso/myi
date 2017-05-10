<?php

class WebserviceController  extends Zend_Controller_Action{
	
	public function init(){
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
	}
	public function indexAction(){
		$this->_redirect('/');
	}
	/**
	 * OA回复报价
	 */
	public function serviceAction(){
		$wsdl_url = "../docs/wsdl/userservice.wsdl";
		ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
		$server = new SoapServer($wsdl_url);
		$server->setClass('Default_Service_UserService');
		$server->handle();
	}
}

?>