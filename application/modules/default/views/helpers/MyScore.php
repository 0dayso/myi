<?php
class Zend_View_Helper_MyScore extends Zend_View_Helper_Abstract
{
	function MyScore()
	{
		$myscore = 0;
		$this->_jifenService = new Default_Service_JifenService();
	    $myscore = $this->_jifenService->getSurplusScore();
	    return $myscore?$myscore:0;
	}
}