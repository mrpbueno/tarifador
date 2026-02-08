function dateTimeFormatter(val, row) {
    let dateTime = new Date(val);
    return moment(dateTime).format(datetimeformat);
}

function dateFormatter(val, row){
    return moment(val).format(dateformat);
}

(function($) {
    $(function() {
        var gridContainer = $('#grid-container');
        var toastDataJson = gridContainer.data('toast');
        if (toastDataJson) {
            fpbxToast(toastDataJson.message, toastDataJson.title, toastDataJson.level);
            gridContainer.removeAttr('data-toast');
        }
    });
})(jQuery);

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'call';
    const menuLinks = $('.list-group a.list-group-item');
    menuLinks.removeClass('active');
    menuLinks.each(function() {
        const link = $(this);
        const href = link.attr('href');
        if (href && href.includes('page=' + currentPage)) {
            link.addClass('active');
        }
    });
});
