<?php
class SapserviceController extends Zend_Controller_Action {
	public function init() {
		/*
		 * Initialize action controller here
		 */
		
	}
	public function indexAction() {
		$this->_redirect("/");
	}
	/**
	 * 获取需要更新的产品库存，存在阶梯价的产品
	 */
	public function needupdateAction(){

	$soap = new SoapServer(null,array('uri'=>"http://192.168.36.244:8080/"));//This uri is your SERVER ip.
    $soap->addFunction('minus_func');                                                 //Register the function
    $soap->addFunction(SOAP_FUNCTIONS_ALL);
     $soap->handle();

	}
	
	/**
	 * 从sap获取到库存，更新盛芯电子库存
	 */
	public function updatestockAction() {
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
	   try {
       $client = new SoapClient(null,
        array('location' =>"http://192.168.36.244:8080/sapservice/needupdate",'uri' => "http://127.0.0.1/")
        );
       echo $client->minus_func(110,99);
      } catch (SoapFault $fault){
       echo "Error: ",$fault->faultcode,", string: ",$fault->faultstring;
        }
	}
}
function minus_func($i, $j){
	$res = $i - $j;
	return $res;
}

