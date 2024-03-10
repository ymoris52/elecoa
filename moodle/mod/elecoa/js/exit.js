(function() {
    var core = new ElecoaCore();
    var results = core.sendCommand("EXITALL");
    if (results.result) {
        top.location.href = top.baseUrl + '/course/view.php?id=' + encodeURIComponent(top.cid);
    } else {
        alert("errorCode" in results.commandResultArray ? "error (" + results.commandResultArray["errorCode"] + ")" : "An unexpected error occurred");
    }
})();
