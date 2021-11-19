function exportToPDF(){
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({format: "a4"});
    doc.autoTable({
        html: '#pinuser',
        margin: { top: 5, right: 5, left: 5, bottom: 5 },
    });
    doc.save('exportPinUser.pdf');
}

function linkFormatPinUser(value, row, index){
    let html = (row['enabled']==1)?'<i class="fa fa-toggle-on" style="color:green"></i>&nbsp;':'<i class="fa fa-toggle-off" style="color:red"></i>&nbsp;';
    html += '<a href="?display=tarifador&page=pinuser&view=form&id='+value+'"><i class="fa fa-pencil-square-o"></i></a>&nbsp;';
    html += '<a class="delAction" href="?display=tarifador&page=pinuser&action=delete&id='+value+'"><i class="fa fa-trash"></i></a>';
    return html;
}


function activeFormat(value) {
    if(value === 1) {
        return '<i class="text-success fa fa-check-circle-o"></i>';
    } else  {
        return '<i class="text-danger fa fa-ban"></i>';
    }
}