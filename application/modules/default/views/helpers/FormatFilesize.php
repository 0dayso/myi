<?php
class Zend_View_Helper_FormatFilesize extends Zend_View_Helper_Abstract
{
	function formatFilesize($bytes, $precision = 2)
	{
		if($bytes<=0){
			return '';
		}
    	$base = log($bytes) / log(1024);
    	$suffixes = array('', 'KB', 'MB', 'G', 'T');   
   	 	return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
}