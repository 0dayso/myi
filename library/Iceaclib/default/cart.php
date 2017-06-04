<?php
/**
 * 购物车基本功能
 * 1) 将物品加入购物车
 * 2) 从购物车中删除物品 ，既更新物品数量为0
 * 3) 更新购物车物品信息 【+1/-1】
 * 4) 对购物车物品进行统计
 *    1. 总项目
	  2. 总数量
	  3. 总金额
	  4. 运费
 * 5) 对购物单项物品的数量及金额进行统计
 * 6) 清空购物车
 *
 * @author quanshuidingdang
 */
require_once 'Iceaclib/common/fun.php';
class Cart {
	//物品id及名称规则,调试信息控制
	private $product_id_rule = '/^[\.a-z0-9-_]+$/i';  	//小写字母 | 数字 | ._-
	private $debug = FALSE;
	//购物车
	private $_cart_contents = array();

	/**
	 * 构造函数
	 *
	 * @param array
	 */
	public function __construct() {
		$this->fun  = new MyFun();
		//是否第一次使用?
		if(isset($_SESSION['cart_contents'])) {
			$this->_cart_contents = $_SESSION['cart_contents'];
		}
		//用户操作checkbox后，剩下的物品清单
		if(!isset($_SESSION['cart_subtract'])) {
			$_SESSION['cart_subtract']=array();
		}
		if($this->debug === TRUE) {
			//$this->_log("cart_create_success");
		}
	}
	/**
	 * 更新cart_subtract
	 */
	public function setsubtract($cart=array())
	{
		$_SESSION['cart_subtract'] = $cart;
	}
	/**
	 * 获得cart_subtract
	 */
	public function getsubtract()
	{
		return $_SESSION['cart_subtract'];
	}
	/**
	 * 获得结算时的cart
	 */
	public function getcart()
	{
    	return false; 
	}
	/**
	 * 将物品加入购物车，包括新增和更新
	 *
	 * @access 	public
	 * @param	array	一维或多维数组,必须包含键值名:
	 id -> 物品ID标识,
	 part_no->商零件编号
	 qty -> 数量,
	 break_price -> 阶梯价格,
	 mpq_price ->最小包装价格
	 moq -> 最小起订量，
	 mpq -> 最小包装量，
	 options->其他参数
	 * @return 	bool
	 */
	public function add($items = array())
	{
		if(isset($items['id'])) {
			$olditems = $this->total_ids_place();
			$oldkeys = array_flip($olditems); //把数组的键和值交换形成了新的数组
			if(in_array($items['id'],$olditems))
			{
				//更新
				$rowid = $oldkeys[$items['id']];
				$newqty = $this->_cart_contents[$rowid]['qty']+$items['qty'];
				$arr = array('rowid' => $rowid,'qty' =>$newqty);
				return $this->update($arr);
			}else{
				//新增
				return $this->insert($items);
			}
		}
	}
	/**
	 * 将物品加入购物车
	 *
	 * @access 	public
	 * @param	array	一维或多维数组,必须包含键值名: 
						id -> 物品ID标识, 
						qty -> 数量(quantity), 
						price -> 单价(price), 
						name -> 物品姓名
	 * @return 	bool
	 */
	public function insert($items = array()) {
		//输入物品参数异常
		if( ! is_array($items) || count($items) == 0) {
			if($this->debug === TRUE) {
				$this->_log("cart_no_items_insert");
			}
			return FALSE;
		}
		
		//物品参数处理
		$save_cart = FALSE;
		if(isset($items['id'])) {
			if($this->_insert($items) === TRUE) {
				$save_cart = TRUE;
			}
		} else {
			foreach($items as $val) {
				if(is_array($val) && isset($val['id'])) {
					if($this->_insert($val) == TRUE) {
						$save_cart = TRUE;
					}
				}
			}
		}
		//当插入成功后保存数据到session
		if($save_cart) {
			$this->_save_cart();
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * 更新购物车物品信息
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	public function update($items = array()) {
		//输入物品参数异常
		if( !is_array($items) || count($items) == 0) {
			if($this->debug === TRUE) {
				$this->_log("cart_no_items_insert");
			}
			return FALSE;
		}
		
		//物品参数处理
		$save_cart = FALSE;
		if(isset($items['rowid']) && isset($items['qty'])) {
			if($this->_update($items) === TRUE) {
				$save_cart = TRUE;
			}
		} else {
			foreach($items as $val) {
				if(is_array($val) && isset($val['rowid']) && isset($val['qty'])) {
					if($this->_update($val) === TRUE) {
						$save_cart = TRUE;
					}
				}
			}
		}
		
		//当更新成功后保存数据到session
		if($save_cart) {
			$this->_save_cart();
			return TRUE;
		}
		
		return FALSE;
	}
	/**
	 * 删除购物车物品信息
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	public function delete($rowid) {
	     //既跟新物品数量为0
		 return $this->update(array('rowid' => $rowid,'qty' =>0));
	}
	/**
	 * 获取购物车物品总金额
	 *
	 * @return	int
	 */
	public function total() {
		return $this->_cart_contents['cart_total'];
	}
	
	/**
	 * 获取购物车物品种类
	 *
	 * @return	int
	 */
	public function total_items() {
		return count($this->_cart_contents);
	}
	/**
	 * 获取购物车已经存在的物品
	 *
	 * @return	int
	 */
	public function total_ids() {
		$totalid = array();
		foreach($this->_cart_contents as $item){
			if(is_array($item)){
				$totalid[$item['rowid']]=$item['pord_id'];
			}
		}
		return $totalid;
	}
	public function total_ids_place() {
		$totalid = array();
		foreach($this->_cart_contents as $item){
			if(is_array($item)){
				$totalid[$item['rowid']]=$item['id'];
			}
		}
		return $totalid;
	}
	/**
	 * 获取购物车
	 * @return	array
	 */
	public function contents() {
		return $this->_cart_contents;
	}
	/**
	 * 获取购物车分开两地
	 * @return	array
	 */
	public function contents_by_delivery() {
	    $cart_all = $this->contents();
    	$items = array();
    	//分开交货地
    	if($cart_all){
    		foreach($cart_all as $item){
    			if($item['delivery_place']=='SZ'){
    				$items['SZ'][] = $item;
    			}elseif($item['delivery_place']=='HK'){
    				$items['HK'][] = $item;
    			}
    		}
    	}
    	return $items;
	}
	/**
	 * 赋值购物车
	 *
	 */
	public function setvalue($contents) {
		//unset($this->_cart_contents);
		$this->_cart_contents = $contents;
	    //当插入成功后保存数据到session
	    $this->_save_cart();
		return TRUE;
	}
	/**
	 * 获取购物车物品options
	 *
	 * @param 	string
	 * @return	array
	 */
	public function options($rowid = '') {
		if($this->has_options($rowid)) {
			return $this->_cart_contents[$rowid]['options'];
		} else {
			return array();
		}
	}
	/**
	 * 清空购物车
	 *
	 */
	public function destroy() {
		unset($this->_cart_contents);
		$this->_cart_contents['freight']    = 0;
	    $this->_cart_contents['total_quantity'] = 0;
		$this->_cart_contents['total_items']= 0;
		$this->_cart_contents['cart_total'] = 0;		
		unset($_SESSION['cart_contents']);
		unset($_SESSION['cart_subtract']);;
	}
	
	/**
	 * 判断购物车物品是否有options选项
	 * 
	 * @param	string
	 * @return	bool
	 */
	private function has_options($rowid = '') {
		if( ! isset($this->_cart_contents[$rowid]['options']) || count($this->_cart_contents[$rowid]['options']) === 0) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * 插入数据
	 *
	 * @access	private 
	 * @param	array
	 * @return	bool
	 */
	private function _insert($items = array()) {
		//输入物品参数异常
		if( ! is_array($items) || count($items) == 0) {
			if($this->debug === TRUE) {
				$this->_log("cart_no_data_insert");
			}
			return FALSE;
		}
		//如果物品参数无效
		if( ! isset($items['id']) || ! isset($items['part_no']) || ! isset($items['qty']) || ! isset($items['break_price']) || ! isset($items['unit']) ||! isset($items['byprice']) ) {
			if($this->debug === TRUE) {
				$this->_log("cart_items_data_invalid");
			}
			return FALSE;
		}
		
		//去除物品数量左零及非数字字符
		$items['qty'] = trim(preg_replace('/([^0-9])/i', '', $items['qty']));
		$items['qty'] = trim(preg_replace('/^([0]+)/i', '', $items['qty']));
		
		//如果物品数量为0，或非数字，则我们对购物车不做任何处理!
		if( ! is_numeric($items['qty']) || $items['qty'] == 0) {
			if($this->debug === TRUE) {
				$this->_log("cart_items_data(qty)_invalid");
			}
			return FALSE;
		}
		
		//物品ID正则判断
		if( ! preg_match($this->product_id_rule, $items['id'])) {
			if($this->debug === TRUE) {
				$this->_log("cart_items_data(id)_invalid");
			}
			return FALSE;
		}
		
		//生成物品的唯一id
		
		$rowid = md5($items['id'].$items['supplier_id']);
		
		//加入物品到购物车
		unset($this->_cart_contents[$rowid]);
		$this->_cart_contents[$rowid]['rowid'] = $rowid;
		foreach($items as $key => $val) {
			$this->_cart_contents[$rowid][$key] = $val;
		}
		return TRUE;
	}
	
	/**
	 * 更新购物车物品信息（私有）
	 *
	 * @access 	private
	 * @param	array
	 * @return 	bool
	 */
	private function _update($items = array()) {
		//输入物品参数异常
		if( ! isset($items['rowid']) || ! isset($items['qty']) || ! isset($this->_cart_contents[$items['rowid']])) {
			if($this->debug == TRUE) {
				$this->_log("cart_items_data_invalid");
			}
			return FALSE;
		}
		
		//如果非0，去除物品数量左零及非数字字符
		
		if($items['qty'] > 0)
		{
		  $items['qty'] = preg_replace('/([^0-9])/i', '', $items['qty']);
		  $items['qty'] = preg_replace('/^([0]+)/i', '', $items['qty']);
		}elseif($items['qty']<0)
		{
			if($this->debug == TRUE) {
				$this->_log("qty < 0;");
			}
			return FALSE;
		}
		//如果物品数量非数字，对购物车不做任何处理!
		if( ! is_numeric($items['qty'])) {
			if($this->debug === TRUE) {
				$this->_log("cart_items_data(qty)_invalid");
			}
			return FALSE;
		}
		//如果购物车物品数量与需要更新的物品数量一致，则不需要更新
		if($this->_cart_contents[$items['rowid']]['qty'] == $items['qty']) {
			if($this->debug === TRUE) {
				$this->_log("cart_items_data(qty)_equal");
			}
			return FALSE;
		}
		
		//如果需要更新的物品数量等于0，表示不需要这件物品，从购物车种清除
		//否则修改购物车物品数量等于输入的物品数量
		if($items['qty'] == 0) {

			unset($this->_cart_contents[$items['rowid']]);
		} else {
			$this->_cart_contents[$items['rowid']]['qty'] = $items['qty'];
		}
		
		return TRUE;
	}
	
	/**
	 * 保存购物车数据到session
	 * 
	 * @access	private
	 * @return	bool
	 */
	private function _save_cart() {
		//然后遍历数组统计物品种类及总金额
		$total = 0;$total_quantity = 0;
		$this->_cart_contents_tmp = array();
		foreach($this->_cart_contents as $key => $val) {
			//去除总价为0的元素
			if($val['byprice'] <0 && $val['qty']){
				unset($this->_cart_contents[$key]);
			}
			//重新计算价格
			$this->_cart_contents[$key]['byprice'] = $this->fun->getPrice($val['break_price'], $val['qty']);
				
		}
		if(count($this->_cart_contents) <= 0) unset($this->_cart_contents);
		//保存购物车数据到session
		$_SESSION['cart_contents'] = $this->_cart_contents;
		return TRUE;
	}
	
	/**
	 * 日志记录
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	private function _log($msg) {
		//return @file_put_contents('cart_err.log', $msg, '/');
	}
}

/*End of file cart.php*/
/*Location /htdocs/cart.php*/