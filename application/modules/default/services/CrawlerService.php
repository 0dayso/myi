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
		$searchEngine = 'iclego';
		if($searchEngine=='ichunt'){
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
		}elseif($searchEngine=='icgoo'){
			if($supId==2){
				$productArray = $this->getIcgooArrow($keyworld);
 			}elseif($supId==3){
				$productArray = $this->getIcgooFuture($keyworld);
			}elseif($supId==4){
				$productArray = $this->getIcgooMouser($keyworld);
			}elseif($supId==5){
				$productArray = $this->getIcgooDigiKey($keyworld);
			}elseif($supId==6){
				$productArray = $this->getIcgooElement14($keyworld);
			}elseif($supId==7){
				$productArray = $this->getIcgooVerical($keyworld);
			}elseif($supId==8){
				$productArray = $this->getIcgooRs($keyworld);
			}elseif($supId==9){
				$productArray = $this->getIcgooAvnet($keyworld);
			}elseif($supId==10){
				$productArray = $this->getIcgooChipOneStop($keyworld);
			}
		}elseif($searchEngine=='iclego'){
			$url = "http://user.iclego.com/search_api.php?keyword=".$keyworld."&view=gys&first=1";
			$html = $this->getContents($url);
			if($supId==2){
				$productArray = $this->getIclegoArrow($html);
 			}elseif($supId==3){
				$productArray = $this->getIclegoFuture($html);
			}elseif($supId==4){
				$productArray = $this->getIclegoMouser($html);
			}elseif($supId==5){
				$productArray = $this->getIclegoDigiKey($html);
			}elseif($supId==6){
				$productArray = $this->getIclegoElement14($html);
			}elseif($supId==7){
				$productArray = $this->getIclegoVerical($html);
			}elseif($supId==8){
				$productArray = $this->getIclegoRs($html);
			}elseif($supId==9){
				$productArray = $this->getIclegoAvnet($html);
			}elseif($supId==10){
				$productArray = $this->getIclegoChipOneStop($html);
			}
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
				 $newprod['brand'] = $element->find('.w150',1)->find('span',0)->innertext;
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
	
	
	/*************************************www.icgoo.com*****************************/
	public function getIcgooArrow($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=arrow&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooFuture($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=future&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooMouser($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=mouser&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooDigiKey($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=digikey&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooElement14($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=element14&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooVerical($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=verical&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooRs($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=rs&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooAvnet($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=avnet&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	public function getIcgooChipOneStop($keyworld){
		$url = "http://www.icgoo.net/search/getdata/?sup=chip1stop&partno=".$keyworld."&qty=1";
		$html = $this->getContents($url);
		$re = [];
		if($html){
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			foreach($exphtml->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->find('.rc',0)->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==2){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $elementLi){
								$newprice = array();
								$newprice['moq'] = $this->number($elementLi->find('span',0)->innertext);
								$newprice['usdprice'] = $this->number($elementLi->find('span',1)->innertext);
								$newprice['rmbprice'] = $this->number($elementLi->find('span',2)->innertext);
								$newprod['bookprice'][] = $newprice;
							}
						}elseif($k==6){
							$newprod['moq'] = $this->number($elementTd->find('p',0)->innertext);
						}
					}
					$re[]=$newprod;
				}
			}
		}
		return $re;
	}
	/*********************************end www.icgoo.com*****************************/
	
	/*************************************www.iclego.com*****************************/
	public function getIclegoArrow($html){
		$re = [];
		if($html){
			try {
				$exphtml = new simple_html_dom();
				// 从字符串中加载
				$exphtml->load($html);
				$re = array();
				if(!$exphtml->find('div[gys=6]',0)){
					return $re;
				}
				foreach($exphtml->find('div[gys=6]',0)->find('table',0)->find('tr') as $key=>$element){
					if($key>0){
						$newprod = array();
						$newprod['bookprice'] = array();
						$newprice = array();
						foreach($element->find('td') as $k=>$elementTd){
							if($k==0){
								$newprod['pratNo'] = $elementTd->innertext;
							}elseif($k==1){
								$newprod['brand'] = $elementTd->innertext;
							}elseif($k==3){
								$newprod['stock'] = $this->number($elementTd->innertext);
							}elseif($k==4){
								foreach($elementTd->find('li') as $item=>$elementLi){
									$newprice[$item]['moq'] = $this->number($elementLi->innertext);
								}
							}elseif($k==5){
								foreach($elementTd->find('li') as $item=>$elementLi){
									$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
								}
							}elseif($k==6){
								foreach($elementTd->find('div') as $item=>$elementLi){
									$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
								}
							}elseif($k==8){
								if(isset($newprice[0]['moq'])){
									$newprod['moq'] = $newprice[0]['moq'];
								}else{
									$newprod['moq'] = $newprod['stock'];
								}
							}
						}
						$newprod['bookprice'] = $newprice;
						$re[]=$newprod;
					}
				}
			} catch (Exception $e) {
				
			}
		}
		return $re;
	}
	public function getIclegoFuture($html){
		$re = [];
		if($html){
			try {
				$exphtml = new simple_html_dom();
				// 从字符串中加载
				$exphtml->load($html);
				$re = array();
				if(!$exphtml->find('div[gys=7]',0)){
					return $re;
				}
				foreach($exphtml->find('div[gys=7]',0)->find('table',0)->find('tr') as $key=>$element){
					if($key>0){
						$newprod = array();
						$newprod['bookprice'] = array();
						$newprice = array();
						foreach($element->find('td') as $k=>$elementTd){
							if($k==0){
								$newprod['pratNo'] = $elementTd->innertext;
							}elseif($k==1){
								$newprod['brand'] = $elementTd->innertext;
							}elseif($k==3){
								$newprod['stock'] = $this->number($elementTd->innertext);
							}elseif($k==4){
								foreach($elementTd->find('li') as $item=>$elementLi){
									$newprice[$item]['moq'] = $this->number($elementLi->innertext);
								}
							}elseif($k==5){
								foreach($elementTd->find('li') as $item=>$elementLi){
									$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
								}
							}elseif($k==6){
								foreach($elementTd->find('div') as $item=>$elementLi){
									$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
								}
							}elseif($k==8){
								if(isset($newprice[0]['moq'])){
									$newprod['moq'] = $newprice[0]['moq'];
								}else{
									$newprod['moq'] = $newprod['stock'];
								}
							}
						}
						$newprod['bookprice'] = $newprice;
						$re[]=$newprod;
					}
				}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoMouser($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=13]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=13]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoDigiKey($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=8]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=8]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoElement14($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=252]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=252]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoVerical($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=70]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=70]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoRs($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=69]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=69]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
				
			}
		}
		return $re;
	}
	public function getIclegoAvnet($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=152]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=152]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	public function getIclegoChipOneStop($html){
		$re = [];
		if($html){
			try{
			$exphtml = new simple_html_dom();
			// 从字符串中加载
			$exphtml->load($html);
			$re = array();
			if(!$exphtml->find('div[gys=81]',0)){
				return $re;
			}
			foreach($exphtml->find('div[gys=81]',0)->find('table',0)->find('tr') as $key=>$element){
				if($key>0){
					$newprod = array();
					$newprod['bookprice'] = array();
					$newprice = array();
					foreach($element->find('td') as $k=>$elementTd){
						if($k==0){
							$newprod['pratNo'] = $elementTd->innertext;
						}elseif($k==1){
							$newprod['brand'] = $elementTd->innertext;
						}elseif($k==3){
							$newprod['stock'] = $this->number($elementTd->innertext);
						}elseif($k==4){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['moq'] = $this->number($elementLi->innertext);
							}
						}elseif($k==5){
							foreach($elementTd->find('li') as $item=>$elementLi){
								$newprice[$item]['usdprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==6){
							foreach($elementTd->find('div') as $item=>$elementLi){
								$newprice[$item]['rmbprice'] = $this->number($elementLi->innertext);
							}
						}elseif($k==8){
							if(isset($newprice[0]['moq'])){
								$newprod['moq'] = $newprice[0]['moq'];
							}else{
								$newprod['moq'] = $newprod['stock'];
							}
						}
					}
					$newprod['bookprice'] = $newprice;
					$re[]=$newprod;
				}
			}
			} catch (Exception $e) {
			
			}
		}
		return $re;
	}
	/*********************************end www.iclego.com*****************************/
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