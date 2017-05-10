<?php
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/default/cart.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/fpdf/pdfclass.php';
class CartController extends Zend_Controller_Action
{
	//获取购物车寄存
    private $_cartService;
    private $_proService;
    public function init()
    {
    	$_SESSION['menu'] = 'cart';
    	//获取购物车寄存，要在$this->cart = new Cart()前
    	$this->_cartService = new Default_Service_CartService();
    	$this->_cartService->getCartDeposit();
        
    	$this->_proService = new Default_Service_ProductService();
        /* Initialize action controller here */
    	$this->filter  = new MyFilter();
    	$this->cart    = new Cart();
    	$this->fun     = $this->view->fun =new MyFun();
    	$this->cdModel = new Default_Model_DbTable_CartDeposit();
    	//客户端ip
    	$this->ip = $this->fun->getIp();
        $this->config = Zend_Registry::get('config');
        $this->_defaultlogService = new Default_Service_DefaultlogService();
    }

    public function indexAction()
    {
    	$this->view->items = $this->cart->contents_by_delivery();
    	$this->view->pdfkey = md5(session_id());
    	//$this->cart->destroy();
    	//echo '<pre>';
    	//print_r($this->view->items);
    	//print_r($_SESSION['cart_subtract']);
    }
    /*
     * 头部询价下拉
    */
    public function dropdownAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->view->items = $this->cart->contents();
    	$_SESSION['cartnumber'] = count($this->view->items);
    	//新版本
    	if(isset($_SESSION['new_version'])){
    		$this->fun->changeView($this->view,$_SESSION['new_version']);
    	}
    }
    /**
     * 加入购物车
     *
     * @access 	public
     * @param
     * @return
     */
    public function addAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id    = (int)($formData['id']);
    		$buynum = (int)($formData['buynum']);
    		$delivery_place = $formData['delivery_place'];
    		$bpp_stock_id = (int)($formData['bpp_stock_id']);
			$product = $this->_proService->getStockProd($id);
			$product = $this->fun->filterProduct($product,$bpp_stock_id);
    		if(empty($product) || !in_array($delivery_place,array('SZ','HK'))){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"   =>'参数错误'));
    			exit;
    		}
    	    if(!$product['surplus_stock_sell'] && $buynum < $product['moq']){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'不要低于最少购买数量:'.$product['moq'].'！'));
    			exit;
    		}
    		if(!$buynum) $buynum = $product['moq'];
    		$surplus = 0;
    		$lead_time_show = '';
    		$stock_type = '<b class="fontgreen">现货</b>';
    		$stock      = 0;
    		if($delivery_place == 'SZ'){
    			if($buynum > $product['f_stock_sz'])
    			{
    				$surplus = 1;
    				$lead_time_show = $product['lead_time'];
    				$stock_type = '<b class="fontorange">订货</b';
    				$stock      = $product['f_stock_sz'];
    			}
    		}elseif($delivery_place == 'HK'){
    			if($buynum > $product['f_stock_hk'])
    			{
    				$surplus = 1;
    				$lead_time_show = $product['lead_time'];
    				$stock_type = '<b class="fontorange">订货</b';
    				$stock      = $product['f_stock_hk'];
    			}
    		}
    		if($surplus==1){
    			if($product['can_sell']!=1){
    			echo Zend_Json_Encoder::encode(array("code"=>101,
    					"message"       =>'购买数量超出库存',
    					"buynum"        =>$buynum,
    					"surplus"       =>$surplus,
    					"lead_time_show"=>$lead_time_show,
    					"stock_type"    =>$stock_type,
    					"stock"         =>$stock
    			));
    			exit;
    			}
    		}
    		//加入购物车
    		$re = $this->_cartService->addCartlist($id, $buynum, $product,$delivery_place,$bpp_stock_id);
    		//寄存购物车
    		$this->cdModel->insert(array('ip'=>$this->ip,
    				'prod_id'=>$product['id'],
    				'number'=>$buynum,
    				'delivery'=>$delivery_place,
    				'bpp_stock_id'=>$bpp_stock_id,
    				'created'=>time()));
    		if($re){
  
    			//购物车记录数，用于头部显示
    			$_SESSION['cartnumber']=$this->_cartService->total_items();
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'加入购物车成功',"cartnumber"=>$_SESSION['cartnumber']));
    			exit;
    		}else{
   				echo Zend_Json_Encoder::encode(array("code"=>4, "message"=>'加入购物车失败'));
    			exit;
    		 }
    	}else $this->_redirect('/');
    }
     /**
     * 重新计算,用于多选按钮的改变
     *
     * @access 	public
     * @param
     * @return
     */
    public function recalculatedAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$rowid    = $this->filter->pregHtmlSql($formData['rowid']);
    		$qty      = (int)($formData['qty']);
    		$ids      = $this->cart->total_ids();
    		$total = $this->cart->contents();
    		$product = $this->_proService->getStockProd($ids[$rowid]);
    		if(empty($product)){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"   =>'参数错误'));
    			exit;
    		}
    		$product = $this->fun->filterProduct($product,$total[$rowid]['options']['bpp_stock_id']);
    		if(!$product['surplus_stock_sell'] && $qty < $product['moq']){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'不要低于最少购买数量:'.$product['moq'].'！'));
    			exit;
    		}
    		
    		if($total[$rowid]['delivery_place'] == 'SZ'){
    			
    			if($qty > $product['f_stock_sz'])
    			{
    				$surplus = 1;
    			}
    		}elseif($total[$rowid]['delivery_place'] == 'HK'){
    			if($qty > $product['f_stock_hk'])
    			{
    				$surplus = 1;
    			}
    		}
    		if($surplus==1){
    			if($product['can_sell']!=1){
    			echo Zend_Json_Encoder::encode(array("code"=>0,
    					"message"       =>'很抱歉，库存不足',
    					'id'            =>$ids[$rowid],
    					'surplus'       =>$surplus,
    					"price"         =>number_format($total[$rowid]['byprice'],DECIMAL),
    					"itemtotal"     =>number_format($total[$rowid]['byprice']*$total[$rowid]['qty'],DECIMAL)
    			));
    			exit;
    			}
    		}
    		
    		$this->cart->update(array('rowid' => $rowid,'qty' =>$qty));
    		$total = $this->cart->contents();
    		$surplus = 0;
    		//寄存购物车
    		$this->cdModel->update(array('number'=>$total[$rowid]['qty']), "ip='{$this->ip}' AND prod_id='".$total[$rowid]['id']."'");

            echo Zend_Json_Encoder::encode(array("code"=>0, 
    						"message"       =>'更新成功',
            		        'id'            =>$ids[$rowid],
            		        'surplus'       =>$surplus,
            			    "price"         =>number_format($total[$rowid]['byprice'],DECIMAL),
    						"itemtotal"     =>number_format($total[$rowid]['byprice']*$total[$rowid]['qty'],DECIMAL)
							));
    		exit;
    		
    	}else $this->_redirect('/');
    }
    /**
     * 产品详细页重新计算
     *
     * @access 	public
     * @param
     * @return
     */
    public function calculateAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$partid         = (int)($formData['partid']);
    		$buynum         = (int)($formData['buynum']);
    		$delivery_place = $formData['delivery_place'];
    		$product = $this->_proService->getStockProd($partid);
    		if(empty($product) || !in_array($delivery_place,array('SZ','HK'))){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"   =>'参数错误'));
    			exit;
    		}
    		$product = $this->fun->filterProduct($product);
    		if(!$product['surplus_stock_sell'] && $buynum < $product['moq']){
    			echo Zend_Json_Encoder::encode(array("code"=>200,"message"=>'不要低于最少购买数量:'.$product['moq'].'！'));
    			exit;
    		}
    		$surplus = 0;
    		$lead_time_show = '';
    		$stock_type = '<b class="fontgreen">现货</b>';
    		$stock      = 0;
    		if($delivery_place == 'SZ'){
    			if($buynum > $product['f_stock_sz'])
    			{
    				$surplus = 1;
    				$lead_time_show = $product['lead_time'];
    				$stock_type = '<b class="fontorange">订货</b';
    				$stock      = $product['f_stock_sz'];
    			}else{
    				$lead_time_show = $product['f_lead_time_cn'];
    			}
    			$byprice = $this->fun->getPrice($product['break_price_rmb'], $buynum);
    		}elseif($delivery_place == 'HK'){
    			if($buynum > $product['f_stock_hk'])
    			{
    				$surplus = 1;
    				$lead_time_show = $product['lead_time'];
    				$stock_type = '<b class="fontorange">订货</b';
    				$stock      = $product['f_stock_hk'];
    			}else{
    				$lead_time_show = $product['f_lead_time_hk'];
    			}
    			$byprice = $this->fun->getPrice($product['break_price'], $buynum);
    		}
    		//计算价格
    		$total   = $this->fun->formnum($byprice*$buynum);
    		echo Zend_Json_Encoder::encode(array("code"=>0,
    				"message"       =>'计算成功',
    				"price"         =>$this->fun->formnum($byprice,5),
    				"total"         =>$total,
    				"buynum"        =>$buynum,
    				"surplus"       =>$surplus,
    				"lead_time_show"=>$lead_time_show,
    				"stock_type"    =>$stock_type,
    				"stock"         =>$stock
    		));
    	}
    }
    /**
     * 选择按钮改变
     *
     * @access 	public
     * @param	
     * @return 	bool
     */
    public function changeitemAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$rowids    = $formData['rowids'];
    		$checked  = (string)$formData['checked'];
    		$total = $this->cart->contents($this->freight);
    		$ids      = $this->cart->total_ids();
    		if($checked=='false')
    		{
    			$updatearray=$arraytmp=array();
    			if(is_array($rowids))
    			{
    				foreach($rowids as $v)
    				{
    					$arraytmp['rowid'] = $v;
    					$arraytmp['qty']   = 0;
    					$updatearray[] = $arraytmp;
    				}
    			}else{
    				$updatearray['rowid'] = $rowids;
    				$updatearray['qty']   = 0;
    			}
    			$re = $this->cart->update($updatearray);
    			
    		}else $re=true;
    		if($re){
    		   $totalnew = $this->cart->contents($this->freight);
    		   $this->cart->setsubtract($totalnew);
    		    //重新赋值
    			$this->cart->setvalue($total);
    			$_SESSION['cartnumber']=$total['total_items'];
    			echo Zend_Json_Encoder::encode(array("code"=>0,
    					"message"       =>'更新成功',
    					"total_quantity"=>$totalnew['total_quantity']==''?0:$totalnew['total_quantity'],
    					"freight"       =>number_format($totalnew['freight']==''?0:$totalnew['freight'],DECIMAL),
    					"cart_total"    =>number_format($totalnew['cart_total']==''?0:$totalnew['cart_total'],DECIMAL)
						));
    		    exit;
    	    }else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'更新失败'));
    		    exit;
    		}
    	}else $this->_redirect('/');
    }
    /**
     * 删除购物车记录
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    function delectcartAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$this->fun = new MyFun();
    	
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$rowids    = $formData['rowids'];
    		$updatearray=$arraytmp=array();
    		$ids = $this->cart->total_ids_place();
    		if(is_array($rowids))
    		{
    			foreach($rowids as $v)
    			{
    				$arraytmp['rowid'] = $v;
    				$arraytmp['qty']   = 0;
    				$updatearray[] = $arraytmp;
    				
    				//删除购物车寄存
    				$this->_cartService->delectCart($ids[$v]);
    			}
    		}else{
    				$updatearray['rowid'] = $rowids;
    				$updatearray['qty']   = 0;
    				//删除购物车寄存
    				$this->_cartService->delectCart($ids[$rowids]);
    		}
    		$re = $this->cart->update($updatearray);
    		if($re){
    			$cart=$this->cart->contents();
    			$_SESSION['cartnumber']=count($cart);
    			echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'删除成功',));
    		    exit;
    	    }else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'删除失败'));
    		    exit;
    		}
    	}else $this->_redirect('/');
    }
    /**
     * 更改购物车单条记录的交货地
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    function changeplaceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$this->fun = new MyFun();
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$rowids    = $formData['rowids'];
    		$cart_all = $this->cart->contents();
    		if($cart_all[$rowids]){
    			$product = $this->_proService->getStockProd($cart_all[$rowids]['pord_id']);
    			if(empty($product)){
    				echo Zend_Json_Encoder::encode(array("code"=>200,"message" =>'参数错误'));
    				exit;
    			}
    			$bpp_stock_id = $cart_all[$rowids]['options']['bpp_stock_id'];
    			$product = $this->fun->filterProduct($product,$bpp_stock_id);
    			$buynum = $cart_all[$rowids]['qty'];
    			if($cart_all[$rowids]['delivery_place']=='SZ') {
    				$delivery_place = 'HK';
    				if(!$product['f_show_price_hk']){
    					echo Zend_Json_Encoder::encode(array("code"=>200,"message" =>'很抱歉，此商品只支持在国内交货'));
    					exit;
    				}
    			}
    			elseif($cart_all[$rowids]['delivery_place']=='HK') {
    				$delivery_place = 'SZ';
    				if(!$product['f_show_price_sz']){
    					echo Zend_Json_Encoder::encode(array("code"=>200,"message" =>'很抱歉，此商品只支持在香港交货'));
    					exit;
    				}
    			}
    			else{
    				echo Zend_Json_Encoder::encode(array("code"=>200,"message" =>'参数错误'));
    				exit;
    			}
    			
    			//删除购物车寄存
    			$this->_cartService->delectCart($cart_all[$rowids]['id']);
    			$re = $this->cart->update(array('rowid' => $rowids,'qty'  => 0));
    			if($re){
    				//加入购物车
    				$newcartService = new Default_Service_CartService();
    				$re = $newcartService->addCartlist($product['id'], $buynum, $product,$delivery_place,$bpp_stock_id);
    			   //寄存购物车
    			    $this->cdModel->insert(array('ip'=>$this->ip,
    					'prod_id'=>$product['id'],
    					'number'=>$buynum,
    					'delivery'=>$delivery_place,
    			    	'bpp_stock_id'=>$bpp_stock_id,
    					'created'=>time()));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更改交货地成功'));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>4, "message"=>'更改交货地失败'));
    				exit;
    			}
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'参数错误'));
    			exit;
    		}
    	}else $this->_redirect('/');
    }
    
    /**
     * 结算时检查订单
     *
     * @access 	public
     * @param
     * @return 	bool
     */
    function checkorderAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    	   $formData = $this->getRequest()->getPost();
    	   $delivery_place = $formData['delivery_place'];
    	   $items = $this->cart->contents_by_delivery();
    	   if(!empty($items[$delivery_place]))
    	   {
    	      foreach($items[$delivery_place] as $pordarr){
    	      	 $product = $this->_proService->getInqProd($pordarr['pord_id']);
    	      	 $re = $this->_cartService->checkProdInCart($product,$delivery_place,$pordarr);
    	      	 if(!$re){
    	      	 	echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'很抱歉，购物车数据过期，请清空购物车再购买。'));
    	      	 	exit;
    	      	 }elseif($re==1){
    	      	 	
    	      	 	echo Zend_Json_Encoder::encode(array("code"=>102, "message"=>'您购买产品其中有购买产品数量超过库存数，需要向工厂订货，预计货期会延长，确定需要继续购买吗?','items'=>$this->fun->encrypt_aes($delivery_place),'key' => md5(session_id())));
    	      	 	exit;
    	      	 	
    	      	 }
    	      }
    	      echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'订单有效','items'=>$this->fun->encrypt_aes($delivery_place),'key' => md5(session_id())));
    	      exit;
    	   }else{
    	      echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'很抱歉，购物车数据过期，请清空购物车再购买。'));
    	     exit;
    	   }
    	}else $this->_redirect('/cart');
    }
    /**
     * 打开添加到购物车
     */
    function inputnumAction(){
    	$this->_helper->layout->disableLayout();
    	$partid = (int)$_GET['partid'];
    	$prodModel = new Default_Model_DbTable_Product();
    	$sqlstr ="SELECT pro.id,pro.part_no,pro.part_img,pro.manufacturer,pro.part_level1,pro.part_level2,pro.part_level3,
    			pro.break_price,pro.moq,pro.mpq,pro.break_price_rmb,pro.sz_stock,pro.hk_stock,pro.bpp_stock,pro.bpp_cover,pro.lead_time,
    			pro.lead_time_cn,pro.lead_time_hk,pro.surplus_stock_sell,pro.special_break_prices,
    			pro.can_sell,pro.sz_cover,pro.hk_cover,pro.show_price,pro.price_valid,pro.price_valid_rmb,
    			pc.name as cname,br.name as bname
		FROM product as pro 
    	LEFT JOIN prod_category as pc ON pro.part_level3=pc.id
    	LEFT JOIN brand as br ON pro.manufacturer=br.id
		WHERE pro.id=:partid AND pro.status='1'";
    	$this->view->prodarr  = $prodModel->getByOneSql($sqlstr, array('partid'=>$partid));
    	$this->view->number   = (int)$_GET['number'];
    	$this->view->currency = $_GET['currency'];
    }
    /**
     * 加入购物车后提示框
     */
    function showAction(){
    	$this->view->items = $this->cart->contents();
    	$this->_helper->layout->disableLayout();
    }
    /**
     * 将购物车生成PDF
     */
    public function createpdfAction(){
        $this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$pdfkey=$_GET['pdfkey'];
		if($pdfkey != md5(session_id())) $this->_redirect('/cart');
		//获取购物车
		$contents = $this->cart->contents();
		if(!$contents['cart_total']) $this->_redirect('/cart');
		
		$pdf = new PDF ();
		$pdf->Open ();
		$pdf->AddGBFont ( 'simhei','微软雅黑');
		$pdf->AliasNbPages ();
		$pdf->AddPage ();
		// Log
		$pdf->Image ( 'images/default/logo.jpg', 10, 6, 38 );
		$pdf->Ln ( 6 );
		$pdf->SetFont ( 'simhei', 'B', 8 );
		
		// Colors, line width and bold font
		$pdf->SetFillColor ( 153, 153, 153 );
		$pdf->SetTextColor ( 255 );
		$pdf->SetDrawColor ( 0, 0, 0 );

		// Header
		$w = array (10,40,30,20,20,20 );
		$header = array ('序号','Part No.','品牌','购买数量','单价','小计');
		for($i = 0; $i < count ( $header ); $i ++) {
			$pdf->Cell ( $w [$i], 5, iconv ( "UTF-8", "GB2312", $header [$i] ), 1, 0, 'C', 1 );
		}
		$pdf->Ln ();
		// Color and font restoration
		$pdf->SetFillColor ( 224, 235, 255 );
		$pdf->SetTextColor ( 0 );
		$pdf->SetFont ( 'simhei', '', 8 );
		// Data
		$tmp=0;
		foreach($contents as $item) {
			if(is_array($item)){
			$tmp++;
			$pdf->Cell ( $w [0], 5, $tmp, 'LRTB', 0, 'C' );
			
			$mate = iconv ( "UTF-8", "GB2312", $item['part_no'] );
			$pdf->Cell ( $w [1], 5, "$mate", 'LRTB', 0, 'C' );
			
			$mate = iconv ( "UTF-8", "GB2312", $item['options']['brand']);
			$pdf->Cell ( $w [2], 5, "$mate", 'LRTB', 0, 'C' );
			
			$mate = iconv ( "UTF-8", "GB2312", $item['qty'] );
			$pdf->Cell ( $w [3], 5, "$mate", 'LRTB', 0, 'C' );
			
			$mate = iconv ( "UTF-8", "GB2312", '￥'.number_format($item['price'],DECIMAL) );
			$pdf->Cell ( $w [4], 5, "$mate", 'LRTB', 0, 'C' );
			
			$mate = iconv ( "UTF-8", "GB2312", '￥'.number_format($item['subtotal'],DECIMAL) );
			$pdf->Cell ( $w [5], 5, "$mate", 'LRTB', 1, 'C' );
			}
		}
		$pdf->SetTextColor ( 0);
		$mate = iconv ( "UTF-8", "GB2312", '产品金额总计（不含运费及包装费）：￥'.number_format($contents['cart_total'],DECIMAL) );
		$pdf->Cell ( array_sum($w), 5, "$mate", 'LRTB', 1, 'R' );
		
		$pdf->Ln ();
		$pdf->SetTextColor ( 0 );
		$des = iconv ( "UTF-8", "GB2312", '由IC易站提供。' );
		$pdf->WriteHTML ( "$des<a href='".$this->config->email->foot_url."'>".$this->config->email->foot_url."</a>." );
		$pdf->Output ();
		exit ();
    }
    /**
     * 超过购买数量跳出提示
     */
    public function outridebuynumAction()
    {
    	$this->commonconfig = Zend_Registry::get('commonconfig');
    	$this->_helper->layout->disableLayout();
    	$this->view->partid = $_GET['id'];
    	$this->view->stock  = $_GET['stock'];
    	$this->view->lead_time = $this->_proService->getLeadtime($_GET['id']);
    	$this->view->tel    = $this->commonconfig->email->foot_tel;
    }
    /**
     * 快速订购
     */
    public function quickorderAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData    = $this->getRequest()->getPost();
    		$keyword = strtoupper(trim($this->filter->pregHtmlSql($formData['q_partno'])));
    		if(!empty($keyword)){
    			//产品总数
    			$this->_prodService = new Default_Service_ProductService();
    			$re = $this->_prodService->getPartTmp();
    			$total = 0;$searcharr = array();
    			foreach($re as $v){
    				if(stristr($v['part_no'],$keyword)) {
    					$searcharr[] = $v;
    				}
    			}
    			$total = count($searcharr);
    			//记录搜索日志
    			$this->_defaultlogService->addLog(array('log_id'=>'A','temp2'=>$keyword,'temp3'=>$total,'temp4'=>'快速订购：'.$total));
    			//只搜索到一个
    			if($total==1){
    				//判断是否可以直接购买
    				$prodarr = $this->_prodService->getPartById($searcharr[0]['id']);
    				if($prodarr){
    					//过滤产品
    					$data = $this->fun->filterProduct($prodarr);
    					if($data['f_show_price_sz'] || $data['f_show_price_hk']){
    						//可以购买
    						echo Zend_Json_Encoder::encode(array("code"=>0, 'id'=>$data['id'],'message'=>'找到一个型号，弹出购买框'));
    						exit;
    					}elseif(!$data['noinquiry']){
    						//可以询价
    						echo Zend_Json_Encoder::encode(array("code"=>1, 'id'=>$data['id'],'message'=>'为您找到一个型号，但不能现货购买，您需要询价吗？'));
    						exit;
    					}else{
    						//不可以购买也可以询价
    						echo Zend_Json_Encoder::encode(array("code"=>2, 'id'=>$data['id'],'message'=>'不能购买也不能询价','url'=>$data['f_produrl']));
    						exit;
    					}
    				}else{
    					//搜索为空
    					echo Zend_Json_Encoder::encode(array("code"=>200, 'message'=>'很抱歉，没有找到型号。您想告诉我们吗？我们为您寻找。'));
    					exit;
    				}
    			}elseif($total > 1){
    				//搜索到多个跳转到搜索页
    				echo Zend_Json_Encoder::encode(array("code"=>100, 'message'=>'搜索到多个型号'));
    				exit;
    			}else{
    				//搜索为空
    				echo Zend_Json_Encoder::encode(array("code"=>200, 'message'=>'很抱歉，没有找到型号。您想告诉我们吗？'));
    				exit;
    			}
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>400, 'message'=>'请输入产品型号！'));
    		    exit;
    		}
    		
    	}
    }
}

