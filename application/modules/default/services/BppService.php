<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_BppService
{

	public function __construct() {
		$this->_bppModel = new Icwebadmin_Model_DbTable_Model("bpp_stock");
	}
	/**
	 * 获取bpp列表
	 */
	 private function getBppListByPartId($partId,$where=''){
		return $this->_bppModel->Query("SELECT bs.*,v.vendor_name,blf.lead_time_hk,blf.lead_time_cn FROM `bpp_stock` as bs
				 LEFT JOIN vendor as v ON bs.vendor_id=v.vendor_id
				 LEFT JOIN bpp_location_freight as blf ON (blf.vendor_id = bs.vendor_id AND blf.location_code=bs.location_code)
				 WHERE bs.`part_id` = '{$partId}' $where");
	}
	/**
	 * 获取最优售价
	 */
	public function getBestPrice($partId){
		$pr = $this->getBppListByPartId($partId,"AND bs.duplicated='N' AND (bs.stock - bs.bpp_stock_cover)>0 AND (bs.stock - bs.bpp_stock_cover)>=bs.moq  AND bs.moq>0 AND bs.break_price!=''");
		if($pr){
			if(count($pr)==1) {
				$pr[0]["break_price"] = $this->addLirun($pr[0]["break_price"]);
				return $pr[0];
			}else{
				$bestPrice = array();
				foreach($pr as $key=>$value){
					//亚洲仓
					if($value['location']=="Asia"){
						$pr[$key]["break_price"] = $this->addLirun($pr[$key]["break_price"]);
						return $pr[$key];
					}
					if($value['break_price']){
						$arrTmp = explode(";",$value['break_price']);
						$arr    = explode("|",$arrTmp[0]);
						if($arr[0]>0) {
							$bestPrice[$key] = $arr[1]/$arr[0];
						}
					}
				}
				//最好价格
				asort($bestPrice);
				foreach($bestPrice as $k=>$v){
					$pr[$k]["break_price"] = $this->addLirun($pr[$k]["break_price"]);
					return $pr[$k];
				}
			}
		}else return null;
	}
	/**
	 * 获取bpp可以销售库存
	 */
	public function getCanSell($partId,$num,$bpp_stock_id,$mpq){
		$pr = $this->getBppListByPartId($partId,"AND id != '{$bpp_stock_id}' AND bs.duplicated='N' AND (bs.stock - bs.bpp_stock_cover)>0 AND (bs.stock - bs.bpp_stock_cover)>=moq AND bs.break_price!=''");
		if($pr){	
			$bestPrice = array();
			foreach($pr as $key=>$value){
				$stock = $value["stock"] - $value["bpp_stock_cover"];
				if($stock >= $num && $num >= $value["moq"]){
					$value["break_price"] = $this->addLirun($value["break_price"]);
					return $value;
				}else return null;
			}
		}else return null;
	}
	
	/**
	 * 根据bpp ID获取bpp库存
	 */
	public function getBppById($bpp_stock_id){
		$value = $this->_bppModel->QueryRow("SELECT bs.*,p.part_no,v.vendor_name FROM `bpp_stock` as bs
				LEFT JOIN vendor as v ON bs.vendor_id=v.vendor_id
				LEFT JOIN product as p ON p.id=bs.part_id
				WHERE bs.`id` = '{$bpp_stock_id}' AND bs.duplicated='N' 
		        AND (bs.stock - bs.bpp_stock_cover)>0 
		        AND (bs.stock - bs.bpp_stock_cover)>bs.moq 
		        AND bs.moq>0
		        AND bs.break_price!=''");
		if($value){
			$value["break_price"] = $this->addLirun($value["break_price"]);
			return $value;
		}else return null;
	}
	/**
	 * 加上利润
	 */
	public function addLirun($break_price){
		//利润
		$value = $this->_bppModel->QueryItem("SELECT value FROM dictionary WHERE type='bpp_li_run' AND status=1");
		$liRun = $value?$value:1.15;

		$re = "";
		$arr1 = explode(";",$break_price);
		foreach($arr1 as $v1){
			$arr2 = explode("|", $v1);
			if($arr2[0]){
			    $re .= $arr2[0]."|".($arr2[1]*$liRun).";";
			}
		}
		return $re?$re:$break_price;
	}
	/**
	 * 代购运费
	 */
	public function daigou($bpp_stock_id,$currency,$total){
		$re = 0;
		$value = $this->_bppModel->QueryRow("SELECT lf.* FROM `bpp_stock` as bs
				LEFT JOIN bpp_location_freight as lf ON bs.vendor_id=lf.vendor_id
				WHERE bs.`id` = '{$bpp_stock_id}' 
				AND bs.location = lf.location
				AND lf.currency = '{$currency}'");
		if($value){
			if($value['free_total']==-1) return 0;
			elseif($total >= $value['free_total'] && $value['free_total']!=0) return 0;
			else return $value['money'];
		}else return $re;
	}
}