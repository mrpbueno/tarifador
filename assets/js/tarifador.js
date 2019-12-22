function dateTimeFormatter(val, row) {
    var dateTime = new Date(val);
    return moment(dateTime).format(datetimeformat);
}

function secFormatter(val, row){
    var hours   = Math.floor(val / 3600);
    var minutes = Math.floor((val - (hours * 3600)) / 60);
    var seconds = val - (hours * 3600) - (minutes * 60);

    // round seconds
    seconds = Math.round(seconds * 100) / 100

    var result = (hours < 10 ? "0" + hours : hours);
    result += ":" + (minutes < 10 ? "0" + minutes : minutes);
    result += ":" + (seconds  < 10 ? "0" + seconds : seconds);
    return result;
}

function costFormatter(val, row) {
    var a = new Number(val);
    return a.toLocaleString('pt-BR',{minimumFractionDigits: 2});
}

function totalTextFormatter(data) {
    return '<b>Total</b>';
}

function sumCostFormatter(data) {
    field = this.field;
    var result = data.reduce(function(sum, row) {
        return sum + (+row[field]);
    }, 0);
    return "<b>"+costFormatter(result)+"</b>";
}

function sumSecFormatter(data) {
    var field = this.field;
    var total = data.reduce(function(sum, row) {
        return sum + (+row[field]);
    }, 0);
    var result = secFormatter(total);
    return "<b>"+result+"</b>";
}

$(document).ready(function () {
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

function linkFormatPinUser(value, row, index){
    var html = (row['enabled']==1)?'<i class="fa fa-toggle-on" style="color:green"></i>&nbsp;':'<i class="fa fa-toggle-off" style="color:red"></i>&nbsp;';
    html += '<a href="?display=tarifador&page=pinuser&view=form&id='+value+'"><i class="fa fa-pencil-square-o"></i></a>&nbsp;';
    html += '<a class="delAction" href="?display=tarifador&page=pinuser&action=delete&id='+value+'"><i class="fa fa-trash"></i></a>';
    return html;
}

function linkFormatRate(value, row, index){
    var html = '<a href="?display=tarifador&page=rate&view=form&id='+value+'"><i class="fa fa-pencil-square-o"></i></a>&nbsp;';
    html += '<a class="delAction" href="?display=tarifador&page=rate&action=delete&id='+value+'"><i class="fa fa-trash"></i></a>';
    return html;
}

function activeFormat(value) {
    if(value == 1) {
        var html = '<i class="text-success fa fa-check-circle-o"></i>';
    } else  {
        var html = '<i class="text-danger fa fa-ban"></i>';
    }
    return html;
}

function dateFormatter(val, row){
    return moment(val).format(dateformat);
}

function rateFormatter(val, row){
    var a = new Number(val);
    return a.toLocaleString('pt-BR',{minimumFractionDigits: 5});
}
