<?php

class CMI5Extension
{
    protected $db;
    protected $cmi5sessionKey;
    protected $endpoint_base_url;
    protected $lrs_authorization;
    protected $site_base_url;

    function __construct() {
        global $DB;
        global $CFG;
        global $CMI5CFG;

        $this->db = $DB;
        $this->cmi5sessionKey = 'cmi5session';
        $this->site_base_url = $CFG->wwwroot . '/';
        $this->endpoint_base_url = $CMI5CFG->endpoint_base_url;
        $this->lrs_authorization = $CMI5CFG->lrs_authorization;
    }

    public function getEndpointBaseUrl() {
        return $this->endpoint_base_url;
    }

    public function getSiteBaseUrl() {
        return $this->site_base_url;
    }

    public function getLRSAuthorization() {
        return $this->lrs_authorization;
    }

    public function getRegistration($cid, $uid) {
        if ($result = $this->db->get_record('elecoa_registration', array('cid' => $cid, 'uid' => $uid))) {
            return $result->registration;
        } else {
            return FALSE;
        }
    }

    public function setSessionLaunchMode($launchMode) {
        if (!isset($_SESSION['cmi5session'])) {
            $_SESSION[$this->cmi5sessionKey] = array();
        }
        $_SESSION[$this->cmi5sessionKey]['launchMode'] = $launchMode;
    }

    public function getSessionLaunchMode() {
        if (!isset($_SESSION[$this->cmi5sessionKey]['launchMode'])) {
            return 'Normal';
        }
        return $_SESSION[$this->cmi5sessionKey]['launchMode'];
    }

    public function enforcePreviousSessionsAbandoned($registration, $cmId) {
        $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
        $userId = $result->uid;
        $userName = $this->getUserName($userId);
        if ($rs = $this->db->get_records('elecoa_authtoken', array('registration' => $registration))) {
            foreach ($rs as $record) {
                $sessionId = $record->sessionid;
                if ($sessionId) {
                    $activityId = $record->activity;
                    $activityTitle = $record->title;
                    $this->postAbandoned($userId, $userName, $cmId, $activityId, $activityTitle, $registration, $sessionId);
                    $this->db->delete_records('elecoa_authtoken', array('registration' => $registration, 'sessionid' => $sessionId));
                }
            }
        }
    }

    public function createAuthorizationToken($registration, $activityId, $activityTitle, $attempt, $sessionId) {
        $record = new stdClass();
        $record->registration = $registration;
        $record->activity = $activityId;
        $record->title = $activityTitle;
        $record->attempt = $attempt;
        $record->sessionid = $sessionId;
        $record->genkey = $this->create_guid();
        $record->authuser = $this->create_guid();
        $record->password = $this->create_guid();
        $record->fetched = 0;
        $record->valid = 1;
        $this->db->insert_record('elecoa_authtoken', $record);
        return $record->genkey;
    }

    public function getAuthorizationToken($genkey) {
        if ($record = $this->db->get_record('elecoa_authtoken', array('genkey' => $genkey))) {
            if (!$record->fetched) {
                $record->fetched = 1;
                $this->db->update_record('elecoa_authtoken', $record);
            }
            return array('AuthToken' => base64_encode($record->authuser . ':' . $record->password), 'Fetched' => $record->fetched, 'Valid' => $record->valid);
        } else {
            return FALSE;
        }
    }

    public function getGenKey($auth_user, $auth_pw) {
        if ($record = $this->db->get_record('elecoa_authtoken', array('authuser' => $auth_user, 'password' => $auth_pw))) {
            return $record->genkey;
        } else {
            return FALSE;
        }
    }

    public function getUserName($userId) {
	    $user = $this->db->get_record('user', array('id' => $userId));
	    return $user->lastname . ' ' . $user->firstname;
    }

    public function writeLog($registration, $activityId, $attempt, $key_value_pairs) {
       $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
       $ctx = makeContext($result->uid, $result->cid, $attempt);
       writeLog($ctx, $activityId, 0, 'CMI5', $key_value_pairs);
    }

    public function readLog($registration, $activityId, $attempt, $keys) {
       $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
       $ctx = makeContext($result->uid, $result->cid, $attempt);
       return readLog($ctx, $activityId, NULL, 'CMI5', $keys);
    }

    public function writeObjectiveLog($registration, $activityId, $attempt, $key_value_pairs) {
       $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
       $ctx = makeContext($result->uid, $result->cid, 1);
       writeLog($ctx, $activityId, 0, 'CMI5', $key_value_pairs);
    }

    public function readObjectiveLog($registration, $activityId, $attempt, $keys) {
       $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
       $ctx = makeContext($result->uid, $result->cid, 1);
       return readLog($ctx, $activityId, NULL, 'CMI5', $keys);
    }

    private function checkCmi5Allowed($data) {
        if (!isset($data["actor"]["account"])) {
            return FALSE;
        }
        if (!isset($data["verb"])) {
            return FALSE;
        }
        if (!isset($data["object"])) {
            return FALSE;
        }
        if (!isset($data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/sessionid"])) {
            return FALSE;
        }
        if (!isset($data["context"]["contextActivities"]["grouping"])) {
            return FALSE;
        }
        return TRUE;
    }

    public function checkStatement($data, $genkey) {
        if (!$this->checkCmi5Allowed($data)) {
            return FALSE;
        }
        $sessionId = $data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/sessionid"];
        $record = $this->db->get_record('elecoa_authtoken', array('genkey' => $genkey));
        if (!$record) {
            return FALSE;
        }
        $registration = $record->registration;
        $activityId = $record->activity;
        $attempt = $record->attempt;
        $values = $this->readLog($registration, $activityId, $attempt, array($sessionId, "masteryScore"));
        $moveOnStatus = json_decode($values[$sessionId], true);
        $objValues = $this->readObjectiveLog($registration, $activityId, $attempt, array('Passed', 'Completed'));
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/completed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return FALSE;
            }
            if (isset($objValues["Completed"])) {
                return FALSE;
            }
            if (isset($moveOnStatus["Completed"])) {
                return FALSE;
            }
        }
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/passed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return FALSE;
            }
            if (isset($objValues["Passed"])) {
                return FALSE;
            }
            if (isset($moveOnStatus["Passed"])) {
                return FALSE;
            }
            if (isset($values["masteryScore"])) {
                if (is_numeric($data["result"]["score"]["scaled"])) {
                    $scaledScore = floatval($data["result"]["score"]["scaled"]);
                    $masteryScore = floatval($values["masteryScore"]);
                    if ($scaledScore < $masteryScore) {
                        return FALSE;
                    }
                } else {
                    return FALSE;
                }
            }
        }
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/failed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return FALSE;
            }
            if (isset($moveOnStatus["Passed"])) {
                return FALSE;
            }
            if (isset($values["masteryScore"])) {
                if (is_numeric($data["result"]["score"]["scaled"])) {
                    $scaledScore = floatval($data["result"]["score"]["scaled"]);
                    $masteryScore = floatval($values["masteryScore"]);
                    if ($scaledScore >= $masteryScore) {
                        return FALSE;
                    }
                } else {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    public function handleStatement($data, $genkey) {
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/terminated") {
            $this->db->delete_records('elecoa_authtoken', array('genkey' => $genkey));
            return;
        }
        $sessionId = $data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/sessionid"];
        $record = $this->db->get_record('elecoa_authtoken', array('genkey' => $genkey));
        if (!$record) {
            return;
        }
        $registration = $record->registration;
        $activityId = $record->activity;
        $attempt = $record->attempt;
        $sessionValues = $this->readLog($registration, $activityId, $attempt, array($sessionId));
        $moveOnStatus = json_decode($sessionValues[$sessionId], true);
        $objValues = $this->readObjectiveLog($registration, $activityId, $attempt, array('Passed', 'Completed'));
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/completed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return;
            }
            if (isset($objValues["Completed"])) {
                return;
            }
            $moveOnStatus["Completed"] = "true";
            $objValues["Completed"] = "true";
        }
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/passed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return;
            }
            if (isset($objValues["Passed"])) {
                return;
            }
            $moveOnStatus["Passed"] = "true";
            $objValues["Passed"] = "true";
        }
        if ($data["verb"]["id"] === "http://adlnet.gov/expapi/verbs/failed") {
            if ($moveOnStatus["launchMode"] !== 'Normal') {
                return;
            }
            $moveOnStatus["Passed"] = "false";
        }
        $this->checkMoveOn($registration, $activityId, $attempt, $moveOnStatus, $data);
        $this->writeLog($registration, $activityId, $attempt, array($sessionId => json_encode($moveOnStatus)));
        $this->writeObjectiveLog($registration, $activityId, $attempt, $objValues);
    }

    public function checkMoveOn($registration, $activityId, $attempt, $moveOnStatus, $data) {
        $sessionId = $data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/sessionid"];
        $userName = $data["actor"]["name"];
        $cmi5ActivityId = $data["object"]["id"];
        $title = NULL;
        if (isset($data["object"]["definition"]["name"]["en-US"])) {
            $title = $data["object"]["definition"]["name"]["en-US"];
        }
        $result = $this->db->get_record('elecoa_registration', array('registration' => $registration));
        $userId = $result->uid;
        $sufficientlyCompleted = false;
        if ($moveOnStatus["moveOn"] === "NotApplicable") {
            $sufficientlyCompleted = true;
        }
        if ($moveOnStatus["moveOn"] === "Completed" and isset($moveOnStatus["Completed"]) and $moveOnStatus["Completed"] === "true") {
            $sufficientlyCompleted = true;
        }
        if ($moveOnStatus["moveOn"] === "Passed" and isset($moveOnStatus["Passed"]) and $moveOnStatus["Passed"] === "true") {
            $sufficientlyCompleted = true;
        }
        if ($moveOnStatus["moveOn"] === "CompletedOrPassed" and ((isset($moveOnStatus["Passed"]) and $moveOnStatus["Passed"] === "true") or (isset($moveOnStatus["Completed"]) and $moveOnStatus["Completed"] === "true"))) {
            $sufficientlyCompleted = true;
        }
        if ($moveOnStatus["moveOn"] === "CompletedAndPassed" and isset($moveOnStatus["Passed"]) and $moveOnStatus["Passed"] === "true" and isset($moveOnStatus["Completed"]) and $moveOnStatus["Completed"] === "true") {
            $sufficientlyCompleted = true;
        }
        if ($sufficientlyCompleted) {
            //$this->postSatisfied($userId, $userName, $registration, $cmi5ActivityId, null, $title, $sessionId);
            $this->writeObjectiveLog($registration, $activityId, $attempt, array("status" => "SufficientlyCompleted"));
        }
    }
    
    public function postAbandoned($userId, $userName, $cmId, $activityId, $title, $registration, $sessionId) {
        global $CFG;
        $agent_data = array('objectType' => 'Agent', 'name' => $userName, 'account' => array('name' => $userId, 'homePage' => $CFG->wwwroot));
        $cmi5ActivityId = $this->getSiteBaseUrl() . 'activities/' . $cmId . '/' . $activityId;
        $context_data = json_decode('{"contextActivities": {"grouping": [{"objectType": "Activity", "id": "' . $cmi5ActivityId . '"}]},"extensions": {"https://w3id.org/xapi/cmi5/context/extensions/sessionid": "' . $sessionId . '"}}', true);
        $statement_data = array(
            "id" => $this->createGUID(),
            "actor" => $agent_data,
            "verb" => array(
                "id" => "https://w3id.org/xapi/adl/verbs/abandoned",
                "display" => array("en-US" => "Abandoned")
            ),
            "object" => array("objectType" => "Activity", "id" => $cmi5ActivityId),
            "context" => $context_data,
            "timestamp" => (new DateTime())->format('c')
        );
        $statement_data["context"]["registration"] = $registration;
        $statement_data["context"]["contextActivities"]["category"] = array(array("id" => "https://w3id.org/xapi/cmi5/context/categories/cmi5"));
        if ($title) {
            $statement_data["object"]["definition"] = array("name" => array("en-US" => $title));
        }
        $endpoint_base_url = $this->getEndpointBaseUrl();
        $curl = curl_init($endpoint_base_url . 'statements');
        $post_json = json_encode($statement_data);
        $authorization = $this->getLRSAuthorization();
        $options = array(
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'X-Experience-API-Version: 1.0.0', 'Authorization: Basic '. $authorization, 'Content-Length: ' . strlen($post_json), 'Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
        );
        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POST, true); // POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_json);
        $response = curl_exec($curl);
        curl_close($curl);
    }

    public function postSatisfied($userId, $userName, $registration, $cmi5ActivityId, $type, $title, $sessionId) {
        global $CFG;
        // Satisified
        $agent_data = array('objectType' => 'Agent', 'name' => $userName, 'account' => array('name' => $userId, 'homePage' => $CFG->wwwroot));
        $cmi5Type = null;
        if ($type === 'block') {
            $cmi5Type = 'https://w3id.org/xapi/cmi5/activitytype/block';
        }
        if ($type === 'course') {
            $cmi5Type = 'https://w3id.org/xapi/cmi5/activitytype/course';
        }
        $context_data = json_decode('{"contextActivities": {"grouping": [{"objectType": "Activity", "id": "' . $cmi5ActivityId . '"}]},"extensions": {"https://w3id.org/xapi/cmi5/context/extensions/sessionid": "' . $sessionId . '"}}', true);
        $statement_satisfied_data = array(
            "id" => $this->createGUID(),
            "actor" => $agent_data,
            "verb" => array(
                "id" => "http://adlnet.gov/expapi/verbs/satisfied",
                "display" => array("en-US" => "Satisfied")
            ),
            "object" => array("objectType" => "Activity", "id" => $cmi5ActivityId),
            "context" => $context_data,
            "timestamp" => (new DateTime())->format('c')
        );
        $statement_satisfied_data["context"]["registration"] = $registration;
        $statement_satisfied_data["context"]["contextActivities"]["category"] = array(array("id" => "https://w3id.org/xapi/cmi5/context/categories/cmi5"));
        if ($title) {
            $statement_satisfied_data["object"]["definition"] = array("name" => array("en-US" => $title));
        }
        if (!is_null($cmi5Type)) {
            $statement_satisfied_data["object"]["definition"]["type"] = $cmi5Type;
            $statement_satisfied_data["context"]["contextActivities"]["grouping"][0]["definition"] = array("type" => $cmi5Type);
        }
        $endpoint_base_url = $this->getEndpointBaseUrl();
        $curl = curl_init($endpoint_base_url . 'statements');
        $post_json = json_encode($statement_satisfied_data);
        $authorization = $this->getLRSAuthorization();
        $options = array(
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'X-Experience-API-Version: 1.0.0', 'Authorization: Basic '. $authorization, 'Content-Length: ' . strlen($post_json), 'Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
        );
        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POST, true); // POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_json);
        $response = curl_exec($curl);
        curl_close($curl);
    }

    public function createRegistration($cid, $uid) {
        $record = new stdClass();
        $record->cid = $cid;
        $record->uid = $uid;
        $record->registration = $this->create_guid();
        $this->db->insert_record('elecoa_registration', $record);
        return $record->registration;
    }

    public function createSessionID() {
        return $this->create_guid();
    }

    public function createGUID() {
        return $this->create_guid();
    }

    private function create_guid() {
        if (function_exists('com_create_guid') === true) {
            return strtolower(trim(com_create_guid(), '{}'));
        }
        return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
    }
}

function getCMI5Extension() {
    return new CMI5Extension();
}
