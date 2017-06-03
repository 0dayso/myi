<?php 
set_time_limit(0);
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/common/excel/PHPExcel.php';
class Icwebadmin_CpcpglController extends Zend_Controller_Action
{
	private $_prodService;
	private $_filter;
	private $_mycommon;
	private $_fun;
	private $_adminlogService;
	private $_prodatsService;
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
    	$this->atsurl    = "/icwebadmin/CpAts/";
    	$this->view->ajaxtag= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/getajaxtag";
    	$this->editpropertyurl  = $this->view->editpropertyurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/editproperty";
    	 
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
    	$this->_fun = $this->view->fun = new MyFun();
    	$this->_filter = new MyFilter();
    	$this->_model = new Icwebadmin_Model_DbTable_Product();
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    	$this->_prodatsService = new Icwebadmin_Service_ProductatsService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->logDir = dirname(APPLICATION_PATH)."/public/upload/batchlog/";
    	$this->exportLabels  = array(
    			'no-specified'=>"No Specified",
    			'id'=>"* Part ID",
    			'manufacturer'=>'* Manufacturer',
    			'part_no'=>'* Part #',
    			'part_level3'=>'* Category Level 3',
    			'series'=>'Series',
    			'description'=>'* Short Description',
    			'supplier_device_package'=>'Package',
    			'packaging'=>'Packaging',
    			'rohs'=>"ROHS(Y/N)",
    			'moq'=>"MOQ",
    			'mpq'=>"MPQ",
    			'break_price'=>"Break Price(USD)",
    			'break_price_rmb'=>"Break Price(RMB)",
    			'lead_time'=>"Lead Time(Weeks)",
    			'lead_time_cn'=>"SZ Delivery Days",
    			'lead_time_hk'=>"HK Delivery Days",
    			'part_img'=>"Image",
    			'datasheet'=>'Datasheet(URL)',
    			'parameters'=>'Parameters',
    			'price_valid'=>'Price Valid Date(USD)',
    			'price_valid_rmb'=>'Price Valid Date(RMB)',
    			'hk_stock'=>'HK Stock',
    			'sz_stock'=>'SZ Stock',
    			'restricted'=>"Restricted(Y/N)",
    			'status'=>"Published(Y/N)",
    			'samples'=>"Samples(Y/N)"
    	);
    	$this->exportLimit = 1000;
    }
    public function indexAction(){
    	$typeArr =array('on','stock');
    	if(in_array($_GET['type'],$typeArr)) $this->view->type = $type = $_GET['type'];
    	else $this->view->type = $type='on';
    	
    	$wheresql = '';

    	//产品线
    	$this->view->selectbrand = $_GET['brand'];
    	if($this->view->selectbrand)
    	    $wheresql .= " AND manufacturer = '{$this->view->selectbrand}'";

    	
    	if($_GET['partno']){
    		$this->view->partno = trim($_GET['partno']);
    		$total =$this->view->selectnum = $this->_prodService->getSelectNum($this->view->partno,$wheresql);
    	}else{
    		if($type == 'on'){
    			$total = $this->_prodService->getTotalNum($wheresql);
    	    }elseif($type == 'stock'){
    	    	$total = $this->_prodService->getHstockNum($wheresql);
    	    }else{
    		    echo '参数错误';exit;
    	    }
    	}
    		
    	//分页
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	
    	if($_GET['partno']){
    		$product =  $this->_prodService->getSelect($offset,$perpage,$this->view->partno,$wheresql);
    	}else{
    		$wheresql .= " ORDER BY id DESC ";
    		if($type == 'on'){
    		    $product =  $this->_prodService->getOn($offset,$perpage,$wheresql);
    	    }elseif($type == 'stock'){
    		    $product =  $this->_prodService->getHstock($offset,$perpage,$wheresql);
    	    }
    	}
    	$this->view->product = $product;
    	//获取品牌
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!=''"," name ASC");
    }
    /*
     * 编辑页面
     */
    public function editAction(){
    	$this->view->id = $id = $this->_getParam('id');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		//图片
    		if($data['part_img']){
    			$stnum = strrpos($data['part_img'],'/');
    			$data['part_img'] = substr($data['part_img'],$stnum+1);
    		}else $data['part_img'] = 'no.gif';

    		$prodModel = new Icwebadmin_Model_DbTable_Product();
    		$prodModel->update($data, "id='{$id}'");
    		
    		$_SESSION['message'] = '更新成功。'.$alertmess;
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更新产品成功','description'=>$data['break_price'].'<>'.$data['break_price_rmb'].'<>'.$data['special_break_prices']));
    	}
    	//获取品牌
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    	//产品信息
    	$this->view->product = $this->_prodService->getProductByID($id);
    	
    }
	/*
	 * 添加页面
	 */
    public function addAction(){
    	//BOM转询价添加产品
    	$bomdid = $this->_getParam('bomdid');
        $this->view->pn = $this->_getParam('pn');
    	
    	$this->view->id = $id = $this->_getParam('id');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		//查询重复型号
    		$pid = $this->_prodService->getPid($data['part_no']);
    		if($pid){
    			$_SESSION['message'] = '添加失败，型号：'.$data['part_no']."已经存在";
    			$this->_redirect('/icwebadmin/CpCpgl/add');
    		}
    		//图片
    		if($data['part_img']){
    			$stnum = strrpos($data['part_img'],'/');
    			$data['part_img'] = substr($data['part_img'],$stnum+1);
    		}else $data['part_img'] = 'no.gif';
    		//阶梯价
    		$order   = array("\r\n", "\n", "\r");$replace = ';';
    		$str=str_replace($order, $replace, $str);
    		$data['break_price'] = str_replace($order, $replace, $data['break_price']);
    		$prodModel = new Icwebadmin_Model_DbTable_Product();
    		//替换 为了处理图片
    		$data['overview'] = str_replace('\\','',$data['overview']);
    		//相关产品
    		$relevance = $data['relevance'];
    		$partarr = $data['part_id']?array_unique($data['part_id']):array();
    		unset($data['relevance']);
    		unset($data['part_id']);
    		unset($data['related_ic']);
    		//多个数据文档
    		$datasheet_name = $data['datasheet_name'];
    		$datasheet = $data['datasheet'];
    		unset($data['datasheet_name_add']);
    		unset($data['datasheet_name']);
    		unset($data['datasheet']);
    		if(!empty($datasheet)){
    			$datasheet_str = '';
    			foreach($datasheet as $k=>$v){
    				$datasheet_str .='<>'.$datasheet_name[$k].'()'.$v;
    			}
    			$data['datasheet'] = $datasheet_str;
    		}
    		
    		$newid = $prodModel->addData($data);
    		if($newid){
    			//如果是bom转询价添加型号
    			if($bomdid){
    				//更新
    				$bomdModel = new Icwebadmin_Model_DbTable_BomDetailed();
    				$bomre = $bomdModel->updateBomdet($bomdid, array('part_id'=>$newid));
    				if($bomre){
    					$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$bomdid,'temp4'=>'BOM转询价添加产品成功'));
    				}
    			}
    			//相关产品
    			if($relevance || $partarr){
    				$this->_prodService->updateRelevance($newid,$partarr,$relevance);
    			}
    			//日志
    		    $this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$newid,'temp4'=>'添加产品成功','description'=>$data['break_price'].'<>'.$data['break_price_rmb'].'<>'.$data['special_break_prices']));
    		    
    		    //发送上线通知邮件
    		    $this->_serchinqModel = new Icwebadmin_Model_DbTable_SearchInquiry();
    		    $serchinfo = $this->_serchinqModel->getByOneSql("SELECT sinq.*,u.uname,u.email from search_inquiry as sinq
    		    		LEFT JOIN user as u on sinq.uid = u.uid
    		    		WHERE sinq.part_no='".$data['part_no']."' AND sinq.notice=1 AND sinq.status!=101");
    		    if($serchinfo){
    		            $prodinfo = $this->_prodService->getInqProd($newid);
    		            if($prodinfo){
    		    		   $emailre = $this->_prodService->newprodEmail($prodinfo,$serchinfo);
    		    		   if($emailre){
    		    			   $_SESSION['message'] = '添加成功，并发送新品上线邮件通知成功';
    		    		       $re = $this->_serchinqModel->updateById(array('status'=>101), $serchinfo['id']);
    		    			   $this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$serchinfo['id'],'temp4'=>'发送新品上线邮件通知成功'));
    		    		   }else{
    		    			   $_SESSION['message'] = '添加成功，但发送新品上线邮件通知失败';
    		    			   $this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$serchinfo['id'],'temp4'=>'发送新品上线邮件通知失败'));
    		    		   }
    		            }
    		    }else{
    		    	$_SESSION['message'] = '添加成功';
    		    }
    		}else{
    			$_SESSION['message'] = '添加失败';
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'添加产品失败'));
    		}
    		$this->_redirect('/icwebadmin/CpCpgl/add');
    	}
    	//获取品牌
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    	//应用分类
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    	//产品信息
    	$this->view->product = $this->_prodService->getProductByID($id);

    }
    
    public function updateatsAction(){
    	$linecard_arr   = array(
    			'EPSON'=>'13',
    			'MTG'=>'10',
    			'QCA'=>'18',
    			'FM'=>'25',
    			'AGAMEM'=>'28',
    			'CSR'=>'6',
    			'NSC'=>'23',
    			'TI'=>'23',
    			'IDT'=>'8',
    			'INTERSIL'=>'7',
    			'MURATA'=>'24',
    			'VIA'=>'31',
    			'FSL'=>'14',
    			'Hisilicon'=>'16',
    			'MICROCHIP'=>'21',
    			'OTAX'=>'17',
    			'SKYWORKS'=>'20',
    			'ACARD'=>'1',
    			'AOS'=>'27',
    			'BCD'=>'3',
    			'BEL'=>'4',
    			'CEACSZ'=>'42',
    			'CE-STAR'=>'42',
    			'GCT'=>'15',
    			'LITE ON'=>'9',
    			'MPOWER'=>'26',
    			'NXP'=>'33',
    			'ON'=>'34',
    			'CAT'=>'34',
    			'Other'=>'40',
    			'CSB'=>'40',
    			'DOW CORNING'=>'40',
    			'GOOWI'=>'40',
    			'LG'=>'40',
    			'Marcom'=>'40',
    			'MAXIM'=>'40',
    			'QCA/ITTIM'=>'18',
    			'ST ERICSSON'=>'40',
    			'SIMCOM'=>'22',
    			'ST'=>'35',
    			'AVX'=>'45',
    			'ERICSSON'=>'58',
    			'INFINEON'=>'46',
    			'LINEAGE'=>'59',
    			'LINEAR'=>'47',
    			'MICREL'=>'48',
    			'MICREL'=>'49',
    			'NCC'=>'55',
    			'Power One'=>'50',
    			'RICHTEK'=>'51',
    			'SAMSUNG'=>'52',
    			'Sitel'=>'53',
    			'TRIQUINT'=>'54',
    			'VERSATECH'=>'56',
    			'ZETEX'=>'44',
    			'ZILOG'=>'43',
    			'IR'=>'32',
    			'SUPERPIX'=>'57'
    	);
    	$ats_data = array();
    	$ats = $this->_getParam('ats');
    	$ats_data = ($ats) ? $this->_prodatsService->getById($ats) : array();
    	$this->view->id = $id = $this->_getParam('id');
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		//图片
    		if($data['part_img']){
    			$stnum = strrpos($data['part_img'],'/');
    			$data['part_img'] = substr($data['part_img'],$stnum+1);
    		}else $data['part_img'] = 'no.gif';
    		//阶梯价
    		// check data 
    		$order   = array("\r\n", "\n", "\r");$replace = ';';
    		$str=str_replace($order, $replace, $str);
    		$data['break_price'] = str_replace($order, $replace, $data['break_price']);
    		$prodModel = new Icwebadmin_Model_DbTable_Product();
    		$prodModel->addData($data);
    		$atsmodel  = new Icwebadmin_Model_DbTable_ProductAts();
    		$update_data      =  array(
    			'status'=>0
    		);

    		$atsmodel->updateById($update_data,$ats);
    		//$_SESSION['message'] = '添加成功';
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'添加产品成功'));
    		$this->_redirect('/icwebadmin/CpAts/');
    		
    	}
    	//获取品牌
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    	//应用分类
    	$brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->view->brand = $brandMod->getAllByWhere("id!=''");
    	$this->view->product      = $ats_data;
    	$this->view->linecard_arr = $linecard_arr;
    	//产品信息
    	//$this->view->product = $this->_prodService->getProductByID($id);
    
    }
    /*
     * ajax获取Part NO.
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	echo $this->_prodService->getPartNoLike($this->_filter->pregHtmlSql($_GET['q']));
    }

    
    public  function  importAction()
    {
    	$catModel     = new Icwebadmin_Model_DbTable_ProdCategory();
    	$fieldMapping = array();
    	$objExcel		  = new PHPExcel();
		if($this->getRequest()->isPost()){
			// save the file to server
			$adapter = new Zend_File_Transfer_Adapter_Http();
			$adapter->addValidator('Extension', false, 'xls,xlsx');
			$adapter->addValidator('FilesSize',false,	array('min' => '1KB', 'max' => '10MB'));		
			$adapter->setDestination('../docs');
			if($adapter->isValid()){
				try{
					$adapter->receive();
					$filename = $adapter->getFileName();
					$objPHPExcel = PHPExcel_IOFactory::load($filename);
					$objWorksheet = $objPHPExcel->getActiveSheet();
					$tmp_arr   = $objPHPExcel->getActiveSheet()->toArray();	
					$count  = 0;
					$header = $tmp_arr[0];
					$headerMapping = array_flip($this->exportLabels);

					foreach($header as $col_index=>$title)
					{
						$title = trim($title);				 
						if(in_array($title,$this->exportLabels)){
							$fieldMapping[$col_index] =   	$headerMapping[$title];
						} 
					}
					$flipFieldMapping = array_flip($fieldMapping);
					
					foreach($tmp_arr as $k=> $val)
					{
						$error  = 0; $insert = false;
						if($k>0){
							$v 						= $this->convert($val);
							$part_id 				= $v[$flipFieldMapping['id']];
							$insert					= (is_numeric($part_id) && $part_id !='#N/A') ? false : true;
							$part_no 				= $v[$flipFieldMapping['part_no']];
							$manufacturer	 	= $v[$flipFieldMapping['manufacturer']];	
							$msg     				= $k.",".$part_no.",".$manufacturer."\n";
							if($insert == true)
							{
								if(!$v[$flipFieldMapping['part_no']]){
									$error++;
								}
								if(!$v[$flipFieldMapping['manufacturer']]){
									$error++;
								}				
								if(!$v[$flipFieldMapping['part_level3']]){
									$error++;
								}			
								if(!$v[$flipFieldMapping['description']]){
									$error++;
								}					
								if($error!=0){
									// insert failed
									$insert_log_name = 'insert-failed'.time().'.csv';
									$insert_log = $this->logDir.$insert_log_name;
									error_log($msg,3,$insert_log);
									$_SESSION['part_log'] = '/upload/batchlog/'.$insert_log_name;									
								}													
							}
							if($v[$flipFieldMapping['part_level3']])
							{
								$name = $v[$flipFieldMapping['part_level3']];
								
								$sql      = "SELECT a.id as part_level3,a.parent_id as part_level2,b.parent_id as part_level1 FROM `prod_category` a 
LEFT JOIN prod_category b on b.id = a.parent_id
where a.`name` ='$name'";
								$cat = $catModel->getBySql($sql);
								if($cat){
									$v[$flipFieldMapping['part_level3']] = $cat[0]['part_level3'];
									$cat_arr['part_level3'] = $cat[0]['part_level3'];
									$cat_arr['part_level2'] = $cat[0]['part_level2'];
									$cat_arr['part_level1'] = $cat[0]['part_level1'];
								}else{
									// failed match category
									$error++;
									$cat_log_name = 'category-match-failed'.time().'.csv';
									$cat_log = $this->logDir.$cat_log_name;
									error_log($msg,3,$cat_log);
									$_SESSION['cat_log'] ='/upload/batchlog/'.$cat_log_name;
								}
							}
							
							$data					= $this->arrayJoin($flipFieldMapping,$v);
							if($insert == true) unset($data['id']);
							$data['modified']	= time();
							if(isset($cat_arr) && !empty($cat_arr)){
								$data['part_level2'] = $cat_arr['part_level2'];
								$data['part_level1'] = $cat_arr['part_level1'];								
							}
							$data['modified_by']	= $_SESSION['staff_sess']['staff_id'];
							$linecard      		= $this->_prodService->getBrandByName($manufacturer);
							if($linecard){
								$row = $this->_prodService->getProductByNo($part_no,$linecard['id']);
								$data['manufacturer'] = $linecard['id'];
							}else{
								// failed match linecard
								$error++;
								$mfr_log_name = 'manufacturer-match-failed'.time().'.csv';
								$mfr_log = $this->logDir.$mfr_log_name;
								error_log($msg,3,$mfr_log);
								$_SESSION['mfr_log'] =  '/upload/batchlog/'.$mfr_log_name;
							}
							if($insert == false){
								if($row){
									$pid = $row['id'];
								}else{
									// failed match part id
									$error++;
									$part_log_name = 'partid-match-failed'.time().'.csv';
									$part_log = $this->logDir.$part_log_name;
									error_log($msg,3,$part_log);
									$_SESSION['part_log'] = '/upload/batchlog/'.$part_log_name;
								}								
							}else{
								if($row){
									$error++; // already exists part
								}
							}
							
							if($error == 0){
								if($insert == false)
								{
									$this->_model->updateById($data, $pid);
									$count++;
								}else{
									$this->_model->addData($data);
									$count++;
								}
							}
						}
					}
					$messages = $count."条记录更新";
					$this->_helper->flashMessenger->addMessage($messages);
					$this->_redirect($this->indexurl."/import");
					unlink($filename);
				}catch(Zend_File_Transfer_Exception $e){	
					$e->getMessage();
				}
			}else{
				// not valid
				$tmp  = array_values($adapter->getMessages());
				$messages = $tmp[0];
				$this->_helper->flashMessenger->addMessage($messages);
				$this->_redirect($this->indexurl."/import");
			}
		}
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }  
    /**
     * 编辑产品选型
     */  
    public function editpropertyAction(){
    	$this->_propertyservice = new Icwebadmin_Service_PropertyService();
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		if($data['pc']){
    			$this->_productpropertymoder  = new Icwebadmin_Model_DbTable_Model('product_property');
    		    foreach($data['pc'] as $pvstr){
    		    	$pvarr = explode('_',$pvstr);
    		    	if($pvarr[0]){
    		    		if($pvarr[1]){
    		    		   $this->_productpropertymoder->updateByWhere(array('value_id'=>$pvarr[1],'status'=>1), "id='".$pvarr[0]."'");
    		    		}else{
    		    		   $this->_productpropertymoder->updateByWhere(array('status'=>0), "id='".$pvarr[0]."'");
    		    		}
    		    	}elseif($pvarr[1]){
    		    		$this->_productpropertymoder->addData(array('part_id'=>$data['pid'],'value_id'=>$pvarr[1]));
    		    	}
    		    }
    		}
    	}
    	$this->view->id = $id = $this->_getParam('id');
    	//产品信息
    	$this->view->product = $this->_prodService->getProductByID($id);
    	//查询所有属性
    	$pparray = $this->_propertyservice->getPropertyByPartid($id);
		//处理属性
		$this->view->productproperty = array();
		if($pparray){
			foreach($pparray as $pp){
				$this->view->productproperty[$pp['property_id']] = $pp;
			}
		}
    	if(	$this->view->product['part_level3']){
    		$cid = $this->view->product['part_level3'];
    	}elseif($this->view->product['part_level2']){
    		$cid = $this->view->product['part_level2'];
    	}else{
    		$cid = $this->view->product['part_level1'];
    	}
    	$this->view->cid = $cid;
    	$this->view->category = $this->_prodService->getProdCategoryById($cid);
    	//属性与参数
    	$this->view->propertycategory = $this->_propertyservice->getPropertyCategoryByCid($cid,"AND pc.status=1");
    }
    /**
     * 导出Excel -- 导出样品
     */
    public function exportAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
 
    	$newname = "IC_samples.xls";
    	header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    	header('Content-Disposition: attachment;filename="'.$newname.'"');
    	header('Cache-Control: max-age=0');
    		
    	$prod = $this->_prodService->getProductBySql("po.samples=1 AND po.status=1");
    	
    	if($prod){
    			//生成新excel
    			$objExcel = new PHPExcel();
    			$objExcel->createSheet();
    			$objExcel->getSheet(0)->setTitle("break1");
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,1,"盛芯电子产品ID");
    			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,1,"型号");
    			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,1,"品牌");
    			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,1,"阶梯价(USD)");
    			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(45);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,1,"阶梯价(RMB)");
    			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(45);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,1,"国内库存");
    			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
    			$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,1,"香港库存");
    			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
    			
    			$objProps = $objExcel->getActiveSheet();
    			$objStyleA1R2 = $objProps->getStyle('A1:G1');
    			$objFillA1R2  = $objStyleA1R2->getFill();
    			$objFillA1R2->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    			$objFillA1R2->getStartColor()->setARGB('FFCCCCFF');
    
    			$objFontA1R2 = $objStyleA1R2->getFont();
    			$objFontA1R2->setBold(true);
    			 
    			$row1 = 2;
    			foreach($prod as $v){
    				
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(0,$row1,$v['id']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(1,$row1,$v['part_no']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(2,$row1,$v['bname']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(3,$row1,$v['break_price']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(4,$row1,$v['break_price_rmb']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(5,$row1,$v['sz_stock']);
    				$objExcel->getSheet(0)->setCellValueByColumnAndRow(6,$row1,$v['hk_stock']);
    				$row1++;
    			}
    			$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    			$objWriter->save('php://output');
    	}
    }
    /**
     * 导出Excel -- 根据查询参数导出产品
     * 限制5000条
     */
    
    public function deriveAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$where = array();
    	$offset 		= (int)$this->getRequest()->getParam('offset',0);
		$brand 		= (int)$this->getRequest()->getParam('brand',0);
		$part_level3 	=(int) $this->getRequest()->getParam('part_level3',0);
		$ats 	         	= (int) $this->getRequest()->getParam('ats',0);
		$part_no      	= $this->getRequest()->getParam('partno','');
		
		
		//可购买
		$cansell = $this->getRequest()->getParam('cansell','');
		if($cansell){
			array_push($where,'(break_price>0 OR break_price_rmb>0)');
			array_push($where,'((sz_stock - sz_cover) > 0 OR (hk_stock - hk_cover) > 0)');
		}
		//可询价
		$caninq =$this->getRequest()->getParam('caninq','');
		if($caninq){
			array_push($where,'noinquiry = 0');
		}
		//可申请样片
		$cansamp = $this->getRequest()->getParam('cansamp','');
		if($cansamp){
			array_push($where,'samples = 1');
			array_push($where,'(sz_stock - sz_cover) > 0');
		}
		
		if($brand != 0){
			array_push($where,'manufacturer='.$brand);
		}
		if($part_level3 != 0){
			array_push($where,'part_level3='.$part_level3);
		}		
		if($ats != 0){
			array_push($where,'ats='.$ats);
		}		
		if(!empty($part_no) ){
			array_push($where,'part_no='.$part_no);
		}		
    	$prod = $this->_prodService->getProductByWhere($offset,$this->exportLimit,$where);
    	if($prod){
    		$objExcel		  = new PHPExcel();
    		$row1= $row2 = $row3 = 1;
    		$worksheet3   = $objExcel->createSheet(0);
    		$worksheet3->setTitle("Product");
    		$col_idx = 0;
    		foreach($this->exportLabels as $key=>$val)
    		{
    			if($key !='no-specified'){
    				$worksheet3->setCellValueByColumnAndRow($col_idx,$row3,$val);
    				$col_idx++;
    			}
    		}
    		$row3++;

    		foreach($prod as $p)
    		{
    			$rohs  = ($p['rohs']==1) ? "Y" :"N";
    			$restricted  = ($p['restricted']==1) ? "Y" :"N";
    			$published  = ($p['status']==1) ? "Y" : "N";
    			$worksheet3->setCellValueByColumnAndRow(0,$row3,$p['id']);
    			$worksheet3->setCellValueByColumnAndRow(1,$row3,$p['brand']);
    			$worksheet3->setCellValueByColumnAndRow(2,$row3,$p['part_no']);
    			$worksheet3->setCellValueByColumnAndRow(3,$row3,$p['cat3']);
    			$worksheet3->setCellValueByColumnAndRow(4,$row3,$p['series']);
    			$worksheet3->setCellValueByColumnAndRow(5,$row3,$p['description']);
    			$worksheet3->setCellValueByColumnAndRow(6,$row3,$p['supplier_device_package']);
    			$worksheet3->setCellValueByColumnAndRow(7,$row3,$p['packaging']);
    			$worksheet3->setCellValueByColumnAndRow(8,$row3,$rohs);
    			$worksheet3->setCellValueByColumnAndRow(9,$row3,$p['moq']);
    			$worksheet3->setCellValueByColumnAndRow(10,$row3,$p['mpq']);
    			$worksheet3->setCellValueByColumnAndRow(11,$row3,$p['break_price']);
    			$worksheet3->setCellValueByColumnAndRow(12,$row3,$p['break_price_rmb']);
    			$worksheet3->setCellValueByColumnAndRow(13,$row3,$p['lead_time']);
    			$worksheet3->setCellValueByColumnAndRow(14,$row3,$p['lead_time_cn']);
    			$worksheet3->setCellValueByColumnAndRow(15,$row3,$p['lead_time_hk']);
    			$worksheet3->setCellValueByColumnAndRow(16,$row3,$p['part_img']);
    			$worksheet3->setCellValueByColumnAndRow(17,$row3,$p['datasheet']);
    			$worksheet3->setCellValueByColumnAndRow(18,$row3,$p['parameters']);
     			$worksheet3->setCellValueByColumnAndRow(19,$row3,$p['price_valid']?$p['price_valid']:'');
    			$worksheet3->setCellValueByColumnAndRow(20,$row3,$p['price_valid_rmb']?$p['price_valid_rmb']:'');
    			$worksheet3->setCellValueByColumnAndRow(21,$row3,$p['hk_stock'] - $p['hk_cover']);
    			$worksheet3->setCellValueByColumnAndRow(22,$row3,$p['sz_stock'] - $p['sz_cover']);
    			$worksheet3->setCellValueByColumnAndRow(23,$row3,$restricted);
    			$worksheet3->setCellValueByColumnAndRow(24,$row3,$published);
    			if(($p['sz_stock'] - $p['sz_cover'])>0 && $p['samples']==1) $cs = "Y";
    			else $cs = "N";
    			$worksheet3->setCellValueByColumnAndRow(25,$row3,$cs);
    			$row3++;
    		}
    		 
    		$objExcel->setActiveSheetIndex(0);
    		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    		$filename = "product".date("YmdHis").".xls";
    		header("Content-Type: application/force-download");
    		header("Content-Type: application/octet-stream");
    		header("Content-Type: application/download");
    		header("Content-Disposition: attachment; filename=".urlencode($filename));
    		header("Content-Transfer-Encoding: binary");
    		header("Expires: Mon, 26 Jul 2016 05:00:00 GMT");
    		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    		header("Pragma: no-cache");
    		$objWriter->save('php://output');
    		$objExcel->disconnectWorksheets();
    		unset($objExcel);    		
    	}
    
    }    
    
    public function templateAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$brandModel = new Icwebadmin_Model_DbTable_Brand();
    	$catModel     = new Icwebadmin_Model_DbTable_ProdCategory();
    	$prodModel   = new Icwebadmin_Model_DbTable_Product();
    	$objExcel		  = new PHPExcel();
    	$row1= $row2 = $row3 = 1;
    	$worksheet3   = $objExcel->getActiveSheet();
    	$worksheet3->setTitle("Data Required Fields");
    	$col_idx = 0;
    	foreach($this->exportLabels as $key=>$val)
    	{
    		$val = ($val =='* Category Level 3') ? 'Category Level 3' : $val;
    		$val = ($val =='* Short Description') ? 'Short Description' : $val;
    		if($key !='no-specified'){
    			$worksheet3->setCellValueByColumnAndRow($col_idx,$row3,$val);
    			$col_idx++;
    		}
    	}
    	$row3++;    	
    	$worksheet1	  = $objExcel->createSheet(2);
    	$worksheet1->setTitle("Manufacturer ID Mapping");
    	$worksheet1->setCellValueByColumnAndRow(0,$row1,"Manufacturer Name");
    	$worksheet1->setCellValueByColumnAndRow(1,$row1,"ID");
    	$row1++;
    	$worksheet2   = $objExcel->createSheet(1);
    	$worksheet2->setTitle("Category ID Mapping");
    	$worksheet2->setCellValueByColumnAndRow(0,$row2,"Category Level1");
    	$worksheet2->setCellValueByColumnAndRow(1,$row2,"Category Level2");
    	$worksheet2->setCellValueByColumnAndRow(2,$row2,"Category Level3");
    	$worksheet2->setCellValueByColumnAndRow(3,$row2,"ID");
    	$row2++;
    	//write data
    	$brands       	  = $brandModel->getBySql("select id,name from brand");
    	foreach($brands as $b)
    	{
    		$worksheet1->setCellValueByColumnAndRow(0,$row1,$b['name']);
    		$worksheet1->setCellValueByColumnAndRow(1,$row1,$b['id']);
    		$row1++;
    	}
    	$cats               = $catModel->getBySql("SELECT c.name as cat1,b.name as cat2, a.name as cat3,a.id FROM `prod_category` a
		LEFT JOIN prod_category b on b.id = a.parent_id
		LEFT JOIN prod_category c on c.id = b.parent_id
		where a.`status` =1 and a.`level` =3;");
    	foreach($cats as $c)
    	{
    		$worksheet2->setCellValueByColumnAndRow(0,$row2,$c['cat1']);
    		$worksheet2->setCellValueByColumnAndRow(1,$row2,$c['cat2']);
    		$worksheet2->setCellValueByColumnAndRow(2,$row2,$c['cat3']);
    		$worksheet2->setCellValueByColumnAndRow(3,$row2,$c['id']);
    		$row2++;
    	}
    	$prods                  = $prodModel->getBySql("SELECT  a.id, b.`name`as mfr,a.part_no,c.`name`as cat3,a.series,a.description,a.supplier_device_package,a.packaging,a.hk_stock,a.sz_stock,a.bpp_stock,a.bpp_warehouse,a.rohs,a.moq,a.mpq,a.break_price,a.break_price_rmb,
		a.lead_time, a.lead_time_cn,a.lead_time_hk, a.part_img, a.datasheet,a.parameters,a.restricted
		FROM `product` a
		LEFT JOIN brand b on b.id = a.manufacturer
		LEFT JOIN prod_category c on c.id = a.part_level3
		WHERE a.`status`
		LIMIT 10
		;");
    	foreach($prods as $p)
    	{
    		$rohs  = ($p['rohs']==1) ? "Y" :"N";
    		$restricted  = ($p['restricted']==1) ? "Y" :"N";
    		$published  = ($p['status']==1) ? "Y" : "N";
    		$sample	   = ($p['samples']==1) ? "Y" : "N";
    		$worksheet3->setCellValueByColumnAndRow(0,$row3,$p['id']);
    		$worksheet3->setCellValueByColumnAndRow(1,$row3,$p['mfr']);
    		$worksheet3->setCellValueByColumnAndRow(2,$row3,$p['part_no']);
    		$worksheet3->setCellValueByColumnAndRow(3,$row3,$p['cat3']);
    		$worksheet3->setCellValueByColumnAndRow(4,$row3,$p['series']);
    		$worksheet3->setCellValueByColumnAndRow(5,$row3,$p['description']);
    		$worksheet3->setCellValueByColumnAndRow(6,$row3,$p['supplier_device_package']);
    		$worksheet3->setCellValueByColumnAndRow(7,$row3,$p['packaging']);
    		$worksheet3->setCellValueByColumnAndRow(8,$row3,$rohs);
    		$worksheet3->setCellValueByColumnAndRow(9,$row3,$p['moq']);
    		$worksheet3->setCellValueByColumnAndRow(10,$row3,$p['mpq']);
    		$worksheet3->setCellValueByColumnAndRow(11,$row3,$p['break_price']);
    		$worksheet3->setCellValueByColumnAndRow(12,$row3,$p['break_price_rmb']);
    		$worksheet3->setCellValueByColumnAndRow(13,$row3,$p['lead_time']);
    		$worksheet3->setCellValueByColumnAndRow(14,$row3,$p['lead_time_cn']);
    		$worksheet3->setCellValueByColumnAndRow(15,$row3,$p['lead_time_hk']);
    		$worksheet3->setCellValueByColumnAndRow(16,$row3,$p['part_img']);
    		$worksheet3->setCellValueByColumnAndRow(17,$row3,$p['datasheet']);
    		$worksheet3->setCellValueByColumnAndRow(18,$row3,$p['parameters']);
    		$worksheet3->setCellValueByColumnAndRow(19,$row3,$p['price_valid']);
    		$worksheet3->setCellValueByColumnAndRow(20,$row3,$p['price_valid_rmb']);
    		$worksheet3->setCellValueByColumnAndRow(21,$row3,$p['hk_stock']);
    		$worksheet3->setCellValueByColumnAndRow(22,$row3,$p['sz_stock']);
    		$worksheet3->setCellValueByColumnAndRow(23,$row3,$restricted);
    		$worksheet3->setCellValueByColumnAndRow(24,$row3,$published);
    		$worksheet3->setCellValueByColumnAndRow(25,$row3,$sample);
    		$row3++;
    	}
    	 
    	$objExcel->setActiveSheetIndex(0);
    	$objWriter = new PHPExcel_Writer_Excel5($objExcel);
    	$filename = "template".date("YmdHis").".xls";
    	header("Content-Type: application/force-download");
    	header("Content-Type: application/octet-stream");
    	header("Content-Type: application/download");
    	header("Content-Disposition: attachment; filename=".urlencode($filename));
    	header("Content-Transfer-Encoding: binary");
    	header("Expires: Mon, 26 Jul 2016 05:00:00 GMT");
    	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("Pragma: no-cache");
    	$objWriter->save('php://output');
    	$objExcel->disconnectWorksheets();
    	unset($objExcel);
    
    	 
    }    
    
    
    public function convert($array)
    {
    	$tmp = array();
    	foreach($array as $key=>$val)
    	{
    		if(in_array($val,array('Y','N')))
    		{
    			$val = ($val=='Y') ? 1 : 0;
    		}
    		$tmp[$key] = $val;
    	}
    	return $tmp;
    }
    
    public function arrayJoin($source,$target)
    {
    	$tmp = array();
    	foreach($source as $k=>$v)
    	{
    		$tmp[$k] = $target[$v];
    	}
    	return $tmp;
    }
}
