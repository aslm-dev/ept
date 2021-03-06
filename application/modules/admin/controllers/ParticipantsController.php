<?php

class Admin_ParticipantsController extends Zend_Controller_Action
{

    public function init()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('index', 'html')
            ->addActionContext('view-participants', 'html')
            ->addActionContext('get-datamanager', 'html')
            ->addActionContext('get-datamanager-names', 'html')
            ->addActionContext('get-participant', 'html')
            ->initContext();
        $this->_helper->layout()->pageName = 'configMenu';
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $params = $this->getAllParams();
            $clientsServices = new Application_Service_Participants();
            $clientsServices->getAllParticipants($params);
        }
    }

    public function addAction()
    {
        $participantService = new Application_Service_Participants();
        $commonService = new Application_Service_Common();
        $dataManagerService = new Application_Service_DataManagers();
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $participantService->addParticipant($params);
            $this->redirect("/admin/participants");
        }

        $this->view->affiliates = $participantService->getAffiliateList();
        $this->view->networks = $participantService->getNetworkTierList();
        $this->view->dataManagers = $dataManagerService->getDataManagerList();
        $this->view->countriesList = $commonService->getcountriesList();
        $this->view->enrolledPrograms = $participantService->getEnrolledProgramsList();
        $this->view->siteType = $participantService->getSiteTypeList();
    }
    
    public function bulkImportAction()
    {
        $participantService = new Application_Service_Participants();
        if ($this->getRequest()->isPost()) {
            $this->view->response = $participantService->addBulkParticipant();
        }
    }
    
    public function participantUploadStatisticsAction()
    {
        $participantService = new Application_Service_Participants();
        if ($this->getRequest()->isPost()) {
            $result = $participantService->addBulkParticipant();
            if(!$result){
                $this->redirect("/admin/participants");
            }else{
                $this->view->response = $result;
            }
        }else{
            $this->redirect("/admin/participants");
        }
    }

    public function editAction()
    {

        $participantService = new Application_Service_Participants();
        $commonService = new Application_Service_Common();
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $participantService->updateParticipant($params);
            $this->redirect("/admin/participants");
        } else {
            if ($this->_hasParam('id')) {
                $userId = (int) $this->_getParam('id');
                $this->view->participant = $participantService->getParticipantDetails($userId);
            }
            $this->view->affiliates = $participantService->getAffiliateList();
            $dataManagerService = new Application_Service_DataManagers();
            $this->view->networks = $participantService->getNetworkTierList();
            $this->view->enrolledPrograms = $participantService->getEnrolledProgramsList();
            $this->view->siteType = $participantService->getSiteTypeList();
            $this->view->dataManagers = $dataManagerService->getDataManagerList();
            $this->view->countriesList = $commonService->getcountriesList();
        }
        $scheme = new Application_Service_Schemes();
        $this->view->schemes = $scheme->getAllSchemes();
        $this->view->participantSchemes = $participantService->getSchemesByParticipantId($userId);
    }

    public function pendingAction()
    {
        // action body
    }

    public function viewParticipantsAction()
    {
        $this->_helper->layout()->setLayout('modal');
        $participantService = new Application_Service_Participants();
        if ($this->_hasParam('id')) {
            $dmId = (int) $this->_getParam('id');
            $this->view->participant = $participantService->getAllParticipantDetails($dmId);
        }
    }

    public function participantManagerMapAction()
    {
        $participantService = new Application_Service_Participants();
        $dataManagerService = new Application_Service_DataManagers();
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $participantService->addParticipantManagerMap($params);
            $this->redirect("/admin/participants/participant-manager-map");
        }
        $this->view->participants = $participantService->getAllActiveParticipants();
        $this->view->dataManagers = $dataManagerService->getDataManagerList();
    }

    public function getDatamanagerAction()
    {
        $dataManagerService = new Application_Service_DataManagers();
        if ($this->_hasParam('participantId')) {
            $participantId = $this->_getParam('participantId');
            $this->view->paticipantManagers = $dataManagerService->getParticipantDatamanagerList($participantId);
        }
        $this->view->dataManagers = $dataManagerService->getDataManagerList();
    }

    public function getDatamanagerNamesAction()
    {
        $this->_helper->layout()->disableLayout();
        $dataManagerService = new Application_Service_DataManagers();
        if ($this->_hasParam('search')) {
            $participant = $this->_getParam('search');
            $this->view->paticipantManagers = $dataManagerService->getParticipantDatamanagerSearch($participant);
        }
    }

    public function getParticipantAction()
    {
        $participantService = new Application_Service_Participants();
        $dataManagerService = new Application_Service_DataManagers();
        if ($this->_hasParam('datamanagerId')) {
            $datamanagerId = $this->_getParam('datamanagerId');
            $this->view->mappedParticipant = $dataManagerService->getDatamanagerParticipantList($datamanagerId);
        }
        $this->view->participants = $participantService->getAllActiveParticipants();
    }

    public function exportParticipantsDetailsAction()
    {
        $this->_helper->layout()->disableLayout();
        if ($this->getRequest()->isPost()) {
            $params['type'] = 'from-participant';
            $participantService = new Application_Service_Participants();
            $this->view->result = $participantService->exportShipmentRespondedParticipantsDetails($params);
        } else {
            return false;
        }
    }
}
