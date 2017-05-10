<?php
require_once 'Iceaclib/common/fun.php';
class ProdlistController extends Zend_Controller_Action {
	public function init() {
		$this->fun =$this->view->fun= new MyFun();
	}
	public function indexAction() {
		$url = '';
		if($this->_getParam('subid'))
		{
			$url = '-'.(int)$this->_getParam('subid');
		}
		if($this->_getParam('secid'))
		{
			$url .= '-'.(int)$this->_getParam('secid');
		}
		if($this->_getParam('thiid'))
		{
			$url .= '-'.(int)$this->_getParam('thiid');
		}
		header( "HTTP/1.1 301 Moved Permanently" ) ;
        header("Location:/list{$url}.html");
	}
	/*
	 * 点击品牌查看产品
	 */
	public function brandAction() {
		header( "HTTP/1.1 301 Moved Permanently" );
		header("Location:/brand-".$this->_getParam('bid').".html");
	}
}

