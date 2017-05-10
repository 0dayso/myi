<?php
class Default_Service_ApplicationsService
{
	private $_sprodModer;
	public function __construct()
	{
		$this->_sprodModer = new Default_Model_DbTable_Model('solution_product');
	}
	/**
	 * 根据核心器件获取推荐方案
	 * (sp.type='core' OR sp.type='rim') 
	 */
	public function getSolutionByCode($prod_id,$limit='LIMIT 0 , 10'){
		return $this->_sprodModer->Query("SELECT DISTINCT(s.id),s.title FROM `solution_product` as sp
				LEFT JOIN solution as s ON s.id=sp.solution_id
				WHERE sp.prod_id ='{$prod_id}' AND (sp.type='core' OR sp.type='rim')  {$limit} ");
	}
	/*
	 * 根据方案id和type获取数据
	 */
	public function getPart($sid,$type='')
	{
		$typesql = '';
		if($type) $typesql = " AND type='{$type}'";
		return $this->_sprodModer->getAllByWhere("solution_id = '{$sid}' $typesql AND status=1 AND prod_id>0 ");
	}
	/*
	 * 根据方案id和type获取产品id
	*/
	public function getPartID($sid,$type='')
	{
		$arr = array();
		$re = $this->getPart($sid,$type);
		if($re){
			foreach($re as $v){
				$arr[] = $v['prod_id'];
			}
		}
		return $arr;
	}
	/**
	 * 获取相关视频
	 */
	public function getOutside($sid,$orderbystr='',$limit=''){
		$sql = "SELECT osl.*
		FROM relevance as rel
		LEFT JOIN outside_link as osl ON osl.id = rel.t_id 
		WHERE rel.status=1 AND osl.status=1 AND rel.f_id='{$sid}' AND rel.f_type='solution' AND rel.t_type='outside' {$orderbystr} $limit";
	    return $this->_sprodModer->Query($sql);
	}
	/**
	 * 获取相关资讯
	 */
	public function getNews($sid,$orderbystr='',$limit=''){
		$sql = "SELECT n.id,n.title,n.image
		FROM relevance as rel
		LEFT JOIN news as n ON n.id = rel.t_id
		WHERE rel.status=1 AND n.status=1 AND rel.f_id='{$sid}' AND rel.f_type='solution' AND rel.t_type='news' {$orderbystr} $limit";
		return $this->_sprodModer->Query($sql);
	}
	/**
	 * 获取相关项目
	 */
	public function getCase($sid,$orderbystr='',$limit=''){
		$sql = "SELECT sc.*,scom.company_name,scom.company_profile
		FROM solution_case as sc
		LEFT JOIN solution_company as scom ON scom.id = sc.company_id
		WHERE sc.solution_id='{$sid}' AND sc.status=1  AND scom.status=1 {$orderbystr} $limit";
		return $this->_sprodModer->Query($sql);
	}
	/**
	 * 获取设计文档
	 */
	public function getDocument($sid){
		$array = array();
		$sql = "SELECT sd.*,dt.type_name
		FROM solution_document as sd
		LEFT JOIN document_type as dt ON dt.id = sd.doc_type
		WHERE sd.solution_id='{$sid}' AND sd.status=1  AND dt.status=1";
		$re = $this->_sprodModer->Query($sql);
		foreach($re as $v){
			$array[$v['doc_type']][] = $v;
		}
		return $array;
	}
	/**
	 * 获取设计文档 by id
	 */
	public function getDocumentByid($id){
		$array = array();
		$sql = "SELECT sd.*,dt.type_name
		FROM solution_document as sd
		LEFT JOIN document_type as dt ON dt.id = sd.doc_type
		WHERE sd.id='{$id}' AND sd.status=1  AND dt.status=1";
		return $this->_sprodModer->QueryRow($sql);
	}
	/**
	 * 获取技术支持
	 */
	public function getEngineer($sid){
		$sql = "SELECT e.*,se.role
		FROM solution_engineer as se
		LEFT JOIN engineer as e ON e.id = se.engineer_id
		WHERE se.solution_id='{$sid}' AND se.status=1  AND e.status=1";
		return $this->_sprodModer->Query($sql);
	}
	/**
	 * 获取用户权限
	 */
	public function getSolutionrule($sid)
	{
		$userruleService = new Default_Service_UserruleService();
		$solutionRule = $userruleService->getRuleByType('solution');
		if(empty($solutionRule)){
			//按单个
			$ruled = $userruleService->getRuleDetailed($sid,'solution');
			if($ruled['areas'] && $ruled['rights'] && $ruled['apply']==2){
				$solutionrule = $userruleService->getSolutionRuleBys($ruled['areas'],$ruled['rights']);
			}
		}else{
			//访问权限  1、按组
			$solutionrule = $userruleService->getSolutionRule($solutionRule);
		}
		return $solutionrule;
	}
}