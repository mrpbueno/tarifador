function dateTimeFormatter(val, row) {
    let dateTime = new Date(val);
    return moment(dateTime).format(datetimeformat);
}

function dateFormatter(val, row){
    return moment(val).format(dateformat);
}
