<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpAtsController extends Zend_Controller_Action
{
    private $_filter;
    private $_mycommon;
    private $_prodatsService;
    public function init(){
        /*************************************************************
         ***        创建区域ID               ***
        **************************************************************/
        $controller            = $this->_request->getControllerName();
        $controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
        $this->Section_Area_ID = $this->view->Section_Area_ID = $controllerArray[1];
        $this->Staff_Area_ID   = $this->view->Staff_Area_ID = $controllerArray[2];
        
        /*************************************************************
         ***        创建一些通用url             ***
        **************************************************************/
        $this->indexurl = $this->view->indexurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}";
        $this->addurl   = $this->view->addurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/add";
        $this->editurl  = $this->view->editurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/edit";
        $this->deleteurl= $this->view->deleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delete";
        $this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
        /*****************************************************************
         ***        检查用户登录状态和区域权限       ***
        *****************************************************************/
        $loginCheck = new Icwebadmin_Service_LogincheckService();
        $loginCheck->sessionChecking();
        $loginCheck->staffareaCheck($this->Staff_Area_ID);
        
        /*************************************************************
         ***        区域标题               ***
        **************************************************************/
        $this->areaService = new Icwebadmin_Service_AreaService();
        $this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
        
        //加载通用自定义类
        $this->_mycommon = $this->view->mycommon = new MyAdminCommon();
        $this->_filter = new MyFilter();
        $this->_prodatsService = new Icwebadmin_Service_ProductatsService();
    }
    public function indexAction(){
        $brandName = $this->_request->getParam('brand','0');
        $isSample = $this->_request->getParam('isSample','0');
        $hasStock = $this->_request->getParam('hasStock','0');
        $matching  = $this->_request->getParam('matching','All');
        $sampleStock = $this->_request->getParam('sampleStock','0');
        $perpage = 50;
        if(!empty($brandName))
        {
            $where[] = "b.wgbez='$brandName'";
        }
        if(!empty($matching) && $matching == 'Y')
        {
            $where[] = 'b.part_id !=-1';
        }
        if(!empty($matching) && $matching == 'N')
        {
            $where[] = 'b.part_id =-1';
        }        
        if(!empty($hasStock) && $hasStock =='Y')
        {
            $where[] = 'b.total_stock>0';
        }
        if(!empty($sampleStock) && $sampleStock =='Y')
        {
            $where[] = 'b.sample_stock>0';
        }     
        if(!empty($isSample) && $isSample =='Y')
        {
            $where[] = 'b.is_sample="Y"';
        }              
        $total = $this->_prodatsService->getAtsTotal($where);
        $page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
        $offset  = $page_ob->offset();
        $this->view->page_bar= $page_ob->show(6);
        $this->view->matching = $matching;
        $this->view->hasStock = $hasStock;
        $this->view->sampleStock = $sampleStock;
        $this->view->brandName = $brandName;
        $this->view->isSample = $isSample;
        $this->view->total  = $total;
        $this->view->product = $this->_prodatsService->getAtsList($offset,$perpage,$where);
    }
    public function editAction(){

        $request = $this->getRequest()->getPost();
        $id            = $request['pk'];
        $data['ic_part_no'] = $request['value'];
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->_prodatsService->updatebyid($data, $id);
        echo "updated!";
    }
} 