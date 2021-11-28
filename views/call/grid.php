<?php include __DIR__."/form.php"; ?>

<div id="buttons-toolbar">
    <div class="btn-group" role="group">
        <button type="button"
                class="btn btn-default"
                title="<?php echo _("Download PDF")?>"
                onclick="exportToPDF('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')">
            <i class="fa fa-file-pdf-o"></i>
        </button>
        <a type="button"
                class="btn btn-default"
                title="<?php echo _("Download Excel")?>"
                download="<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>_ExportCall.xls"
                onclick="return ExcellentExport.excel(this, 'tarifador', 'call');">
            <i class="fa fa-file-excel-o"></i>
        </a>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download CSV")?>"
           download="<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>_ExportCall.csv"
           onclick="return ExcellentExport.csv(this, 'tarifador');">
            <i class="fa fa-file-text-o"></i>
        </a>
    </div>
</div>

<table id="tarifador"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=call"
       data-cache="false"
       data-query-params="queryParams"
       data-state-save="true"
       data-state-save-id-table="tarifador_grid"
       data-maintain-selected="true"
       data-show-columns="true"
       data-toggle="table"
       data-pagination="true"
       data-side-pagination="server"
       data-show-refresh="true"
       data-toolbar="#buttons-toolbar"
       data-page-list="[10, 50, 100, 500, 1000, 5000, 10000]"
       class="table table-sm small">
	<thead>
		<tr>
            <th data-field="calldate" data-sortable="true" data-formatter="callDateFormatter"><?php echo _("Data / Hora")?></th>
            <th data-field="uniqueid" data-sortable="true" data-formatter="linkFormatUniqueId"><?php echo _("UniqueID")?></th>
            <th data-field="user"><?php echo _("Usuário")?></th>
            <th data-field="src" data-sortable="true"><?php echo _("Origem")?></th>
            <th data-field="cnam"><?php echo _("Nome")?></th>
            <th data-field="did"><?php echo _("DDR")?></th>
            <th data-field="dst" data-sortable="true"><?php echo _("Destino")?></th>
            <th data-field="lastapp"><?php echo _("App")?></th>
            <th data-field="disposition" data-formatter="dispositionFormatter"><?php echo _("Estado")?></th>
            <th data-field="wait" data-formatter="secFormatter"><?php echo _("Espera")?></th>
            <th data-field="billsec" data-formatter="secFormatter"><?php echo _("Duração")?></th>
            <th data-field="calltype" data-formatter="callTypeFormatter"><?php echo _("Tipo")?></th>
            <th data-field="rate"><?php echo _("Tarifa")?></th>
            <th data-field="cost" data-formatter="costFormatter"><?php echo _("Custo")?></th>
		</tr>
	</thead>
</table>
<br>
<div class = "display full-border">
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#dispositionChart"
                   title="<?php echo _("Download PDF")?>"
                   class="fa fa-bar-chart"
                   onclick="exportChartToPDF('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')"
                   aria-hidden="true"></a> <?php echo _("Estado das chamadas")?>
            </div>
            <div class="panel-body">
                <div>
                    <canvas id="dispositionChart" style="position: relative; height:30vh; width:80vw"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
</div>


<!-- Modal -->
<div class="modal fade" id="cel-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 100%; width: auto; display: table;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="myModalLabel"><?php echo _("Detalhes da Chamada")?></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="cel-table"
                       data-toggle="table"
                       data-show-columns="true"
                       data-pagination="true"
                       data-search="true"
                       data-mobile-responsive="true"
                       data-check-on-init="true"
                       data-page-list="[10, 25, 50, 100, ALL]"
                       class="table table-sm small">
                    <thead>
                    <th data-field="eventtime" data-sortable="true" data-formatter="callDateFormatter"><?php echo _("Data / Hora")?></th>
                    <th data-field="eventtype" data-sortable="true" ><?php echo _("Evento")?></th>
                    <th data-field="cid_num" data-sortable="true" ><?php echo _("Número")?></th>
                    <th data-field="cid_name" data-sortable="true" ><?php echo _("Nome")?></th>
                    <th data-field="exten" data-sortable="true" ><?php echo _("Exten")?></th>
                    <th data-field="cid_dnid" data-sortable="true" ><?php echo _("DNID")?></th>
                    <th data-field="context" data-sortable="true" ><?php echo _("Contexto")?></th>
                    <th data-field="channame" data-sortable="true"><?php echo _("Canal")?></th>
                    <th data-field="uniqueid" data-sortable="true" ><?php echo _("UniqueID")?></th>
                    <th data-field="linkedid" data-sortable="true" ><?php echo _("LinkedID")?></th>
                    </thead>
                </table>

            </div>
            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"><?php echo _("Fechar")?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="modules/tarifador/assets/js/views/call.js"></script>