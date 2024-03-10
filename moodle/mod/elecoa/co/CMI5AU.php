<?php
require_once dirname(__FILE__) . "/SimpleLeaf.php";

class CMI5AU extends SimpleLeaf {
    protected $uid;
    protected $cid;
    protected $baseUrl;
    protected $masteryScore;
    protected $moveOn;
    protected $launchMethod;
    protected $launchParameters;
    protected $entitlementKey;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->cid = $ctx->getCid();
        $this->uid = $ctx->getUid();
        $this->attempt = $ctx->getAttemptCount();
        $this->addData($this->dataNode);
        $this->addCommands();
        $ctx = makeContext($this->uid, $this->cid, 1);
        $values = readLog($ctx, $this->getID(), NULL, 'CMI5', array('status'));
        if (isset($values['status'])) {
            $this->sufficientlyCompleted = TRUE;
        } else {
            $this->sufficientlyCompleted = FALSE;
        }
    }

    function addCommands() {
        $this->cmdTableFromSelf['EXIT'] = array('Func' => 'exeExit');
    }

    function addData($data) {
        $launcher = selectSingleNode($data, 'launcher');
        $this->baseUrl = $launcher->getAttribute('baseUrl');
        $this->masteryScore = $launcher->getAttribute('masteryScore');
        $this->moveOn = $launcher->getAttribute('moveOn');
        $this->launchMethod = $launcher->getAttribute('launchMethod');
        $launchParameters = selectSingleNode($launcher, 'launchParameters');
        if ($launchParameters) {
            $this->launchParameters = $launchParameters->nodeValue;
        }
        $entitlementKey = selectSingleNode($launcher, 'entitlementKey');
        if ($entitlementKey) {
            $this->entitlementKey = $entitlementKey->nodeValue;
        }
    }

    function getAPIAdapterProvider(){
        return createAPIAdapterProvider('CMI5');
    }

    protected function exeIndexP($val) {
        $result = parent::exeIndexP($val);
        $result['Value']['sufficientlyCompleted'] = $this->sufficientlyCompleted;
        return $result;
    }

    function exeExit($val, $rtm) {
        return array('Result' => TRUE, 'Continue' => TRUE, 'Value' => array('sessionId' => $this->sessionId));
    }

    function exeRollUpMain() {
        $ctx = makeContext($this->uid, $this->cid, 1);
        $values = readLog($ctx, $this->getID(), NULL, 'CMI5', array('status'));
        if (isset($values['status'])) {
            $this->sufficientlyCompleted = TRUE;
        }
    }

    protected function exeGetValueP($params) {
        $value = NULL;
        if ($params[0] === 'cmi5.SufficientlyCompleted') {
            $value = $this->sufficientlyCompleted;
        }
        return array('Value' => $value);
    }

    function exeInit($val, $rtm) {
        global $CFG;
        $result = parent::exeInit($val, $rtm);
        if ($result['Result']) {
            $cmi5ext = getCMI5Extension();
            $endpoint_base_url = $cmi5ext->getEndpointBaseUrl();
            $registration = $cmi5ext->getRegistration($this->cid, $this->uid);
            $authorization = $cmi5ext->getLRSAuthorization();
            list($cm, $course, $elecoa) = elecoa_get_courses_array_from_instance_id($this->cid);
            $context = context_module::instance($cm->id);
            $cmi5ActivityId = $cmi5ext->getSiteBaseUrl() . 'activities/' . $cm->id . '/' . $this->getID();
            $returnURL = $cmi5ext->getSiteBaseUrl() . 'mod/elecoa/return.php?cmid=' . $cm->id;
            $launchMode = $cmi5ext->getSessionLaunchMode();
            // enforce non-terminated sessions abandoned
            $cmi5ext->enforcePreviousSessionsAbandoned($registration, $cm->id);
            $sessionId = $cmi5ext->createSessionID();
            $this->sessionId = $sessionId;
            // create tokengen key
            $tokengenKey = $cmi5ext->createAuthorizationToken($registration, $this->getID(), $this->getTitle(), $this->attempt, $this->sessionId);
            // launchMethod
            $launchMethod = 'AnyWindow';
            if ($this->launchMethod) {
                $launchMethod = $this->launchMethod;
            }
            // moveOn
            $moveOn = 'NotApplicable';
            if ($this->moveOn) {
                $moveOn = $this->moveOn;
            }
            // write moveOn status and masteryScore
            $ctx = makeContext($this->uid, $this->cid, $this->attempt);
            writeLog($ctx, $this->getID(), 0, 'CMI5', array($sessionId => json_encode(array('moveOn' => $moveOn, 'launchMode' => $launchMode))));
            if ($this->masteryScore) {
                writeLog($ctx, $this->getID(), 0, 'CMI5', array('masteryScore' => $this->masteryScore));
            }
            $agent_data = array('objectType' => 'Agent', 'name' => elecoa_session_get_username(), 'account' => array('name' => $this->uid, 'homePage' => $CFG->wwwroot));
            $agent = json_encode($agent_data);
            // DELETE
            $curl = curl_init($endpoint_base_url . 'activities/state?activityId=' . urlencode($cmi5ActivityId) . '&agent=' . urlencode($agent) . '&stateId=LMS.LaunchData');
            $options = array(
                CURLOPT_HTTPHEADER => array('Accept: application/json', 'X-Experience-API-Version: 1.0.0', 'Authorization: Basic '. $authorization),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
            );
            curl_setopt_array($curl, $options);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            $response = curl_exec($curl);
            // PUT
            $curl = curl_init($endpoint_base_url . 'activities/state?activityId=' . urlencode($cmi5ActivityId) . '&agent=' . urlencode($agent) . '&stateId=LMS.LaunchData&registration=' . $registration);
            $launch_data = json_decode('{"contextTemplate": {"contextActivities": {"grouping": [{"objectType": "Activity", "id": "' . $cmi5ActivityId . '"}]},"extensions": {"https://w3id.org/xapi/cmi5/context/extensions/sessionid": "' . $sessionId . '"}}, "launchMode": "' . $launchMode . '", "returnURL": "' . $returnURL . '", "launchMethod": "' . $launchMethod . '", "moveOn": "' . $moveOn . '"}', true);
            if ($this->masteryScore) {
                $launch_data["masteryScore"] = $this->masteryScore;
            }
            if ($this->launchParameters) {
                $launch_data["launchParameters"] = $this->launchParameters;
            }
            if ($this->entitlementKey) {
                $launch_data["entitlementKey"] = $this->entitlementKey;
            }
            $launch_data["contextTemplate"]["contextActivities"]["grouping"][0]["definition"] = array("name" => array("en-US" => $this->getTitle()));
            $put_json = json_encode($launch_data);
            $options = array(
                CURLOPT_HTTPHEADER => array('Accept: application/json', 'X-Experience-API-Version: 1.0.0', 'Authorization: Basic '. $authorization, 'Content-Length: ' . strlen($put_json)),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
            );
            curl_setopt_array($curl, $options);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $put_json);
            $response = curl_exec($curl);
            curl_close($curl);
            // Launched
            $statement_launched_data = array(
                "id" => $cmi5ext->createGUID(),
                "actor" => $agent_data,
                "verb" => array(
                    "id" => "http://adlnet.gov/expapi/verbs/launched",
                    "display" => array("en-US" => "Launched")
                ),
                "object" => array("objectType" => "Activity", "id" => $cmi5ActivityId, "definition" => array("name" => array("en-US" => $this->getTitle()))),
                "context" => $launch_data["contextTemplate"],
                "timestamp" => (new DateTime())->format('c')
            );
            $statement_launched_data["context"]["registration"] = $registration;
            $statement_launched_data["context"]["contextActivities"]["category"] = array(array("id" => "https://w3id.org/xapi/cmi5/context/categories/cmi5"));
            $statement_launched_data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/launchurl"] = $cmi5ext->getSiteBaseUrl() . 'pluginfile.php/' . $context->id . '/mod_elecoa/content/0/' . $this->baseUrl;
            $statement_launched_data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/launchmode"] = $launchMode;
            $statement_launched_data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/moveon"] = $moveOn;
            if ($this->masteryScore) {
                $statement_launched_data["context"]["extensions"]["https://w3id.org/xapi/cmi5/context/extensions/masteryscore"] = $this->masteryScore;
            }
            $curl = curl_init($endpoint_base_url . 'statements');
            $post_json = json_encode($statement_launched_data);
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
            //$result['Value'] = preg_replace('/rtm_button_param=.*/', 'rtm_button_param=SUSPEND', $result['Value']);
            $data = array('baseUrl' => $this->baseUrl,
                          'registration' => $registration,
                          'actor' => $agent,
                          'key' => $tokengenKey,
                          'activityId' => $cmi5ActivityId);
            if (is_null($data)) {
                $data = array();
            }
            $result['Value'] .= "\ninit_result_js=" . json_encode($data);
        }
        return $result;
    }
}
