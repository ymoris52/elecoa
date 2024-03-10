var success_status = undefined;
var score__scaled = undefined;
var scaled_passing_score = undefined;
var completion_status = undefined;
var progress_measure = undefined;
var completion_threshold = undefined;

var ViewNum = 0;

function FindAPI(win) {
	if (typeof(win.API_1484_11) != "undefined" && win.API_1484_11 != null) {
		return win.API_1484_11;
	}
	else if (win.location == top.location) {
		return null;
	}
	else {
		return FindAPI(win.parent);
	}
}

function myInit() {
	API = FindAPI(window);
	if (API != null) {
		API.Initialize("");
	}
	else {
		alert("API instance is not found.");
	}
	var objLen = API.GetValue("cmi.objectives._count");
	for(i=0;i<objLen;i++){
		objId = API.GetValue("cmi.objectives." + i + ".id");
		window.document.getElementById("p0").options[i+1] = new Option(objId, (i+1) + ""); 
	}
	updateStatus();
}

function myFin() {
	if (API != null) {
		API.Terminate("");
	}
}

function updateStatus() {
	if (API == null) {
		return;
	}
	ViewNum = 0;
	success_status = API.GetValue("cmi.success_status");
	score__scaled = API.GetValue("cmi.score.scaled");
	scaled_passing_score = API.GetValue("cmi.scaled_passing_score");
	completion_status = API.GetValue("cmi.completion_status");
	progress_measure = API.GetValue("cmi.progress_measure");
	completion_threshold = API.GetValue("cmi.completion_threshold");

	window.document.getElementById("p1").innerHTML = success_status || "unknown";
	window.document.getElementById("p2").value = score__scaled != undefined && score__scaled != "" ? score__scaled : "";
	window.document.getElementById("p3").innerHTML = scaled_passing_score != undefined && scaled_passing_score != "" ? scaled_passing_score : "unknown";
	window.document.getElementById("p4").innerHTML = completion_status || "unknown";
	window.document.getElementById("p5").value = progress_measure != undefined && progress_measure != "" ? progress_measure : '';
	window.document.getElementById("p6").innerHTML = completion_threshold != undefined && completion_threshold != "" ? completion_threshold : "unknown";
}

function localView(num){
	ViewNum = num;
	var tnum = num - 1;
	obj_success_status = API.GetValue("cmi.objectives." + tnum + ".success_status");
	obj_score__scaled = API.GetValue("cmi.objectives." + tnum + ".score.scaled");
	obj_completion_status = API.GetValue("cmi.objectives." + tnum + ".completion_status");
	obj_progress_measure = API.GetValue("cmi.objectives." + tnum + ".progress_measure");

	window.document.getElementById("p1").innerHTML = obj_success_status || "unknown";
	window.document.getElementById("p2").value = obj_score__scaled != undefined && obj_score__scaled != "" ? obj_score__scaled : "";
	window.document.getElementById("p3").innerHTML = "N/A";
	window.document.getElementById("p4").innerHTML = obj_completion_status || "unknown";
	window.document.getElementById("p5").value = obj_progress_measure != undefined && obj_progress_measure != "" ? obj_progress_measure : '';
	window.document.getElementById("p6").innerHTML = "N/A";
}

function changeStatus(param, value) {
	if(ViewNum == 0){
		param = "cmi" + param;
	}else{
		var tnum = ViewNum - 1;
		param = "cmi.objectives." + tnum + param;
	}
	var ret = API.SetValue(param, value);
	if (ret == "true") {
		if(ViewNum == 0){
			updateStatus();
		}else{
			localView(ViewNum);
		}
	}else {
		alert("failed");
	}
}

function changeStatus2(param, value) {
	var ret = API.SetValue(param, value);
	if (ret == "true") {
	}else {
		alert("failed");
	}
}

function changeView(num){
	if(num == 0){
		updateStatus();
	}else{
		localView(num);
	}
}
