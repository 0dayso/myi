<?php
class Icwebadmin_Service_ProductatsService
{
	private $_prodatsModer;
	private $_atspriceModer;
	public function __construct()
	{
		$this->_prodatsModer = new Icwebadmin_Model_DbTable_ProductAts();
		$this->_atspriceModer = new Icwebadmin_Model_DbTable_Model("oa_atsprice");
	}
	/*
	 * 获取总数
	*/
	public function getNum($where=''){
		$allver = $this->_prodatsModer->getByOneSql("SELECT count(id) as num FROM product_ats WHERE id!='' ".$where);
		return $allver['num'];
	}

    public function getAtsTotal($where)
    {
        $select =  $this->_prodatsModer->select()->from(array('b'=>'product_ats'),array('count(*) as total'));
        if(!empty($where)){

            foreach($where as $w)
            {
                $select->where($w);
            }
        }
        $rowset =  $this->_prodatsModer->fetchRow($select);
        return $rowset->total;
    }
    /*
     * 获取 ATS 产品线
     */
    public function getAtsList($offset,$perpage,$where)
    {
        $select  = $this->_prodatsModer->getAdapter()->select();
        $select->from(array('b' => 'product_ats'),
            array('b.*'));
        if(!empty($where)){

            foreach($where as $w)
            {
                $select->where($w);
            }
        }
        $select	->order('wgbez asc')
            ->limit($perpage,$offset);
        return $this->_prodatsModer->getAdapter()->fetchAll($select);
    }
	public function getById($id)
	{
		 $where   = ' id='.$id;
		return $this->_prodatsModer->getRowByWhere($where);
	}
	
	/*
	 * 获取记录bysql
	*/
	public function getAllBySql($offset='',$perpage='',$where=''){
		$limit = '';
		if($offset && $perpage) $limit = "LIMIT {$offset},{$perpage}";
		$sqlstr ="SELECT * FROM product_ats WHERE id!='' {$where} {$limit}";
		return	$this->_prodatsModer->getBySql($sqlstr);
	}
	/*
	 * 更新by id
	*/
	public function updatebyid($data,$id){
		return $this->_prodatsModer->updateById($data,$id);
	}
	/*
	 * 更新All
	*/
	public function updateall(){
		return $this->_prodatsModer->updateBySql("UPDATE product_ats SET status=0");
	}
	/*
	 * 获取总数
	*/
	public function getAtsPriceNum($where=''){
		return $this->_atspriceModer->QueryItem("SELECT count(ap.id) as num FROM oa_atsprice as ap WHERE ap.id!='' ".$where);
	}
	public function getAtsPrice($offset,$perpage,$sql=''){
		return $this->_atspriceModer->Query("SELECT ap.*,po.id as partid,po.part_no,po.break_price,po.break_price_rmb,po.moq,po.mpq,br.name as brandname 
				FROM oa_atsprice as ap
				LEFT JOIN product as po ON po.part_no=replace(ap.material,'+','/NOPB')
				LEFT JOIN brand as br ON po.manufacturer=br.id
				WHERE ap.id!='' ".$sql." LIMIT {$offset},{$perpage}");
	}
} 