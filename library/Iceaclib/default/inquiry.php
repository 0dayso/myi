<?php
/**
 * 询价基本功能
 * 1) 将物品加入询价
 * 2) 从询价中删除物品 ，既更新物品数量为0
 * 3) 更新询价物品信息 【+1/-1】
 * 4) 对询价物品进行统计
 *    1. 总项目
	  2. 总数量
 * 5) 清空询价
 *
 * @author quanshuidingdang
 */
class Iceaclib_default_inquiry {
	//物品id及名称规则,调试信息控制
	private $product_id_rule = '/^[\.a-z0-9-_]+$/i';  	//小写字母 | 数字 | ._-
	private $debug = FALSE;

	//询价单
	private $_inquiry_contents = array();
	
	/**
	 * 构造函数
	 *
	 * @param array
	 */
	public function __construct() {
		//是否第一次使用?
		if(isset($_SESSION['inquiry_contents'])) {
			$this->_inquiry_contents = $_SESSION['inquiry_contents'];
		} else {
			$this->_inquiry_contents['total_items']= 0;
			$this->_inquiry_contents['delivery']= '';
			$this->_inquiry_contents['currency']= '';
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
				$this->_log("inquiry_no_items_insert");
			}
			return FALSE;
		}
		
		//物品参数处理
		$save_inquiry = FALSE;
		if(isset($items['id'])) {
			if($this->_insert($items) === TRUE) {
				$save_inquiry = TRUE;
			}
		} else {
			foreach($items as $val) {
				if(is_array($val) && isset($val['id'])) {
					if($this->_insert($val) == TRUE) {
						$save_inquiry = TRUE;
					}
				}
			}
		}
		//当插入成功后保存数据到session
		if($save_inquiry) {
			$this->_save_inquiry();
			return TRUE;
		}
		
		return FALSE;
	}
	/**
	 * 获取询价类别条数
	 *
	 * @return	int
	 */
	public function total_items() {
		return $this->_inquiry_contents['total_items'];
	}
	/**
	 * 获取询价列表已经存在的物品
	 *
	 * @return	int
	 */
	public function total_ids() {
		$totalid = array();
		foreach($this->_inquiry_contents as $item){
			if(is_array($item)){
				$totalid[$item['rowid']]=$item['id'];
			}
		}
		return $totalid;
	}
	/**
	 * 获取询价列表
	 * @return	array
	 */
	public function contents() {
		return $this->_inquiry_contents;
	}
	/*
	 * 获取交货地和交易币种
	 */
	public function getDelCur() {
		return array('delivery'=>$this->_inquiry_contents['delivery'],
				'currency'=>$this->_inquiry_contents['currency']);
	}
	/**
	 * 清空询价列表
	 *
	 */
	public function destroy() {
		unset($this->_inquiry_contents);
		$this->_inquiry_contents['total_items']= 0;
		$this->_inquiry_contents['delivery']   = '';
		$this->_inquiry_contents['currency']   = '';
		unset($_SESSION['inquiry_contents']);
		unset($_SESSION['inquiry_subtract']);
		unset($_SESSION['inquirynumber']);
	}
	
	/**
	 * 删除询价列表物品信息
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	public function delete($rowid) {
		//既跟新物品数量为0
		unset($this->_inquiry_contents[$rowid]);
		$this->_save_inquiry();
		return $this->_inquiry_contents['total_items'];
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
				$this->_log("inquiry_no_data_insert");
			}
			return FALSE;
		}
		
		//如果物品参数无效
		if( ! isset($items['id']) || ! isset($items['part_no']) || ! isset($items['hope_number']) || ! isset($items['hope_price'])) {
			if($this->debug === TRUE) {
				$this->_log("inquiry_items_data_invalid");
			}
			return FALSE;
		}

		//物品ID正则判断
		if( ! preg_match($this->product_id_rule, $items['id'])) {
			if($this->debug === TRUE) {
				$this->_log("inquiry_items_data(id)_invalid");
			}
			return FALSE;
		}
		
		//生成物品的唯一id
		$rowid = md5($items['id']);
		//加入物品到购物车
		unset($this->_inquiry_contents[$rowid]);
		$this->_inquiry_contents[$rowid]['rowid'] = $rowid;
		foreach($items as $key => $val) {
			$this->_inquiry_contents[$rowid][$key] = $val;
		}
		//交货地和交易币种
		$this->_inquiry_contents['delivery']= $items['delivery'];
		$this->_inquiry_contents['currency']= $items['currency'];
		
		return TRUE;
	}
	/**
	 * 保存询价列表数据到session
	 * 
	 * @access	private
	 * @return	bool
	 */
	private function _save_inquiry() {
		//首先清除询价列表总物品
		unset($this->_inquiry_contents['total_items']);
		$total_items = 0;
		if(!empty($this->_inquiry_contents))
		{
			foreach($this->_inquiry_contents as $arr){
				if(is_array($arr)) $total_items++;
			}
		}
		$this->_inquiry_contents['total_items'] = $total_items;
		
		//保存购物车数据到session
		$_SESSION['inquiry_contents'] = $this->_inquiry_contents;
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
		//return @file_put_contents('inquiry_err.log', $msg, '/');
	}
}