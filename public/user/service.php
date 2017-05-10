<?php
require_once 'Icwebadmin_Service_OainquiryService.php';
$wsdl_url = "feedbackinquiry.wsdl";
ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
$server = new SoapServer($wsdl_url);
$server->setClass('Icwebadmin_Service_OainquiryService');
$server->handle();
