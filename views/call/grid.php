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
<table id="tarifador"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=call<?=$request?>"
       data-cache="false"
       data-state-save="true"
       data-state-save-id-table="tarifador_grid"
       data-toolbar="#toolbar-all"
       data-maintain-selected="true"
       data-show-columns="true"
       data-show-toggle="true"
       data-toggle="table"
       data-pagination="true"
       data-search="true"
       data-show-export="true"
       data-export-footer="true"
       data-show-refresh="true"
       data-show-footer="true"
       data-page-list="[10, 25, 50, 100, 200, 400, 800, 1600, ALL]"
       class="table table-sm small">
	<thead>
		<tr>
            <th data-field="calldate" data-sortable="true" data-formatter="dateTimeFormatter"><?php echo _("Data / Hora")?></th>
            <th data-field="accountcode" data-sortable="true"><?php echo _("Usuário")?></th>
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
    $(document).ready(function() {
        $('#tarifador').bootstrapTable({
            exportDataType: $(this).val(),
            exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
            exportOptions: {
                fileName: 'exportCall',
                jspdf: {
                    orientation: 'l',
                    format: 'a4',
                    margins: {left:10, right:10, top:20, bottom:20},
                    autotable: {
                        theme: 'grid',
                        tableWidth: 'wrap',
                        styles: {cellWidth: 'wrap', overflow: 'linebreak'},
                        columnStyles: {text: {cellWidth: 'wrap'}}
                    }
                }
            }
        });
    });
</script>