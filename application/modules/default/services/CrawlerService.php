<?php
/**
 * 爬虫类
 * @author Bear
 *
 */
require_once 'Iceaclib/common/simple_html_dom.php';
class Default_Service_CrawlerService
{
	public function __construct()
	{
		
	}
	/**
	 * 获取搜索到的型号
	 */
	public function getProduct($allid,$keyworld){
		$productArrayAll = array();
		if(empty($keyworld)){
			return $productArray;
		}
		$frontendOptions = array('lifeTime' => 3600*24,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$sqlstr = "SELECT sc.id,sc.g_excode
					FROM  sx_collection as sc
					WHERE sc.activation = 1 LIMIT 1";
		$rateModel = new Default_Model_DbTable_SupplierGrab();
		$scre = $rateModel->getByOneSql($sqlstr);
		$cache_key = 'crawler_product_'.$scre['id'].'_'.md5($keyworld);
		if(!$productArrayAll = $cache->load($cache_key)) {
			$isnonull = false;
			if($allid){
				if($scre['g_excode']){
					eval($scre['g_excode']);
				}
				foreach($allid as $supId){
					if($supId>0){
						$productArray = array();
						$sqlstr = "SELECT ssg.id,ssg.name,ssg.img,ssg.delivery_cn,ssg.delivery_hk,sc.g_excode,scs.excode
						FROM sx_collection_supplier as scs
						LEFT JOIN sx_collection as sc ON sc.id=scs.collection_id
						LEFT JOIN sx_supplier_grab as ssg ON ssg.id=scs.supplier_id
						WHERE sc.activation = 1 AND ssg.state = 1 AND ssg.id='{$supId}' LIMIT 1";
			
						$reexcode = $rateModel->getByOneSql($sqlstr);
			
						if($reexcode['excode']){
							eval($reexcode['excode']);
						}
						if(!$isnonull && !empty($productArray)){
							$isnonull = true;
						}
						$productArrayAll['product'][$supId] = $productArray;
						$productArrayAll['sup'][$supId]['id'] = $reexcode['id'];
						$productArrayAll['sup'][$supId]['scid'] = $scre['id'];
						$productArrayAll['sup'][$supId]['name'] = $reexcode['name'];
						$productArrayAll['sup'][$supId]['img'] = $reexcode['img'];
						$productArrayAll['sup'][$supId]['delivery_cn'] = $reexcode['delivery_cn'];
						$productArrayAll['sup'][$supId]['delivery_hk'] = $reexcode['delivery_hk'];
					}
				}
			}
			$cache->save($productArrayAll,$cache_key);
		}else{
			foreach($productArrayAll['product'] as $v){
				if(!empty($v)){
					$isnonull = true;
					break;
				}
			}
		}
		if($isnonull){
			return $productArrayAll;
		}else{
			return [];
		}
	}
	
	/**
	 * 获取内容
	 * @param unknown $url
	 * @return mixed
	 */
	public function getContents($url,$method='GET',$data=array())
	{
		//定义伪造用户浏览器信息HTTP_USER_AGENT
		$binfo =array('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; InfoPath.2; AskTbPTV/5.17.0.25589; Alexa Toolbar)',
				'Mozilla/5.0 (Windows NT 5.1; rv:22.0) Gecko/20100101 Firefox/22.0','Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET4.0C; Alexa Toolbar)',
				'Mozilla/4.0(compatible; MSIE 6.0; Windows NT 5.1; SV1)',
				$_SERVER['HTTP_USER_AGENT']);
		$userinfo = $binfo[mt_rand(0,3)];
		//定义伪造IP来源段
		$cip = '123.125.68.'.mt_rand(0,254);
		$xip = '125.90.88.'.mt_rand(0,254);
		$header = array(
				'CLIENT-IP:'.$cip,
				'X-FORWARDED-FOR:'.$xip,
		);
		$ch = curl_init();
		$timeout = 5;
		
		curl_setopt ($ch, CURLOPT_URL, "$url");
		curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt ($ch, CURLOPT_REFERER, "http://www.baidu.com/");
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_USERAGENT, "$userinfo");
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if ($method == 'GET') {
			curl_setopt($ch, CURLOPT_POST, 0);
		} else  {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		$contents = curl_exec($ch);
		curl_close($ch);
		return $contents;
	}
	public function number($str)
	{
		return preg_replace('/[^\.0123456789]/s', '', $str);
	}
}