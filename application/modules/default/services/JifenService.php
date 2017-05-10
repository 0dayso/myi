<?php
class Default_Service_JifenService
{
	private $_giftmoder;
	public function __construct()
	{
		$this->_giftmoder = new Default_Model_DbTable_Model('gift');
	}
	/**
	 * 获取by id
	 */
	public function getGiftById($id)
	{
		return $this->_giftmoder->QueryRow("SELECT gf.*,(gf.stock-gf.stock_cover) as stock_have,gfc.name as cname FROM gift as gf 
				LEFT JOIN gift_category as gfc ON gfc.id=gf.category 
				WHERE gf.status=1 AND gfc.status=1 AND gf.id='{$id}'");
	}
	/**
	 * 获取数数量
	 */
	public function getNum($sql)
	{
		return $this->_giftmoder->QueryItem("SELECT count(gf.id) as num FROM gift as gf  WHERE gf.status=1 {$sql}");
	}
	/*
	 * 获所有积分礼品
	*/
	public function getAllGift($offset,$perpage,$typestr='',$orderby='')
	{
		if(!$orderby) $orderby = 'ORDER BY gf.home DESC,gf.score DESC,gf.modified DESC';
		$sql = "SELECT gf.*,gfc.name as cname FROM gift as gf 
				LEFT JOIN gift_category as gfc ON gfc.id=gf.category 
				WHERE gf.status=1 AND gfc.status=1 {$typestr}
		        {$orderby} LIMIT $offset,$perpage";
		return $this->_giftmoder->Query($sql);
	}
	/**
	 * 获取用户剩余积分
	 */
	public function getSurplusScore()
	{
		if(isset($_SESSION['userInfo']['uidSession'])){
		  return $this->_giftmoder->QueryItem("SELECT (score-score_consume) FROM user_profile WHERE uid='".$_SESSION['userInfo']['uidSession']."'");
		}else return 0;
	}
	/**
	 * 兑换产品判断
	 */
	public function checkexchang($giftid){
		//检查礼品是否存在
		$gift = $this->getGiftById($giftid);
		$error = 0;$mess = '';
		if(!$gift){
			$error++;
			$mess = '此商品已经下架';
		}elseif((int)$gift['stock_have']<=0){
			$error++;
			$mess = '此商品库存不足';
		}
		//检查积分是否足够兑换
		$userscore = $this->getSurplusScore();
		if($userscore<$gift['score']){
			$error++;
			$mess = '您的积分不足兑换此商品';
		}
		return array('error'=>$error,'mess'=>$mess);
	}
	/**
	 * 获取积分排行
	 */
	public function getScorelist($offset,$perpage){
		return  $this->_giftmoder->getBySql("SELECT u.uname,up.score FROM user_profile as up
				LEFT JOIN user as u  ON u.uid=up.uid
				ORDER BY up.score DESC LIMIT $offset , $perpage");
	}
	/**
	 * 获取兑换排行
	 */
	public function getExchanglist($number=0){
         $re = array(); 
		 $arr = $this->_giftmoder->getBySql("SELECT ge.giftid,ge.giftname,ge.score FROM gift_exchange as ge
         		WHERE ge.status = 301");
         foreach($arr as $v){
         	$re[$v['giftid']]['total']++;
         	$re[$v['giftid']][] = $v;
         }
         arsort($re);
         if(!$number) return $re;
         else{
            $retmp = array();
            $n = 0;
            foreach($re as $v){
               $n++;
         	   if($n > $number) break;
         	   else $retmp[] = $v;
            }
            return $retmp;
         }
	}
	/**
	 * 最新兑换
	 */
	public function newexchange($offset,$perpage,$typestr=''){
		return  $this->_giftmoder->getBySql("SELECT u.uname,ge.giftid,ge.giftname,ge.score,ge.created FROM gift_exchange as ge
				LEFT JOIN user as u  ON u.uid=ge.uid
				WHERE ge.status = 301 $typestr
				ORDER BY ge.created DESC LIMIT $offset , $perpage");
	}
	/**
	 * 滚动图片
	 */
	public function getTopimage($offset=0,$perpage=4){
		$lt = '';
		if($perpage) $lt = " LIMIT $offset , $perpage";
		return $this->_giftmoder->getBySql("SELECT * FROM home_photo WHERE type='jifen' AND status=1 ORDER BY displayorder ASC $lt");
	}
}