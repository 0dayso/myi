<?php
/**
 * SAP 服务类
 */
class Iceaclib_common_sapservice {
	private $_objSoapClient;
	/**
	 * 构造函数
	 *
	 * @param array
	 */
	public function __construct() {
		$this->_objSoapClient = new SoapClient("http://192.168.0.12/DwWebService/Service1.asmx?WSDL");
	}
	/**
	 * 根据part no 获取数据
	 * 
	 * $sqpservice = new Iceaclib_common_sapservice();
	 * $re = $sqpservice->getByPartno('AP2204R-3.3TRG1');
	 */
	public function getByPartno($partno)
	{
		$param = array('Mantr'=>$partno);
		try{
		   $out = $this->_objSoapClient->__call('GetDataAtsByMatnr', array('parameters' => $param));
		   $atsAll = get_object_vars($out);
		   $atsAllResult = get_object_vars($atsAll['GetDataAtsByMatnrResult']);
		   $allResultXml = new SimpleXMLElement($atsAllResult['any']);
		   return array('partno'=>$partno,
		   		'hk_stock'=>(int)$instance->NORMAL_HK,
		   		'hk_stock'=>(int)$instance->NORMAL_HK,
		   		'sz_stock'=>(int)$instance->NORMAL_SZ);
		} catch (Exception $e) {
		   return array();
		}
	}
}