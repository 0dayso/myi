<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	private $_config;
	private $_commonconfig;
	private $_seoconfig;
	protected function _initAutoload() {
		//新版
		if(!isset($_SESSION['old_version'])){
		    $_SESSION['new_version'] = 2014;
		}else{
			unset($_SESSION['new_version']);
		}
		$this->_config = new Zend_Config_Ini('../application/configs/config.ini',null, true);
	    Zend_Registry::set('config',$this->_config);//注册 
	
	    $this->_commonconfig = new Zend_Config_Ini('../application/configs/common.ini',null, true);
	    Zend_Registry::set('commonconfig',$this->_commonconfig);//注册


	    $this->_seoconfig = new Zend_Config_Ini('../application/configs/seoconfig.ini',null, true);
	    Zend_Registry::set('seoconfig',$this->_seoconfig);//注册

	    //银行账户
	    define("BANK_COM",$this->_config->bank->bankcom);
	    define("BANK_NAME",$this->_config->bank->bankname);
	    define("BANK_ACCOUNT",$this->_config->bank->bankaccount);
	    define("VAT_NUMBER",$this->_config->bank->vatnumber);
	    
	    define("BANK_HK_COM",$this->_config->bank->bankcom_hk);
	    define("BANK_HK_NAME",$this->_config->bank->bankname_hk);
	    define("BANK_HK_ACCOUNT",$this->_config->bank->bankaccount_hk);
	    define("SWIFT_CODE_HK",$this->_config->bank->swiftcode_hk);
	    define("BANK_ADDRESS_HK",$this->_config->bank->bankaddress_hk);
	    //税率
	    define("RATE",$this->_config->general->rate);
	    //小数位
	    define("DECIMAL",$this->_commonconfig->decimal->point);
	    //单价小数位
	    define("DECIMAL_UNIT",3);
	    //询价订单交期变更窗口时间，单位为天
	    define("WINDOW_DAY",$this->_config->general->window_day);
	    //可补发票申请时间，单位为天
	    define("INVOICE_DAY",$this->_config->general->invoice_day);
	    //自定义一些通用常量
	    //检查大写字母
	    define("ISCAPITAL", "value=value.replace(/[^\\A-Z]/,'')");
	    define("ISLETTER", "value=value.replace(/[^\\a-zA-Z]/,'')");
	    define("ISNUMBER", "value=value.replace(/[^\\d]/g,'')");
	    define("ISFLOAT", "value=value.replace(/[^\\d\\.]/g,'')");
	    define("ISLEADTIME", "value=value.replace(/[^\\d\\-]/g,'')");
	    define("HTTPHOST", 'http://'.$_SERVER['HTTP_HOST']);
	    define("HTTPSHOST", 'https://'.$_SERVER['HTTP_HOST']);
	    //产品图片路径
	    define("PRODUCTICON",'/upload/default/product/icon/');
	    define("PRODUCTICONBIG",'upload/default/product/big/');
	    //品牌路径
	    define("BRANDICON",'/upload/default/brandimg/logo/');
	    define("BRANDICON2",'upload/default/brandimg/logo/');//用于php上传
	    //应用方案图标图片路径
	    define("APPICON",'/upload/default/applications/icon/');
	    //应用方案设计图路径
	    define("SOLUTIONICON",'/upload/default/applications/solve/');
	    //汇款回执单图片路径
	    define("RECEIPT",'/upload/default/order_annex/');
	    //用户上传回执单图片路径
	    define("UP_RECEIPT",'upload/default/order_annex/');
	    //用户上传公司附件路径
	    define("COM_ANNEX",'upload/default/company_annex/');
	    //询价下单后存放报价单文件夹
	    define("ORDER_INQPDF",'upload/default/orderinqpdf/');
	    //纸质合同
	    define("ORDER_PAPER",'upload/default/ordercontract/paper/');
	    //电子合同
	    define("ORDER_ELECTRONIC",'upload/default/ordercontract/electronic/');
	    //PI路径
	    define("ORDER_PI",'upload/default/pi/');
	    //存储缓存路径
	    define("CACHE_PATH",'../docs/tmp/');
	    //新浪微博
	    define( "WB_AKEY" , '381478344' );
        define( "WB_SKEY" , 'c1b0648d09adec0c8b646b3c77201a6d' );
        define( "WB_CALLBACK_URL" , '' );
        //腾讯微博
        define( "QQ_CLIENT_ID" , '801437338' );
        define( "QQ_CLIENT_SECRET" , 'e2e82507741a4d1eec8c2d69db8e5b72' );
        define( "QQ_CALLBACK_URL" , '' );
	}
	
    protected function _initAppAutoload() {
	  $autoloader = new Zend_Application_Module_Autoloader(array(
			'namespace' => 'App',
			'basePath'  => dirname(__FILE__),
	  ));
	  return $autoloader;
    }
	
	//加载不同的Layout
	protected function _initLayoutHelper(){
		$this->bootstrap('frontController');
		$layout = Zend_Controller_Action_HelperBroker::addHelper( new Rockux_Controller_Action_Helper_LayoutLoader());
	}
	protected  function _initRouter()
	{
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		if($_SERVER['REQUEST_URI'])
		{
			$urlarray  = explode("/",$_SERVER['REQUEST_URI']);
			//跳出框url
			define("ALERTURLHOST", 'http://'.$_SERVER['HTTP_HOST']);
			//如果user 转https
			/*if($urlarray[1] && $urlarray[1]=="user")
			{
				//http转化为https
				if ($_SERVER["HTTPS"]<>"on")
				{
					header("Location: ".HTTPSHOST.$_SERVER["REQUEST_URI"]);
				}
			}else{
				if ($_SERVER["HTTPS"]=="on")
				{
					header("Location: ".HTTPHOST.$_SERVER["REQUEST_URI"]);
				}
			}*/
			
			if($urlarray[1] && $urlarray[1]!="icwebadmin")
			{
				//如果前台加入过滤
				require_once 'Iceaclib/common/360_safe3.php';
				
				//活动专栏路由
				//创建一个路由器实例
				if(isset($urlarray[3]) && $urlarray[3]){
				   $route = new Zend_Controller_Router_Route($urlarray[1].'/:id/:page',
						array(
								'controller' => $urlarray[1],
								'action'     => 'view'),
						array('id' => '\d+','page'=>'[a-zA-Z-_0-9]+'));
				}else{
					$route = new Zend_Controller_Router_Route($urlarray[1].'/:id',
							array(
									'controller' => $urlarray[1],
									'action'     => 'view'),
							array('id' => '\d+'));
				}
				$router->addRoute($urlarray[1], $route);
			}
			//产品详细页
			$item = explode("_",$urlarray[1]);
			//去掉？号
			if(strrpos($item[0],'?')>0) $item[0] = substr($item[0],0,strrpos($item[0],'?'));
			
			if($item[0]=='item'){
				$route = new Zend_Controller_Router_Route(
						'/:item',
						array(
								'controller' => 'proddetails',
								'action'     => 'index'
						),
						array('item'=>'item|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}    
			//分类列表页
			$item = explode("-",$urlarray[1]);
		    if($item[0]=='list'){
				$route = new Zend_Controller_Router_Route(
						'/:list',
						array(
								'controller' => 'proddetails',
								'action'     => 'list'
						),
						array('list'=>'list|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//品牌
			
			if($item[0]=='brand' && isset($item[1])){
				$route = new Zend_Controller_Router_Route(
						'/:brand',
						array(
								'controller' => 'proddetails',
								'action'     => 'brand'
						),
						array('brand'=>'brand|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			if($item[0]=='pl'){
				$route = new Zend_Controller_Router_Route(
						'/:pl/:brandname',
						array(
								'controller' => 'proddetails',
								'action'     => 'brand'
						)
				);
				$router->addRoute('/', $route);
			}
			
			//应用方案列表
			if($item[0]=='solutionlist'){
				$route = new Zend_Controller_Router_Route(
						'/:solutionlist',
						array(
								'controller' => 'applications',
								'action'     => 'index'
						),
						array('solutionlist'=>'solutionlist|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//应用方案
			if($item[0]=='solution'&& $item[1]==null){
				$route = new Zend_Controller_Router_Route(
						'/:solution',
						array(
								'controller' => 'applications',
								'action'     => 'home'
						)
				);
				$router->addRoute('/', $route);
			}
			if($item[0]=='solution' && $item[1]!=null){
				$route = new Zend_Controller_Router_Route(
						'/:solution',
						array(
								'controller' => 'applications',
								'action'     => 'details'
						),
						array('solution'=>'solution|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			
			//代码库
			$route = new Zend_Controller_Router_Route_Regex(
					'codelist-(\d+).html',
					array(
							'controller' => 'code',
							'action'     => 'index'
					),
					array(
							1 => 'app_level1',
					),
					'codelist-%d.html'
			);
			$router->addRoute('codelist', $route);
			$route = new Zend_Controller_Router_Route_Regex(
					'code-(\d+).html',
					array(
							'controller' => 'code',
							'action'     => 'view'
					),
					array(
							1 => 'id',
					),
					'code-%d.html'
			);
			$router->addRoute('code', $route);
			
			//研讨会列表 
			if($item[0]=='seminarlist'){
				$route = new Zend_Controller_Router_Route(
						'/:seminarlist',
						array(
								'controller' => 'seminar',
								'action'     => 'index'
						),
						array('seminarlist'=>'seminarlist|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			if($item[0]=='webinarlist'){
				$route = new Zend_Controller_Router_Route(
						'/:webinarlist',
						array(
								'controller' => 'seminar',
								'action'     => 'index'
						),
						array('webinarlist'=>'webinarlist|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//研讨会
			if($item[0]=='webinar'){
				$route = new Zend_Controller_Router_Route(
						'/:webinar',
						array(
								'controller' => 'seminar',
								'action'     => 'details'
						),
						array('webinar'=>'webinar|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//视频研讨会
			if($item[0]=='webinarvideo'){
				$route = new Zend_Controller_Router_Route(
						'/:webinarvideo',
						array(
								'controller' => 'seminar',
								'action'     => 'onlinedetails'
						),
						array('webinarvideo'=>'webinarvideo|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//样片申请详细页
			if($item[0]=='samples' && $item[1]){
				$route = new Zend_Controller_Router_Route(
						'/:samples',
						array(
								'controller' => 'samples',
								'action'     => 'view'
						),
						array('samples'=>'samples|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			//积分商城
			if($item[0]=='jifen' && $item[1]){
				$route = new Zend_Controller_Router_Route(
						'/:jifen',
						array(
								'controller' => 'jifen',
								'action'     => 'view'
						),
						array('jifen'=>'jifen|[\-]+[0-9]+')
				);
				$router->addRoute('/', $route);
			}
			
			$route = new Zend_Controller_Router_Route_Regex(
					'news-(\d+).html',
					array(
							'controller' => 'news',
							'action'     => 'view'
					),
					array(
							1 => 'id',
					),
					'news-%d.html'
			);
			$router->addRoute('news', $route);
			$route = new Zend_Controller_Router_Route_Regex(
					'newslist-(\d+).html',
					array(
							'controller' => 'news',
							'action'     => 'list'
					),
					array(
							1 => 'app_level1',
					),
					'newslist-%d.html'
			);
			$router->addRoute('newslist', $route);	

			$route = new Zend_Controller_Router_Route_Regex(
					'marketlist-(\d+).html',
					array(
							'controller' => 'market',
							'action'     => 'list'
					),
					array(
							1 => 'app_level1',
					),
					'marketlist-%d.html'
			);
			$router->addRoute('marketlist', $route);
			
		}
	}
	protected function _initViewHelpers()
	{
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		$view->doctype('XHTML1_STRICT');
		$view->headMeta()->appendHttpEquiv('Content-Type','text/html;charset=utf-8');
		$view->headTitle()->setSeparator('-');
		$view->headTitle($this->_seoconfig->general->default_title);
		$view->headMeta()->appendName('description',$this->_seoconfig->general->default_description);
		$view->headMeta()->appendName('keywords',$this->_seoconfig->general->default_keywords);
		
		$view->siteTitle = $this->_commonconfig->common->site_title;
		$view->PIC = $this->_commonconfig->common->PIC;
		$view->company = $this->_commonconfig->common->company;
		$view->companyUrl = $this->_commonconfig->common->company_url;
		$view->hotTel = $this->_commonconfig->common->hot_tel;
		
	}
	protected function _initTranslate ()
	{
		$options = $this->getOption('resources');
		$options = $options['translate'];
		if (! isset($options['data'])) {
			throw new Zend_Application_Resource_Exception('对不起,没有找到语言文件！');
		}
		$adapter = isset($options['adapter']) ? $options['adapter'] : Zend_Translate::AN_ARRAY;
		$session_language = new Zend_Session_Namespace('language');
		$locale = '';
		if(substr($_SERVER['REQUEST_URI'],1,10)=='icwebadmin'){
		   if ($session_language->icwebadmin) {
			   $icwebadmin_locale = $session_language->icwebadmin;
		   } else {
			   $session_language->icwebadmin = $icwebadmin_locale =isset($options['locale']) ? $options['locale'] :null;
		   }
		   $data = '';
		   if (isset($options['data']['icwebadmin'][$icwebadmin_locale])) {
			   $data = $options['data']['icwebadmin'][$icwebadmin_locale];
		   }
		   $locale = $icwebadmin_locale;
		}else{
			if ($session_language->default) {
				$default_locale = $session_language->default;
			} else {
				$session_language->default = $default_locale =isset($options['locale']) ? $options['locale'] :null;
			}
			$data = '';
			if (isset($options['data']['default'][$default_locale])) {
				$data = $options['data']['default'][$default_locale];
			}
			$locale = $default_locale;
		}
		$translateOptions =isset($options['options']) ? $options['options'] :array();
		$translate =new Zend_Translate($adapter, $data, $locale,$translateOptions);
		Zend_Form::setDefaultTranslator($translate);
		Zend_Registry::set('Zend_Translate', $translate);
		return $translate;
	}
}

