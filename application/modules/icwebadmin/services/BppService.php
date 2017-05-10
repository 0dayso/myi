<?php
class Icwebadmin_Service_BppService {
	private $_model;
    private $_linecardModel;
	public function __construct() {
		$this->_model = new Icwebadmin_Model_DbTable_Model("bpp_stock");
        $this->_linecardModel = new Icwebadmin_Model_DbTable_BppLinecard();
	}
	/**
	 * 获取数量
	 */
	public function getRowNum($where)
	{
		$sqlstr = "SELECT count(bs.id) as num FROM bpp_stock as bs
		LEFT JOIN vendor as v ON bs.vendor_id=v.vendor_id
		LEFT JOIN product as p ON p.id = bs.part_id 
		LEFT JOIN brand as b ON b.id = p.manufacturer 
		WHERE bs.part_id != -1 AND bs.break_price > 0 AND bs.duplicated = 'N' {$where}";
		return $this->_model->QueryItem($sqlstr);
	}
	/**
	 * 获取bpp列表
	 */
	public function getBppList($offset, $perpage,$where){
		$sqlstr = "SELECT bs.*,v.vendor_name,p.part_no,b.name,blf.lead_time_hk,blf.lead_time_cn FROM bpp_stock as bs
		LEFT JOIN vendor as v ON bs.vendor_id=v.vendor_id
		LEFT JOIN product as p ON p.id = bs.part_id
		LEFT JOIN brand as b ON b.id = p.manufacturer
		LEFT JOIN bpp_location_freight as blf ON (blf.vendor_id = bs.vendor_id AND blf.location_code=bs.location_code)
		WHERE bs.part_id != -1 AND bs.break_price > 0 AND bs.duplicated = 'N' {$where} ORDER BY bs.id DESC 
		LIMIT $offset,$perpage";
		return $this->_model->Query($sqlstr);
	}
    /*
     * 获取 BPP 产品线列表
     */
    public function getBppLinecardList($offset,$perpage,$where)
    {
        $select  = $this->_linecardModel->getAdapter()->select();
        $select->from(array('b' => 'bpp_linecard'),
            array('b.id','brand_id','linecard_name','brand_name','b.updated_at'))
            ->join(array('v'=>'vendor'),'b.vendor_id = v.vendor_id','v.vendor_name');

        if(!empty($where)){

            foreach($where as $w)
            {
                $select->where($w);
            }
        }
        $select	->order('brand_name asc')
            ->limit($perpage,$offset);
        return $this->_linecardModel->getAdapter()->fetchAll($select);
    }

    public function getLinecardTotal($where)
    {
        $select =  $this->_linecardModel->select()->from(array('b'=>'bpp_linecard'),array('count(*) as total'));
        if(!empty($where)){

            foreach($where as $w)
            {
                $select->where($w);
            }
        }
        $rowset =  $this->_linecardModel->fetchRow($select);
        return $rowset->total;
    }

    /*
     * 更新by id
    */
    public function updatebyid($data,$id){
        return $this->_linecardModel->updateById($data,$id);
    }
}

?>