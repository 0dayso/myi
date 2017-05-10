<?php
require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpBpplController extends Zend_Controller_Action
{
    private $_filter;
    private $_mycommon;
    private $_service;
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
        $this->_service = new Icwebadmin_Service_BppService();
    }
    public function indexAction(){

        $vendor_id = $this->_request->getParam('vendor_id','-1');
        $brand_id = $this->_request->getParam('brand_id','0');
        $perpage = 20;
        if(!empty($brand_id) && $brand_id != 0)
        {
            $where[] = 'b.brand_id='.$brand_id;
        }
        if(!empty($vendor_id) && $vendor_id != '-1')
        {
            $where[] = 'b.vendor_id='.$vendor_id;
        }
        $total = $this->_service->getLinecardTotal($where);
        $page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
        $offset  = $page_ob->offset();
        $this->view->page_bar= $page_ob->show(6);
        $this->view->vendor_id = $vendor_id;
        $this->view->brand_id = $brand_id;
        $this->view->total  = $total;
        $this->view->product = $this->_service->getBppLinecardList($offset,$perpage,$where);
        //x-editable
        $brand = new Icwebadmin_Model_DbTable_Brand();
        $brand_arr = $brand->fetchAll(null,'name asc')->toArray();
        foreach($brand_arr as $k=>$v)
        {
            $json_arr[$k]['value'] = $v['id'];
            $json_arr[$k]['text'] = $v['name'];
        }
        $brand_json = json_encode($json_arr);
        $this->view->brand_json = $brand_json;
    }
    public function editAction(){

        $request = $this->getRequest()->getPost();
        $id 		   = $request['pk'];
        $data['brand_id'] = $request['value'];
        $brand = new Icwebadmin_Model_DbTable_Brand();
        $tmp = $brand->fetchRow("id=".$data['brand_id'])->toArray();
        $data['brand_name'] = $tmp['name'];
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $bppLinecard = new Icwebadmin_Model_DbTable_BppLinecard();
        $bppLinecard->updateById($data,$id);
        echo "updated!";
    }
}