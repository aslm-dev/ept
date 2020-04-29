<?php
require_once 'tcpdf/tcpdf.php';
$schemeType = $result['shipment'][0]['scheme_type'];
//var_dump($result['shipment'][0]['responseResult'][0]['testkit1']);die;
$pdfNew = new Zend_Pdf();
$extractor = new Zend_Pdf_Resource_Extractor();
$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
$shipmentCode = '';

$dtsResults = array();
foreach ($possibleDtsResults as $pr) {
    $dtsResults[$pr['id']] = ucfirst(strtolower($pr['response']));
}
if (sizeof($result['shipment']) > 0) {
    if (!file_exists(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports') && !is_dir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports')) {
        mkdir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports');
    }
    class MYPDF extends TCPDF
    {
        public $scheme_name = '';

        public function setSchemeName($header, $schemeName, $logo, $logoRight, $comingFrom, $schemeType)
        {
            $this->scheme_name = $schemeName;
            $this->header = $header;
            $this->logo = $logo;
            $this->logoRight = $logoRight;
            $this->comingFrom = $comingFrom;
            $this->schemeType = $schemeType;
        }

        //Page header
        public function Header()
        {
            // Logo
            //$image_file = K_PATH_IMAGES.'logo_example.jpg';
            if (trim($this->logo) != "") {
                if (file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logo)) {
                    $image_file = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logo;
                    $this->Image($image_file, 10, 8, 30, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
            }
            // if (trim($this->logoRight) != "") {
            //     if (file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logoRight)) {
            //         $image_file = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR . $this->logoRight;
            //         $this->Image($image_file, 180, 10, 20, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            //     }
            // }

            // Set font

            $this->SetFont('helvetica', '', 10);

            $this->header = nl2br(trim($this->header));
            $this->header = preg_replace('/<br>$/', "", $this->header);

            if ($this->schemeType == 'vl') {
                //$html='<span style="font-weight: bold;text-align:center;">Proficiency Testing Program for HIV Viral Load using Dried Tube Specimen</span><br><span style="font-weight: bold;text-align:center;">All Participants Summary Report</span><br><small  style="text-align:center;">'.$this->header.'</small>';

                $html = '<span style="font-weight: bold;text-align:center;"><span  style="text-align:center;">' . $this->header . '</span><br>Proficiency Testing Program for HIV Viral Load using ' . $this->scheme_name . '</span><br><span style="font-weight: bold; font-size:11;text-align:center;">Individual Participant Results Report</span>';
            } else if ($this->schemeType == 'eid') {
                $this->SetFont('helvetica', '', 10);
                //$html='<span style="font-weight: bold;text-align:center;">Proficiency Testing Program for HIV-1 Early Infant Diagnosis using Dried Blood Spot</span><br><span style="font-weight: bold;text-align:center;">All Participants Summary Report</span><br><small  style="text-align:center;">'.$this->header.'</small>';
                $html = '<span style="font-weight: bold;text-align:center;"><span  style="text-align:center;">' . $this->header . '</span><br>Proficiency Testing Program for HIV-1 Early Infant Diagnosis using ' . $this->scheme_name . '</span><br><span style="font-weight: bold; font-size:11;text-align:center;">Individual Participant Results Report</span>';
            } else {
                //$html='<span style="font-weight: bold;text-align:center;">Proficiency Testing Program for Anti-HIV Antibodies Diagnostics using '.$this->scheme_name.'</span><br><span style="font-weight: bold;text-align:center;">All Participants Summary Report</span><br><small  style="text-align:center;">'.$this->header.'</small>';
                $this->SetFont('helvetica', '', 10);
                $html = '<span style="font-weight: bold;text-align:center;"><span  style="text-align:center;">' . $this->header . '</span><br>Proficiency Testing Program for Anti-HIV Antibodies Diagnostics using ' . $this->scheme_name . '</span><br><span style="font-weight: bold; font-size:11;text-align:center;">Individual Participant Results Report</span>';
            }

            $this->writeHTMLCell(0, 0, 42, 10, $html, 0, 0, 0, true, 'J', true);
            $html = '<hr/>';
            $this->writeHTMLCell(0, 0, 10, 38, $html, 0, 0, 0, true, 'J', true);
        }

        // Page footer
        public function Footer()
        {
            $finalizeReport = "";
            if (trim($this->comingFrom) == "finalize") {
                $finalizeReport = ' | INDIVIDUAL REPORT | FINALIZED ';
            }
            // Position at 15 mm from bottom
            $this->SetY(-12);
            // Set font
            $this->SetFont('helvetica', '', 7);
            // Page number
            //$this->Cell(0, 10, "Report generated at :".date("d-M-Y H:i:s").$finalizeReport, 0, false, 'C', 0, '', 0, false, 'T', 'M');
            //$this->Cell(0, 10, "Report generated on ".date("d M Y H:i:s").$finalizeReport, 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->writeHTML("<hr>", true, false, true, false, '');
            $this->writeHTML('Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . " - Report generated at :" . date("d-M-Y H:i:s") . $finalizeReport, true, false, true, false, 'C');
        }
    }
    $totalPages = count($result['shipment']);
    $j = 1;
    //$result['dmResult'];
    //var_dump($result['shipment']);die;

    foreach ($result['shipment'] as $result) {

        if ( /*(isset($result['responseResult'][0]['is_excluded']) && $result['responseResult'][0]['is_excluded'] == 'yes') || */
            (isset($result['responseResult'][0]['is_pt_test_not_performed']) && $result['responseResult'][0]['is_pt_test_not_performed'] == 'yes')
        ) {
            continue;
        }

        //Zend_Debug::dump($result['responseResult'][0]);die;

        if (!file_exists(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $result['shipment_code']) && !is_dir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $result['shipment_code'])) {
            mkdir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $result['shipment_code']);
        }
        //error_log($i);
        // Extend the TCPDF class to create custom Header and Footer

        // create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setSchemeName($header, $result['scheme_name'], $logo, $logoRight, $comingFrom, $schemeType);
        // set document information
        //$pdf->SetCreator(PDF_CREATOR);
        //$pdf->SetAuthor('ePT');
        //$pdf->SetTitle('DEPARTMENT OF HEALTH AND HUMAN SERVICES');
        //
        //

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins

        $pdf->SetMargins(PDF_MARGIN_LEFT, 50, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once dirname(__FILE__) . '/lang/eng.php';
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('helvetica', '', 11);

        // add a page
        $pdf->AddPage();

        // set some text to print
        //$txt = <<<EOD
        //International Laboratory Branch
        //
        //Custom page header and footer are defined by extending the TCPDF class and overriding the Header() and Footer() methods.
        //EOD;
        //
        //// print a block of text using Write()
        //$pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);

        // ---------------------------------------------------------

        if (trim($result['shipment_date']) != "") {
            $result['shipment_date'] = $this->dateFormat($result['shipment_date']);
        }
        if (trim($result['lastdate_response']) != "") {
            $result['lastdate_response'] = $this->dateFormat($result['lastdate_response']);
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.ini", APPLICATION_ENV);
        $responseDate = "";
        $shipmentTestDate = "";
        $shipmentScore = 0;
        $documentationScore = 0;
        $score = 0;

        $testThreeOptionalDisplay = "";
        if (isset($config->evaluation->dts->dtsOptionalTest3) && $config->evaluation->dts->dtsOptionalTest3 == 'yes') {
            $testThreeOptionalDisplay = ";display:none;";
        }


        if (isset($result['responseResult'][0]['responseDate']) && trim($result['responseResult'][0]['responseDate']) != "") {
            $splitDate = explode(" ", $result['responseResult'][0]['responseDate']);
            $responseDate = $this->dateFormat($splitDate[0]);
        }
        $attributes = '';
        if (isset($result['attributes'])) {
            $attributes = json_decode($result['attributes'], true);
        }

        $sampleRehydrationDate = "";
        if (isset($attributes['sample_rehydration_date']) && trim($attributes['sample_rehydration_date']) != "") {
            $sampleRehydrationDate = $this->dateFormat($attributes['sample_rehydration_date']);
        }
        $shipmentReceiptDate = "";
        if (isset($result['responseResult'][0]['shipment_receipt_date']) && trim($result['responseResult'][0]['shipment_receipt_date']) != "") {
            $shipmentReceiptDate = $this->dateFormat($result['responseResult'][0]['shipment_receipt_date']);
        }

        if (isset($result['responseResult'][0]['shipment_test_date']) && trim($result['responseResult'][0]['shipment_test_date']) != "") {
            $shipmentTestDate = $this->dateFormat($result['responseResult'][0]['shipment_test_date']);
        }

        //if($result['result_name']=='Fail'){
        //    $wishes="";
        //    $splStr=explode("###",$result['failure_reason']);
        //    $k=sizeof($splStr);
        //    for($c=0;$c<$k;$c++){
        //        $wishes.='<p> '.$splStr[$c].'</p><br/>';
        //    }
        //    //$wishes = "<ul><li>" .str_replace("###","</li><li>",$result['failure_reason']) . "</li></ul>";
        //
        //}

        //Coment Details

        if ($schemeType != 'vl' && $schemeType != 'eid') {

            $labInfo = '<table cellpadding="3" style="font-size:11px;">';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Participant Code</strong> : ' . $result['unique_identifier'] . '</td>';

            $labInfo .= '	<td><strong>Performing Participant</strong>  : ' . $result['first_name'] . " " . $result['last_name'] . '</td>';

            $labInfo .= '	<td><strong>PT Panel Name and Date</strong> : ' . $result['distribution_code'] . '(' . $result['shipment_date'] . ')</td>';

            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Shipment Date</strong> : ' . $result['shipment_date'] . '</td>';
            $labInfo .= '	<td><strong>Shipment Code</strong> : ' . $result['shipment_code'] . '</td>';
            $labInfo .= '	<td><strong>Shipment Type</strong> : ' . $result['scheme_name'] . " " . $result['last_name'] . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Panel Receipt Date</strong> : ' . $shipmentReceiptDate . '</td>';
            $labInfo .= '	<td><strong>Rehydration Date</strong> : ' . $sampleRehydrationDate . '</td>';

            $labInfo .= '	<td><strong>Result Due Date</strong> : ' . $result['lastdate_response'] . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Response Date</strong> : ' . $responseDate . '</td>';
            $labInfo .= '	<td><strong>Shipment Test Date</strong> : ' . $shipmentTestDate . '</td>';
            $labInfo .= '	<td>';
            if (isset($attributes['algorithm'])) {
                $labInfo .= '	<strong>Algorithm</strong> : ' . ucfirst($attributes['algorithm']);
            }
            $labInfo .= ' </td>';
            $labInfo .= '</tr>';
            $labInfo .= '<tr>';
            $labInfo .= '	<td>';
            $labInfo .= '	<strong>Supervisor Name</strong> : ' . ($result['participant_supervisor']);
            $labInfo .= ' </td>';
            $labInfo .= '	<td>';
            if (isset($this->haveCustom) && $this->haveCustom == 'yes') {
                $labInfo .= '	<strong>' . $this->customField1 . '</strong> <br>' . $this->shipment['custom_field_1'];
            }
            $labInfo .= ' </td>';
            $labInfo .= '	<td>';
            if (isset($this->haveCustom) && $this->haveCustom == 'yes') {
                $labInfo .= '	<strong>' . $this->customField2 . '</strong> <br>' . $this->shipment['custom_field_2'];
            }
            $labInfo .= ' </td>';
            $labInfo .= '</tr>';

            $labInfo .= '</table>';
            //shipment_test_date
            $pdf->writeHTML($labInfo, true, false, true, false, '');
        } else if ($schemeType == 'eid') {

            // Samples without response need not be generated
            if (sizeof($result['responseResult']) == 0) {
                continue;
            }

            $labInfo = '<table cellpadding="3" style="width:830px;font-size:11px;">';
            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>PT Panel Name and Date</strong> <br>' . $result['distribution_code'] . '(' . $result['shipment_date'] . ')</td>';
            $labInfo .= '	<td><strong>PT Panel Received</strong> <br>' . $shipmentReceiptDate . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            //$labInfo.='    <td><strong>Lab Id</strong> <br>'.$result['unique_identifier'].'</td>';
            $labInfo .= '	<td><strong>Extraction Assay </strong> <br>' . $result['extractionAssayVal'] . '</td>';
            $labInfo .= '	<td><strong>PT Panel Tested </strong> <br>' . $shipmentTestDate . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Detection Assay </strong> <br>' . $result['extractionAssayVal'] . '</td>';
            $labInfo .= '	<td><strong>Results Submitted Date</strong> <br>' . $responseDate . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Lab ID</strong> <br>' . $result['unique_identifier'] . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '</table>';

            $pdf->writeHTML($labInfo, true, false, true, false, '');
        } else {

            $labInfo = '<table cellpadding="2" style="font-size:12px;width:830px;">';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>PT Panel </strong> <br>' . $result['distribution_code'] . '(' . $result['shipment_date'] . ')</td>';
            $labInfo .= '	<td><strong>Date PT Panel Received</strong> <br>' . $shipmentReceiptDate . '</td>';

            $labInfo .= '</tr>';

            $labInfo .= '<tr>';
            $labInfo .= '	<td><strong>Your Lab Id</strong> <br>' . $result['unique_identifier'] . '</td>';
            $labInfo .= '	<td><strong>Date PT Panel Tested</strong> <br>' . $shipmentTestDate . '</td>';
            $labInfo .= '</tr>';

            $labInfo .= '</table>';
            //shipment_test_date
            $pdf->writeHTML($labInfo, true, false, true, false, '');
        }

        if (sizeof($result['responseResult']) > 0) {
            if ($schemeType != 'vl' && $schemeType != 'eid') {
                $labRes = '<span style="font-weight: bold;font-size:12px;">Your laboratory test results : <br/></span><table border="1" style="font-size:12px;">';
                $labRes .= '<tr style="background-color:#dbe4ee;"><td></td><td style="text-align:center;font-weight:bold;">Test-1</td><td style="text-align:center;font-weight:bold;">Test-2</td><td style="text-align:center;font-weight:bold;' . $testThreeOptionalDisplay . '">Test-3</td><td colspan="4" style="border:none;"></td></tr>';
                $labRes .= '<tr><td style="text-align:center;font-weight:bold;background-color:#dbe4ee;">Kit Name</td><td>' . $result['responseResult'][0]['testkit1'] . '</td><td>' . $result['responseResult'][0]['testkit2'] . '</td><td style="' . $testThreeOptionalDisplay . '">' . $result['responseResult'][0]['testkit3'] . '</td><td colspan="4"></td></tr>';
                $labRes .= '<tr><td style="text-align:center;font-weight:bold;background-color:#dbe4ee;">Lot No.</td><td>' . $result['responseResult'][0]['lot_no_1'] . '</td><td>' . $result['responseResult'][0]['lot_no_2'] . '</td><td style="' . $testThreeOptionalDisplay . '">' . $result['responseResult'][0]['lot_no_3'] . '</td><td colspan="4"></td></tr>';
                $labRes .= '<tr><td style="text-align:center;font-weight:bold;background-color:#dbe4ee;">Expiry Date</td><td>' . $this->dateFormat($result['responseResult'][0]['exp_date_1']) . '</td><td>' . $this->dateFormat($result['responseResult'][0]['exp_date_2']) . '</td><td style="' . $testThreeOptionalDisplay . '">' . $this->dateFormat($result['responseResult'][0]['exp_date_3']) . '</td><td colspan="4"></td></tr>';
                $labRes .= '<tr style="background-color:#dbe4ee;">
							<td style="text-align:center;font-weight:bold;">Specimen Panel ID </td>
							<td style="text-align:center;font-weight:bold;">Result-1</td>
							<td style="text-align:center;font-weight:bold;">Result-2</td>
							<td style="text-align:center;font-weight:bold;' . $testThreeOptionalDisplay . '">Result-3</td>
							<td style="text-align:center;font-weight:bold;">Expected Result</td>
							<td style="text-align:center;font-weight:bold;">Your Result</td>

							<td style="text-align:center;font-weight:bold;" colspan="2">Score</td>
						</tr>';

                $nonMandatorySamples = array();
                $controlSamples = array();
                $correctSamples = array();
                $totalProperSamples = 0;
                $correctSamplesCount = 0;
                $wrongSamples = array();
                $otherSamples = array();
                $allSamples = array();

                foreach ($result['responseResult'] as $response) {
                    $allSamples[] = $response['sample_label'];
                    if ($response['control'] == 1) {
                        $controlSamples[] = $response['sample_label'];
                    }

                    if ($response['mandatory'] == 0) {
                        $nonMandatorySamples[] = $response['sample_label'];
                    } else if ($response['calculated_score'] == 'Pass') {
                        $correctSamples[] = $response['sample_label'];
                    } else if ($response['calculated_score'] == 'Fail') {
                        $wrongSamples[] = $response['sample_label'];
                    } else {
                        $otherSamples[] = $response['sample_label'];
                    }
                }

                $correctSamplesCount = count($correctSamples);
                $totalProperSamples = $correctSamplesCount + count($wrongSamples);
                $maxDocumentationPoints = (isset($config->evaluation->dts->documentationScore) && $config->evaluation->dts->documentationScore > 0) ? ($config->evaluation->dts->documentationScore) : 0;
                $maximumResponseScore = 100 - $maxDocumentationPoints;
                $scorePerCorrectSample = round($maximumResponseScore / (count($allSamples) - count($nonMandatorySamples) - count($controlSamples)), 2);

                foreach ($result['responseResult'] as $response) {

                    if ($response['calculated_score'] == 'Pass') {
                        $img = UPLOAD_PATH . '/../images/check.jpg';

                        $score = ($response['control'] == 0) ? $scorePerCorrectSample : "N.A.";
                    } else if ($response['calculated_score'] == 'Fail') {
                        $img = UPLOAD_PATH . '/../images/cross.jpg';
                        $score = ($response['control'] == 0) ? 0 : "N.A.";
                    } else {
                        $img = UPLOAD_PATH . '/../images/minus.jpg';
                        $score = "N.A.";
                    }
                    $labRes .= '<tr>
							<td style="text-align:center;">' . $response['sample_label'] . '</td>
							<td>' . (isset($response['test_result_1']) && $response['test_result_1'] != "" ? $dtsResults[$response['test_result_1']] : "") . '</td>
							<td>' . (isset($response['test_result_2']) && $response['test_result_2'] != "" ? $dtsResults[$response['test_result_2']] : "") . '</td>
							<td style="' . $testThreeOptionalDisplay . '">' . (isset($response['test_result_3']) && $response['test_result_3'] != "" ? $dtsResults[$response['test_result_3']] : "") . '</td>
							<td>' . ucfirst(strtolower($response['referenceResult'])) . '</td>
							<td>' . ucfirst(strtolower($response['labResult'])) . '</td>

							<td style="text-align:center;"><img style="width:10px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $score . '</td>
						  </tr>';
                }
                $labRes .= '</table>';

                $pdf->SetLeftMargin(15);
                $pdf->writeHTML($labRes, true, false, true, false, '');

                if (count($nonMandatorySamples) > 0) {

                    $nmsTable = "The following samples have been excluded from this evaluation : " . implode(", ", $nonMandatorySamples);
                    $nmsTable .= "<br/>";
                    $pdf->writeHTML($nmsTable, true, false, true, false, '');
                }

                //Let us now calculate documentation score
                $documentationScore = 0;
                $documentationScorePerItem = (isset($config->evaluation->dts->documentationScore) && $config->evaluation->dts->documentationScore > 0) ? ($config->evaluation->dts->documentationScore / 5) : 0;
                $attributes = json_decode($result['attributes'], true);

                $img = array();
                $imgPass = UPLOAD_PATH . '/../images/check.jpg';
                $imgFail = UPLOAD_PATH . '/../images/cross.jpg';

                $docRes = '<span style="font-weight:bold;font-size:12px;">Your documentation score :</span> <br/>
					<table border="1" style="font-size:12px;width:100%;">
						<tr style="background-color:#dbe4ee;">
							<td style="text-align:center;font-weight:bold;width:75%">Documentation Item</td>
							<td style="text-align:center;font-weight:bold;width:25%" colspan="2">Score</td>
						</tr>';

                if (strtolower($result['responseResult'][0]['supervisor_approval']) == 'yes' && trim($result['responseResult'][0]['participant_supervisor']) != "") {
                    $scoreDoc = $documentationScorePerItem;
                    $img = $imgPass;
                } else {
                    $scoreDoc = 0;
                    $img = $imgFail;
                }

                $docRes .= '<tr>
							<td style="text-align:left;font-weight:bold;">Supervisor Approval</td>
							<td style="text-align:center;"><img style="width:9px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $scoreDoc . '</td>
					</tr>';

                if (isset($result['responseResult'][0]['shipment_receipt_date']) && trim($result['responseResult'][0]['shipment_receipt_date']) != "") {
                    $scoreDoc = $documentationScorePerItem;
                    $img = $imgPass;
                } else {
                    $scoreDoc = 0;
                    $img = $imgFail;
                }

                $docRes .= '<tr>
							<td style="text-align:left;font-weight:bold;">Panel/Shipment Receipt Date Specified</td>
							<td style="text-align:center;"><img style="width:9px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $scoreDoc . '</td>
					</tr>';

                if (isset($attributes['sample_rehydration_date']) && trim($attributes['sample_rehydration_date']) != "") {
                    $scoreDoc = $documentationScorePerItem;
                    $img = $imgPass;
                } else {
                    $scoreDoc = 0;
                    $img = $imgFail;
                }

                $docRes .= '<tr>
							<td style="text-align:left;font-weight:bold;">Reporting of the Sample Rehydration Date</td>
							<td style="text-align:center;"><img style="width:9px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $scoreDoc . '</td>
					</tr>';

                if (isset($result['responseResult'][0]['shipment_test_date']) && trim($result['responseResult'][0]['shipment_test_date']) != "") {
                    $scoreDoc = $documentationScorePerItem;
                    $img = $imgPass;
                } else {
                    $scoreDoc = 0;
                    $img = $imgFail;
                }

                $docRes .= '<tr>
							<td style="text-align:left;font-weight:bold;">Reporting of the Shipment Test Date</td>
							<td style="text-align:center;"><img style="width:9px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $scoreDoc . '</td>
					</tr>';

                $config = new Zend_Config_Ini(APPLICATION_PATH . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.ini", APPLICATION_ENV);
                $sampleRehydrationDate = new DateTime($attributes['sample_rehydration_date']);
                $testedOnDate = new DateTime($result['responseResult'][0]['shipment_test_date']);
                $interval = $sampleRehydrationDate->diff($testedOnDate);

                // Testing should be done within 24*($config->evaluation->dts->sampleRehydrateDays) hours of rehydration.
                $sampleRehydrateDays = $config->evaluation->dts->sampleRehydrateDays;
                $rehydrateHours = $sampleRehydrateDays * 24;

                if ($interval->days > $sampleRehydrateDays) {
                    $scoreDoc = 0;
                    $img = $imgFail;
                } else {
                    $scoreDoc = $documentationScorePerItem;
                    $img = $imgPass;
                }

                $docRes .= '<tr>
							<td style="text-align:left;font-weight:bold;">Testing to be done within ' . $rehydrateHours . ' hours of rehydration.</td>
							<td style="text-align:center;"><img style="width:11px;" src="' . $img . '" /></td>
							<td style="text-align:center;">' . $scoreDoc . '</td>
					</tr>';

                $docRes .= '</table>';

                $pdf->writeHTML($docRes, true, false, true, false, '');

                if (isset($result['responseResult'][0]['failure_reason']) && $result['responseResult'][0]['failure_reason'] != "" && $result['responseResult'][0]['failure_reason'] != "[]" && $result['responseResult'][0]['failure_reason'] != null) {
                    $failRes = '<span style="font-weight:bold;font-size:12px;">Suggested Corrective actions for your response :</span> <br/>';
                    $failRes .= '<table border="1" style="font-size:11px;">';
                    $failRes .= '<tr style="background-color:#dbe4ee;"><td style="text-align:center;font-weight:bold;">Failure Reasons (or) Warnings</td><td style="text-align:center;font-weight:bold;">Corrective Actions (if any)</td></tr>';
                    $warnings = json_decode($result['responseResult'][0]['failure_reason'], true);
                    foreach ($warnings as $warning) {
                        $failRes .= '<tr>';
                        $failRes .= '<td> ' . (isset($warning['warning']) ? $warning['warning'] : "") . ' </td>';
                        $failRes .= '<td> ' . (isset($warning['correctiveAction']) ? $warning['correctiveAction'] : "") . ' </td>';
                        $failRes .= '</tr>';
                    }
                    $failRes .= '</table>';
                    $pdf->writeHTML($failRes, true, false, true, false, '');
                }
            } else if ($schemeType == 'eid') {

                $n = count($result['responseResult']);
                $labRes = '<span style="font-weight: bold;font-size:13px;">Your laboratory test results : <br/></span>';
                $labRes .= '<table border="1" style="font-size:11px;" cellpadding="3">';
                $labRes .= '<tr style="background-color:#dbe4ee;">';
                $labRes .= '<td colspan="' . ($n + 2) . '" style="text-align:center;font-weight:bold;">Sample ID</td>';
                $labRes .= '</tr>';
                $labRes .= '<tr style="background-color:#dbe4ee;">';
                $labRes .= '<td></td>';
                //Sample codes
                foreach ($result['responseResult'] as $response) {
                    $labRes .= '<td style="text-align:center;font-weight:bold;">' . $response['sample_label'] . '</td>';
                }

                $labRes .= '<td style="text-align:center;font-weight:bold;">Score(%)</td>';
                $labRes .= '</tr>';
                $labRes .= '<tr>';
                $labRes .= '<td>Expected Result</td>';
                foreach ($result['responseResult'] as $response) {
                    $labRes .= '<td style="text-align:left;">' . $response['referenceResult'] . '</td>';
                }
                $labRes .= '<td rowspan="2" style="text-align:center;"><br><br>' . $result['shipment_score'] . '</td>';
                $labRes .= '</tr>';
                $labRes .= '<tr>';
                $labRes .= '<td>Your Results</td>';
                foreach ($result['responseResult'] as $response) {
                    $labRes .= '<td style="text-align:left;">' . $response['labResult'] . '</td>';
                }
                $labRes .= '</tr>';

                $labRes .= '</table>';

                $pdf->SetLeftMargin(15);
                $pdf->writeHTML($labRes, true, false, true, false, '');
            } else {
                //Vl report
                //var_dump($attributes);die;
                $labRes = '<h5>Platform : ' . $result['responseResult'][0]['vl_assay'] . '</h5>';
                if (isset($attributes['vl_assay']) && $attributes['vl_assay'] == 6) {
                    $labRes = '<h5>Platform : ' . $attributes['other_assay'] . '</h5>';
                }
                //$labRes.='</h5>';

                //if($result['responseResult'][0]['no_of_participants'] > 6 && $attributes['vl_assay']!=6) {
                if ($result['responseResult'][0]['no_of_participants'] > 0) {
                    $labRes .= '<table border="1" style="text-align:center;font-weight:bold;width:650px;font-size:11px;">
								<tr>
									<td style="background-color:#8ECF64;"><br><br>Specimen ID </td>
									<td style="background-color:#8ECF64;">Your Results<br/>(log<sub>10</sub> copies/ml)</td>
									<td style="background-color:#8ECF64;">Mean<br/>(log<sub>10</sub> copies/ml)</td>
									<td style="background-color:#8ECF64;"><br><br>S.D.</td>
									<td style="background-color:#8ECF64;"><br><br>No. of Labs</td>
									<td style="background-color:#8ECF64;">Lowest <br/> Acceptable Limit</td>
									<td style="background-color:#8ECF64;">Highest <br/> Acceptable Limit</td>
									<td style="background-color:#8ECF64;"><br><br>Your Grade</td>
								</tr>';
                    if ($result['is_excluded'] == 'yes') {
                        foreach ($result['responseResult'] as $response) {
                            $labRes .= '<tr>
										<td style="text-align:center;">' . $response['sample_label'] . '</td>
										<td>' . $response['reported_viral_load'] . '</td>
										<td>' . number_format(round($response['mean'], 2), 2, '.', '') . '</td>
										<td>' . number_format(round($response['sd'], 2), 2, '.', '') . '</td>
										<td>' . $response['no_of_participants'] . '</td>
										<td>' . number_format(round($response['low'], 2), 2, '.', '') . '</td>
										<td>' . number_format(round($response['high'], 2), 2, '.', '') . '</td>
										<td>Not Evaluated</td>
									  </tr>';
                        }
                    } else {
                        foreach ($result['responseResult'] as $response) {
                            $labRes .= '<tr>
										<td style="text-align:center;">' . $response['sample_label'] . '</td>
										<td>' . ((isset($response['reported_viral_load']) && !empty($response['reported_viral_load']) ? $response['reported_viral_load'] : "")) . '</td>
										<td>' . number_format(round($response['mean'], 2), 2, '.', '') . '</td>
										<td>' . number_format(round($response['sd'], 2), 2, '.', '') . '</td>
										<td>' . $response['no_of_participants'] . '</td>
										<td>' . number_format(round($response['low'], 2), 2, '.', '') . '</td>
										<td>' . number_format(round($response['high'], 2), 2, '.', '') . '</td>
										<td>' . $response['grade'] . '</td>
									  </tr>';
                        }
                    }
                    $labRes .= '</table>';
                } else {

                    $labRes .= '<table border="1" style="text-align:center;font-weight:bold;width:650px;font-size:13px;">
								<tr>
									<td style="background-color:#8ECF64;">Specimen ID </td>
									<td style="background-color:#8ECF64;">Your Results<br/>(log<sub>10</sub> copies/ml)</td>
									<!-- <td style="background-color:#8ECF64;"># of Labs</td> -->
									<td style="background-color:#8ECF64;">Your Grade</td>
								</tr>';

                    foreach ($result['responseResult'] as $response) {

                        $labRes .= '<tr>
										<td style="text-align:center;">' . $response['sample_label'] . '</td>
										<td>' . round($response['reported_viral_load'], 2) . '</td>
										<!-- <td>' . $response['no_of_participants'] . '</td> -->
										<td>Not Graded</td>
									  </tr>';
                    }

                    $labRes .= '</table>';
                    $labRes .= '<h5>' . $result['responseResult'][0]['vl_assay'];
                    if (isset($attributes['vl_assay']) && $attributes['vl_assay'] == 6) {
                        $labRes .= " - " . $attributes['other_assay'];
                    }
                    $labRes .= ' platform was not analyzed or graded</h5>';
                }

                //if(isset($attributes['vl_assay']) && $attributes['vl_assay']==6){
                //    $labRes.='<h5>"Other Platforms were not analyzed or graded"</h5>';
                //}

                $pdf->SetLeftMargin(15);
                $pdf->writeHTML($labRes, true, false, true, false, '');

                $footerHead = '<h5>0.00 = Target Not Detected or less than lower limit of detection</h5><br/>';
                $pdf->writeHTML($footerHead, true, false, true, false, '');
            }
        }

        if ($result['is_excluded'] == 'yes') {
            $wishes = '<p>Your response was not considered for evaluation</p>';
            $pdf->SetLeftMargin(15);
            $pdf->writeHTML($wishes, true, false, true, false, '');
        } else if ($schemeType == 'dts' && $result['is_excluded'] != 'yes') {
            $totalScore = $result['shipment_score'] + $result['documentation_score'];
            if ($totalScore >= $this->passPercentage) {
                $wishes = '<p style="font-size:12px;">Congratulations! You have received a satisfactory score of ' . round($totalScore, 2) . '%.</p>';
            } else {
                $wishes = '<p style="font-size:12px;">You have received a score of ' . round($totalScore, 2) . '%.</p>';
            }

            $pdf->SetLeftMargin(15);

            $pdf->writeHTML($wishes, true, false, true, false, '');
        }

        //if(trim($result['distribution_date'])!=""){
        //    $result['distribution_date']=$this->dateFormat($result['distribution_date']);
        //}
        if (trim($result['shipment_comment']) != "" || trim($result['evaluationComments']) != "" || trim($result['optional_eval_comment']) != "") {
            $comment = '<br><br><table border="1" style="width:100%;font-size:12px;" cellpadding="3">';

            if (trim($result['evaluationComments']) != "") {
                $comment .= '<tr>';
                $comment .= '<td style="font-weight:bold;width:30%;">Evaluation Comments </td>';
                $comment .= '<td style="width:70%;">' . $result['evaluationComments'] . '</td>';
                $comment .= '</tr>';
            }

            if (trim($result['optional_eval_comment']) != "") {
                $comment .= '<tr>';
                $comment .= '<td style="font-weight:bold;width:30%;">Specific Comments/Feedback</td>';
                $comment .= '<td style="width:70%;">' . $result['optional_eval_comment'] . '</td>';
                $comment .= '</tr>';
            }

            if (trim($result['shipment_comment']) != "") {
                $comment .= '<tr>';
                $comment .= '<td style="font-weight:bold;" colspan="2">' . $result['shipment_comment'] . '</td>';
                $comment .= '</tr>';
            }

            $comment .= '</table>';
            //$pdf->SetTopMargin(13);
            $pdf->writeHTML($comment, true, false, true, false, '');
        }
        if ($schemeType == 'vl') {
            $html = '<p>Thank you for participating in the HIV Viral Load Proficiency Testing Program.</p>';
        } else if ($schemeType == 'eid') {
            $html = '<p>Thank you for participating in HIV-1 Proficiency Testing Program for Early Infant Diagnosis using Dried Blood Spot.</p>';
            if (!empty($this->reportComment)) {
                $html .= '<br>' . $this->reportComment;
            }
        } else {
            $html = '<p style="font-size:12px;">Thank you for participating in the ' . ($result['scheme_name']) . ' Proficiency Testing Program.</p>';
        }
        if ($schemeType == 'vl') {
            $html .= '<br><small>Note: A VL platform with the most participants was used as a reference value to evaluate results for VL platforms with less than 6 participants on this PT round.</small>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        if (ob_get_contents()) {
            ob_end_clean();
        }

        //Close and output PDF document
        if (isset($result['last_name']) && trim($result['last_name']) != "") {
            $result['last_name'] = "_" . $result['last_name'];
        }

        //    if (!file_exists(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports'. DIRECTORY_SEPARATOR.$result['shipment_code']) && !is_dir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR .'reports'. DIRECTORY_SEPARATOR.$result['shipment_code'])) {
        //        mkdir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports'. DIRECTORY_SEPARATOR.$result['shipment_code']);
        //    }

        $fileName = $result['shipment_code'] . "-" . $result['map_id'] . ".pdf";
        $fileName = preg_replace('/[^A-Za-z0-9.]/', '-', $fileName);
        $fileName = str_replace(" ", "-", $fileName);
        $filePath = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $result['shipment_code'] . DIRECTORY_SEPARATOR . $fileName;
        $created = $pdf->Output($filePath, "F");

        //$pdf->Output($fileName, 'I');

        $loadpdf = Zend_Pdf::load($filePath);

        foreach ($loadpdf->pages as $page) {
            $pdfExtract = $extractor->clonePage($page);
            //$pdfExtract->setFont($font, 8) ->drawText('Page '.$j.' / '.$totalPages, 280, 50);
            $pdfNew->pages[] = $pdfExtract;
        }
        $shipmentCode = $result['shipment_code'];
        $j++;
    }

    //$mergePdf = $shipmentCode."-bulk-participant-report.pdf";
    $mergePdf = $shipmentCode . "-" . $this->bulkfileNameVal . "-bulk-participant-report.pdf";
    $mergeFilePath = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $shipmentCode . DIRECTORY_SEPARATOR . $mergePdf;
    $pdfNew->save($mergeFilePath);

    foreach ($result['dmResult'] as $dmID => $dmRes) {
        $pdfNew->pages = array();
        $expRes = explode(",", $dmRes);
        $resCount = count($expRes);
        if ($resCount > 0) {
            foreach ($expRes as $res) {
                $expStrRes = explode("#", $res);
                $dmFileName = $dmID . ".pdf";
                $participantFileName = $expStrRes[1] . ".pdf";
                $participantFileName = preg_replace('/[^A-Za-z0-9.]/', '-', $participantFileName);
                $participantFileName = str_replace(" ", "-", $participantFileName);
                $filePath = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $shipmentCode . DIRECTORY_SEPARATOR . $participantFileName;
                if (file_exists($filePath)) {
                    $loadpdf = Zend_Pdf::load($filePath);
                    foreach ($loadpdf->pages as $page) {
                        $pdfExtract = $extractor->clonePage($page);
                        $pdfNew->pages[] = $pdfExtract;
                    }
                }
            }
        }

        $dmFileName = preg_replace('/[^A-Za-z0-9.]/', '-', $dmFileName);
        $dmFileName = str_replace(" ", "-", $dmFileName);

        $mergePdf = $shipmentCode . "-" . $dmFileName;
        $mergeFilePath = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $shipmentCode . DIRECTORY_SEPARATOR . $mergePdf;
        $pdfNew->save($mergeFilePath);
    }
    //============================================================+
    // END OF FILE
    //============================================================+
    exit;
}
