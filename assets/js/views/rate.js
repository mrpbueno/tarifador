$('#rate').on('reorder-row.bs.table', function (e, data){
    var order = [];
    $.each(data, function (i, value) {
        order[i] = {id : value.id, seq : i};
    });
    $.ajax({
        type: 'POST',
        url: 'ajax.php?module=tarifador&command=updateOrderRate',
        data: {'data' : order},
        dataType: 'json',
        success: function(data) {
            fpbxToast(_('A ordem da tarifa foi atualizada.'),_('Updated'),'success');
        }
    });
});

function exportToPDF(){
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({format: "a4"});
    doc.autoTable({
        html: '#rate',
        margin: { top: 5, right: 5, left: 5, bottom: 5 },
    });
    doc.save('exportRate.pdf');
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