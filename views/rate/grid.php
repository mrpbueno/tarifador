<div id="buttons-toolbar">
    <div class="btn-group" role="group">
        <button type="button"
                class="btn btn-default"
                onclick="location.href='?display=tarifador&page=rate&view=form';">
            <i class="fa fa-plus"></i>&nbsp;<?php echo _("Adicionar Tarifa")?>
        </button>
        <button type="button"
                class="btn btn-default"
                title="<?php echo _("Download PDF")?>"
                onclick="exportToPDF()">
            <i class="fa fa-file-pdf-o"></i> <?php echo _("PDF")?>
        </button>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download Excel")?>"
           download="exportRate.xls"
           onclick="return ExcellentExport.excel(this, 'rate', 'rate');">
            <i class="fa fa-file-excel-o"></i> <?php echo _("XLS")?>
        </a>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download CSV")?>"
           download="exportRate.csv"
           onclick="return ExcellentExport.csv(this, 'rate');">
            <i class="fa fa-file-text-o"></i> <?php echo _("CSV")?>
        </a>
    </div>
</div>

<table id="rate"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=rate"
       data-cache="false"
       data-state-save="true"
       data-state-save-id-table="rate_grid"
       data-toolbar="#buttons-toolbar"
       data-maintain-selected="true"
       data-show-columns="true"
       data-show-refresh="true"
       data-reorderable-rows="true"
       data-use-row-attr-func="true"
       data-toggle="table"
       data-pagination="true"
       data-search="true"
       data-page-list="[10, 25, 50, 100, 200, 400, 800, 1600]"
       class="table table-sm">
	<thead>
		<tr>
            <th data-field="name"><?php echo _("Nome")?></th>
            <th data-field="telco"><?php echo _("Operadora")?></th>
            <th data-field="dial_pattern"><?php echo _("Padrão de discagem")?></th>
            <th data-field="rate" data-formatter="rateFormatter"><?php echo _("Tarifa")?></th>
            <th data-field="start" data-formatter="dateFormatter"><?php echo _("Início da vigência")?></th>
            <th data-field="end" data-formatter="dateFormatter"><?php echo _("Fim da vigência")?></th>
            <th data-field="id" data-formatter="linkFormatRate" class="text-center"><?php echo _("Ações")?></th>
		</tr>
	</thead>
</table>
<script type="text/javascript" charset="utf-8">

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
        let doc = new jsPDF();
        doc.autoTable({
            html: '#rate',
            margin: { top: 5, right: 5, left: 5, bottom: 5 },
        });
        doc.save('exportRate.pdf');
    }
</script>