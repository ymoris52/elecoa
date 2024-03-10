top.Core.onInitialized = function() {
    var error_msg = {
        'AvailableOnlyForReviewer': 'レビューアのみが登録できます',
        'AvailableOnlyForAuthor': '作問者のみが登録できます',
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
    };
};
