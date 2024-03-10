var install_scorm_error_handler = function() {
    var error_msg = {
        'NB.2.1-1': 'Current Activity is already defined / Sequencing session has already begun',
        'NB.2.1-2': 'Current Activity is not defined / Sequencing session has not begun',
        'NB.2.1-3': 'Suspended Activity is not defined',
        'NB.2.1-4': 'Flow Sequencing Control Mode violation',
        'NB.2.1-5': 'Flow or Forward Only Sequencing Control Mode violation',
        'NB.2.1-6': 'No activity is “previous” to the root',
        'NB.2.1-7': 'Unsupported navigation request',
        'NB.2.1-8': 'Choice Exit Sequencing Control Mode violation',
        'NB.2.1-9': 'No activities to consider',
        'NB.2.1-10': 'Choice Sequencing Control Mode violation',
        'NB.2.1-11': 'Target activity does not exist',
        'NB.2.1-12': 'Current Activity already terminated',
        'NB.2.1-13': 'Undefined navigation request',
        'TB.2.3-1': 'Current Activity is not defined / Sequencing session has not begun',
        'TB.2.3-2': 'Current Activity already terminated',
        'TB.2.3-3': 'Cannot suspend an inactive root',
        'TB.2.3-4': 'Activity tree root has no parent',
        'TB.2.3-5': 'Nothing to suspend; No active activities',
        'TB.2.3-6': 'Nothing to abandon; No active activities',
        'TB.2.3-7': 'Undefined termination request',
        'SB.2.1-1': 'Last activity in the tree',
        'SB.2.1-2': 'Cluster has no available children',
        'SB.2.1-3': 'No activity is “previous” to the root',
        'SB.2.1-4': 'Forward Only Sequencing Control Mode violation',
        'SB.2.2-1': 'Flow Sequencing Control Mode violation',
        'SB.2.2-2': 'Activity unavailable',
        'SB.2.4-1': 'Forward Traversal Blocked',
        'SB.2.4-2': 'Forward Only Sequencing Control Mode violation',
        'SB.2.4-3': 'No activity is “previous” to the root',
        'SB.2.5-1': 'Current Activity is defined / Sequencing session already begun',
        'SB.2.6-1': 'Current Activity is defined / Sequencing session already begun',
        'SB.2.6-2': 'No Suspended Activity defined',
        'SB.2.7-1': 'Current Activity is not defined / Sequencing session has not begun',
        'SB.2.7-2': 'Flow Sequencing Control Mode violation',
        'SB.2.8-1': 'Current Activity is not defined / Sequencing session has not begun',
        'SB.2.8-2': 'Flow Sequencing Control Mode violation',
        'SB.2.9-1': 'No target for Choice',
        'SB.2.9-2': 'Target activity does not exist or is unavailable',
        'SB.2.9.3': 'Target activity hidden from choice',
        'SB.2.9-4': 'Choice Sequencing Control Mode violation',
        'SB.2.9-5': 'No activities to consider',
        'SB.2.9-6': 'Unable to activate target; target is not a child of the Current Activity',
        'SB.2.9-7': 'Choice Exit Sequencing Control Mode violation',
        'SB.2.9-8': 'Unable to choose target activity – constrained choice',
        'SB.2.9-9': 'Choice request prevented by Flow-only activity',
        'SB.2.10-1': 'Current Activity is not defined / Sequencing session has not begun',
        'SB.2.10-2': 'Current Activity is active or suspended',
        'SB.2.10-3': 'Flow Sequencing Control Mode violation',
        'SB.2.11-1': 'Current Activity is not defined / Sequencing session has not begun',
        'SB.2.11-2': 'Current Activity has not been terminated',
        'SB.2.12-1': 'Undefined sequencing request',
        'DB.1.1-1': 'Cannot deliver a non-leaf activity',
        'DB.1.1-2': 'Nothing to deliver',
        'DB.1.1-3': 'Activity unavailable',
        'DB.2-1': 'Identified activity is already active'
    };
    // override error handler
    top.Core._originalHandleError = top.Core.handleError;
    top.Core.handleError = function(error) {
        if (typeof error == 'string') {
            error = {
                code: undefined, 
                message: error
            };
        }
        
        if ((typeof error != 'undefined') && (typeof error.code != 'undefined') && error_msg[error.code]) {
            top.Core._originalHandleError({ code:error.code, message:error_msg[error.code] });
        }
        else {
            top.Core._originalHandleError(error);
        }
        
        setTimeout(function(){
            top.Frameset.insertCloseLink();
        }, 500);
    };
};
