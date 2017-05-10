<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
class Icwebadmin_ReadHomeController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_pcModer;
	private $_hpModer;
	private $_rModer;
	private $_brandMod;
	private $_rService;
	private $_adminlogService;
    public function init(){
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
    	$this->Section_Area_ID = $this->view->Section_Area_ID = $controllerArray[1];
    	$this->Staff_Area_ID   = $this->view->Staff_Area_ID = $controllerArray[2];
    	
    	/*************************************************************
    	 ***		创建一些通用url             ***
    	**************************************************************/
    	$this->indexurl = $this->view->indexurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}";
    	$this->addurl   = $this->view->addurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/add";
    	$this->editurl  = $this->view->editurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/edit";
    	$this->deleteurl= $this->view->deleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delete";
    	$this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->areaService = new Icwebadmin_Service_AreaService();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	
    	$this->_hpModer = new Icwebadmin_Model_DbTable_HomePhoto();
    	$this->_rModer  = new Icwebadmin_Model_DbTable_Recommend();
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->_pcModer = new Icwebadmin_Model_DbTable_ProdCategory();
    	$this->_rService = new Icwebadmin_Service_ReadService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	//滚动图片
    	$this->view->topimageArr = $this->_hpModer->getAllByWhere("image!=''",array("type ASC","status DESC","displayorder ASC"));
    }
    /**
     * 首页滚动图片改变状态
     */
    public function hpstatusAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id     = (int)$formData['id'];
    		$status = (int)$formData['status'];
    		$this->_hpModer->update(array('status'=>$status),"id = {$id}");
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
    /**
     * 添加首页滚动图片
     */
    public function hpeditAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$data['status'] = $data['status']?$data['status']:0;
    		$re = $this->_hpModer->updateById($data, $data['id']);
    		$_SESSION['messages'] = "更新成功.";
    		$this->view->processData = $data;
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'更新成功','description'=>implode('|',$data)));
    	}else{
    		$this->view->id = $id =(int) $this->getRequest()->getParam('id');
    		if(!$id) $this->_redirect($this->indexurl);
    		$this->view->processData = $this->_hpModer->getRowByWhere("id = $id");
    	}
    }
    /**
     * 添加首页滚动图片
     */
    public function hpaddAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$data['status'] = $data['status']?$data['status']:0;
    		
    		if(!$data['title']){
    			$_SESSION['messages'] = "请填写标题";
    		}elseif(!$data['image']){
    			$_SESSION['messages'] = "请上传图片";
    		}else{
    			$re = $this->_hpModer->addData($data);
    			if($re){
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$re,'temp4'=>'添加成功','description'=>implode('|',$data)));
    				$this->_redirect($this->indexurl);
    			}
    		}
    		$this->view->processData = $data;
    	}
    }
    /**
     * 代理品牌
     */
    public function actingbrandAction(){
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		print_r($data);
    		if($data['id'])
    		{
    			foreach($data['id'] as $id){
    				$this->_rModer->update(array("comid"=>$data['brand_'.$id]), "id = $id AND type='acting_brand'");
    			}
    			$this->_redirect('/icwebadmin/ReadHome/actingbrand');
    		}
    	}
    	//代理品牌
    	$sqlstr ="SELECT re.id,re.comid,b.name
		     FROM recommend as re
	   		 LEFT JOIN brand as b ON re.comid=b.id
		     WHERE re.type='acting_brand' AND re.part='home' AND re.status = 1 ORDER BY re.displayorder LIMIT 0 , 26";
    	$this->view->actingBrand = $this->_rModer->getBySql($sqlstr);
    	//获取品牌
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!='' AND status=1" ," name ASC");
    }
    /**
     * 产品分类推荐品牌
     */
    public function recbrandAction(){
    	if($this->getRequest()->isPost()){
    		if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    		{
    			echo "权限不够。";
    			exit;
    		}
    		$data   = $this->getRequest()->getPost();
    		$this->_rModer->update(array("status"=>$data['status']), "type='category_brand' AND  part='home' AND cat_id = ".$data['cat_id']);
    		
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'更新成功'));
    		exit;
    	}
    	$pc_brand = array();
    	$prodCategory   = $this->_pcModer->getAllByWhere("status='1' AND level = 1","displayorder ASC");
    	$str = "SELECT re.id,re.cat_id,re.cat_id,re.status,re.comid,b.name
		     FROM recommend as re
    		 LEFT JOIN brand as b ON re.comid=b.id
		     WHERE re.type='category_brand' AND re.part='home' ORDER BY re.displayorder ASC";
    	$category_brand = $this->_rModer->getBySql($str);
    	foreach($prodCategory as $v){
    		$temp = array();
    		foreach($category_brand as $rv){
    			if($v['id']==$rv['cat_id']){
    				$temp['rid']     = $rv['id'];
    				$temp['cat_id']  = $rv['cat_id'];
    				$temp['name']    = $v['name'];
    				$temp['status']  = $rv['status'];
    				$temp['comid'][] = $rv['comid'];
    				$temp['brandname'][] = $rv['name'];
    			}else{
    				$temp['cat_id'] = $v['id'];
    				$temp['name'] = $v['name'];
    			}
    		}
    		$pc_brand[] = $temp;
    	}
    	$this->view->pc_brand = $pc_brand;
    }
    /**
     * 添加产品分类推荐品牌
     */
    public function rbaddAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->cat_id  = $_GET['cat_id'];
    	$this->view->title   = $_GET['title'];
    	if($this->getRequest()->isPost()){
    		$this->_helper->viewRenderer->setNoRender();
    		$data = $this->getRequest()->getPost();
    		$this->_rModer->addData(array("cat_id"=>$data['cat_id'],
    				"type"=>"category_brand",
    				"comid"=>$data['brandid'],
    				"part"=>"home"));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'添加成功'));
    		exit;
    	}
    }
    /**
     * 修改产品分类推荐品牌
     */
    public function rbeditAction(){
        if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->view->cat_id = $cat_id =(int) $this->getRequest()->getParam('cat_id');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		foreach($data['id'] as $id){
    			$re = $this->_rModer->updateById($id,array("comid"=>$data['comid_'.$id]));
    		}
    		$_SESSION['messages'] = "更新成功.";
    		$this->_redirect('/icwebadmin/ReadHome/rbedit/cat_id/'.$data['cat_id']);
    	}else{
    		if(!$cat_id) $this->_redirect($this->indexurl);
    		$this->view->rbarr = $this->_rService->getRbBand($cat_id);
    	}
    }
    /**
     * 精品 新品
     */
    public function newprodAction(){
    	$allhotArr = $this->_rService->getHot();
    	$bandhotArr = array();
    	for($i=0;$i<count($allhotArr);$i++)
    	{
    	    $bandhotArr[$allhotArr[$i]['cat_id']][] = $allhotArr[$i];
    	}
    	$this->view->bandhotArr = $bandhotArr;
    }
    /**
     * 热销产品推荐
     */
    public function hotprodAction(){
    	$this->view->hot_prod = $this->_rService->getHotProd();
    }
    public function hotprodeditAction(){
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		foreach($data['id'] as $key=>$id){
    			$status = 0;
    			if($data['status']){
    			   if(in_array($data['comid'][$key],$data['status'])) $status = 1;
    			}
    			$updata = array("comid"=>$data['comid'][$key],
    					"displayorder"=>$data['displayorder'][$key],
    					"status"=>$status);
    			$re = $this->_rModer->updateById($data['id'][$key],$updata);
    		}
    		$_SESSION['messages'] = "更新成功.";
    	}
    	$this->view->hot_prod = $this->_rService->getHotProd();
    }
    /**
     * 添加热销产品
     */
    public function hotprodaddAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		if($data['prod_id']){
    			$displayorder = $data['displayorder']?$data['displayorder']:0;
    			$re = $this->_rModer->addData(array('type'=>'hot_prod','part'=>'home','comid'=>$data['prod_id'],'displayorder'=>$displayorder));
    			if($re){
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加成功'));
    				exit;
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'添加失败'));
    				exit;
    			}
    			
    		}else{
    		  echo Zend_Json_Encoder::encode(array("code"=>101, "message"=>'请输入产品ID'));
    		  exit;
    		}
    	}
    }
    /**
     * 修改 精品 新品
     */
    public function npeditAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->view->bandid = $bandid =(int) $this->getRequest()->getParam('bandid');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		foreach($data['id'] as $id){
    			$re = $this->_rModer->updateById($id,array("cat_id"=>$data['bandid'],"comid"=>$data['comid_'.$id]));
    		}
    		$_SESSION['messages'] = "更新成功.";
    		$this->_redirect('/icwebadmin/ReadHome/npedit/bandid/'.$data['bandid']);
    	}else{
    		if(!$bandid) $this->_redirect($this->indexurl);
    		$this->view->hotarr = $this->_rService->getHotByBand($bandid);
    	}
    }
    /**
     * 应用分类
     */
    public function appAction(){
    	if($this->getRequest()->isPost()){
    		if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    		{
    			echo "权限不够。";
    			exit;
    		}
    		$data   = $this->getRequest()->getPost();
    		$this->_rModer->update(array("status"=>$data['status']), " (type='app' OR type='solution' OR type='brand') AND part='home' AND cat_id = ".$data['cat_id']);
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'更新成功'));
    		exit;
    	}
    	//应用种类
    	$sqlstr ="SELECT re.cat_id,re.status,app.name,app.name_en,app.icon
		     FROM recommend as re,app_category as app
		     WHERE re.type='app' AND re.part='home' AND re.cat_id=app.id
			ORDER BY app.displayorder ASC";
    	$appArrs = $this->_rModer->getBySql($sqlstr);
    	$appArr=array();
    	//推荐应用方案
    	$sqlstr ="SELECT re.cat_id,re.head,
	   		   sol.id as solid,sol.title,sol.sol_img
		       FROM recommend as re
			   LEFT JOIN solution as sol ON re.comid=sol.id
		       WHERE re.type='solution' AND re.part='home'";
    	$solutionArr = $this->_rModer->getBySql($sqlstr, array());
    	//应用品牌
    	$sqlstr ="SELECT re.cat_id,b.id,b.name,b.name_en,b.logo
		    FROM recommend as re LEFT JOIN brand as b
		    ON re.comid=b.id
		    WHERE re.type='brand' AND re.part='home'
		    ORDER BY re.head DESC,re.displayorder";
    	$allBrand = $this->_rModer->getBySql($sqlstr, array());
    	//应用推荐产品
    	$sqlstr ="SELECT re.cat_id,re.comid,re.head,pro.part_no,pro.part_img,pro.mpq_price,
			   pro.break_price,pc.name
		       FROM recommend as re
			   LEFT JOIN product as pro ON re.comid=pro.id
		       LEFT JOIN prod_category as pc ON pro.part_level3=pc.id
		       WHERE re.type='app' AND re.part='home' ORDER BY re.displayorder";
    	$allApps = $this->_rModer->getBySql($sqlstr, array());
    	if(!empty($solutionArr)){
    		foreach($solutionArr as $key=>$soltmp)
    		{
    			//查询关联元件
    			$sqlstr ="SELECT * FROM solution_product WHERE solution_id='".$soltmp['solid']."'";
    			$solprod = $this->_rModer->getBySql($sqlstr, array());
    			$prodTmp = array();
    			if(!empty($solprod)){
    				foreach($solprod as $v){
    					$sqlstr ="SELECT p.id,p.part_img,p.part_no,p.description,p.manufacturer,
		   	   	p.break_price,p.mpq_price,pc.id as pcid,pc.name,br.name as bname
		   	   	FROM product as p
		   	    LEFT JOIN prod_category as pc ON p.part_level3 = pc.id
		   	   	LEFT JOIN brand as br ON p.manufacturer=br.id
		   	   	WHERE p.id='".$v['prod_id']."'";
    					$retmp = $this->_rModer->getBySql($sqlstr, array());
    					if(!empty($retmp[0])) $prodTmp[] = $retmp[0];
    				}
    			}
    			$solutionArr[$key]['rec_prod'] = $prodTmp;
    		}
    	}
    	foreach($appArrs as $tmp){
    		if(!array_key_exists($tmp['cat_id'],$appArr)){
    			//状态
    			$appArr[$tmp['cat_id']]['status'] = $tmp['status'];
    			
    			$solutionrr = $apparr = $brand = $sonapp = array();
    			$arr=array('name'=>$tmp['name'],
    					   'name_en'=>$tmp['name_en'],
    					   'icon'=>$tmp['icon']);
    			$appArr[$tmp['cat_id']]['par']=$arr;
    			//推荐应用方案
    			foreach($solutionArr as $soltmp){
    				if($soltmp['cat_id'] == $tmp['cat_id']){
    					$solutionrr[] = $soltmp;
    				}
    			}
    			$appArr[$tmp['cat_id']]['solution'] = $solutionrr;
    			//产品
    			foreach($allApps as $apptmp){
    				if($apptmp['cat_id'] == $tmp['cat_id']){
    					$apparr[] = $apptmp;
    				}
    			}
    			$appArr[$tmp['cat_id']]['value'] = $apparr;
    			//品牌
    			foreach($allBrand as $brandtmp){
    				if($brandtmp['cat_id'] == $tmp['cat_id']){
    					$brand[] = $brandtmp;
    				}
    			}
    			$appArr[$tmp['cat_id']]['brand'] = $brand;
    		}
    	}
    	$this->view->appArr = $appArr;
    }
    /**
     * 修改 应用分类
     */
    public function appeditAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->view->appid = $appid =(int) $this->getRequest()->getParam('appid');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		//推荐方案
    		$this->_rModer->updateById($data['solution_id'],array("comid"=>$data['solution']));
    		//产品
    		foreach($data['prod'] as $id){
    			$re = $this->_rModer->updateById($id,array("comid"=>$data['prod_'.$id]));
    		}
    		//品牌
    		foreach($data['brand'] as $id){
    			$re = $this->_rModer->updateById($id,array("comid"=>$data['brand_'.$id]));
    		}
    		$_SESSION['messages'] = "更新成功.";
    		$this->_redirect('/icwebadmin/ReadHome/appedit/appid/'.$data['appid']);
    	}else{
    		if(!$appid) $this->_redirect($this->indexurl);
    		$this->view->app = $this->_rService->getAppById($appid);
    	}
    }
    /*
     * 改变状态
    */
    public function changestatusAction(){
    	if(!$this->_mycommon->checkA($this->Staff_Area_ID) && !$this->_mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id     = (int)$formData['id'];
    		$status = (int)$formData['status'];
    		$re = $this->_rModer->updateById($id,array("status"=>$status));
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改状态成功，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'更改状态失败，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
}