<?php

class Application_Model_DbTable_Participants extends Zend_Db_Table_Abstract
{

    protected $_name = 'participant';
    protected $_primary = 'ParticipantID';


    public function getParticipantsByUserSystemId($userSystemId)
    {
        return $this->fetchAll("UserSystemID = $userSystemId");
    }

    public function getParticipant($partSysId)
    {
        return $this->fetchRow("ParticipantSystemID = '" . $partSysId . "'");
    }

    public function getAllParticipants($parameters)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */

        $aColumns = array('ParticipantFName', 'ParticipantLName', 'ParticipantMobile', 'ParticipantPhone', 'ParticipantAffiliation', 'ParticipanteMail', 'status');

        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = "UserSystemID";


        /*
         * Paging
         */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            $sOrder = "";
            for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
                if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . "
				 	" . ($parameters['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, "", -2);
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = "";
        if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
            $searchArray = explode(" ", $parameters['sSearch']);
            $sWhereSub = "";
            foreach ($searchArray as $search) {
                if ($sWhereSub == "") {
                    $sWhereSub .= "(";
                } else {
                    $sWhereSub .= " AND (";
                }
                $colSize = count($aColumns);

                for ($i = 0; $i < $colSize; $i++) {
                    if ($i < $colSize - 1) {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                } else {
                    $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                }
            }
        }


        /*
         * SQL queries
         * Get data to display
         */

        $sQuery = $this->getAdapter()->select()->from(array('p' => $this->_name));

        if (isset($sWhere) && $sWhere != "") {
            $sQuery = $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery = $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery = $sQuery->limit($sLimit, $sOffset);
        }

        //error_log($sQuery);

        $rResult = $this->getAdapter()->fetchAll($sQuery);


        /* Data set length after filtering */
        $sQuery = $sQuery->reset(Zend_Db_Select::LIMIT_COUNT);
        $sQuery = $sQuery->reset(Zend_Db_Select::LIMIT_OFFSET);
        $aResultFilterTotal = $this->getAdapter()->fetchAll($sQuery);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $sQuery = $this->getAdapter()->select()->from($this->_name, new Zend_Db_Expr("COUNT('" . $sIndexColumn . "')"));
        $aResultTotal = $this->getAdapter()->fetchCol($sQuery);
        $iTotal = $aResultTotal[0];

        /*
         * Output
         */
        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $aColumns = array('UserFName', 'UserLName', 'UserPhoneNumber', 'UserID', 'UserSecondaryemail', 'status');
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['ParticipantFName'];
            $row[] = $aRow['ParticipantLName'];
            $row[] = $aRow['ParticipantMobile'];
            $row[] = $aRow['ParticipantPhone'];
            $row[] = $aRow['ParticipantAffiliation'];
            $row[] = $aRow['ParticipanteMail'];
            $row[] = $aRow['status'];
            $row[] = '<a href="/admin/participants/edit/id/' . $aRow['ParticipantSystemID'] . '" class="btn btn-warning btn-xs" style="margin-right: 2px;"><i class="icon-pencil"></i></a>';

            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    public function updateParticipant($params)
    {
        $authNameSpace = new Zend_Session_Namespace('Zend_Auth');

       $data = array(
            'ParticipantID' => $params['pid'],
            'ParticipantFName' => $params['pfname'],
            'ParticipantLName' => $params['plname'],
            'ParticipantMobile' => $params['pphone2'],
            'ParticipantPhone' => $params['pphone1'],
            'ParticipanteMail' => $params['pemail'],
            'ParticipantAffiliation' => $params['partAff'],
            'Updated_on' => new Zend_Db_Expr('now()')
        );

        if(isset($params['status']) && $params['status'] != "" && $params['status'] != null){
            $data['status'] = $params['status'];
        }

        if(isset($authNameSpace->UserID) && $authNameSpace->UserID != ""){
            $data['Updated_by'] = $authNameSpace->UserID;
        }

        if(isset($authNameSpace->primary_email) && $authNameSpace->primary_email != ""){
            $data['Updated_by'] = $authNameSpace->primary_email;
        }

        return $this->update($data, "ParticipantSystemID = '" . $params['PartSysID'] . "'");
    }

    public function addParticipant($params)
    {
        $authNameSpace = new Zend_Session_Namespace('Zend_Auth');

        $data = array(
            'ParticipantID' => $params['participantId'],
            'ParticipantFName' => $params['pfname'],
            'ParticipantLName' => $params['plname'],
            'ParticipantMobile' => $params['pphone2'],
            'ParticipantPhone' => $params['pphone1'],
            'ParticipanteMail' => $params['pemail'],
            'ParticipantAffiliation' => $params['partAff'],
            'status' => $params['status'],
            'Created_on' => new Zend_Db_Expr('now()'),
            'Created_by' => $authNameSpace->primary_email,
        );
        return $this->insert($data);
    }

}

