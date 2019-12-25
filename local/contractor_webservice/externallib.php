<?php
require_once($CFG->libdir . "/externallib.php");

class local_contractor_webservice_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function qualifications_parameters()
    {
        return new external_function_parameters(array());
    }

    /**
     * Retrieving courses
     */
    public static function qualifications()
    {
        global $DB;

        $last_export_date = self::get_export_date('qualifications');
        $course_completions = $DB->get_records_sql("SELECT ccomp.id, ccomp.userid, ccomp.course, ccomp.timecompleted, cid.data,u.idnumber
           FROM {course_completions} ccomp
           INNER JOIN {user} u ON u.id = ccomp.userid
           LEFT JOIN {course_info_data} cid ON cid.courseid = ccomp.course
           LEFT JOIN {course_info_field} cif ON cif.id = cid.fieldid
           WHERE ccomp.status = ? AND ccomp.timecompleted >= ?
           AND cif.shortname = 'ContractorQualificationCode' ORDER BY ccomp.userid", array('50', $last_export_date));
        
        $time = time();
        header("Content-type: text/xml");
        $xmlcont = '<?xml version="1.0"?>';
        //$parentRecord = $xml->addChild('n0:QualiProtMT');
        $xmlcont .= '<QualiProfMT>';
        if($course_completions) {
            while ($cc = current($course_completions) ){

                $xmlcont .= '<QualiProRow>';
                $xmlcont .= '<PerNr>'.$cc->idnumber.'</PerNr>';
                $xmlcont .= '<Begda>'.date('Ymd', $cc->timecompleted).'</Begda>';
                $xmlcont .= '<Endda>'.date('Ymd', strtotime('+1 year', $cc->timecompleted)).'</Endda>';
                $xmlcont .= '<Objid>'.strip_tags($cc->data).'</Objid>';
                $xmlcont .= '</QualiProRow>';
                next($course_completions);
            }
        }
        $xmlcont .= '</QualiProfMT>';
        print($xmlcont);

        self::set_export_date('qualifications', $time, $course_completions ? 1 : 0);
    }

    /**
     * Returns description of method result value
     */
    public static function qualifications_returns()
    {
        return;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function qualificationsbydate_parameters()
    {
        return new external_function_parameters(array());
    }

    /**
     * Retrieving courses
     */
    public static function qualificationsbydate()
    {
        
        global $DB;
        $last_export_date = self::get_export_date('qualificationsbydate');
        
        $startdateunix = strtotime('2018/12/01');
        $endateunix    = strtotime('2018/12/18');
        //$course_completions = null;
        $course_completions = $DB->get_records_sql("SELECT ccomp.id, ccomp.userid, ccomp.course, ccomp.timecompleted, cid.data,u.idnumber
           FROM {course_completions} ccomp
           INNER JOIN {user} u ON u.id = ccomp.userid
           LEFT JOIN {course_info_data} cid ON cid.courseid = ccomp.course
           LEFT JOIN {course_info_field} cif ON cif.id = cid.fieldid
           WHERE ccomp.status = ? AND ccomp.timecompleted >= ? AND ccomp.timecompleted <= ?
           AND cif.shortname = 'ContractorQualificationCode' ORDER BY ccomp.userid", array('50', $startdateunix, $endateunix));

        $time = time();
        //$xmlcont = null;
        header("Content-type: text/xml");
        $xmlcont = '<?xml version="1.0"?>';
        $xmlcont .= '<QualiProfMT>';
            if($course_completions) {
                while ($cc = current($course_completions) ){
                    $xmlcont .= "<QualiProRow>";
                    $xmlcont .= "<PerNr>".$cc->idnumber."</PerNr>";
                    $xmlcont .= "<Begda>".date('Ymd', $cc->timecompleted)."</Begda>";
                    $xmlcont .= "<Endda>".date('Ymd', strtotime('+1 year', $cc->timecompleted))."</Endda>";
                    $xmlcont .= "<Objid>".strip_tags($cc->data)."</Objid>";
                    $xmlcont .= "</QualiProRow>";
                    next($course_completions);
                }
            }
        $xmlcont .= "</QualiProfMT>";
        print($xmlcont);
        self::set_export_date('qualificationsbydate', $time, $course_completions ? 1 : 0);
    }

    /**
     * Returns description of method result value
     */
    public static function qualificationsbydate_returns()
    {
        return;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function trainings_parameters()
    {
        return new external_function_parameters(array());
    }

    /**
     * Retrieving facetoface activities
     */
    public static function trainings()
    {
        global $DB;

        $last_export_date = self::get_export_signupids('trainings');
        $signidsarr = json_decode($last_export_date);
        $signupidmaxvalue = max($signidsarr);
        //print_r($last_export_date);die('test111111');
        $sql = "
        SELECT signups.id, u.username, sessiondate.timestart, sessiondate.timefinish, u.idnumber, sessiondate.sessionid, sessinfodata.data 
        FROM {facetoface_signups} signups 
        INNER JOIN {user} u ON u.id = signups.userid 
        INNER JOIN {facetoface_sessions_dates} sessiondate ON (sessiondate.sessionid = signups.sessionid)
        INNER JOIN {facetoface_session_info_data} sessinfodata
            ON sessinfodata.facetofacesessionid = sessiondate.sessionid
        INNER JOIN {facetoface_session_info_field} sessinfofield
            ON sessinfofield.id = sessinfodata.fieldid
        INNER JOIN {facetoface_signups_status} signups_status ON signups_status.signupid = signups.id
        WHERE sessinfofield.shortname = 'hitzoni' AND signups_status.statuscode = ? AND signups_status.superceded >= ? AND signups.id > ?
        ";
        $facetoface = $DB->get_records_sql($sql, [70, '1', $signupidmaxvalue]);
        //AND signups_status.timecreated >= ?
        //echo '<pre>';print_r($facetoface);echo '</pre>';die('test123');
        $time = time();
        $signupdata = array();
        header("Content-type: text/xml");
        $xmlcont = '<?xml version="1.0"?>';
        //$parentRecord = $xml->addChild('n0:TrainingMT');
        $xmlcont .= '<TrainingMT>';
        if($facetoface) {
            while ($ff = current($facetoface)) {

                $xmlcont .= '<TrainingsRow>';
                $xmlcont .= '<EmployeeId>'.$ff->idnumber.'</EmployeeId>';
                $xmlcont .= '<TrainingDate>'.date('Ymd', $ff->timestart).'</TrainingDate>';
                $xmlcont .= '<EndingDate>'.date('Ymd', $ff->timefinish).'</EndingDate>';
                $xmlcont .= '<StartHour>'.date('H:i', $ff->timestart).'</StartHour>';
                $xmlcont .= '<EndHour>'.date('H:i', $ff->timefinish).'</EndHour>';
                $xmlcont .= '<TrainingType>'.$ff->data.'</TrainingType>';
                $xmlcont .= '</TrainingsRow>';
                $signupdata[] = $ff->id;
                next($facetoface);
            }
        }
        $xmlcont .= '</TrainingMT>';
        print($xmlcont);

        self::set_export_date_signupids('trainings', $time, $facetoface ? 1 : 0, json_encode($signupdata));
    }

    /**
     * Returns description of method result value
     */
    public static function trainings_returns()
    {
        return;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function trainingsbydate_parameters()
    {
        return new external_function_parameters(array());
    }

    /**
     * Retrieving facetoface activities
     */
    public static function trainingsbydate()
    {
        

        global $DB;
        $last_export_date = self::get_export_date('trainingsbydate');
        
        $startdateunix = strtotime('2018/12/01');//YYYY/MM/DD
        $endateunix    = strtotime('2018/12/25');//YYYY/MM/DD
        $endateunix    += 86400;
        $facetoface = null;
        $sql = "
        SELECT signups.id, u.username, sessiondate.timestart, sessiondate.timefinish, u.idnumber, sessiondate.sessionid, sessinfodata.data 
        FROM {facetoface_signups} signups 
        INNER JOIN {user} u ON u.id = signups.userid 
        INNER JOIN {facetoface_sessions_dates} sessiondate ON (sessiondate.sessionid = signups.sessionid)
        INNER JOIN {facetoface_session_info_data} sessinfodata
            ON sessinfodata.facetofacesessionid = sessiondate.sessionid
        INNER JOIN {facetoface_session_info_field} sessinfofield
            ON sessinfofield.id = sessinfodata.fieldid
        INNER JOIN {facetoface_signups_status} signups_status ON signups_status.signupid = signups.id
        WHERE sessinfofield.shortname = 'hitzoni' AND signups_status.statuscode = ? AND signups_status.superceded >= ? AND sessiondate.timestart >= ? AND sessiondate.timestart <= ?
        ";
        $facetoface = $DB->get_records_sql($sql, array(70, '1', $startdateunix, $endateunix));
        

        $time = time();
        header("Content-type: text/xml");
        $xmlcont = '<?xml version="1.0"?>';
        $xmlcont .= '<TrainingMT>';
        
            if($facetoface) {
                while ($ff = current($facetoface) ){

                    $xmlcont .= '<TrainingsRow>';
                    $xmlcont .= '<EmployeeId>'.$ff->idnumber.'</EmployeeId>';
                    $xmlcont .= '<TrainingDate>'.date('Ymd', $ff->timestart).'</TrainingDate>';
                    $xmlcont .= '<EndingDate>'.date('Ymd', $ff->timefinish).'</EndingDate>';
                    $xmlcont .= '<StartHour>'.date('H:i', $ff->timestart).'</StartHour>';
                    $xmlcont .= '<EndHour>'.date('H:i', $ff->timefinish).'</EndHour>';
                    $xmlcont .= '<TrainingType>'.$ff->data.'</TrainingType>';
                    $xmlcont .= '</TrainingsRow>';
                    next($facetoface);
                }
            }
       
        $xmlcont .= '</TrainingMT>';
        print($xmlcont);
        self::set_export_date('trainingsbydate', $time, $facetoface ? 1 : 0);
    }

    /**
     * Returns description of method result value
     */
    public static function trainingsbydate_returns()
    {
        return;
    }

    

    /**
     * Retrieve last success export's date
     * @param $service
     * @return int
     */
    public static function get_export_date($service)
    {
        global $DB;

        $export_date = $DB->get_record('contractor_service_history', array('service' => $service), 'MAX(time) AS time');

            return $export_date->time ? $export_date->time : 0;
    }

    /**
     * Save export's date
     * @param $service
     * @param $time
     * @param int $status
     */
    public static function set_export_date( $service, $time, $status = 1)
    {
        global $DB;

        $export_data = new stdClass();
        $export_data->service = $service;
        $export_data->time = $time;
        $export_data->status = $status;

        $DB->insert_record('contractor_service_history', $export_data);
    }

    /**
     * Retrieve last success export's date
     * @param $service
     * @return int
     */
    public static function get_export_signupids($service)
    {
        global $DB;
        $export_date = $DB->get_record_sql("SELECT id, time, status, signupids FROM {contractor_service_history} WHERE service = ? AND status = ? ORDER BY id DESC", array($service, '1'),$limitfrom=0, $limitnum=1);
        if($export_date->status == 1 && $export_date->signupids != null) {
            return $export_date->signupids;
        } else {
            return json_encode(array(0));
        }
    }

    /**
     * Save export's date
     * @param $service
     * @param $time
     * @param int $status
     */
    public static function set_export_date_signupids( $service, $time, $status = 1, $signupdata = null)
    {
        global $DB;

        $export_data = new stdClass();
        $export_data->service = $service;
        $export_data->time = $time;
        $export_data->status = $status;
        $export_data->signupids = $signupdata;

        $DB->insert_record('contractor_service_history', $export_data);
    }
}