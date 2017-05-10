<?php

class Default_Bootstrap extends Zend_Application_Module_Bootstrap 
{
    protected function _initAppAutoload() {
    	
	    $autoloader = new Zend_Application_Module_Autoloader(array(
			'namespace' => 'default',
			'basePath' => APPLICATION_PATH . '/modules/default'));
		
	    return $autoloader;
    }
}