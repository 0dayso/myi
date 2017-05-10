<?php
class Icwebadmin_Service_CpsoglService
{
	/*************************************************************
	 ***		公共函数                                    ***
	**************************************************************/
	private $_spModel;
	private $_solModel;
	private $_solcaseModel;
	private $_solengineerModel;
	private $_solutiondocumentModel;
	public function __construct(){
		$this->_spModel = new Icwebadmin_Model_DbTable_SolutionProduct();
		$this->_solModel = new Icwebadmin_Model_DbTable_Solution();
		$this->_solcaseModel = new Icwebadmin_Model_DbTable_Model('solution_case');
		$this->_solengineerModel = new Icwebadmin_Model_DbTable_Model('solution_engineer');
		$this->_solutiondocumentModel = new Icwebadmin_Model_DbTable_Model('solution_document');
	}
	/*
	 * 添加推荐产品
	 */
	public function addSolutionProduct($solution_id,$parts,$type='rim',$bomarr=array())
	{
		$parts_arr = explode(',',$parts);
		foreach($parts_arr as $k=>$partid)
		{
			if($partid)
			{
				$data[$k]['solution_id'] = $solution_id;
				$data[$k]['prod_id'] = $partid;
				$data[$k]['type'] = $type;
				if($bomarr['dosage']){
					$data[$k]['dosage'] = $bomarr['dosage'][$k];
				}
				if($bomarr['remark']){
					$data[$k]['remark'] = $bomarr['remark'][$k];
				}
			}
		}
		$this->_spModel->addDatas($data);
	}
	/**
	 * 更新推荐产品
	 */
	public function updateSolutionProduct($solution_id,$parts,$type='rim',$bomarr=array())
	{
		//首先全部设为 status = 0
		$this->_spModel->doSql("UPDATE solution_product SET status = '0' WHERE solution_id  ='{$solution_id}' AND type='{$type}'");
		
		$all = $this->_spModel->getAllByWhere("solution_id = '$solution_id'  AND type='{$type}'");
		$parts_arr = explode(',',$parts);
		$addarr = array();
		$allnum = count($all);
		foreach($parts_arr as $k=>$partid)
		{
			if( $k < $allnum && $partid)
			{
				$upstr = '';
				if($bomarr['dosage']){
					$upstr .=",dosage='".$bomarr['dosage'][$k]."'";
				}
				if($bomarr['remark']){
					$upstr .=",remark='".$bomarr['remark'][$k]."'";
				}
				$this->_spModel->doSql("UPDATE solution_product SET status = '1',
						prod_id = '{$partid}' {$upstr} WHERE id =".$all[$k]['id']);
			}elseif($k >= $allnum){
				$addarr[$k]['solution_id'] = $solution_id;
				$addarr[$k]['prod_id']     = $partid;
				$addarr[$k]['type']        = $type;
				if($bomarr['dosage']){
					$addarr[$k]['dosage'] = $bomarr['dosage'][$k];
				}
				if($bomarr['remark']){
					$addarr[$k]['remark'] = $bomarr['remark'][$k];
				}
			}
		}
		if($addarr) $this->_spModel->addDatas($addarr);
		return true;
	}
	/**
	 * 更新成功案例
	 */
	public function updateSolutionCase($solution_id,$comid_arr,$project_arr)
	{
		//首先全部设为 status = 0
		$this->_solcaseModel->doSql("UPDATE solution_case SET status = '0' WHERE solution_id  ='{$solution_id}'");
	
		$all = $this->_solcaseModel->getAllByWhere("solution_id = '$solution_id'");
		$addarr = array();
		$allnum = count($all);
		foreach($comid_arr as $k=>$comid)
		{
			if( $k < $allnum && $comid && $project_arr[$k])
			{
				$this->_solcaseModel->update(array('company_id'=>$comid,
						'project_name'=>$project_arr[$k],
						'status'=>1,
						'created'=>time(),
						'created_by'=>$_SESSION['staff_sess']['staff_id']), "id=".$all[$k]['id']);
			}elseif($k >= $allnum && $comid && $project_arr[$k]){
			$addarr[$k]['solution_id'] = $solution_id;
			$addarr[$k]['company_id']  = $comid;
			$addarr[$k]['project_name']=$project_arr[$k];
            $addarr[$k]['status'] = 1;
			$addarr[$k]['created'] = time();
			$addarr[$k]['created_by'] = $_SESSION['staff_sess']['staff_id'];
			}
		}
		if($addarr) $this->_solcaseModel->addDatas($addarr);
		return true;
	}
	/**
	 * 更新技术支持
	 */
	public function updateSolutionEngineer($solution_id,$engid_arr)
	{
		//首先全部设为 status = 0
		$this->_solengineerModel->doSql("UPDATE solution_engineer SET status = '0' WHERE solution_id  ='{$solution_id}'");
	
		$all = $this->_solengineerModel->getAllByWhere("solution_id = '$solution_id'");
		$addarr = array();
		$allnum = count($all);
		foreach($engid_arr as $k=>$engid)
		{
			if( $k < $allnum && $engid)
			{
				$this->_solengineerModel->update(array('engineer_id'=>$engid,
						'status'=>1,
						'created'=>time()), "id=".$all[$k]['id']);
			}elseif($k >= $allnum && $engid){
				$addarr[$k]['solution_id'] = $solution_id;
				$addarr[$k]['engineer_id']  = $engid;
				$addarr[$k]['status'] = 1;
				$addarr[$k]['created'] = time();
			}
		}
		if($addarr) $this->_solengineerModel->addDatas($addarr);
		return true;
	}
	/*
	 * 获取推荐产品
	*/
	public function getSolutionProduct($solution_id,$type='rim')
	{
		$parts = '';
		$all = $this->_spModel->getAllByWhere("solution_id = '$solution_id' AND status =1 AND type='{$type}'");
		for($i = 0; $i < count($all) ;$i++)
		{
			if($i==0) $parts = $all[$i]['prod_id'];
			else $parts .= ','.$all[$i]['prod_id'];
		}
		$all['parts_str']=$parts;
		return $all;
	}
	/**
	 * 获取num
	 */
	public function getRowNum($str='')
	{
		$sqlstr = "SELECT count(sol.id) as num FROM solution as sol
		WHERE sol.id!='' {$str}";
		$allrel = $this->_solModel->getByOneSql($sqlstr);
		return $allrel['num'];
	}
	/**
	 * 获取方案记录
	 */
	public function getAllSol($offset,$perpage,$typestr='',$orderbystr='')
	{
		$limit = '';
		if($offset || $perpage) $limit ="LIMIT $offset,$perpage";
		if(!$orderbystr) $orderbystr = "ORDER BY sol.home DESC,sol.status DESC,sol.created DESC";
		$sqlstr = "SELECT sol.*,app1.name as appname1 
		FROM solution as sol
		LEFT JOIN app_category as app1 ON sol.app_level1 = app1.id
		WHERE sol.id!='' {$typestr} {$orderbystr} {$limit}";
		return $this->_solModel->getBySql($sqlstr);
	}
	/**
	 * 获取成功案例
	 */
	public function getSolCaseByid($solid){
		$sqlstr = "SELECT solc.*,scom.company_name
               FROM solution_case as solc
			   LEFT JOIN solution_company as scom ON solc.company_id = scom.id
			   WHERE solc.solution_id='$solid' AND solc.status=1";
		return $this->_solModel->getBySql($sqlstr);
	}
	/**
	 * 获取公司
	 */
	public function getSolutionCompany(){
		$sqlstr = "SELECT scom.*
		FROM solution_company as scom
		WHERE scom.status=1 ORDER BY scom.id DESC";
		return $this->_solModel->getBySql($sqlstr);
	}
	/**
	 * 获取技术支持
	 */
	public function getSolEngineer(){
		$sqlstr = "SELECT eng.*
		FROM engineer as eng
		WHERE eng.status=1 ORDER BY eng.id DESC";
		return $this->_solModel->getBySql($sqlstr);
	}
	/**
	 * 获取技术支持byid
	 */
	public function getSolEngineerByid($solid){
		$sqlstr = "SELECT sole.role,sole.engineer_id,eng.*
		FROM solution_engineer as sole
		LEFT JOIN engineer as eng ON sole.engineer_id = eng.id
		WHERE sole.solution_id='$solid' AND sole.status=1";
		return $this->_solModel->getBySql($sqlstr);
	}
	/**
	 * 获取设计文档
	 */
	public function getSolDocumentByid($solid){
		$re = array();
		$sqlstr = "SELECT sold.*
		FROM solution_document as sold
		WHERE sold.solution_id='$solid'";
		$rearr = $this->_solModel->getBySql($sqlstr);
		foreach($rearr as $arr){
			$re[$arr['doc_type']][]=$arr;
		}
		return $re;
	}
	/**
	 * 更新设计文档
	 */
	public function updateSolutionDocument($solution_id,$data){
		$all = $this->_solutiondocumentModel->getAllByWhere("solution_id = '$solution_id'");
		$addarr = array();
		if($all){
			foreach($all as $v){
				$name = $url = '';
				if($v['doc_type']==1){
					$name = $data['schematic']?$data['schematic']:'电路图';
					$url  = $data['schematic_file'];
				}elseif($v['doc_type']==2){
					$name = $data['sourcecode']?$data['sourcecode']:'源代码';
					$url  = $data['sourcecode_file'];
				}elseif($v['doc_type']==3){
					$name = $data['pcdlayout']?$data['pcdlayout']:'PCB layout';
					$url  = $data['pcdlayout_file'];
				}elseif($v['doc_type']==4){
					$name = $data['test']?$data['test']:'测试文档';
					$url  = $data['test_file'];
				}elseif($v['doc_type']==5){
					$name = $data['guidebook']?$data['guidebook']:'开发指导书';
					$url  = $data['guidebook_file'];
				}
				$this->_solutiondocumentModel->update(array('name'=>$name,
						'url'=>$url,
						'status'=>1,
						'created'=>time()), "id=".$v['id']);
			}
		}else{
			$addarr[1]=array('solution_id' => $solution_id,
					'doc_type' => 1,
					'name'=>$data['schematic']?$data['schematic']:'电路图',
					'url'=>$data['schematic_file'],
					'created'=>time());
			$addarr[2]=array('solution_id' => $solution_id,
					'doc_type' => 2,
					'name'=>$data['sourcecode']?$data['sourcecode']:'源代码',
					'url'=>$data['sourcecode_file'],
					'created'=>time());
			$addarr[3]=array('solution_id' => $solution_id,
					'doc_type' => 3,
					'name'=>$data['pcdlayout']?$data['pcdlayout']:'PCB layout',
					'url'=>$data['pcdlayout_file'],
					'created'=>time());
			$addarr[4]=array('solution_id' => $solution_id,
					'doc_type' => 4,
					'name'=>$data['test']?$data['test']:'测试文档',
					'url'=>$data['test_file'],
					'created'=>time());
			$addarr[5]=array('solution_id' => $solution_id,
					'doc_type' => 5,
					'name'=>$data['guidebook']?$data['guidebook']:'开发指导书',
					'url'=>$data['guidebook_file'],
					'created'=>time());
			$this->_solutiondocumentModel->addDatas($addarr);
		}
		return true;
	}
}