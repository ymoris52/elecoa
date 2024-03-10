(function() {
    var core = new ElecoaCore();
    var results = core.sendCommand("READY");
    if (results.result && (results.action.type == core.ACTION_MOVE)) {
        if (top.ownwindow) {
            top.location.href = "ownwindow.php?id=" + top.elecoa_id + "&NextID=" + encodeURIComponent(results.action.to);
        } else {
            top.location.href = "container.php?id=" + top.elecoa_id + "&NextID=" + encodeURIComponent(results.action.to);
        }
    }
    else {
        alert("errorCode" in results.commandResultArray ? "error (" + results.commandResultArray["errorCode"] + ")" : "An unexpected error occurred");
    }
})();
