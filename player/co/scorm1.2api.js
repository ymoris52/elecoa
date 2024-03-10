var version = "1.0";

/**
 * Find API_1484_11 object.
 * 
 * @param windowObject window object
 */
function _findAPI(windowObject) {
    if (typeof(windowObject.API_1484_11) != "undefined" && windowObject.API_1484_11 != null) {
        return windowObject.API_1484_11;
    }
    else if (windowObject.location == top.location) {
        return null;
    }
    else {
        return _findAPI(windowObject.parent);
    }
}

/**
 * Initialization.
 * 
 * @param string parameter
 */
function LMSInitialize(parameter) {
    _lastError = null;
    var api = _findAPI(window);
    
    return api.Initialize(parameter);
}

/**
 * Finishing.
 * 
 * @param string parameter
 */
function LMSFinish(parameter) {
    _lastError = null;
    var api = _findAPI(window);
    
    return api.Terminate(parameter);
}

/**
 * Convert decimal value: -1..1 -> 0..100
 * @param string value
 * @param string
 */
function _convert_decimal_range_2004_to_12(value) {
    if (value == '' || value == 'unknown') {
        return value;
    }
    
    return String(Number(parseFloat(value) * 50 + 50).toFixed(2));
}

/**
 * Convert decimal value: 0..100 -> -1..1
 * @param string value
 * @param string
 */
function _convert_decimal_range_12_to_2004(value) {
    if (value == '' || value == 'unknown') {
        return value;
    }
    return Number((parseFloat(value) - 50.0) / 50.0).toFixed(7);
}

/**
 * Convert timeinterval value (SCORM 2004) to CMITImespan value (SCORM 1.2)
 * @param string value timeinterval value.
 * @return string CMITimespan value.
 */
function _convert_timeinterval_to_cmitimespan(value){
    if (!value.match(/^P(?:(\d*)Y)?(?:(\d*)M)?(?:(\d*)D)?(?:T(?:(\d*)H)?(?:(\d*)M)?(?:(\d*(?:.\d{1,2})?)S)?)?$/)) {
        return value;
    }
    
    // 1: convert to seconds
    var years = Number(RegExp.$1);
    var months = Number(RegExp.$2);
    var days = Number(RegExp.$3);
    var hours = Number(RegExp.$4);
    var minutes = Number(RegExp.$5);
    var seconds = Number(RegExp.$6);
    
    seconds += 60.0 * minutes;
    seconds += 60.0 * 60.0 * hours;
    seconds += 60.0 * 60.0 * 24.0 * days;
    seconds += 60.0 * 60.0 * 24.0 * 30.0 * months;
    seconds += 60.0 * 60.0 * 24.0 * 30.0 * 365.0 * years;
    
    // 2: format to CMITimespan
    var span_seconds = seconds % 60.0;
    span_seconds = span_seconds.toFixed(2);
    span_seconds = ('00' + String(span_seconds)).slice(-5);
    
    var span_minutes = Math.floor(seconds / 60.0) % 60.0;
    span_minutes = span_minutes.toFixed(0);
    span_minutes = ('00' + String(span_minutes)).slice(-2);
    
    var span_hours = Math.floor(seconds / (60.0 * 60.0));
    span_hours = span_hours.toFixed(0);
    if (span_hours > 9999) {
        span_hours = 9999;
    }
    span_hours = ('0000' + String(span_hours)).slice(-4);
    
    return span_hours + ':' + span_minutes + ':' + span_seconds;
}

/**
 * Convert CMITImespan value (SCORM 1.2) to timeinterval value (SCORM 2004)
 * @param string value CMITimespan value.
 * @return string timeinterval value.
 */
function _convert_cmitimespan_to_timeinterval(value) {
    if (!value.match(/^(\d{2,4}):(\d{2}):(\d{2}(\.\d{1,2})?)$/)) {
        return value;
    }
    
    var hours = Number(RegExp.$1);
    var minutes = Number(RegExp.$2);
    var seconds = Number(RegExp.$3);
    
    var result = '';
    if (hours >= 0) {
        result += String(hours) + 'H';
    }
    if (minutes >= 0) {
        result += String(minutes) + 'M';
    }
    if (seconds >= 0.0) {
        result += String(seconds) + 'S';
    }
    
    if (result == '') {
        return result;
    }
    else {
        return 'PT' + result;
    }
}

/**
 * Convert the element name and set the converter function to need.
 * @param string element element name.
 * @param boolean is_get true to get, false to set.
 * @return like a object { element: [element name], converter: [converter function] }.
 */
function _convert_element(element, is_get) {
    var result = {
        element: element, 
        converter: null
    };
    
    switch (element) {
        case 'cmi.core._children':
            result.element = 'cmi._children';
            result.converter = is_get ? function (value) {
                var va = value.split(',');
                var rs = [];
                for (var i in va) {
                    if (va[i] === 'learner_id')           va[i] = 'student_id';
                    if (va[i] === 'learner_name')         va[i] = 'student_name';
                    if (va[i] === 'location')             va[i] = 'lesson_location';
                    if (va[i] === 'completion_status')    va[i] = 'lesson_status';
                    if (va[i] === 'completion_threshold') continue;
                    if (va[i] === 'success_status')       continue;
                    rs.push(va[i]);
                }
                return rs.join();
            } : null;
            break;
            
        case 'cmi.core.student_id':
            result.element = 'cmi.learner_id';
            break;
            
        case 'cmi.core.student_name':
            result.element = 'cmi.learner_name';
            break;
            
        case 'cmi.core.lesson_location':
            result.element = 'cmi.location';
            break;
            
        case 'cmi.core.credit':
            result.element = 'cmi.credit';
            break;
            
        case 'cmi.core.lesson_status':
            // already processed.
            break;
            
        case 'cmi.core.entry':
            result.element = 'cmi.entry';
            break;
            
        case 'cmi.core.score':
            result.element = 'cmi.score';
            break;
            
        case 'cmi.core.score.raw':
            result.element = 'cmi.score.scaled';
            result.converter = is_get ? _convert_decimal_range_2004_to_12 : _convert_decimal_range_12_to_2004
            break;
            
        case 'cmi.core.score.max':
            result.element = 'cmi.score.max';
            break;
            
        case 'cmi.core.score.min':
            result.element = 'cmi.score.min';
            break;
            
        case 'cmi.core.score._children':
            result.element = 'cmi.score._children';
            break;
            
        case 'cmi.core.total_time':
            result.element = 'cmi.total_time';
            result.converter = is_get ? _convert_timeinterval_to_cmitimespan : _convert_cmitimespan_to_timeinterval;
            break;
            
        case 'cmi.core.lesson_mode':
            result.element = 'cmi.mode';
            break;
            
        case 'cmi.core.exit':
            result.element = 'cmi.exit';
            break;
            
        case 'cmi.core.session_time':
            result.element = 'cmi.session_time';
            result.converter = is_get ? _convert_timeinterval_to_cmitimespan : _convert_cmitimespan_to_timeinterval;
            break;
            
        case 'cmi.suspend_data':
            // same
            break;
            
        case 'cmi.launch_data':
            // same
            break;
            
        case 'cmi.comments':
            result.element = 'cmi.comments_from_learner';
            break;
            
        case 'cmi.comments_from_lms':
            // same
            break;
            
        case 'cmi.objectives':
            // same
            break;
            
        case 'cmi.student_data.mastery_score':
            result.element = 'cmi.scaled_passing_score';
            result.converter = is_get ? _convert_decimal_range_2004_to_12 : _convert_decimal_range_12_to_2004
            break;
            
        case 'cmi.student_data.max_time_allowed':
            result.element = 'cmi.max_time_allowed';
            result.converter = is_get ? _convert_timeinterval_to_cmitimespan : _convert_cmitimespan_to_timeinterval;
            break;
            
        case 'cmi.student_data.time_limit_action':
            result.element = 'cmi.time_limit_action';
            break;
            
        case 'cmi.student_preference.text':
            result.element = 'cmi.learner_preference.audio_captioning';
            break;
            
        case 'cmi.interactions':
            // same
            break;
            
        default:
            if (element.match(/^cmi\.objectives\.(\d+)\.score\.raw$/)) {
                result.element = 'cmi.objectives.' + RegExp.$1 + '.score.scaled';
                result.converter = is_get ? _convert_decimal_range_2004_to_12 : _convert_decimal_range_12_to_2004;
            }
            else if (element.match(/^cmi\.interactions\.(\d+)\.latency$/)) {
                result.converter = is_get ? _convert_timeinterval_to_cmitimespan : _convert_cmitimespan_to_timeinterval;
            }
            else if (element.match(/^cmi\.interactions\.(\d+)\.student_response$/)) {
                result.element = element.replace('student_response', 'learner_response');
            }
            else if (element.match(/^cmi\.interactions\.(\d+)\.result$/)) {
                result.converter = is_get ? function (value) { return value === 'incorrect' ? 'wrong' : value; } : function (value) { return value === 'wrong' ? 'incorrect' : value; }
            }
            //else {
            //    console.log('LMSGetValue(element:"' + element + '")');
            //}
            break;
    }
    
    return result;
}

/**
 * Handle special elements to get.
 * 
 * @param object api API_1484_11 object.
 * @param string element element string.
 * @return object like a object { handled: true|false, value: value }.
 */
function _handle_special_element_get(api, element) {
    // cmi.student_data._children
    if (element === 'cmi.student_data._children') {
        return {
            handled: true,
            value: 'mastery_score,max_time_allowed,time_limit_action'
        };
    }
    // cmi.student_preference._children
    if (element === 'cmi.student_preference._children') {
        return {
            handled: true,
            value: 'text'
        };
    }
    // cmi.interactions
    if (element.indexOf("cmi.interactions") == 0) { // starts with cmi.interactions
        if (element.indexOf('.', element.indexOf('cmi.interactions') + 1) < 0) {
            if (element.match('\._count$') != '._count' && element.match('\._children$') != '._children') { // not ends with ._count, ._children
                _lastError = '404';
                return {
                    handled: true,
                    value: ''
                };
            }
        }
        // interactions.n.correct_responses.n.pattern
        if (element.match(/cmi\.interactions\.(\d+)\.correct_responses\.(\d+)\.pattern/)) {
            var cmi_interactions_n_type = 'cmi.interactions.' + RegExp.$1 + '.type';
            var type_value = api.GetValue(cmi_interactions_n_type);
            if (type_value === 'numeric') {
                var pattern_2004_value = api.GetValue(element); // expected int[:]int
                var pattern_value = pattern_2004_value.substring(0, pattern_2004_value.indexOf('['));
                return {
                    handled: true,
                    value: pattern_value
                };
            } else {
                return {
                    handled: false
                };
            }
        }
    }
    // lesson_status or cmi.objective.n.status
    var completion_status_key = null;
    var success_status_key = null;
    
    if (element == 'cmi.core.lesson_status') {
        completion_status_key = 'cmi.completion_status';
        success_status_key = 'cmi.success_status';
    }
    else if (element.match(/cmi\.objective\.(\d+)\.status/)) {
        completion_status_key = 'cmi.objective.' + RegExp.$1 + '.completion_status';
        success_status_key = 'cmi.objective.' + RegExp.$1 + '.success_status';
    }
    
    if (completion_status_key && success_status_key) {
        var completion_status = api.GetValue(completion_status_key);
        var success_status = api.GetValue(success_status_key);
        switch (success_status) {
            case 'passed':
                return {
                    handled: true,
                    value: success_status
                };
                break;
            case 'failed':
                return {
                    handled: true,
                    value: success_status
                };
                break;
            default:
                switch (completion_status) {
                    case 'unknown':
                        return {
                            handled: true,
                            value: 'not attempted'
                        };
                        break;
                    default:
                        return {
                            handled: true,
                            value: completion_status
                        };
                }
        }
    }
    
    return {
        handled: false
    };
}

/**
 * Get the value.
 * 
 * @param string element
 */
function LMSGetValue(element) {
    _lastError = null;
    var api = _findAPI(window);
    
    var result = _handle_special_element_get(api, element);
    if (result.handled) {
        return result.value;
    }
    
    var converted = _convert_element(element, true);
    
    var value = api.GetValue(converted.element);
    if (converted.converter) {
        value = converted.converter(value);
    }
    
    return value;
}

/**
 * Handle special elements to set.
 * 
 * @param object api API_1484_11 object.
 * @param string element element string.
 * @param string value value string.
 * @return object like a object { handled: [true|false], result: [result] }.
 */
function _handle_special_element_set(api, element, value) {
    // .id
    if (element.match('\.id$') == '.id') { // ends with .id
        if (value.length > 255) {
            _lastError = "405";
            return {
                handled: true,
                result: 'false'
            };
        }
    }
    // lesson_location
    if (element == 'cmi.core.lesson_location') {
        if (value.length > 255) {
            _lastError = "405";
            return {
                handled: true,
                result: 'false'
            };
        }
    }
    // suspend_data
    if (element == 'cmi.suspend_data') {
        if (value.length > 4096) {
            _lastError = "405";
            return {
                handled: true,
                result: 'false'
            };
        }
    }
    // lesson_status or cmi.objective.n.status
    var conversion_type = 0;
    if (element == 'cmi.core.lesson_status') {
        conversion_type = 1;
    }
    else if (element.match(/cmi\.objective\.(\d+)\.status/)) {
        conversion_type = 2;
    }
    
    if (conversion_type > 0) {
        var completion_status = 'unknown';
        var success_status = 'unknown';
        
        switch (value) {
            case 'passed':
                completion_status = 'completed';
                success_status = 'passed';
                break;
                
            case 'failed':
                completion_status = 'incomplete';
                success_status = 'failed';
                break;
                
            case 'completed':
                completion_status = 'completed';
                success_status = 'unknown';
                break;
                
            case 'incomplete':
            case 'browsed':
                completion_status = 'incomplete';
                success_status = 'unknown';
                break;
                
            case 'unknown':
                break;
            case 'not attempted':
            default:
                _lastError = "405";
                return {
                    handled: true,
                    result: 'false'
                }
                break;
        }
        
        if (conversion_type == 1) {
            if (api.SetValue('cmi.completion_status', completion_status) != 'true') {
                return {
                    handled: true,
                    result: 'false'
                };
            }
            
            var result = api.SetValue('cmi.success_status', success_status);
            return {
                handled: true,
                result: result
            };
        }
        else {
            if (api.SetValue('cmi.objective.' + RegExp.$1 + '.completion_status', completion_status) != 'true') {
                return {
                    handled: true,
                    result: 'false'
                };
            }
            
            var result = api.SetValue('cmi.objective.' + RegExp.$1 + '.success_status', success_status);
            return {
                handled: true,
                result: result
            };
        }
    }
    
    // cmi.core.score.raw
    if (element == 'cmi.core.score.raw') {
        var result = api.SetValue('cmi.score.raw', value);
        if (result != 'true') {
            return {
                handled: true, 
                result: result
            };
        }
        
        result = api.SetValue('cmi.score.scaled', _convert_decimal_range_12_to_2004(value));
        return {
            handled: true,
            result: result
        };
    }
    
    // cmi.objectives.n.score.raw
    if (element.match(/^cmi\.objectives\.(\d+)\.score\.raw$/)) {
        var result = api.SetValue(element, value);
        if (result != 'true') {
            return {
                handled: true, 
                result: result
            };
        }
        
        result = api.SetValue('cmi.objectives.' + RegExp.$1 + '.score.scaled', _convert_decimal_range_12_to_2004(value));
        return {
            handled: true,
            result: result
        };
    }
    
    // cmi.interactions.n.correct_responses.n.pattern
    if (element.match(/^cmi\.interactions\.(\d+)\.correct_responses\.(\d+)\.pattern$/)) {
        var cmi_interactions_n_type = 'cmi.interactions.' + RegExp.$1 + '.type';
        var type_value = api.GetValue(cmi_interactions_n_type);
        if (type_value === 'numeric') {
            result = api.SetValue(element, value + '[:]' + value);
            return {
                handled: true,
                value: result
            };
        }
    }

    return {
        handled: false
    };
}

/**
 * Set the value.
 * 
 * @param string element
 * @param string value
 */
function LMSSetValue(element, value) {
    _lastError = null;
    var api = _findAPI(window);
    
    var result = _handle_special_element_set(api, element, value);
    if (result.handled) {
        return result.result;
    }
    
    var converted = _convert_element(element, false);
    var local_value = value;
    if (converted.converter) {
        local_value = converted.converter(local_value);
    }
    
    return api.SetValue(converted.element, local_value);
};

/**
 * Commit.
 * 
 * @param string parameter
 */
function LMSCommit(parameter) {
    _lastError = null;
    var api = _findAPI(window);
    
    return api.Commit(parameter);
};

var _ERRC_2004_12 = [];
_ERRC_2004_12["0"]   = "0";
_ERRC_2004_12["101"] = "101";
_ERRC_2004_12["102"] = "201"; //"101";
_ERRC_2004_12["103"] = "101";
_ERRC_2004_12["104"] = "101";
_ERRC_2004_12["111"] = "101";
_ERRC_2004_12["112"] = "301"; //"101";
_ERRC_2004_12["113"] = "101";
_ERRC_2004_12["122"] = "301"; //"101";
_ERRC_2004_12["123"] = "101";
_ERRC_2004_12["132"] = "301"; //"101";
_ERRC_2004_12["142"] = "301"; //"101";
_ERRC_2004_12["143"] = "101";
_ERRC_2004_12["201"] = "201";
_ERRC_2004_12["301"] = "101";
_ERRC_2004_12["351"] = "201"; //"101";
_ERRC_2004_12["391"] = "101";
_ERRC_2004_12["401"] = "401";
_ERRC_2004_12["402"] = "401";
_ERRC_2004_12["403"] = "0"; //"301";
_ERRC_2004_12["404"] = "403";
_ERRC_2004_12["405"] = "404";
_ERRC_2004_12["406"] = "405";
_ERRC_2004_12["407"] = "405";
_ERRC_2004_12["408"] = "405";

var _ERRS12 = [];
_ERRS12["0"] = "No Error";
_ERRS12["101"] = "General Exception";
_ERRS12["201"] = "Invalid argument error";
_ERRS12["202"] = "Element cannot have children";
_ERRS12["203"] = "Element not an array. Cannot have count";
_ERRS12["301"] = "Not initialized";
_ERRS12["401"] = "Not implemented error";
_ERRS12["402"] = "Invalid set value, element is keyword";
_ERRS12["403"] = "Element is read only";
_ERRS12["404"] = "Element is write only";
_ERRS12["405"] = "Incorrect Data Type";

var _lastError = null;

/**
 * Get the last error.
 */
function LMSGetLastError() {
    if (_lastError != null) {
        return _lastError;
    }
    var api = _findAPI(window);
    
    return _ERRC_2004_12[api.GetLastError()];
};

/**
 * Get the error string.
 * 
 * @param string errorCode
 */
function LMSGetErrorString(errorCode) {
    //var api = _findAPI(window);
    //return api.GetErrorString(errorCode);
    if (errorCode === "") { return ""; }
    if (errorCode != +errorCode) { return ""; }
    return _ERRS12[errorCode];
};

/**
 * Get diagnostics.
 * 
 * @param string errorCode
 */
function LMSGetDiagnostic(errorCode) {
    _lastError = null;
    var api = _findAPI(window);
    
    return api.GetDiagnostic(errorCode);
};

