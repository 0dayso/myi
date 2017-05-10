<?php
class Icwebadmin_Service_SectionService
{
	/*************************************************************
	 ***		公共函数                                    ***
	**************************************************************/
	private $section;
	public function __construct(){
		$this->section = new Icwebadmin_Model_DbTable_Section();
	}
	public function getSection($Section_ID="")
	{
		$order   ="CAST(`order_id` AS SIGNED) ASC ";
		$where="(section_area_id  != '')";
		if($Section_ID) $where .=" AND section_area_id ='$Section_ID'";
		$dep_tmp=$this->section->getAllByWhere($where,$order );
		return $dep_tmp;
	}
	public function checkDectionid($Section_ID)
	{
		$re=$this->section->getRowByWhere("section_area_id  = '{$Section_ID}'");
		if(empty($re)) return false;
		else return true;
	}
	public function checkTitle($titl,$Section_ID='')
	{
		if($Section_ID) $where =" AND section_area_id !='{$Section_ID}'";
		$re=$this->section->getRowByWhere("section_area_des  = '{$titl}'".$where);
		if(empty($re)) return false;
		else return true;
	}
}