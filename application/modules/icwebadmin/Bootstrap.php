<?php

class Icwebadmin_Bootstrap extends Zend_Application_Module_Bootstrap 
{ 
	protected function _initAppAutoload(){
		
		 $autoloader = new Zend_Application_Module_Autoloader(array(
			'namespace' => 'icwebadmin',
			'basePath' => APPLICATION_PATH . '/modules/icwebadmin'));
		 
	    return $autoloader;
	}
} 

