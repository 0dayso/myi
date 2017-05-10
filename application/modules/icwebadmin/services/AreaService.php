<?php
class Icwebadmin_Service_AreaService
{
	/*************************************************************
	 ***		公共函数                                    ***
	**************************************************************/
	private $area;
	public function __construct(){
		$this->area = new Icwebadmin_Model_DbTable_Sectionarea();
	}
	public function checkDectionid($Area_ID)
	{
		$re=$this->area->getRowByWhere("staff_area_id  = '{$Area_ID}'");
		if(empty($re)) return false;
		else return true;
	}
	public function checkTitle($titl,$Area_ID='')
	{
		if($Area_ID) $where =" AND staff_area_id !='{$Area_ID}'";
		$re=$this->area->getRowByWhere("staff_area_des  = '{$titl}'".$where);
		if(empty($re)) return false;
		else return true;
	}
	/*
	 * 获取区域标题
	*/
	public function getTitle($Section_ID)
	{
		$tmp = $this->area->getRowByWhere("(status=1) AND (staff_area_id='".$Section_ID."')");
		return $tmp['staff_area_des'];
	}
}