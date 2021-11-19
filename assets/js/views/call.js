function exportToPDF(){
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({orientation: "landscape",format: "a4"});
    doc.autoTable({
        html: '#tarifador',
        margin: { top: 5, right: 5, left: 5, bottom: 5 },
    });
    doc.save('exportCall.pdf');
}

function exportChartToPDF() {
    let jsPDF = window.jspdf.jsPDF;
    var doc = new jsPDF({orientation: "landscape", format: 'a4'});
    html2canvas(document.querySelector('#dispositionChart')).then(function(canvas){
        var imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', 10, 10, 281, 66);
        doc.save('exportChart.pdf');
    });
}

function queryParams(params){
    let formData = $("#call-form").serialize();
    $.each(formData.split('&'), function(k,v) {
        let parts = v.split('=');
        params[parts[0]] = parts[1];
    });
    return params;
}

function dispositionFormatter(value) {
    let disposition = {"ANSWERED":"Atendido","BUSY":"Ocupado","FAILED":"Falha","NO ANSWER":"Não Atendido"};
    return disposition[value];
}

function callTypeFormatter(value, row, index) {
    let src = row['src'].length;
    let dst = row['dst'].length;
    if (src === 4 && dst === 4) {
        return "Interna";
    }
    if (src === 4 && dst >= 8) {
        return "Saída";
    }
    if (src !== 4 && dst === 4) {
        return "Entrada";
    }
    return '';
}

function linkFormatUniqueId(value, row, index){
    return '<a href="#" data-toggle="modal" data-target="#cel-modal" data-id="' + value + '">' + value + '</a>';
}

function totalTextFormatter(data) {
    return '<b>Total</b>';
}

function callDateFormatter(val, row) {
    let dateTime = new Date(val);
    return dateTime.toLocaleDateString() + " " + dateTime.toLocaleTimeString();
}

function secFormatter(val, row) {
    let hours   = Math.floor(val / 3600);
    let minutes = Math.floor((val - (hours * 3600)) / 60);
    let seconds = val - (hours * 3600) - (minutes * 60);
    // round seconds
    seconds = Math.round(seconds * 100) / 100;
    let result = (hours < 10 ? "0" + hours : hours);
    result += ":" + (minutes < 10 ? "0" + minutes : minutes);
    result += ":" + (seconds  < 10 ? "0" + seconds : seconds);
    return result;
}

function sumSecFormatter(data) {
    let field = this.field;
    let total = data.reduce(function(sum, row) {
        return sum + (+row[field]);
    }, 0);
    let result = secFormatter(total);
    return "<b>"+result+"</b>";
}

function costFormatter(val, row) {
    let a = new Number(val);
    return a.toLocaleString('pt-BR',{minimumFractionDigits: 2});
}

function sumCostFormatter(data) {
    field = this.field;
    let result = data.reduce(function(sum, row) {
        return sum + (+row[field]);
    }, 0);
    return "<b>"+costFormatter(result)+"</b>";
}

$(function() {
    $('#cel-modal').on('show.bs.modal', function(e){
        let uniqueid = $(e.relatedTarget).data('id');
        let table = $('#cel-table');
        table.bootstrapTable('removeAll');
        table.bootstrapTable('refreshOptions', {
            showRefresh: true,
            url: "ajax.php?module=tarifador&command=getCel&uniqueid="+uniqueid
        });
    });
});

$(function () {
    $('.accountcode').select2({
        placeholder: 'Selecione uma opção',
        minimumInputLength: 3,
        ajax: {
            url: 'ajax.php?module=tarifador&command=getUser',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
});

function requestForm(){
    return $("#call-form").serialize();
}

$(function () {
    $.ajax({
        url: "ajax.php?module=tarifador&command=getDisposition&"+requestForm(),
        method: "GET",
        success: function(data) {
            const ctx = $('#dispositionChart');
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data['disposition'],
                    datasets: [{
                        label: 'Chamadas',
                        data: data['value'],
                        backgroundColor: [
                            'rgba(76, 148, 113, 0.4)'
                        ],
                        borderColor: [
                            'rgba(76, 148, 113, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        error: function(data) {
        console.log(data);
    }
    });
});