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
		if($supId==3){
			$productArray = $this->getIchuntFuture($keyworld);
		}elseif($supId==4){
			$productArray = $this->getIchuntMouser($keyworld);
		}elseif($supId==5){
			$productArray = $this->getIchuntDigiKey($keyworld);
		}elseif($supId==6){
			$productArray = $this->getIchuntElement14($keyworld);
		}elseif($supId==7){
			$productArray = $this->getIchuntVerical($keyworld);
		}elseif($supId==8){
			$productArray = $this->getIchuntRs($keyworld);
		}elseif($supId==9){
			$productArray = $this->getIchuntAvnet($keyworld);
		}elseif($supId==10){
			$productArray = $this->getIchuntChipOneStop($keyworld);
		}
		return $productArray;
	}
	/*************************************www.ichunt.com*****************************/
	public function getIchuntFuture($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=14";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntMouser($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=12";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntDigiKey($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=15";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntElement14($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=13";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntVerical($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=17";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntRs($keyworld){
		$url = "http://www.ichunt.com/GarbPart/fchips.php";
		$arr = array('keywords'=>$keyworld,'fchipsAreaType'=>2);
		$html = $this->getContents($url,'POST',$arr);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			foreach($exphtml->find('.search_line2') as $element){
				 $newprod = [];
				 $newprod['pratNo'] = $element->find('.fc_font',0)->innertext;
				 
				 $kc = $element->find('.w80',0)->innertext;
				 $arr = explode("</p>", $kc);
				 $newprod['moq'] = $arr[1];
				 $newprod['brand'] = '';
				 $newprod['stock'] = $arr[0];
				 
				 $newprod['bookprice'] = [];
				 $element->find('.a-max-height',0)->innertext = '';
				 $pi = $element->find('.w250',0)->innertext;
				 $piarr = explode("</p>", $pi);
				 for($i=0;$i<count($piarr);$i+=3){
				 	$newprice = [];
				 	if(isset($piarr[$i])){
				 		$newprice['moq'] = $this->number($piarr[$i]);
				 	}
				 	if(isset($piarr[$i+1])){
				 		$newprice['rmbprice'] = $this->number($piarr[$i+1]);
				 	}
				 	if(isset($piarr[$i+2])){
				 		$newprice['usdprice'] = $this->number($piarr[$i+2]);
				 	}
				 	if($newprice['moq']){
						$newprod['bookprice'][] = $newprice;
					}
				 }
				 $re[]=$newprod;
			}
		}
		return $re;
	}
	public function getIchuntAvnet($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=19";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && !is_array($array['result'])){
				$exphtml = new simple_html_dom();
				// 从字符串中加载
				$exphtml->load($array['result']);
				foreach($exphtml->find('.search_line2') as $element){
					$newprod = [];
					$newprod['pratNo'] = $element->find('.fc_font',0)->innertext;
						
					$kc = $element->find('.w80',0)->innertext;
					$arr = explode("</p>", $kc);
					$newprod['moq'] = $arr[1];
					$newprod['brand'] = '';
					$newprod['stock'] = $arr[0];
						
					$newprod['bookprice'] = [];
					$element->find('.a-max-height',0)->innertext = '';
					$pi = $element->find('.w250',0)->innertext;
					$piarr = explode("</p>", $pi);
					for($i=0;$i<count($piarr);$i+=3){
						$newprice = [];
						if(isset($piarr[$i])){
							$newprice['moq'] = $this->number($piarr[$i]);
						}
						if(isset($piarr[$i+1])){
							$newprice['rmbprice'] = $this->number($piarr[$i+1]);
						}
						if(isset($piarr[$i+2])){
							$newprice['usdprice'] = $this->number($piarr[$i+2]);
						}
						if($newprice['moq']){
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIchuntChipOneStop($keyworld){
		$url = "http://www.ichunt.com/GarbPart/SecChip1Stop.php?stockNum=0&part=".$keyworld."&flag=12";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$array = json_decode($html,true);
			if(is_array($array) && $array['result']){
				foreach($array['result'] as $pv){
					$newprod = [];
					$newprod['pratNo'] = $pv['newpart']?$pv['newpart']:$pv['part'];
					$newprod['moq'] = $pv['moq'];
					$newprod['brand'] = $pv['brand'];
					$newprod['stock'] = $pv['stock'];
					$newprod['bookprice'] = [];
					if($pv['price']){
						foreach($pv['price'] as $priceV){
							$newprice = [];
							$newprice['moq']  = $priceV['rmbmoq']?$priceV['rmbmoq']:$priceV['usdmoq'];
							$newprice['rmbprice'] = $priceV['rmbprice'];
							$newprice['usdprice'] = $priceV['usdprice'];
							$newprod['bookprice'][] = $newprice;
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	/*********************************end www.ichunt.com*****************************/
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