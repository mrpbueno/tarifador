$('#rate').on('reorder-row.bs.table', function (e, data){
    var order = [];
    $.each(data, function (i, value) {
        if (value && typeof value.id !== 'undefined') {
            order.push({id : value.id, seq : i});
        }
    });
    $.ajax({
        type: 'POST',
        url: 'ajax.php?module=tarifador&command=updateOrderRate',
        data: {'data' : order},
        dataType: 'json',
        success: function(data) {
            fpbxToast(_('The rate order has been updated.'),_('Updated'),'success');
        }
    });
});

function dragHandleFormatter(value, row, index) {
    return '<i class="fa fa-arrows-v drag-handle" style="cursor: move;"></i>';
}

function exportToPDF(name){
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({format: "a4"});
    doc.autoTable({
        html: '#rate',
        margin: { top: 5, right: 5, left: 5, bottom: 5 },
    });
    doc.save(name+'_ExportRate.pdf');
}

function rateFormatter(val, row){
    let a = new Number(val);
    return a.toLocaleString('pt-BR',{minimumFractionDigits: 5});
}

function linkFormatRate(value, row, index){
    let html = '<a href="?display=tarifador&page=rate&view=form&id='+value+'"><i class="fa fa-pencil-square-o"></i></a>&nbsp;';
    html += '<a class="delAction" href="?display=tarifador&page=rate&action=delete&id='+value+'"><i class="fa fa-trash"></i></a>';
    return html;
}