<?php
class Default_Service_ProductService
{
	private $_proModer;
	private $_appModel;
	private $_pdnpcnModel;
	private $_prosql_str;
	private $_homepart;
	public function __construct()
	{
		$this->_proModer = new Default_Model_DbTable_Product();
		$this->_appModel = new Default_Model_DbTable_ProductNotes();
		$this->_pdnpcnModel = new Default_Model_DbTable_Model('pdnpcn');
		$this->fun = new MyFun();
		
		$this->_prosql_str = "pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,
				pro.break_price,pro.moq,pro.mpq,pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.sz_cover,pro.hk_cover,
				pro.bpp_stock,pro.bpp_cover,pro.show_price,pro.can_sell,pro.noinquiry,
				pro.surplus_stock_sell,pro.special_break_prices,pro.price_valid,pro.price_valid_rmb,";
		$this->_homepart = CACHE_PATH."/home/";
	}
	
	/*
	 * 根据part id获取产品信息
	 */
	public function getInqProd($partid)
	{
		$sql ="SELECT p.*,b.name as brand FROM product as p
                LEFT JOIN brand as b ON p.manufacturer=b.id
    		    WHERE p.id=:idtmp AND p.status=1";
		$productTmp = $this->_proModer->getBySql($sql, array('idtmp'=>$partid));
		return $productTmp[0];
	}
	/**
	 * 根据part id 查询 品牌id
	 */
	public function getBrandId($pid)
	{
		$sql ="SELECT manufacturer FROM product WHERE id =:pidtmp";
		$productTmp = $this->_proModer->getByOneSql($sql, array('pidtmp'=>$pid));
		return $productTmp['manufacturer'];
	}
	/*
	 * 根据part id获取产品信息
	*/
	public function getStockProd($partid)
	{
		$sql ="SELECT p.*,b.name as brand FROM product as p 
                LEFT JOIN brand as b ON p.manufacturer=b.id
    		    WHERE p.id=:idtmp AND p.status=1";
		$productTmp = $this->_proModer->getBySql($sql, array('idtmp'=>$partid));
		return $productTmp[0];
	}
	/*
	 * 根据sql条件获取产品总数
	 */
	public function getNum($sqlstr){
		$sqlstr = "SELECT count(po.id) as num FROM product as po
		 LEFT JOIN brand as br ON po.manufacturer=br.id
		 WHERE {$sqlstr} AND po.status=1 AND br.status=1 LIMIT 1";
		$allcan = $this->_proModer->getByOneSql($sqlstr);
		return $allcan['num'];
	}
	/*
	 * 根据sql条件获取产品
	*/
	public function getProdPage($sqlstr,$offset,$perpage){
		$sqlstr ="SELECT po.*,br.name as bname
				   FROM product as po 
				   LEFT JOIN brand as br ON po.manufacturer=br.id
				   WHERE {$sqlstr} AND po.status=1 AND br.status=1 LIMIT {$offset},{$perpage}";
		return $this->_proModer->getBySql($sqlstr);
	}
	/*
	 * 获取所有品牌
	*/
	public function getBrand(){
		$sqlstr ="SELECT * FROM brand WHERE status=1";
		return $this->_proModer->getBySql($sqlstr);
	}
	/*
	 * 获取所有品牌
	*/
	public function getBrandInfoById($id){
		return $this->_proModer->getByOneSql("SELECT * FROM brand WHERE status='1' AND id='$id'");
	}
	/**
	 * 获取品牌名
	 */
	public function getBrandById($id)
	{
		$re = $this->_proModer->getByOneSql("SELECT name FROM brand WHERE status='1' AND id='$id'");
		return $re['name']; 
	}
	/**
	 * 根据品牌id获取产品分类
	 */
	public function getAppByBrandId($brandid,$apporder){
		$sql ="SELECT DISTINCT(part_level3),part_level2 
		FROM product WHERE status='1' AND manufacturer='$brandid'";
		$app3 = $this->_proModer->getBySql($sql);
		$re1 = $re2 = array();
		foreach($app3 as $key=>$v){
			$re1[$v['part_level2']][] = $v['part_level3'];
		}
		$i=0;
		foreach($re1 as $key=>$v){
			$sqlstr ="SELECT name FROM prod_category WHERE id='{$key}' AND status='1'";
			$re = $this->_proModer->getByOneSql($sqlstr);
			$tmp = array();
			if($re['name']){
			   $re2[$i]['app2'] = array($key,$re['name']);
			   if($v){
			      foreach($v as $id){
			   	  $sqlstr ="SELECT name FROM prod_category WHERE id='{$id}' AND status='1'";
			   	  $tmp2 = $this->_proModer->getByOneSql($sqlstr);
			   	  if($tmp2['name']) $tmp[] = array($id,$tmp2['name']);
			   }
			}
			$re2[$i]['app3'] = $tmp;
			$i++;
			}
		}
		sort($re2);
		$renew = array();
		if($apporder){
			$orderarr = explode('|',$apporder);
			foreach($orderarr as $v1){
			   foreach($re2 as $k=>$arr){
				  if($v1==$arr['app2'][0]){
				  	$renew[]=$arr;
				  	unset($re2[$k]);
				  }
			   }
			}
		}
		return array_merge($renew,$re2);
	}
	/**
	 * 获取产品分类名称
	 */
	public function getCategoryById($id)
	{
		$re = $this->_proModer->getByOneSql("SELECT name FROM prod_category WHERE status='1' AND id='$id'");
		return $re['name'];
	}
	/*
	 * 查询库存情况
	 */
	public function checkStock($partid,$stocktype,$num)
	{
		$sql ="SELECT hk_stock,sz_stock,hk_cover,sz_cover FROM product
    		    WHERE id=:idtmp AND status=1";
		$productTmp = $this->_proModer->getByOneSql($sql, array('idtmp'=>$partid));
		if($stocktype=='SZ'){
			if($productTmp['sz_stock'] >=$num && ($productTmp['sz_stock']-$productTmp['sz_cover']) >=$num){
				return true;
			}
			else{
				return false;
			}
		}elseif($stocktype=='HK'){
			if($productTmp['hk_stock'] >=$num && ($productTmp['hk_stock']-$productTmp['hk_cover']) >=$num) return true;
			else return false;
		}else return false;
	}
	/*
	 * 减去下单库存
	*/
	public function minusInventory($partid,$stocktype,$pmpq)
	{
		if($stocktype=='SZ'){
			$udstr = "UPDATE product SET sz_cover =sz_cover + {$pmpq} WHERE id=:idtmp  AND status=1";
		}elseif($stocktype=='HK'){
			$udstr = "UPDATE product SET hk_cover =hk_cover + {$pmpq} WHERE id=:idtmp  AND status=1";
		}else return false;
		$this->_proModer->updateBySql($udstr,array('idtmp'=>$partid));
	}
	/**
	 * 根据part no 查询 part id
	 */
	public function getPid($partno)
	{
		$sql ="SELECT id FROM product WHERE part_no ='{$partno}'";

		$productTmp = $this->_proModer->getByOneSql($sql);
	
		return $productTmp['id'];
	}
	/**
	 * 根据part id 查询 part no
	 */
	public function getPartNo($pid)
	{
		$sql ="SELECT part_no FROM product WHERE id =:pidtmp";
		$productTmp = $this->_proModer->getByOneSql($sql, array('pidtmp'=>$pid));
		return $productTmp['part_no'];
	}
	/**
	 * 更加part no 查询 产品
	 */
	public function getProductByNo($partno)
	{
		$sql ="SELECT p.*,b.name as bname FROM product as p
		LEFT JOIN brand as b ON p.manufacturer=b.id		
		WHERE p.part_no =:partnotmp AND p.status=1 AND b.status=1";
		return $this->_proModer->getByOneSql($sql, array('partnotmp'=>$partno));
	}
	/**
	 * 根据part no 查询 产品
	 */
	public function getProductByPartno($partno)
	{
	    $sql ="SELECT p.* FROM product as p
		WHERE p.part_no =:partnotmp";
	    return $this->_proModer->getByOneSql($sql, array('partnotmp'=>$partno));
	}
	/**
	 * 更加part no 查询 产品
	 */
	public function getProductByWhere($where)
	{
		$sql ="SELECT p.*,b.name as bname FROM product as p
		LEFT JOIN brand as b ON p.manufacturer=b.id
		WHERE p.status=1 AND b.status=1 {$where} LIMIT 1";
		return $this->_proModer->getByOneSql($sql);
	}
	/*
	 * 获取part no By like
	*/
	public function getPartNoLike($keyword)
	{
		$where="`part_no` LIKE '%".$keyword. "%'";
		$sqlstr ="SELECT `part_no`  FROM `product`
		WHERE status!=0 AND {$where} LIMIT 0 , 10";
		$soArr = $this->_proModer->getBySql($sqlstr);
		for($i=0;$i<count($soArr);$i++)
		{
		$str .= $soArr[$i]['part_no'] . "\n";
		}
		return $str;
	}
	/**
	 * 更加 part id 查询 最长交期
	 */
	public function getLeadtime($partid)
	{
		$sql ="SELECT lead_time FROM product WHERE id =:partidtmp";
		$productTmp = $this->_proModer->getByOneSql($sql, array('partidtmp'=>$partid));
		return $productTmp['lead_time'];
	}
	/**
	 * 获取产品目录
	 */
	public function getProdCategory(){
		//产品目录 
		$frontendOptions = array('lifeTime' => null,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		 //$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'pcall_index_cache';
		if(!$pcall = $cache->load($cache_key)) {
			$pcModer = new Default_Model_DbTable_ProdCategory();
			$pcall = $pcModer->getAllByWhere ("status='1'","displayorder ASC");
			$cache->save($pcall,$cache_key);
		}
		$parent = $this->getLevel($pcall, 1);
		$son1   = $this->getLevel($pcall, 2);
		$son3   = $this->getLevel($pcall, 3);
		$second = $this->getSon($parent, $son1);
		$third  = $this->getSon($son1, $son3);
		return array('first'=>$parent,'second'=>$second,'third'=>$third);
	}
	/**
	 * 获取产品目录推荐品牌
	 */
	public function getCategoryBrand(){
		//代理品牌
		$sqlstr ="SELECT re.comid,re.cat_id,b.name
		     FROM recommend as re
	   		 LEFT JOIN brand as b ON re.comid=b.id
		     WHERE re.type='category_brand' AND re.part='home' AND re.status=1 AND b.status=1 ORDER BY re.displayorder";
		$category_brand = $this->_proModer->getBySql($sqlstr);
		if($category_brand){
			$reArr =  array();
			foreach($category_brand as $cb){
				$reArr[$cb['cat_id']][] = $cb;
			}
			return $reArr;
		}else return false;
	}
	/**
	 * 取消订单时恢复被占数量
	 */
	public function reinstate($salesnumber){
		$sql = "SELECT prod_id,sz_cover,hk_cover,bpp_cover,bpp_stock_id FROM `sales_product` WHERE salesnumber = '{$salesnumber}'";
		$arr = $this->_proModer->getBySql($sql);
		for($i=0;$i<count($arr);$i++){
			$sz_cover = $arr[$i]['sz_cover']?$arr[$i]['sz_cover']:0;
			$hk_cover = $arr[$i]['hk_cover']?$arr[$i]['hk_cover']:0;
			$udstr = "UPDATE product SET sz_cover =sz_cover - ".$sz_cover.",hk_cover =hk_cover - ".$hk_cover." WHERE id='".$arr[$i]['prod_id']."' AND sz_cover >=".$sz_cover." AND hk_cover >=".$hk_cover;
			$this->_proModer->updateBySql($udstr);
			//恢复bpp
			if($arr[$i]['bpp_stock_id']){
				$bpp_cover= $arr[$i]['bpp_cover']?$arr[$i]['bpp_cover']:0;
				$udstr = "UPDATE bpp_stock  SET bpp_stock_cover = bpp_stock_cover - ".$bpp_cover." WHERE id='".$arr[$i]['bpp_stock_id']."'";
				$this->_proModer->updateBySql($udstr);
			}
		}
	}
	/**
	 * 释放订单恢复供应商库存
	 */
	public function reinstateBpp($salesnumber){
		$sql = "SELECT prod_id,sz_cover,hk_cover,bpp_cover,bpp_stock_id FROM `sales_product` WHERE salesnumber = '{$salesnumber}'";
		$arr = $this->_proModer->getBySql($sql);
		for($i=0;$i<count($arr);$i++){
			//恢复bpp
			if($arr[$i]['bpp_stock_id']){
				$bpp_cover= $arr[$i]['bpp_cover']?$arr[$i]['bpp_cover']:0;
				$udstr = "UPDATE bpp_stock  SET bpp_stock_cover = bpp_stock_cover - ".$bpp_cover." WHERE id='".$arr[$i]['bpp_stock_id']."'";
				$this->_proModer->updateBySql($udstr);
			}
		}
	}
	/**
	 * 回复订单时占用产品数量
	 */
	public function occupation($salesnumber){
		$sql = "SELECT prod_id,buynum FROM `sales_product` WHERE salesnumber = '{$salesnumber}'";
		$arr = $this->_proModer->getBySql($sql);
		for($i=0;$i<count($arr);$i++){
			$udstr = "UPDATE product SET sz_cover =sz_cover + ".$arr[$i]['buynum']." WHERE id='".$arr[$i]['prod_id']."'";
			$this->_proModer->updateBySql($udstr);
		}
	}
	/*
	 * 获取级别菜单
	*/
	private function getLevel($pcall, $level) {
		$rearray =  array();
		foreach ( $pcall as $pa ) {
			if($pa['level'] == $level)
				$rearray[]=$pa;
		}
		return $rearray;
	}
	/*
	 * 获取孩子
	*/
	private function getSon($parent, $pcall) {
		$rearray =  array();
		foreach ( $parent as $pa ) {
			$tmp =  array();
			foreach ( $pcall as $pc ) {
				if($pc['parent_id'] == $pa['id'])
					$tmp[]=$pc;
			}
			$rearray[$pa['id']] =$tmp;
		}
		return $rearray;
	}
	/**
	 * 获取产品
	 */
	public function getPartById($id){
		$sqlstr ="SELECT po.*,pc1.name as cname1,pc2.name as cname2,pc3.name as cname3,
		br.id as bid,br.name as bname,app1.name as appname1
		FROM product as po
		LEFT JOIN prod_category as pc3 ON po.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON po.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON po.part_level1=pc1.id
		LEFT JOIN app_category as app1 ON po.app_level1=app1.id
		LEFT JOIN brand as br ON po.manufacturer=br.id
		WHERE po.id='{$id}' AND po.status='1' AND br.status='1'";
		return $this->_proModer->getByOneSql($sqlstr);
	}
	/**
	 * 产生产品缓存
	 * @return Ambigous <mixed, false, boolean, string>
	 */
	public function createTmp(){
		//产品系列
		$re = array();
		$frontendOptions = array('lifeTime' => NULL,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH.'searchproduct/');
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'allproduct_';
		$sql = "SELECT pod.id,pod.part_no
			FROM product as pod
			LEFT JOIN brand as br ON pod.manufacturer=br.id
		    WHERE pod.status=1 AND br.status=1 ORDER BY pod.sz_stock DESC,pod.hk_stock DESC,pod.bpp_stock DESC";
		$podModer = new Default_Model_DbTable_Product();
		$re = $podModer->getBySql($sql);
		$cache->save($re,$cache_key);
		//记录搜索日志
		$this->_defaultlogService = new Default_Service_DefaultlogService();
		$this->_defaultlogService->addLog(array('log_id'=>'A','temp3'=>count($re),'temp4'=>'产生产品表缓存'));
		return $re;
	}
	public function getPartTmp(){
		$re = array();
		$frontendOptions = array('lifeTime' => NULL,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH.'searchproduct/');
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'allproduct_';
		$re = $cache->load($cache_key);
		if(!$re) $re = $this->createTmp();
		//$tmpre = $re;
		//for($j=0;$j<15;$j++){
		//$re = array_merge($re,$tmpre);
		//}
		return $re;
	}
	/**
	 * 存储查询结果
	 * @param unknown_type $searcharr
	 */
	public function createResultTmp($searcharr){
		//产品系列
		$re = array();
		$frontendOptions = array('lifeTime' => NULL,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH.'searchproduct/result/');
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'resultproduct_'.md5($this->fun->getIp());
		$cache->save($searcharr,$cache_key);
	}
	/**
	 * 获取查询结果
	 * @param unknown_type $searcharr
	 */
	public function getResultTmp(){
		//产品系列
		$re = array();
		$frontendOptions = array('lifeTime' => NULL,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH.'searchproduct/result/');
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'resultproduct_'.md5($this->fun->getIp());
		return $cache->load($cache_key);
	}
	/**
	 * 根据数组获取产品
	 * @param unknown_type $searcharr
	 * @param unknown_type $offset
	 * @param unknown_type $perpage
	 * @param unknown_type $total
	 * @return multitype:NULL
	 */
	public function getPartArr($searcharr,$offset,$perpage,$total){
		$re = array();
		for($i=$offset;$i<$total && $i<($offset+$perpage);$i++){
			$re[]=$this->getPartById($searcharr[$i]['id']);
		}
		return $re;
	}
	/**
	 * 使用IN 获取数组产品
	 * @param unknown_type $searcharr
	 * @param unknown_type $offset
	 * @param unknown_type $perpage
	 * @param unknown_type $total
	 * @return multitype:Ambigous <multitype:, mixed, string, boolean>
	 */
	public function getPartArrByIn($searcharr,$offset,$perpage,$total){
		$re = array();$insrt='';
		for($i=$offset;$i<$total && $i<($offset+$perpage);$i++){
			if($i==$offset) $insrt ="'".$searcharr[$i]['id']."'";
			else  $insrt .=",'".$searcharr[$i]['id']."'";
		}
		if($insrt){
		   $sqlstr ="SELECT po.*,pc1.name as cname1,pc2.name as cname2,pc3.name as cname3,
		br.id as bid,br.name as bname,app1.name as appname1
		FROM product as po
		LEFT JOIN prod_category as pc3 ON po.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON po.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON po.part_level1=pc1.id
		LEFT JOIN app_category as app1 ON po.app_level1=app1.id
		LEFT JOIN brand as br ON po.manufacturer=br.id
		WHERE po.id IN ({$insrt}) AND po.status='1' AND br.status='1'";
		   return $this->_proModer->getBySql($sqlstr);
		}else return array();
	}
	
	public function getAppNotesBySeries($series='')
	{
		
		if($series =='')
		{
			return false;
		}
		$where = "series='$series'";
		return $this->_appModel->getAllByWhere($where);
		
	}
	
	/*
	 * pnd
	 */
	public function getPdn($partid){
		$sql = "SELECT p.* FROM pdnpcn_part as pp
				LEFT JOIN pdnpcn as p ON p.id=pp.pdnpcn_id
				WHERE pp.part_id='{$partid}' AND p.type='PDN' AND pp.status='1' ORDER BY p.id DESC";
		return $this->_pdnpcnModel->Query($sql);
	}
	/*
	 * pcd
	*/
	public function getPcn($partid){
		$sql = "SELECT p.* FROM pdnpcn_part as pp
		LEFT JOIN pdnpcn as p ON p.id=pp.pdnpcn_id
		WHERE pp.part_id='{$partid}' AND p.type='PCN' AND pp.status='1' ORDER BY p.id DESC";
		return $this->_pdnpcnModel->Query($sql);
	}
	/**
	 * 获取关联产品id
	 */
	public function getRelevancePartid ($partid){
		$rearr = array();
		$sql = "SELECT f_id,t_id FROM relevance 
		WHERE f_type='product' AND t_type='product' AND (f_id='{$partid}' OR t_id='{$partid}') AND status='1' ORDER BY displayorder ASC";
		$re =  $this->_proModer->getBySql($sql);
		foreach($re as $v){
			if($v['f_id']==$partid){
				if(!in_array($v['t_id'],$rearr)) $rearr[]=$v['t_id'];
			}else{
				if(!in_array($v['f_id'],$rearr)) $rearr[]=$v['f_id'];
			}
		}
		return $rearr;
	}
	/**
	 * 获取关联产品信息
	 */
	public function getRelevanceInfo ($partid){
		$rearr = array();
		$re =  $this->getRelevancePartid($partid);
		foreach($re as $v){
			$rearr[] = $this->getPartById($v);
		}
		return $rearr;
	}
	/**
	 * 根据品牌获取系列
	 */
	public function getSeriesByBrand($brandid){
		//产品系列
		$re = array();
		$frontendOptions = array('lifeTime' => 86400,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'series_by_brand_'.$brandid;
		if(!$re = $cache->load($cache_key)) {
			$sql = "SELECT distinct(series) FROM product WHERE manufacturer='{$brandid}' AND series!='' AND series IS NOT NULL AND status='1' ORDER BY series ASC";
			$re = $this->_proModer->getBySql($sql);
			$cache->save($re,$cache_key);
		}
		return $re;
	}
	/**
	 * 首页热销产品缓存
	 */
	public function homeHot(){
		//订单 1小时
		$frontendOptions = array('lifeTime' => 3600,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => $this->_homepart);
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'home_hot';
		if(!$allArr = $cache->load($cache_key)) {
			
			//热销产品
			$sqlstr ="SELECT re.cat_id,pro.id,pro.manufacturer,br.name as brandname,
			{$this->_prosql_str}
			pc2.name as bname2,pc3.name as bname3
			FROM recommend as re
			LEFT JOIN product as pro ON re.comid=pro.id
			LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
			LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
			LEFT JOIN brand as br ON re.cat_id=br.id
			WHERE re.type='hot' AND re.part='home' AND re.status = 1 AND br.status = 1 AND pro.status = 1";
			$allArr =  $this->_proModer->getBySql($sqlstr);
			
			
			$cache->save($allArr,$cache_key);
		}
		return $allArr;
	}
	/**
	 * 首页热推产品缓存
	 */
	public function homeHotPush(){
		//订单 1小时
		$frontendOptions = array('lifeTime' => 4600,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => $this->_homepart);
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'home_hotpush';
		if(!$allArr = $cache->load($cache_key)) {
				
			 $sqlstr ="SELECT re.id,re.comid,re.status,re.displayorder,{$this->_prosql_str}
	   		 b.id as brandid,b.name as brandname,pc2.name as cname2,pc3.name as cname3
		     FROM recommend as re
    		 LEFT JOIN product as pro ON re.comid=pro.id
			 LEFT JOIN brand as b ON b.id=pro.manufacturer
	   		 LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
	   		 LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		     WHERE re.type='hot_prod' AND re.part='home' AND re.status=1 ORDER BY re.displayorder ASC LIMIT 0 , 4";
	         $allArr =  $this->_proModer->getBySql($sqlstr);
				
				
			$cache->save($allArr,$cache_key);
		}
		return $allArr;
	}
	/**
	 * 首页查询关联元件
	 */
	public function homeSolProduct($prodId){
       $sqlstr ="SELECT {$this->_prosql_str}
	    		pc2.id as pcid2,pc3.id as pcid3,pc2.name as bname2,pc3.name as bname3,br.name as bname
		   	   	FROM product as pro
		   	    LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
	   		    LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		   	   	LEFT JOIN brand as br ON pro.manufacturer=br.id
		   	   	WHERE pro.id='{$prodId}' AND pro.status=1 AND br.status = 1";
	    		$allArr = $this->_proModer->getBySql($sqlstr, array());
	
		return $allArr;
	}
	/**
	 * 应用推荐产品缓存
	 */
	public function homeAppProduct(){
		//订单 1小时
		$frontendOptions = array('lifeTime' => 6000,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => $this->_homepart);
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'home_appprod';
		if(!$allArr = $cache->load($cache_key)) {
	
			$sqlstr ="SELECT re.cat_id,re.head,br.name as brandname,
		       {$this->_prosql_str} pc2.name as bname2,pc3.name as bname3
		       FROM recommend as re 
			   LEFT JOIN product as pro ON re.comid=pro.id
		       LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
	   		   LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
			   LEFT JOIN brand as br ON pro.manufacturer=br.id
		       WHERE re.type='app' AND re.part='home' AND re.status = 1 AND pro.status = 1 AND br.status = 1 ORDER BY re.head DESC,re.displayorder";
		     $allArr = $this->_proModer->getBySql($sqlstr);
	
	
			$cache->save($allArr,$cache_key);
		}
		return $allArr;
	}
	
	/**
	 * 添加爬取产品
	 */
	public function addSupplierProduct($collection_id,$supplier_id,$part_no,$item){
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
		$cache_key = 'crawler_product_'.$supplier_id.'_'.md5($part_no);
	   if($product = $cache->load($cache_key)) {
			$prodInfo = [];
	       foreach($product as $k=>$prod){
				if($k==$item){
					$prodInfo = $prod;break;
				}
			}
			if($prodInfo){
				$productSupplier = new Default_Model_DbTable_ProductSupplier();
				$productPrice = new Default_Model_DbTable_ProductPrice();
				$data['part_no'] = $prodInfo['pratNo'];
				$data['manufacturer'] = $prodInfo['brand'];
				$productId = $this->getPid($data['part_no']);
				//如果不存在，添加
				if(!$productId){
				    $productId = $this->_proModer->addData($data);
				}
				if($productId){
					$datas['part_no'] = $prodInfo['pratNo'];
					$datas['product_id'] = $productId;
					$datas['collection_id'] = $collection_id;
					$datas['supplier_id'] = $supplier_id;
					$datas['stock'] = $prodInfo['stock'];
					$datas['moq'] = $prodInfo['moq'];
					$datas['update_time'] = date("Y-m-d");
					$productSupplier->addData($datas);
					$dataps = [];
					foreach($prodInfo['bookprice'] as $bp){
						$tmp = [];
						$tmp['product_id'] = $productId;
						$tmp['collection_id'] = $collection_id;
						$tmp['supplier_id'] = $supplier_id;
						$tmp['moq'] = $bp['moq'];
						$tmp['rmbprice'] = $bp['rmbprice'];
						$tmp['usdprice'] = $bp['usdprice'];
						$dataps[] = $tmp;
					}
					if($dataps){
						$productPrice->addDatas($dataps);
					}
				}
			}
			return true;
		}
		return false;
	}
	/**
	 * 更新爬取产品
	 */
	public function updateSupplierProduct($collection_id,$supplier_id,$part_no,$item,$productId){
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
		$cache_key = 'crawler_product_'.$supplier_id.'_'.md5($part_no);
		if($product = $cache->load($cache_key)) {
			foreach($product as $k=>$prod){
				if($k==$item){
					$prodInfo = $prod;break;
				}
			}
			
			if($prodInfo){
				$productSupplier = new Default_Model_DbTable_ProductSupplier();
				$productPrice = new Default_Model_DbTable_ProductPrice();
				
				if($productId){
					$datas['stock'] = $prodInfo['stock'];
					$datas['moq'] = $prodInfo['moq'];
					$datas['update_time'] = date("Y-m-d");
					
					$productSupplier->update($datas, "product_id='{$productId}'");
					$dataps = [];
					
					foreach($prodInfo['bookprice'] as $bp){
						$tmp = [];
						$tmp['product_id'] = $productId;
						$tmp['collection_id'] = $collection_id;
						$tmp['supplier_id'] = $supplier_id;
						$tmp['moq'] = $bp['moq'];
						$tmp['rmbprice'] = $bp['rmbprice'];
						$tmp['usdprice'] = $bp['usdprice'];
						$dataps[] = $tmp;
					}
					if($dataps){
						$productPrice->delete("product_id='{$productId}'");
						$productPrice->addDatas($dataps);
					}
				}
			}
			return true;
		}
		return false;
	}
	/**
	 * 获取产品
	 */
	public function getProductNew($qstr,$collection_id,$supplier_id){
		$prodModel = new Default_Model_DbTable_Product();
		$sqlstr ="SELECT po.*,pc1.name as cname1,pc2.name as cname2,pc3.name as cname3
		FROM product as po
		LEFT JOIN product_supplier as ps ON ps.product_id=po.id
		LEFT JOIN prod_category as pc3 ON po.part_level3=pc3.id
		LEFT JOIN prod_category as pc2 ON po.part_level2=pc2.id
		LEFT JOIN prod_category as pc1 ON po.part_level1=pc1.id
		WHERE {$qstr} AND po.status='1' AND ps.collection_id='$collection_id' AND ps.supplier_id='$supplier_id'";
		$product =  $prodModel->getByOneSql($sqlstr);
		return $product;
	}
	/**
	 * 获取库存和价格
	 */
    public function getStockPrice($product_id,$collection_id,$supplier_id){
    	$productSupplierModel = new Default_Model_DbTable_ProductSupplier();
    	$sqlstr ="SELECT *
    	FROM product_supplier
    	WHERE product_id='{$product_id}' 
    	AND collection_id='$collection_id'
    	AND supplier_id='$supplier_id'";
    	$stockInfo =  $productSupplierModel->getByOneSql($sqlstr);
    	//查询价格
    	$sqlstr ="SELECT *
    	FROM product_price
    	WHERE product_id='{$product_id}'
    	AND collection_id='$collection_id'
    	AND supplier_id='$supplier_id' ORDER BY moq ASC";
    	$priceInfo =  $productSupplierModel->getBySql($sqlstr);
    	$stockInfo['priceInfo'] = $priceInfo;
    	$stockInfo['rmbprice'] = '';
    	$stockInfo['usdprice'] = '';
    	if($priceInfo){
    		foreach($priceInfo as $k=>$v){
    			if($k==0){
    				$stockInfo['rmbprice'] = $v['moq'].'|'.$v['rmbprice'];
    				$stockInfo['usdprice'] = $v['moq'].'|'.$v['usdprice'];
    			}else{
    				$stockInfo['rmbprice'] .= ';'.$v['moq'].'|'.$v['rmbprice'];
    				$stockInfo['usdprice'] .= ';'.$v['moq'].'|'.$v['usdprice'];
    			}
    		}
    	}
    	//供应商信息
    	$sqlstr ="SELECT *
    	FROM sx_supplier_grab
    	WHERE id='$supplier_id'";
    	$stockInfo['supplierInfo'] =  $productSupplierModel->getByOneSql($sqlstr);
    	
    	return $stockInfo;
    }
    
    /**
     * 更新查看次数
     */
    public function addView($product_id){
        $pInfo = $this->getInqProd($product_id);
        if($pInfo){
            $viewNumber = $pInfo['viewnumber']+1;
            $this->_proModer->updateById(array('viewnumber'=>$viewNumber), $product_id);
        }
    }
}