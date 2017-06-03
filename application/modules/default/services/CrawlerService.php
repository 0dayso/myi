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
    public function getProduct($supId,$keyworld){
		$productArray = array();
		if(empty($keyworld)){
			return $productArray;
		}
		$frontendOptions = array('lifeTime' => 3600*24,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'crawler_product_'.$supId.'_'.md5($keyworld);
		if(!$productArray = $cache->load($cache_key)) {
		    $sqlstr = "SELECT sc.id AS scid,sc.g_excode,scs.excode
		    FROM sx_collection_supplier as scs
		    LEFT JOIN sx_collection as sc ON sc.id=scs.collection_id
		    LEFT JOIN sx_supplier_grab as ssg ON ssg.id=scs.supplier_id
		    WHERE sc.activation = 1 AND ssg.state = 1 AND ssg.id='{$supId}' LIMIT 1";
		    $rateModel = new Default_Model_DbTable_SupplierGrab();
		    $excode = $rateModel->getByOneSql($sqlstr);
		    
		    $code = $excode['g_excode'].$excode['excode'];
		    if($code){
		        eval($code);
		    }
		    if($productArray){
		      $productArray['scid'] = $excode['scid'];
		      $productArray['supid'] = $supId;
		      $cache->save($productArray,$cache_key);
		    }else{
		        $cache->save(1,$cache_key);
		    }
		}
		return $productArray==1?'':$productArray;
		
	}
	/**
	 * 获取e洛盟网站的产品参数
	 * @param unknown $partNo
	 */
	public function getAttributeE14($partNo){
	    $searchurl = 'http://cn.element14.com/webapp/wcs/stores/servlet/Search?categoryName=%E5%85%A8%E9%83%A8&selectedCategoryId=&gs=true&st='.$partNo;
	    $html = $this->getContents($searchurl);
	    $attribute = '';
	    if($html){
	        try{
	            $exphtml = new simple_html_dom();
	            $exphtml->load($html);
	            if($exphtml->find('#sProdList')){
	                $pdurl = $exphtml->find('#sProdList',0)->find('.altRow',0)->find('a',0)->href;
	    
	                if($pdurl){
	                    $pdhtml = $this->getContents($pdurl);
	                    if($pdhtml){
	                        $exppdhtml = new simple_html_dom();
	                        $exppdhtml->load($pdhtml);
	                        if($exppdhtml->find('dl')){
	                            $titleArray = array();
	                            $attribute = '';
	                            foreach($exppdhtml->find('dl',1)->find('dt') as $key=>$element){
	                                $titleArray[$key] = str_replace(":","",trim(strip_tags($element->innertext)));
	                            }
	                            foreach($exppdhtml->find('dl',1)->find('dd') as $key=>$element){
	                                if(!$attribute){
	                                    $attribute = $titleArray[$key].'||'.trim(strip_tags($element->innertext));
	                                }else{
	                                    $attribute .= '[]'.$titleArray[$key].'||'.trim(strip_tags($element->innertext));
	                                }
	                            }
	    
	                        }
	                    }
	                }
	            }
	        } catch (Exception $e) {
	            echo $e->getMessage();
	        }
	    }
	    return $attribute; 
	}
	/**
	 * 获取rs网站的产品参数
	 * @param unknown $partNo
	 */
	public function getAttributeRs($partNo){
	    $attribute = '';
	    if($partNo){
	        $searchurl = 'http://china.rs-online.com/web/c/semiconductors/discrete-semiconductors/igbt-transistors/?searchTerm='.$partNo.'&h=s&sra=oss&redirect-relevancy-data='.rand(1000,9999).'PE26696E3D4'.rand(1000,9999).'4E4B6E6F776E'.rand(1000,9999).'504E266C753D7A68266D6D3D6D61746368616C6C7061727469616C26706D3D5E5B5C772D5C2E2F252C5D2B2426706F3D313326736E3D592673743D4B'.rand(1000,9999).'4F52445F53494E474C455F414C5'.rand(1000,9999).'F4E554D455249432677633D424F5448'.rand(1000,9999).'43D5354474231304E'.rand(1000,9999).'13D5351304E26';
	        $html = $this->getContents($searchurl);
	        if($html){
	            try{
	                $exphtml = new simple_html_dom();
	                $exphtml->load($html);
	                if($exphtml->find('.srtnListTbl')){
	                    $pdurl = $exphtml->find('.srtnListTbl',0)->find('tr',0)->find('a',0)->href;
	                    if($pdurl){
	                        $pdhtml = $this->getContents($pdurl);
	                        if($pdhtml){
	                            $exppdhtml = new simple_html_dom();
	                            $exppdhtml->load($pdhtml);
	                            if($exppdhtml->find('.specificationTable')){
	                                foreach($exppdhtml->find('.specificationTable',0)->find('tr') as $key=>$element){
	                                    $attributeTmp = '';
	                                    foreach($element->find('td') as $k=>$elementTd){
	                                        if($k==1){
	                                            $attributeTmp .= trim(strip_tags($elementTd->innertext));
	                                        }elseif($k==2){
	                                            $attributeTmp .= '||'.trim(strip_tags($elementTd->innertext));
	                                        }
	                                    }
	                                    if($attributeTmp){
	                                        if(!$attribute){
	                                            $attribute = $attributeTmp;
	                                        }else{
	                                            $attribute .= '[]'.$attributeTmp;
	                                        }
	                                    }
	                                }
	                            }
	                        }
	                    }
	                }
	            } catch (Exception $e) {
	            }
	        }
	    }
	    return $attribute;
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