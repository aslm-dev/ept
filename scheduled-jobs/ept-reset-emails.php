<?php
include_once 'CronInit.php';

$conf = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
try {
    $db = Zend_Db::factory($conf->resources->db);
    Zend_Db_Table::setDefaultAdapter($db);

    date_default_timezone_set('GMT');
    $filename = UPLOAD_PATH . DIRECTORY_SEPARATOR . "email-reminder.csv";

    if (!file_exists($filename) || !is_readable($filename))
        return FALSE;
    $data = array();
    ini_set('auto_detect_line_endings', TRUE);
    if (($handle = fopen($filename, 'r')) !== false) {
        while (($line = fgetcsv($handle)) !== false) {
            $data[] = ($line);
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings', FALSE);
    unset($data[0]);
    
    foreach ($data as $row) {
        $resetMails[$row[0]] = $row; // $row[0] is country name, we are just bunching all country data into separate array
    }
    $commonService = new Application_Service_Common();
    foreach ($resetMails as $countryName => $participants) {
            $to = $participants[5];
            $cc = "thanaseelan@deforay.com";
            $bcc = '';
            $subject = '';
            $message = '';
            $fromMail = '';
            $fromName = '';
            //Subject
            $subject .= "[IMPORTANT] ePT Login Credentials expiring for " . $countryName; // $
            //Message
            $message .= '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;background-color:#FFF;">';
            $message .= '<tr><td align="center">';
            $message .= '<table cellpadding="3" style="width:98%;font-family:Helvetica,Arial,sans-serif;margin:30px 0px 30px 0px;padding:2% 0% 0% 2%;background-color:#ffffff;text-align:justify;">';

            $message .= '<tr><td colspan="2">Dear PT Participant,</td></tr>';

            $message .= '<tr><td colspan="2">We are in the process of improving the ePT system. During recent internal system audit, we noticed that few several PT Participants" emails  for '.$countryName.' were invalid and not verified.  </td></tr>';
            $message .= '<tr><td colspan="2">In order to improve security and privacy, we recommend to use valid and active emails for all the participant logins. This will also helps us in sending timely announcements, updates and information on the PT program to the correct email addresses.</td></tr>';
            
            $message .= '<tr><td colspan="2">Below is the list of login emails that will be expired as a part of this initiative. You must provide a valid email for each laboratory account before the expiry date.</td></tr>';
            
            // CREATE THE TABLE WITH LINKS HERE
            $message .= '
            <tr>
                <td>
                    <table style="border-collapse: collapse;">
                        <thead>
                            <tr align="center">
                                <th style="background-color:#f1f1f1;border: 1px solid black;">S.No</th>
                                <th style="background-color:#f1f1f1;border: 1px solid black;">Lab ID</th>
                                <th style="background-color:#f1f1f1;border: 1px solid black;">Lab Name</th>
                                <th style="background-color:#f1f1f1;border: 1px solid black;">Login ID</th>
                                <th style="background-color:#f1f1f1;border: 1px solid black;">Expires On</th>
                                <th style="background-color:#f1f1f1;border: 1px solid black;">Enter Correct Email</th>
                            </tr>
                        </thead>
                        <tbody>';
                        $sno = 1;
                        // foreach ($resetMails as $countryName => $participants) {
                            foreach ($participants as $prow) {
                                if ((isset($participants[5]) && trim($participants[5]) != '')) {
                                    $query = $db->select()->from('data_manager')->where("primary_email = ?", $participants[3])->where("last_date_for_email_reset IS null AND force_profile_check = 'yes'");
                                    // die($query);
                                    $totParticipantsRes = $db->fetchRow($query);
                                    
                                    if($totParticipantsRes){
                                        $message .= '<tr>
                                                        <td style="border: 1px solid black;">'.$sno.'</td>
                                                        <td style="border: 1px solid black;">'.$participants[1].'</td>
                                                        <td style="border: 1px solid black;">'.$participants[2].'</td>
                                                        <td style="border: 1px solid black;">'.$totParticipantsRes['primary_email'].'</td>
                                                        <td style="border: 1px solid black;">'.$participants[4].'</td>
                                                        <td style="border: 1px solid black;"><a href="'.$conf->domain.'auth/verify-email/?t='.hash('sha256', base64_encode($totParticipantsRes['primary_email']).$conf->salt).'">Click here</a></td>
                                                    </tr>';
                                        $db->update('data_manager', array('last_date_for_email_reset' => $participants[4], 'updated_on' => new Zend_Db_Expr('now()')), 'dm_id=' . $totParticipantsRes['dm_id']);
                                    }
                                }
                                $sno++;
                            }
                        // }
            $message .= '</tbody>
                    </table>
                </td>
            </tr>';
            $message .= '<tr><td colspan="2"></td></tr>';
            
            
            $message .= '<tr><td colspan="2">To reset the emails,</td></tr>';
            $message .= '<tr>
                            <td colspan="2">
                                <ol>
                                    <li>Click on the link last column of the table. This will show you the page where you can enter email for that lab (Please not that all labs must have a unique email address). We recommend to use your MoH e-mail if available.</li>
                                </ol>
                            </td>
                        </tr>';

            $message .= '<tr><td colspan="2" width="12%">If you are unable to access ePT, please reach out to pt@vlsmartconnect.com</td></tr>';


            //$message .= '<tr><td colspan="2">Please contact us at gappt@cdc.gov if you have not received your PT shipment.</td></tr>';

            $message .= '<tr><td colspan="2">Sincerely,</td></tr>';
            $message .= '<tr><td colspan="2">Online PT Team</td></tr>';
            $message .= '<tr><td colspan="2"></td></tr>';
            //$message.= '<tr><td colspan="2"><small>This is a system generated mail. Please do not reply to this email</small></td></tr>';

            $message .= '<tr><td colspan="2"></td></tr>';

            $message .= '</table>';
            $message .= '</td></tr>';
            $message .= '</table>';
            $commonService->insertTempMail($to, $cc, $bcc, $subject, $message, $fromMail = null, $fromName = null);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    error_log('whoops! Something went wrong in scheduled-jobs/ept-reset-emails.php');
}
