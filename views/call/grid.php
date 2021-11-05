<?php
include __DIR__."/form.php";
$request = "&accountcode=".$_REQUEST['accountcode'];
$request .= "&src=".$_REQUEST['src'];
$request .= "&startDate=";
$request .= empty($_REQUEST['startDate'])?date('Y-m-d'):$_REQUEST['startDate'];
$request .= "&startTime=";
$request .= empty($_POST['startTime']) ? '00:00' : $_POST['startTime'];
$request .= "&endDate=";
$request .= empty($_REQUEST['endDate'])?date('Y-m-d'):$_REQUEST['endDate'];
$request .= "&endTime=";
$request .= empty($_POST['endTime']) ? '23:59' : $_POST['endTime'];
$request .= "&dst=".$_REQUEST['dst'];
$request .= "&disposition=".$_REQUEST['disposition'];
?>

<div id="buttons-toolbar">
    <div class="btn-group" role="group">
        <button type="button"
                class="btn btn-default"
                title="<?php echo _("Download PDF")?>"
                onclick="exportToPDF()">
            <i class="fa fa-file-pdf-o"></i> <?php echo _("PDF")?>
        </button>
        <a type="button"
                class="btn btn-default"
                title="<?php echo _("Download Excel")?>"
                download="exportCall.xls"
                onclick="return ExcellentExport.excel(this, 'tarifador', 'call');">
            <i class="fa fa-file-excel-o"></i> <?php echo _("XLS")?>
        </a>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download CSV")?>"
           download="exportCall.csv"
           onclick="return ExcellentExport.csv(this, 'tarifador');">
            <i class="fa fa-file-text-o"></i> <?php echo _("CSV")?>
        </a>
    </div>
</div>

<table id="tarifador"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=call<?=$request?>"
       data-cache="false"
       data-state-save="true"
       data-state-save-id-table="tarifador_grid"
       data-maintain-selected="true"
       data-show-columns="true"
       data-toggle="table"
       data-pagination="true"
       data-search="true"
       data-export-footer="true"
       data-show-refresh="true"
       data-show-footer="true"
       data-toolbar="#buttons-toolbar"
       data-page-list="[10, 25, 50, 100, 200, 400, 800, 1600, ALL]"
       class="table table-sm small">
	<thead>
		<tr>
            <th data-field="calldate" data-sortable="true" data-formatter="callDateFormatter"><?php echo _("Data / Hora")?></th>
            <th data-field="uniqueid" data-sortable="true"><?php echo _("ID")?></th>
            <th data-field="user" data-sortable="true"><?php echo _("Usuário")?></th>
            <th data-field="src" data-sortable="true"><?php echo _("Origem")?></th>
            <th data-field="cnam" data-sortable="true"><?php echo _("Nome")?></th>
            <th data-field="did" data-sortable="true"><?php echo _("DDR")?></th>
            <th data-field="dst" data-sortable="true"><?php echo _("Destino")?></th>
            <th data-field="lastapp" data-sortable="true"><?php echo _("App")?></th>
            <th data-field="disposition" data-sortable="true" data-footer-formatter="totalTextFormatter"><?php echo _("Estado")?></th>
            <th data-field="wait" data-sortable="true" data-formatter="secFormatter" data-footer-formatter="sumSecFormatter"><?php echo _("Espera")?></th>
            <th data-field="billsec" data-sortable="true" data-formatter="secFormatter" data-footer-formatter="sumSecFormatter"><?php echo _("Duração")?></th>
            <th data-field="calltype" data-sortable="true"><?php echo _("Tipo")?></th>
            <th data-field="rate" data-sortable="true"><?php echo _("Tarifa")?></th>
            <th data-field="cost" data-sortable="true" data-formatter="costFormatter" data-footer-formatter="sumCostFormatter"><?php echo _("Custo")?></th>
		</tr>
	</thead>
</table>

<script type="text/javascript" charset="utf-8">

    function exportToPDF(){
        let jsPDF = window.jspdf.jsPDF;
        let doc = new jsPDF({orientation: "landscape"});
        doc.autoTable({
            html: '#tarifador',
            margin: { top: 5, right: 5, left: 5, bottom: 5 },
        });
        doc.save('exportCall.pdf');
    }


</script>