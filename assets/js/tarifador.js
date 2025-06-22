function dateTimeFormatter(val, row) {
    let dateTime = new Date(val);
    return moment(dateTime).format(datetimeformat);
}

function dateFormatter(val, row){
    return moment(val).format(dateformat);
}

(function($) {
    // Roda quando o documento estiver pronto
    $(function() {
        // "Toast Message"
        var gridContainer = $('#grid-container');
        var toastDataJson = gridContainer.data('toast');
        if (toastDataJson) {
            fpbxToast(toastDataJson.message, toastDataJson.title, toastDataJson.level);
            gridContainer.removeAttr('data-toast');
        }
    });
})(jQuery);
