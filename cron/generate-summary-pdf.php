<?php
//Zend_Debug::dump($resultArray);
//die;
require_once('tcpdf/tcpdf.php');
//require_once('libchart/classes/libchart.php');
$config = new Zend_Config_Ini(APPLICATION_PATH . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.ini", APPLICATION_ENV);
if ($resultArray['shipment'] != "") {
    if ($resultArray['shipment']['scheme_type'] != 'eid') {
        require_once('libchart/classes/libchart.php');
        $chart = new VerticalBarChart(700, 400);
    }
    if (!file_exists(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports') && !is_dir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports')) {
        mkdir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports');
    }
    if (!file_exists(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code']) && !is_dir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'])) {
        mkdir(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code']);
    }

    

    // create new PDF document
    $pdf = new SummaryPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
        require_once(dirname(__FILE__) . '/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    $pdf->setSchemeName($header, $resultArray['shipment']['scheme_name'], $logo, $logoRight, $comingFrom, $resultArray['shipment']['scheme_type']);
    // add a page
    //$pdf->AddPage();
    // ---------------------------------------------------------
    if ($resultArray['shipment']['scheme_type'] == 'eid') {
        $pdf->AddPage('P', 'A4');


        $pdf->SetFont('helvetica', 'B', 11);

        $referenceRes = '<table style="text-align:center;width:100%" align="left">';
        $referenceRes .= '<tr>';
        $referenceRes .= '<td style="font-weight:normal;width:20%;">PT Panel</td><td style="font-weight:normal;width:30%;">' . $resultArray['shipment']['distribution_code'] . '</td>';
        $referenceRes .= '</tr>';
        $referenceRes .= '<tr>';
        $referenceRes .= '<td style="font-weight:normal;width:20%;">Shipment Date</td><td style="font-weight:normal;width:30%;">' . dateFormat($resultArray['shipment']['shipment_date']) . '</td>';
        $referenceRes .= '</tr>';
        $referenceRes .= '</table>';

        $pdf->writeHTML($referenceRes, true, false, true, false, '');


        $participantCount = '';

        if (isset($resultArray['shipment']['participant_count'])) {
            $participantCount = $resultArray['shipment']['participant_count'];
        }
        if (isset($resultArray['shipment']['summaryResult']) && sizeof($resultArray['shipment']['summaryResult']) > 0) {

            foreach ($resultArray['shipment']['summaryResult'] as $result) {
                $overAllMaxScore = 0;
                $overAllBelowScore = 0;
                $partCount = count($result);
                for ($i = 0; $i < $partCount; $i++) {
                    if ($resultArray['shipment']['max_score'] == $result[$i]['shipment_score']) {
                        $overAllMaxScore++;
                    } else {
                        $overAllBelowScore++;
                    }
                }
            }
            $scoringPer = round(($overAllMaxScore / $partCount) * 100, 2);
            // set font
            $pdf->SetFont('helvetica', 'B', 10);
            $overAllSumRes = '<h3>Summary of All Laboratory Scores*</h3>';
            $overAllSumRes .= '<table border="1" cellpadding="3">';
            $overAllSumRes .= '<tr style="background-color:#BCD7EC;">';
            $overAllSumRes .= '<td style="text-align:center;">Total Number of participating laboratories</td>';
            $overAllSumRes .= '<td style="text-align:center;">Number of laboratories submitted results</td>';
            $overAllSumRes .= '<td style="text-align:center;">Number of laboratories scoring "' . $resultArray['shipment']['max_score'] . '"</td>';
            $overAllSumRes .= '<td style="text-align:center;">Number of laboratories scoring below "' . $resultArray['shipment']['max_score'] . '"</td>';
            $overAllSumRes .= '<td style="text-align:center;">Percentage of laboratories scoring "' . $resultArray['shipment']['max_score'] . '"</td>';
            $overAllSumRes .= '</tr>';

            $overAllSumRes .= '<tr>';
            $overAllSumRes .= '<td style="text-align:center;">' . $participantCount . '</td>';
            $overAllSumRes .= '<td style="text-align:center;">' . $partCount . '</td>';
            $overAllSumRes .= '<td style="text-align:center;">' . $overAllMaxScore . '</td>';
            $overAllSumRes .= '<td style="text-align:center;">' . $overAllBelowScore . '</td>';
            $overAllSumRes .= '<td style="text-align:center;">' . $scoringPer . '%</td>';
            $overAllSumRes .= '</tr>';

            $overAllSumRes .= '</table>';

            $pdf->writeHTML($overAllSumRes, true, false, true, false, '');

            $n = count($resultArray['shipment']['correctRes']);
            if ($n > 0) {
                $overAllCorrectRes = '<h3>Percentage of laboratories reporting correctly*</h3>';
                $overAllCorrectRes .= '<table border="1" cellpadding="4">';

                $overAllCorrectRes .= '<tr style="background-color:#BCD7EC;">';
                $overAllCorrectRes .= '<td  style=""></td>';
                $overAllCorrectRes .= '<td style="text-align:center;font-weight:bold;" colspan="' . ($n) . '">Sample ID</td>';
                $overAllCorrectRes .= '<td></td>';
                $overAllCorrectRes .= '</tr>';

                $overAllCorrectRes .= '<tr style="background-color:#BCD7EC;">';
                $overAllCorrectRes .= '<td></td>';
                foreach ($resultArray['shipment']['correctRes'] as $cKey => $cVal) {
                    $overAllCorrectRes .= '<td style="text-align:center;">' . $cKey . '</td>';
                }
                $overAllCorrectRes .= '<td style="text-align:center;">Average</td>';
                $overAllCorrectRes .= '</tr>';

                $avg = 0;
                $overAllCorrectRes .= '<tr>';
                $overAllCorrectRes .= '<td rowspan="2"  style="text-align:center;">Correctly Reported</td>';

                $tot = 0;
                foreach ($resultArray['shipment']['correctRes'] as $cKey => $cVal) {
                    $avg += $cVal;
                    $overAllCorrectRes .= '<td style="text-align:center;">' . $cVal . '</td>';
                }
                $overAllCorrectRes .= '<td style="text-align:center;">' . round(($avg / $n), 2) . '</td>';
                $overAllCorrectRes .= '</tr>';
                $overAllCorrectRes .= '<tr>';

                $avg = 0;
                foreach ($resultArray['shipment']['correctRes'] as $cKey => $cVal) {
                    $avg += round(($cVal / $partCount) * 100, 2);
                    $overAllCorrectRes .= '<td style="text-align:center;">' . round(($cVal / $partCount) * 100, 2) . '%</td>';
                }
                $overAllCorrectRes .= '<td style="text-align:center;">' . round(($avg / $n), 2) . '%</td>';
                $overAllCorrectRes .= '</tr>';
                $overAllCorrectRes .= '</table>';
                $overAllCorrectRes .= '* Includes In-house and Other assays<br>';

                $pdf->writeHTML($overAllCorrectRes, true, false, true, false, '');
            }

            $k = count($resultArray['shipment']['avgAssayResult']);

            //Zend_Debug::dump($resultArray['shipment']['avgAssayResult']);die;

            $avgAssay = "";
            if ($k > 0) {
                foreach ($resultArray['shipment']['avgAssayResult'] as $assayResult) {
                    $avgAssay = '';
                    $avgAssay .= '<br><div style="border: 1px solid #000000;">';


                    $avgAssay .= '<table border="1" cellpadding="4">';

                    $avgAssay .= '<tr style="background-color:#fff;">';
                    $avgAssay .= '<td colspan="4"><span>Summary of All Laboratories using ' . $assayResult['vlAssay'] . '</span></td>';
                    $avgAssay .= '</tr>';



                    $avgAssay .= '<tr style="background-color:#BCD7EC;">';
                    $avgAssay .= '<td style="text-align:center;">Number of Laboratories</td>';
                    $avgAssay .= '<td style="text-align:center;">Number of Laboratories Scoring "' . $resultArray['shipment']['max_score'] . '"</td>';
                    $avgAssay .= '<td style="text-align:center;">Number of Laboratories Scoring Below "' . $resultArray['shipment']['max_score'] . '"</td>';
                    $avgAssay .= '<td style="text-align:center;">Percentage of Laboratories Scoring "' . $resultArray['shipment']['max_score'] . '"</td>';
                    $avgAssay .= '</tr>';

                    $avgAssay .= '<tr>';
                    $avgAssay .= '<td style="text-align:center;">' . $assayResult['participantCount'] . '</td>';
                    $avgAssay .= '<td style="text-align:center;">';
                    $assayResult['maxScore'] = (isset($assayResult['maxScore']) ? $assayResult['maxScore'] : "0");
                    $avgAssay .= $assayResult['maxScore'];
                    $avgAssay .= '</td>';
                    $avgAssay .= '<td style="text-align:center;">';
                    $assayResult['belowScore'] = (isset($assayResult['belowScore']) ? $assayResult['belowScore'] : "0");
                    $avgAssay .= $assayResult['belowScore'];
                    $avgAssay .= '</td>';
                    $avgAssay .= '<td style="text-align:center;">';
                    $avgAssay .= round(($assayResult['maxScore'] / $assayResult['participantCount']) * 100, 2) . "%";
                    $avgAssay .= '</td>';
                    $avgAssay .= '</tr>';
                    $avgAssay .= '</table>';


                    $m = count($assayResult['specimen']);
                    $avgAssay .= '<br/><br/><table border="1" cellpadding="3">';
                    $avgAssay .= '<tr style="background-color:#BCD7EC;">';
                    $avgAssay .= '<td></td>';
                    $avgAssay .= '<td style="text-align:center;" colspan="' . ($m) . '">Sample ID</td>';
                    $avgAssay .= '<td></td>';
                    $avgAssay .= '</tr>';

                    $avgAssay .= '<tr style="background-color:#BCD7EC;">';
                    $avgAssay .= '<td></td>';
                    foreach ($assayResult['specimen'] as $sKey => $sample) {
                        $avgAssay .= '<td style="text-align:center;">' . $sKey . '</td>';
                    }
                    $avgAssay .= '<td style="text-align:center;">Average</td>';
                    $avgAssay .= '</tr>';
                    $sampleAvg = 0;
                    $sCount = count($assayResult['specimen']);
                    $avgAssay .= '<tr>';
                    $avgAssay .= '<td rowspan="2" style="text-align:center;background-color:#BCD7EC;">Correctly Reported</td>';
                    foreach ($assayResult['specimen'] as $sKey => $sample) {
                        $sampleAvg += $sample['correctRes'];
                        $avgAssay .= '<td style="text-align:center;">' . $sample['correctRes'] . '</td>';
                    }
                    $avg = round(($sampleAvg / $sCount), 2);
                    $avgAssay .= '<td style="text-align:center;">' . $avg . '</td>';
                    $avgAssay .= '</tr>';

                    $sampleAvgInPer = 0;
                    $avgAssay .= '<tr>';
                    foreach ($assayResult['specimen'] as $sKey => $sample) {
                        $sampleAvgInPer += $sample['correctRes'];
                        $avgAssay .= '<td style="text-align:center;">' . round(($sample['correctRes'] / $assayResult['participantCount']) * 100, 2) . '%</td>';
                    }
                    $avgAssay .= '<td style="text-align:center;">' . round(($avg / $assayResult['participantCount']) * 100, 2) . '%</td>';
                    $avgAssay .= '</tr>';
                    $avgAssay .= '</table>';

                    $avgAssay .= '</div><br/>';
                    $pdf->writeHTML($avgAssay, true, false, true, false, '');
                    if ($pdf->getY() >= 250) {
                        $pdf->AddPage();
                    }
                }
            }
        }
    } else if ($resultArray['shipment']['scheme_type'] == 'vl') {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 11);

        $referenceRes = '<table style="text-align:center;width:100%" align="left">';
        $referenceRes .= '<tr>';
        $referenceRes .= '<td style="font-weight:normal;width:20%;">PT Panel</td><td style="font-weight:normal;width:30%;">' . $resultArray['shipment']['distribution_code'] . '</td>';
        $referenceRes .= '</tr>';
        $referenceRes .= '<tr>';
        $referenceRes .= '<td style="font-weight:normal;width:20%;">Shipment Date</td><td style="font-weight:normal;width:30%;">' . dateFormat($resultArray['shipment']['shipment_date']) . '</td>';
        $referenceRes .= '</tr>';
        $referenceRes .= '</table>';
        $pdf->writeHTML($referenceRes, true, false, true, false, '');

        //if (count($resultArray['shipment']['summaryResult']) > 0) {
        //    
        //    
        //    foreach ($resultArray['shipment']['summaryResult'] as $result) {
        //        $acceptableCount = 0;
        //        $notAcceptableScore = 0;
        //        $countIsExcluded = 0;
        //        $partCount = count($result);
        //        
        //        for ($i = 0; $i < $partCount; $i++) {
        //            if($result[$i]['is_excluded']=='yes'){
        //                $countIsExcluded++;
        //            }
        //            
        //            $score=($result[$i]['shipment_score']/$resultArray['shipment']['no_of_samples'])*100;
        //            
        //            if($score==100){
        //                $acceptableCount++;
        //            }else{
        //                $notAcceptableScore++;
        //            }
        //        }
        //    }
        //   
        //    // set font
        //    $pdf->SetFont('helvetica', 'B', 12);
        //    
        //    $overview= '<table border="1" style="font-size:13px;"><tr>';
        //    $overview.='<td style="background-color:#dbe4ee;text-align:center;"># Responses </td>';
        //    $overview.='<td style="background-color:#dbe4ee;text-align:center;"># Acceptable</td>';
        //    $overview.='<td style="background-color:#dbe4ee;text-align:center;"># Not Acceptable</td>';
        //    $overview.='<td style="background-color:#dbe4ee;text-align:center;"># Is Excluded</td>';
        //    $overview.='</tr>';
        //    
        //    
        //    $overview.='<tr>';
        //    $overview.='<td style="text-align:center;">' . $partCount . '</td>';
        //    $overview.='<td style="text-align:center;">' . $acceptableCount.'</td>';
        //    $overview.='<td style="text-align:center;">' . $notAcceptableScore.'</td>';
        //    $overview.='<td style="text-align:center;">'.$countIsExcluded.'</td>';
        //    
        //    $overview.='</tr>';
        //
        //    $overview.='</table><br/>';
        //    $pdf->writeHTML($overview, true, false, true, false, '');     
        //}

        //Zend_Debug::dump($resultArray['vlCalculation']);die;
        if (count($resultArray['vlCalculation']) > 0) {

            foreach ($resultArray['vlCalculation'] as $vlCal) {

                if (isset($vlCal['participant-count']) && $vlCal['participant-count'] > 0) {
                    if (isset($vlCal['otherAssayName']) && count($vlCal['otherAssayName']) > 0) {
                        $calRes = '<h5>Summary of ' . implode(", ", $vlCal['otherAssayName']) . ' | No. of Labs : ' . $vlCal['participant-count'] . ' </h5>';
                    } else {
                        $calRes = '<h5>Summary of ' . $vlCal['vlAssay'] . ' Results | No. of Labs : ' . $vlCal['participant-count'] . ' </h5>';
                    }

                    $calRes .= '<table border="1" style="text-align:center;font-weight:bold;width:650px;font-size:11px;height:500px;">
                    <tr>
                        <!-- <td style="background-color:#8ECF64;text-align:center;"><br><br>Platform </td> -->
                        <td style="background-color:#8ECF64;text-align:center;"><br><br>Specimen ID </td>
                        <td style="background-color:#8ECF64;text-align:center;width:100px;"><br><br>Mean<br/>(log<sub>10</sub> copies/ml)</td>
                        <td style="background-color:#8ECF64;text-align:center;"><br><br>S.D.</td>
                        <td style="background-color:#8ECF64;text-align:center;">Lowest Acceptable Limit</td>
                        <td style="background-color:#8ECF64;text-align:center;">Highest Acceptable Limit</td>
                        <td style="background-color:#8ECF64;text-align:center;"><br><br>CV</td>
                    </tr>';

                    $countCal = count($vlCal) - 1;
                    $otherList = "";

                    for ($c = 0; $c < $countCal; $c++) {
                        if (isset($vlCal[$c]['mean'])) {
                            $calRes .= '<tr>';
                            // if($c==0){
                            //     //if($vlCal[$c]['vl_assay']==6){
                            //         //$calRes.='<td style="text-align:center;" rowspan="'.$countCal.'"><br><br>'.implode(", ",$vlCal['otherAssayName']).'</td>';
                            //         //////$otherList = implode(", ",$vlCal['otherAssayName']);
                            //     //}else{
                            //         $calRes.='<td style="text-align:center;" rowspan="'.$countCal.'"><br><br>'.$vlCal['shortName'].'</td>';
                            //     //}
                            // }

                            $calRes .= '<td>' . $vlCal[$c]['sample_label'] . '</td>
                            <td>' . number_format(round($vlCal[$c]['mean'], 2), 2, '.', '') . '</td>
                            <td>' . number_format(round($vlCal[$c]['sd'], 2), 2, '.', '') . '</td>
                            <td>' . number_format(round($vlCal[$c]['low_limit'], 2), 2, '.', '') . '</td>
                            <td>' . number_format(round($vlCal[$c]['high_limit'], 2), 2, '.', '') . '</td>
                            <td>' . number_format(round($vlCal[$c]['cv'], 2), 2, '.', '') . '</td>
                          </tr>';
                        }
                    }

                    $calRes .= '</table>';

                    $pdf->writeHTML($calRes, true, false, true, false, '');
                }
            }
        }


        $uncalculatedAssayList = null;
        //Zend_Debug::dump($resultArray['pendingAssay']);die;
        // if(isset($resultArray['pendingAssay']['count']) && ($resultArray['pendingAssay']['count'])>0){

        //     if($pdf->getY()>=250){
        //         $pdf->AddPage();
        //     }
        //     $unCalRes='<h5>Summary of Other Results | No. of Labs : '.$resultArray['pendingAssay']['count'].' </h5>';
        //     $unCalRes.='<table cellpadding="6" border="1" style="text-align:left;font-weight:normal;width:660px;font-size:11px;"><tr><td>'.implode(", ",$resultArray['pendingAssay']['assayNames']).' platforms were not analyzed or graded due to less than 6 participating labs using the same platform.</td></tr></table>';

        //     $pdf->writeHTML($unCalRes, true, false, true, false, '');            

        // }
        $footerHead = '<h5>0.00 = Target Not Detected or less than lower limit of detection</h5>';
        $footerHead .= '<small>Note: A VL platform with the most participants was used as a reference value to evaluate results for VL platforms with less than 6 participants on this PT round.</small>';
        $pdf->writeHTML($footerHead, true, false, true, false, '');
    } else {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 12);
        if (count($resultArray['shipment']['referenceResult']) > 0) {

            $referenceRes = '<table style="text-align:center;width:100%" align="left">';
            $referenceRes .= '<tr>';
            $referenceRes .= '<td style="font-weight:normal;width:50%;font-size:12px;"><span style="font-weight:bold;">PT Survey</span><br>' . $resultArray['shipment']['distribution_code'] . ' (' . dateFormat($resultArray['shipment']['shipment_date']) . ')</td>';
            $referenceRes .= '<td style="font-weight:normal;width:50%;font-size:12px;padding-left:20px;"><span style="font-weight:bold;">Shipment Code</span><br>' . $resultArray['shipment']['shipment_code'] . '</td>';
            $referenceRes .= '</tr>';
            $referenceRes .= '</table>';

            $pdf->writeHTML($referenceRes, true, false, true, false, '');
        }
        if (count($resultArray['shipment']['summaryResult']) > 0) {
            $labCounter = 1;
            $pass = $config->evaluation->dts->passPercentage;
            $barPoints["0 - 59"] = 0;
            $barPoints["60 - 69"] = 0;
            $barPoints["70 - $pass"] = 0;
            $abovePass = sprintf("above $pass");
            $barPoints[$abovePass] = 0;
            foreach ($resultArray['shipment']['summaryResult'] as $result) {
                $maxScore = 0;
                $belowScore = 0;
                $partCount = count($result) - 1;
                //Zend_Debug::dump($result['correctCount']);
                for ($i = 0; $i < $partCount; $i++) {
                    if ($result[$i]['is_excluded'] == 'yes') {
                        continue;
                    }
                    $totalScore = $result[$i]['shipment_score'] + $result[$i]['documentation_score'];
                    if ($totalScore > 0 && $totalScore < 60) {
                        $barPoints["0 - 59"]++;
                    } else if ($totalScore > 59 && $totalScore < 70) {
                        $barPoints["60 - 69"]++;
                    } else if ($totalScore > 69 && $totalScore <= $pass) {
                        $barPoints["70 - $pass"]++;
                    } else if ($totalScore > $config->evaluation->dts->passPercentage) {
                        $barPoints[$abovePass]++;
                    } else { }
                    if (($totalScore) >= $config->evaluation->dts->passPercentage) {
                        $maxScore++;
                    } else {
                        $belowScore++;
                    }
                    $labCounter++;
                }

                $scoringPer = round(($maxScore / $partCount) * 100, 2);
            }
            $dataSet = new XYSeriesDataSet();

            $chart->getPlot()->getPalette()->setBarColor(array(
                new Color(128, 0, 0),
                new Color(255, 0, 0),
                new Color(255, 255, 0),
                new Color(0, 128, 0)
            ));
            foreach ($barPoints as $key => $val) {
                $serie = new XYDataSet();
                $serie->addPoint(new Point("", $val));
                $dataSet->addSerie($key, $serie);
            }

            $chart->setDataSet($dataSet);

            //Bound::setUpperBound(50);
            $chart->getPlot()->setGraphCaptionRatio(0.5);
            $chart->setTitle("Comparison of test performance between participating laboratories");
            $chart->render(DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'] . DIRECTORY_SEPARATOR . "bar_chart.png");

            // set font
            $pdf->SetFont('helvetica', 'B', 12);


            $overview = '<table border="1" style="font-size:13px;"><tr>';
            $overview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Participants </td>';
            $overview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Participants Scoring >= "' . $config->evaluation->dts->passPercentage . '"</td>';
            $overview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Participants Scoring < "' . $config->evaluation->dts->passPercentage . '" </td>';
            $overview .= '</tr>';


            $overview .= '<tr>';
            $overview .= '<td style="text-align:center;font-weight:normal;">' . $partCount . '</td>';
            $overview .= '<td style="text-align:center;font-weight:normal;">' . $maxScore . ' (' . round(($maxScore / $partCount) * 100, 2) . '%)</td>';
            $overview .= '<td style="text-align:center;font-weight:normal;">' . $belowScore . ' (' . round(($belowScore / $partCount) * 100, 2) . '%)</td>';

            $overview .= '</tr>';

            $overview .= '</table><br/>';
            $pdf->writeHTML($overview, true, false, true, false, '');


            $sampleCount = count($result['correctCount']);

            $sampleCode = '<table border="1" style="font-size:13px;">';
            $sampleCode .= '<tr>';
            $sampleCode .= '<td rowspan="2"></td>';
            $sampleCode .= '<td colspan="' . $sampleCount . '" style="background-color:#dbe4ee;text-align:center;">Sample ID</td>';
            $sampleCode .= '<td></td>';
            $sampleCode .= '</tr>';

            $sampleCode .= '<tr>';

            foreach ($result['correctCount'] as $sample) {
                $sampleCode .= '<td style="background-color:#dbe4ee;text-align:center;">' . $sample['sample_label'] . '</td>';
            }
            $sampleCode .= '<td style="background-color:#dbe4ee;text-align:center;">Average</td>';
            $sampleCode .= '</tr>';
            $sampleCode .= '<tr>';
            $sampleCode .= '<td style="background-color:#dbe4ee;text-align:center;">Expected Result</td>';

            $nonMandatorySamples = array();

            foreach ($resultArray['shipment']['referenceResult'] as $refRes) {

                if ($refRes['mandatory'] == 0) {
                    $nonMandatorySamples[] = $refRes['sample_label'];
                }
                $sampleCode .= '<td style="text-align:center;font-weight:normal;">' . ucfirst(strtolower($refRes['referenceResult'])) . '</td>';
            }

            $sampleCode .= '<td></td>';
            $sampleCode .= '</tr>';
            $sampleCode .= '<tr>';
            $sampAvg = '0';
            $sampPerAvg = '0';
            $sampleCode .= '<td style="background-color:#BCD7EC;text-align:center;">Correctly Reported</td>';

            foreach ($result['correctCount'] as $sample) {
                $sampAvg += $sample["correctRes"];
                $sampPerAvg += (($sample["correctRes"] / $partCount) * 100);
                $sampleCode .= '<td style="text-align:center;font-weight:normal;">' . $sample["correctRes"] . '<br>(' . round(($sample["correctRes"] / $partCount) * 100, 2) . '%)</td>';
            }

            $sampleCode .= '<td style="text-align:center;font-weight:normal;">' . round(($sampAvg / $sampleCount), 2) . '<br>(' . round(($sampPerAvg / $sampleCount), 2) . '%)</td>';
            $sampleCode .= '</tr>';


            $sampleCode .= '</table></br>';
            $pdf->writeHTML($sampleCode, true, false, true, false, '');

            if (count($nonMandatorySamples) > 0) {
                $nmsTable = '<span style="font-size:13px;">';
                $nmsTable .= "The following samples have been excluded from this evaluation : " . implode(", ", $nonMandatorySamples);
                $nmsTable .= "</span><br/>";
                $pdf->writeHTML($nmsTable, true, false, true, false, '');
            }


            //----------------Participant Performance Overview  start----------------
            $ppOverview = '<span style="font-size:13px;">Participant Performance Overview</span><br/>';
            $ppOverview .= '<table border="1"  style="font-size:13px;"><tr>';
            $ppOverview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Participants</td>';
            $ppOverview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Responses</td>';
            $ppOverview .= '<td style="background-color:#dbe4ee;text-align:center;"># of Valid Responses</td>';
            $ppOverview .= '<td style="background-color:#dbe4ee;text-align:center;">Average Score</td></tr>';

            $ppOverview .= '<tr>';
            $ppOverview .= '<td style="text-align:center;font-weight:normal;">' . $participantPerformance['total_shipped'] . '</td>';
            $ppOverview .= '<td style="text-align:center;font-weight:normal;">' . $participantPerformance['total_responses'] . '</td>';
            $ppOverview .= '<td style="text-align:center;font-weight:normal;">' . $participantPerformance['valid_responses'] . '</td>';
            $ppOverview .= '<td style="text-align:center;font-weight:normal;">' . round($participantPerformance['average_score'], 2) . '</td>';
            $ppOverview .= '</tr>';

            $ppOverview .= '</table><br>';
            //----------------Participant Performance Overview  Ends----------------

            $pdf->writeHTML($ppOverview, true, false, true, false, '');

            if (count($correctivenessArray) > 0) {
                //----------------Participant Corrective Action Overview  start----------------
                $correctiveActionStuff = '<span style="font-size:13px;">Corrective Action Overview </span><br/>
            <table border="1" style="font-weight:normal;font-size:13px;"><tr style="font-weight:bold;">';
                $correctiveActionStuff .= '<td style="background-color:#dbe4ee;text-align:center;width:75%;">Corrective Action</td>';
                $correctiveActionStuff .= '<td style="background-color:#dbe4ee;text-align:center;width:25%;">Responses having Corrective Action</td>';

                $correctiveActionStuff .= '</tr>';
                foreach ($correctivenessArray as $correctiveness) {
                    $correctiveActionStuff .= '<tr>';
                    $correctiveActionStuff .= '<td style="text-align:left;">' . $correctiveness['corrective_action'] . '</td>';
                    $correctiveActionStuff .= '<td style="text-align:center;">' . $correctiveness['total_corrective'] . '</td>';

                    $correctiveActionStuff .= '</tr>';
                }
                $correctiveActionStuff .= '</table><br/>';

                //----------------Participant Corrective Action Overview  Ends----------------

                $pdf->writeHTML($correctiveActionStuff, true, false, true, false, '');
            }

            $image_file = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'] . DIRECTORY_SEPARATOR . 'bar_chart.png';
            //$image_file = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'logo'. DIRECTORY_SEPARATOR.'logo_example.jpg';
            //$mask = $pdf->Image('images/alpha.png', 50, 140, 100, '', '', '', '', false, 300, '', true);
            $y = $pdf->getY() + 5;
            $pdf->Image($image_file, 5, $pdf->getY(), '', '', '', '', '', false, 300);
            if (file_exists($image_file)) {
                unlink($image_file);
            }

            //$pdf->Image($image_file, 10, 10, 25, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        //$pdf->AddPage();

        if ($resultArray['shipment']['scheme_type'] == 'dts') {
            if (count($resultArray['shipment']['pieChart']) > 0) {
                $chart = new PieChart(700, 400);
                $dataSet = new XYDataSet();
                foreach ($resultArray['shipment']['pieChart'] as $piechart) {
                    $dataSet->addPoint(new Point($piechart['kit_name'] . " (N=" . $piechart['count'] . ")", $piechart['count']));
                }
                $piechart = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'] . DIRECTORY_SEPARATOR . 'pieChart.png';
                $chart->setDataSet($dataSet);

                $chart->getPlot()->setGraphCaptionRatio(0.5);
                $chart->setTitle("Test kits used for DTS-based PT survey " . $resultArray['shipment']['distribution_code']);
                $chart->render($piechart);

                $pdf->Image($piechart, 5, $y, '', '', '', '', '', false, 300);
                if (file_exists($piechart)) {
                    unlink($piechart);
                }
            }
        }
        if ($resultArray['shipment']['scheme_type'] == 'dbs') {
            $chart = new PieChart(700, 400);

            $dataSet = new XYDataSet();

            if (trim($resultArray['shipment']['dbsPieChart']['EIA/EIA/EIA/WB']) != "") {
                $dataSet->addPoint(new Point("EIA/EIA/EIA/WB", $resultArray['shipment']['dbsPieChart']['EIA/EIA/EIA/WB']));
            }

            if (trim($resultArray['shipment']['dbsPieChart']['EIA/EIA/EIA']) != "") {
                $dataSet->addPoint(new Point("EIA/EIA/EIA", $resultArray['shipment']['dbsPieChart']['EIA/EIA/EIA']));
            }
            if (trim($resultArray['shipment']['dbsPieChart']['EIA/EIA/WB']) != "") {
                $dataSet->addPoint(new Point("EIA/EIA/WB", $resultArray['shipment']['dbsPieChart']['EIA/EIA/WB']));
            }
            if (trim($resultArray['shipment']['dbsPieChart']['EIA/EIA']) != "") {
                $dataSet->addPoint(new Point("EIA/EIA", $resultArray['shipment']['dbsPieChart']['EIA/EIA']));
            }
            if (trim($resultArray['shipment']['dbsPieChart']['EIA/WB']) != "") {
                $dataSet->addPoint(new Point("EIA/WB", $resultArray['shipment']['dbsPieChart']['EIA/WB']));
            }
            if (trim($resultArray['shipment']['dbsPieChart']['EIA']) != "") {
                $dataSet->addPoint(new Point("EIA", $resultArray['shipment']['dbsPieChart']['EIA']));
            }

            $piechart = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'] . DIRECTORY_SEPARATOR . 'pieChart.png';
            $chart->setDataSet($dataSet);

            $chart->setTitle("HIV Testing Algorithms used for DBS-based PT survey " . $resultArray['shipment']['distribution_code']);
            $chart->render($piechart);

            $pdf->Image($piechart, 5, $y, '', '', '', '', '', false, 300);
            if (file_exists($piechart)) {
                unlink($piechart);
            }
        }
    }

    if ($resultArray['shipment']['scheme_type'] == 'dts' && isset($responseResult) && count($responseResult) > 0  && $responseResult != '') {
        $y = $pdf->getY() + 5;
        $chart1 = new PieChart(700, 400);
        $dataSet = new XYDataSet();

        $passed = $responseResult["number_passed"];
        $failed = $responseResult["number_failed"];
        $notResponded = $responseResult["others"];
        $late = $responseResult["number_late"];
        $excluded = $responseResult["excluded"];

        $dataSet->addPoint(new Point("Passed (N=$passed)", $passed));
        $dataSet->addPoint(new Point("Failed (N=$failed)", $failed));
        $dataSet->addPoint(new Point("Not Responded (N=$notResponded)", $notResponded));
        $dataSet->addPoint(new Point("Late Response (N=$late)", $late));
        $dataSet->addPoint(new Point("Excluded (N=$excluded)", $excluded));

        $performancePiechart = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $responseResult["shipment_code"] . DIRECTORY_SEPARATOR . 'performanceChart.png';

        $chart1->setDataSet($dataSet);
        $chart1->setTitle("Shipment Participant Result Report");
        $chart1->render($performancePiechart);
        $pdf->Image($performancePiechart, 5, $y, '', '', '', '', '', false, 300);
        if (file_exists($performancePiechart)) {
            unlink($performancePiechart);
        }
    }


    //Close and output PDF document
    $fileName = $resultArray['shipment']['shipment_code'] . "-summary.pdf";
    $filePath = DOWNLOADS_FOLDER . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $resultArray['shipment']['shipment_code'] . DIRECTORY_SEPARATOR . $fileName;
    //$pdf->Output('example_003.pdf', 'I');
    $pdf->Output($filePath, "F");

    //============================================================+
    // END OF FILE
    //============================================================+
    echo "success";
}
