(function() {
    var core = new ElecoaCore();
    var results = core.sendCommand("EXITALL");
    if (results.result) {
        top.location.href = "startmodule.php?id=" + top.elecoa_id;
    }
    else {
        alert("errorCode" in results.commandResultArray ? "error (" + results.commandResultArray["errorCode"] + ")" : "An unexpected error occurred");
    }
})();
