<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_ProductService
{
	private $_prodModer;
	public function __construct()
	{
		$this->_prodModer = new Icwebadmin_Model_DbTable_Product();
	}
	/*
	 * 获取part no By like
	*/
	public function getPartNoLike($keyword)
	{
	    $where="`part_no` LIKE '%".$keyword. "%'";
    	$sqlstr ="SELECT `part_no`  FROM `product`
    	WHERE status!=0 AND {$where}";
    	$soArr = $this->_prodModer->getBySql($sqlstr);
    	for($i=0;$i<count($soArr);$i++)
    	{
    	    $str .= $soArr[$i]['part_no'] . "\n";
    	}
    	return $str;
	}
	/**
	 * 根据part no 查询 part id
	 */
	public function getPid($partno)
	{
		$sql ="SELECT p.id,b.name as brand FROM product as p
			   LEFT JOIN brand as b ON p.manufacturer=b.id
			   WHERE p.part_no =:partnotmp";
		return $this->_prodModer->getByOneSql($sql, array('partnotmp'=>$partno));
	}
	/*
	 * 获取part no By like
	*/
	public function getPartLike($keyword)
	{
		$where="`part_no` LIKE '%".$keyword. "%'";
		$sqlstr ="SELECT id, `part_no`  FROM `product`
		WHERE status!=0 AND {$where} limit 10";
		$soArr = $this->_prodModer->getBySql($sqlstr);
		for($i=0;$i<count($soArr);$i++)
		{
			$str[$soArr[$i]['id']]= $soArr[$i]['part_no'] ;
		}
		return $str;
	}
	/*
	 * 获取part no and brand By like
	*/
	public function getPartAndBrandLike($keyword)
	{
		$where="`part_no` LIKE '%".$keyword. "%'";
		$sqlstr ="SELECT p.id, p.part_no,b.name as brand  FROM `product` as p
		LEFT JOIN brand as b ON p.manufacturer=b.id
		WHERE p.status!=0 AND {$where} limit 10";
		$soArr = $this->_prodModer->getBySql($sqlstr);
		for($i=0;$i<count($soArr);$i++)
		{
		   $str[$soArr[$i]['id']]= $soArr[$i]['part_no'].' ('.$soArr[$i]['brand'].')' ;
		}
		return $str;
	}	
	/*
	 * 获取产品信息 By id
	*/
	public function getProductByID($id)
	{
		return $this->_prodModer->getRowByWhere("id='{$id}'");
	}

	public function getProductByWhere($offset=0,$perpage=10,$where=array())
	{
		return $this->_prodModer->getByWhere($offset,$perpage,$where);
	}
	/**
	 * 根据partno判断是否在sap中
	 */
	public function checkSap($partno)
	{
		$re = $this->_prodModer->getByOneSql("SELECT sap FROM product WHERE part_no = '$partno'");
		if($re['sap']) return true;
		else return false; 
	}
	/**
	 * 更新
	 */
	public function update($data,$where){
		return $this->_prodModer->update($data, $where);
	}
	/*
	 * 根据part id获取产品信息
	*/
	public function getInqProd($partid)
	{
		$sql ="SELECT p.*,b.name as brand FROM product as p
                LEFT JOIN brand as b ON p.manufacturer=b.id
    		    WHERE p.id=:idtmp AND p.status=1";
		$productTmp = $this->_prodModer->getBySql($sql, array('idtmp'=>$partid));
		return $productTmp[0];
	}
	/*
	 * 更加品牌和型号获取id
	*/
	public function getIdByBP($brandid,$partno)
	{
		$re = $this->_prodModer->getRowByWhere("manufacturer='{$brandid}' AND part_no='$partno'");
		return $re['id'];
	}
	/*
	 * 获取品牌By id
	*/
	public function getBrandByID($id)
	{
		$brandModer = new Icwebadmin_Model_DbTable_Brand();
		return $brandModer->getRowByWhere("id='{$id}'");
	}
	
	public function getBrandByName($name)
	{
		$brandModer = new Icwebadmin_Model_DbTable_Brand();
		return $brandModer->getRowByWhere("name='{$name}'");		
	}
	
	
	public function getAllBrand()
	{
		$brandModer = new Icwebadmin_Model_DbTable_Brand();
		return $brandModer->fetchAll()->toArray();
	}
	public function getProductByNo($part_no,$lincard_id='')
	{
		return $this->_prodModer->getRowByWhere("part_no='{$part_no}'");
	}
	
    /*
     * 获取产品总数
     */
    public function getTotalNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product as po WHERE id!='' $wheresql");
    }
    /*
     * 获取在线产品总数
    */
    public function getOnNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE status='1' $wheresql");
    }
    /*
     * 获取下架产品总数
    */
    public function getOffNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE status='0' $wheresql");
    }
    /*
     * 获取没有库存产品总数
    */
    public function getNstockNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE sz_stock <= 0 AND hk_stock <= 0 AND status=1 $wheresql");
    }
    /*
     * 获取没有库存产品总数
    */
    public function getStagedNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE staged=1 AND status=1 $wheresql");
    }
    
    /*
     * 获取有库存产品总数
    */
    public function getHstockNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE status=1 $wheresql");
    }
    /*
     * 获取ats总数
     */
    public function getAtsNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE ats=1 AND status=1 $wheresql");
    }
    /*
     * bpp总数
     */
    public function getBppNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM bpp_stock WHERE duplicated='N' AND stock > 0 $wheresql");
    }
    /*
     * 可销售总数
    */
    public function getSellNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE (break_price!='' OR break_price_rmb!='') AND (sz_stock > 0 OR hk_stock>0 OR bpp_stock>0) AND status=1 $wheresql");
    }
    /*
     * 有图片总数
    */
    public function getImgNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE part_img!='' AND part_img IS NOT NULL AND part_img!='no.gif' AND status=1 $wheresql");
    }
    /*
     * 有数据手册总数
    */
    public function getDatasheetNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE datasheet!='' AND datasheet IS NOT NULL AND status=1 $wheresql");
    }
    /*
     * 应用笔记数量
     */
    public function getNotesNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product_notes");
    }
    /*
     * 有价格总数
    */
    public function getPriceNum($wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE (break_price!='' OR break_price_rmb!='') AND status=1 $wheresql");
    }
    /*
     * 获取查询产品总数
    */
    public function getSelectNum($partno,$wheresql){
    	return $this->getNum("SELECT count(id) as num FROM product WHERE  part_no LIKE '%".$partno."%' $wheresql ");
    }
    /*
     * 获取在线产品
    */
    public function getOn($offset,$perpage,$wheresql){
    	$where =" po.status=1 $wheresql LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
    /*
     * 获取下线产品
    */
    public function getOff($offset,$perpage,$wheresql){
    	$where =" po.status=0  $wheresql LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
    /*
     * 获取缺货产品
    */
    public function getNstock($offset,$perpage,$wheresql){
    	$where =" po.sz_stock <= 0 AND po.hk_stock <= 0 AND po.status=1 $wheresql LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
    /*
     * 获取有货产品
    */
    public function getHstock($offset,$perpage,$wheresql){
    	$where =" (po.sz_stock > 0 OR po.hk_stock>0) AND po.status=1 $wheresql LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
    /*
     * 获取有滞留
    */
    public function getStaged($offset,$perpage,$wheresql){
    	$where =" po.staged=1 AND po.status=1 $wheresql LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
    
     /*
     * 获取搜索产品
    */
    public function getSelect($offset,$perpage,$partno,$wheresql){
    	$where =" po.id!='' $wheresql AND po.part_no LIKE '%".$partno."%' LIMIT {$offset},{$perpage}";
    	return	$this->getProductBySql($where);
    }
	/*
	 * 获取记录数
	*/
	private function getNum($sqlstr)
	{
		$allver = $this->_prodModer->getBySql($sqlstr);
		return $allver[0]['num'];
	}
	/*
	 * 获取记录bysql
	*/
	public function getProductBySql($where,$arr=array()){
		$sqlstr ="SELECT po.*,br.name as bname,pc.name as pcname
    	FROM product as po 
    	LEFT JOIN brand as br ON po.manufacturer=br.id
    	LEFT JOIN prod_category as pc ON po.part_level3=pc.id
    	WHERE {$where} ";
		return	$this->_prodModer->getBySql($sqlstr, $arr);
	}
	/**
	 * 获取一条产品分类
	 */
	public function getProdCategoryById($id){
		$pcModer = new Default_Model_DbTable_ProdCategory();
		$pcall = $pcModer->getRowByWhere("id='{$id}'");
		return $pcall;
	}
	/**
	 * 获取产品目录
	 */
	public function getProdCategory(){
		$pcModer = new Default_Model_DbTable_ProdCategory();
		$pcall = $pcModer->getAllByWhere ("id!=''","displayorder ASC");
		$parent = $this->getLevel($pcall, 1);
		$son1   = $this->getLevel($pcall, 2);
		$son3   = $this->getLevel($pcall, 3);
		$second = $this->getSon($parent, $son1);
		$third  = $this->getSon($son1, $son3);
		return array('first'=>$parent,'second'=>$second,'third'=>$third);
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
	 * 获取产品目录级别
	 */
	public function getProdCategoryLevel($cpid){
		$pcModer = new Default_Model_DbTable_ProdCategory();
		$re = $pcModer->getRowByWhere("id='{$cpid}'");
		return $re['level'];
	}
	/**
	 * 新品上线通知客户邮件
	 */
	public function newprodEmail($prodinfo,$serchinfo)
	{
		$this->fun = new MyFun();
		$prodinfo = $this->fun->filterProduct($prodinfo);
		//负责销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($serchinfo['uid']);
		
		$break_price='';$stock = 0;$show_price = 0;
		if($prodinfo['f_show_price_sz']){
			$show_price  = $prodinfo['f_show_price_sz'];
			$stock       = $prodinfo['f_stock_sz'];
			$break_price = $this->fun->getbreakprice_notitle_email($prodinfo['break_price_rmb'],'￥');
		}elseif($prodinfo['f_show_price_hk']){
			$show_price  = $prodinfo['f_show_price_hk'];
			$stock       = $prodinfo['f_stock_hk'];
			$break_price = $$this->fun->getbreakprice_notitle_email($prodinfo['break_price'],'$');
		}
		$mess ='<tr>
                    <td valign="top" bgcolor="#ffffff" align="center">
                        <table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                            <tbody>
                                <tr>
                                    <td valign="top"  height="30" >
                                        <div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$serchinfo['contact_name'].',</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您好，感谢您对IC易站的惠顾！</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您之前在IC易站上搜索的产品&nbsp;<strong style="color:#0055aa;font-family:\'微软雅黑\';">'.$prodinfo['part_no'].'</strong>&nbsp;已上线，&nbsp;<a href="http://www.iceasy.com'.$prodinfo['f_produrl'].'" target="_blank" style="color:#fd2323;font-size:13px;"><b>查看详情</b></a>&nbsp;。</div>     
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>

                </tr>
<!--内容-->';
if($show_price){
 $mess .='<!--新上线产品 有价格-->  
<tr>
<td valign="top" align="left" >
	<table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0">
    <tr><td bgcolor="#ffffff" height="30" valign="bottom" colspan="2"><strong style="font-size:14px; color:#000000;">&nbsp;&nbsp;新上线产品</strong></td></tr> 
    <tr>
        <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
        <td bgcolor="#f9f9f9">
        	<table width="710" border="0" cellspacing="0" bgcolor="#ffffff" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">
            <tr bgcolor="#f3f3f3">
                <th height="30">产品型号</th>
                <th>品牌</th>
                <th>产品描述</th>
                <th>库存</th>
                <th>阶梯价格</th>
            </tr>
            
            <tr bgcolor="#FFFFFF" >
                <td height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><a href="http://www.iceasy.com'.$prodinfo['f_produrl'].'" target="_blank" style="color:#0055aa;font-family:\'微软雅黑\'; "><strong >'.$prodinfo['part_no'].'</strong></a></td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$prodinfo['brand'].'</td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$prodinfo['description'].'</td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#03b000;font-family:\'微软雅黑\'">现货</strong></td>
                <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\'; ">'.$break_price.'</td>
            </tr>
      
            </table>
           
        </td>
    </tr>

    </table>
    </td>     
</td>
</tr>';
}else{
$mess .='<!--新上线产品 无价格-->  
<tr>
<td valign="top" align="left" >
	<table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0">
    <tr><td bgcolor="#ffffff" height="30" valign="bottom" colspan="2"><strong style="font-size:14px; color:#000000;">&nbsp;&nbsp;新上线产品</strong></td></tr> 
    <tr>
        <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
        <td bgcolor="#f9f9f9">
        	<table width="710" border="0" cellspacing="0" bgcolor="#ffffff" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">
            <tr bgcolor="#f3f3f3">
                <th height="30">产品型号</th>
                <th>品牌</th>
                <th>产品描述</th>
                <th>库存</th>
                <th>操作</th>
            </tr>
            
            <tr bgcolor="#FFFFFF" >
                <td height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><a href="http://www.iceasy.com'.$prodinfo['f_produrl'].'" target="_blank" style="color:#0055aa;font-family:\'微软雅黑\'; "><strong >'.$prodinfo['part_no'].'</strong></a></td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$prodinfo['brand'].'</td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';">'.$prodinfo['description'].'</td>
                <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;font-family:\'微软雅黑\';"><strong style="color:#ff6600;font-family:\'微软雅黑\'">订货</strong></td>
                <td style="border-top:1px solid #d6d6d6;font-family:\'微软雅黑\'; "><a href="http://www.iceasy.com'.$prodinfo['f_produrl'].'" target="_blank" style="color:#ffffff;font-family:\'微软雅黑\'; background:#fd2323; text-decoration:none">&nbsp;询价&nbsp;</a>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    </table>
    </td>     
</td>
</tr>';
}
		$fromname = 'IC易站';
		$title    = '您在IC易站寻找的产品#'.$prodinfo['part_no'].'#已上线，请查看';
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('new_product',$serchinfo['uid']);
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->fun->sendemail($serchinfo['email'], $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	/**
	 * 获取产品书本价
	 */
	public function getbookprice($partid)
	{
		return $this->_prodModer->getByOneSql("SELECT pb.*,p.part_no FROM product_bookprice as pb
                LEFT JOIN product as p ON p.id=pb.part_id
				WHERE pb.part_id ='{$partid}' AND pb.status=1");
	}
	/**
	 * pdn,pcn
	 */
	public function checkpdnpcn($partid){
		$arr = array('pdn'=>0,'pcn'=>0);
		$re = $this->_prodModer->getBySql("SELECT p.type FROM pdnpcn_part as pp
		LEFT JOIN pdnpcn as p ON p.id=pp.pdnpcn_id
		WHERE pp.part_id='{$partid}' AND pp.status='1'");
		foreach($re as $ra){
			if($ra['type']=='PDN') $arr['pdn'] = 1;
			if($ra['type']=='PCN') $arr['pcn'] = 1;
		}
		return $arr;
	}
	/**
	 * 获取产品关联id
	 * 
	 */
	public function getRelevanceId ($partid){
		$rearr = array();
		$sql = "SELECT id FROM relevance
		WHERE f_type='product' AND t_type='product' AND (f_id='{$partid}' OR t_id='{$partid}') ORDER BY displayorder ASC";
		$re =  $this->_prodModer->getBySql($sql);
		foreach($re as $v){
			$rearr[] = $v['id'];
		}
		return $rearr;
	}
	/**
	 * 获取关联产品id
	 */
	public function getRelevancePartid ($partid){
		$rearr = array();
		$sql = "SELECT id,f_id,t_id FROM relevance
		WHERE f_type='product' AND t_type='product' AND (f_id='{$partid}' OR t_id='{$partid}') AND status='1' ORDER BY displayorder ASC";
		$re =  $this->_prodModer->getBySql($sql);
		foreach($re as $v){
		if($v['f_id']==$partid){
		if(!in_array($v['t_id'],$rearr)) $rearr[$v['id']]=$v['t_id'];
			}else{
		if(!in_array($v['f_id'],$rearr)) $rearr[$v['id']]=$v['f_id'];
			}
		}
		return $rearr;
	}
	/**
	 * 获取关联产品信息
	 */
	public function getRelevanceInfo($partid){
		$rearr = array();
		$re =  $this->getRelevancePartid($partid);
		foreach($re as $id=>$v){
			$rearr[$id] = $this->getInqProd($v);
		}
		return $rearr;
	}
	/**
	 * 更新关联产品
	 */
	public function updateRelevance($part_id,$partarr,$relevance){
		$reModel = new Icwebadmin_Model_DbTable_Model('relevance');
		if($relevance){
			foreach($relevance as $key=>$reid){
				//update
				if($partarr[$key]){
				   $reModel->update(array("f_id"=>$part_id,"t_id"=>$partarr[$key],"status"=>'1'), "id='{$reid}'");
				}else{
				   $reModel->update(array("status"=>'0'), "id='{$reid}'");
				}
			}
			//如果超过，添加
			if(count($partarr)>count($relevance)){
				for($i=count($relevance);$i<count($partarr);$i++){
				   $reModel->addData(array('f_type'=>'product',
				   		             't_type'=>'product',
				   		             'f_id'=>$part_id,
				   		             't_id'=>$partarr[$i],
				   		             'status'=>1,
				   		             'created'=>time()));
				}
			}
		}else{ //添加
			for($i=0;$i<count($partarr);$i++){
				$reModel->addData(array('f_type'=>'product',
						't_type'=>'product',
						'f_id'=>$part_id,
						't_id'=>$partarr[$i],
						'status'=>1,
						'created'=>time()));
			}
		}
		return true;
	}
}