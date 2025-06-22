
function requestForm(){
    return $("#call-form").serialize();
}

function queryParams(params){
    let formData = $("#call-form").serialize();
    $.each(formData.split('&'), function(k,v) {
        let parts = v.split('=');
        params[parts[0]] = parts[1];
    });
    return params;
}

function numberFormatter(val, row) {
    return new Number(val).toLocaleString('pt-BR');
}

function exportTopSrcCountChart(name) {
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({orientation: "landscape", format: 'a4'});
    html2canvas(document.querySelector('#topSrcCountChart')).then(function(canvas){
        let imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', 10, 10, 281, 66);
        doc.save(name+'_ExportTopCallSource.pdf');
    });
}

function exportTopDstCountChart(name) {
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({orientation: "landscape", format: 'a4'});
    html2canvas(document.querySelector('#topDstCountChart')).then(function(canvas){
        let imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', 10, 10, 281, 66);
        doc.save(name+'_ExportTopCallDestination.pdf');
    });
}

function exportCallsHourChart(name) {
    let jsPDF = window.jspdf.jsPDF;
    let doc = new jsPDF({orientation: "landscape", format: 'a4'});
    html2canvas(document.querySelector('#callsHourChart')).then(function(canvas){
        let imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', 10, 10, 281, 66);
        doc.save(name+'_ExportCallHour.pdf');
    });
}

$(function () {
    $.ajax({
        url: "ajax.php?module=tarifador&command=getTopSrcCount&"+requestForm(),
        method: "GET",
        success: function(data) {
            const ctx = $('#topSrcCountChart');
            let src = [];
            let total = [];
            for (let i in data) {
                src.push(data[i].src);
                total.push(data[i].total);
            }
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: src,
                    datasets: [{
                        label: 'Chamadas',
                        data: total,
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
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: Math.round,
                            font: {
                                weight: 'bold'
                            }
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

$(function () {
    $.ajax({
        url: "ajax.php?module=tarifador&command=getTopDstCount&"+requestForm(),
        method: "GET",
        success: function(data) {
            const ctx = $('#topDstCountChart');
            let dst = [];
            let total = [];
            for (let i in data) {
                dst.push(data[i].dst);
                total.push(data[i].total);
            }
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dst,
                    datasets: [{
                        label: 'Chamadas',
                        data: total,
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
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: Math.round,
                            font: {
                                weight: 'bold'
                            }
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

$(function () {
    $.ajax({
        url: "ajax.php?module=tarifador&command=getCallsHour&"+requestForm(),
        method: "GET",
        success: function(data) {
            const ctx = $('#callsHourChart');
            let hour = [];
            let total = [];
            for (let i in data) {
                hour.push(data[i].hour + ':00');
                total.push(data[i].total);
            }
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hour,
                    datasets: [{
                        label: 'Chamadas',
                        data: total,
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
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: Math.round,
                            font: {
                                weight: 'bold'
                            }
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