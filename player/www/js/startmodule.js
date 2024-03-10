(function() {
    var core = new ElecoaCore();
    var results = core.sendCommand("READY");
    if (results.result && (results.action.type == core.ACTION_MOVE)) {
        top.location.href = "container.php?cid=" + encodeURIComponent(top.content_id) + "&NextID=" + encodeURIComponent(results.action.to);
    }
    else {
        alert("An unexpected error occurred");
    }
})();
