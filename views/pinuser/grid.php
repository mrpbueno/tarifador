<div id="toolbar-all">
    <div class="col-md-6">
        <form action="?display=tarifador&page=pinuser&action=sync" method="post">
            <button type="submit"
                    class="btn btn-default"
                    data-toggle="modal"
                    data-target="#sync">
                <i class="fa fa-refresh"></i> <?php echo _("Sincronizar PINs")?>
            </button>
        </form>
    </div>
    <div class="col-md-6">
        <button type="button"
                class="btn btn-default"
                data-toggle="modal"
                data-target="#import">
            <i class="fa fa-upload"></i> <?php echo _("Upload de CSV")?>
        </button>
    </div>
</div>
<table id="pinuser"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=pinuser"
       data-cache="false"
       data-state-save="true"
       data-state-save-id-table="pinuser_grid"
       data-toolbar="#toolbar-all"
       data-maintain-selected="true"
       data-show-columns="true"
       data-show-toggle="true"
       data-toggle="table"
       data-pagination="true"
       data-search="true"
       data-show-export="true"
       data-show-refresh="true"
       data-page-list="[10, 25, 50, 100, 200, 400, 800, 1600]"
       class="table table-sm"">
	<thead>
		<tr>
            <th data-field="pin" class="col-md-1" data-sortable="true"><?php echo _("PIN")?></th>
            <th data-field="user" class="col-md-5" data-sortable="true"><?php echo _("Nome")?></th>
            <th data-field="department" class="col-md-5" data-sortable="true"><?php echo _("Departamento / Setor")?></th>
            <th data-field="id" data-formatter="linkFormatPinUser" class="col-md-1 text-center"><?php echo _("Ações")?></th>
		</tr>
	</thead>
</table>
<div id="sync" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h2 class="modal-title"><?php echo _("Aguarde!")?></h2>
            </div>
            <div class="modal-body">
                <h3 class="modal-body"><i class="fa fa-spinner fa-spin"></i> <?php echo _("Sincronizando ...")?></h3>
            </div>
        </div>
    </div>
</div>
<div id="import" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h2 class="modal-title"><?php echo _("Upload de CSV")?></h2>
            </div>
            <div class="modal-body">
                <form action="?display=tarifador&page=pinuser&action=import" method="post" enctype="multipart/form-data">
                    <div class="element-container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="form-group">
                                        <div class="col-md-3">
                                            <label class="control-label" for="user_file"><?php echo _("Arquivo CSV") ?></label>
                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="user_file"></i>
                                        </div>
                                        <div class="col-md-9">
										<span class="btn btn-default btn-file">
											<?php echo _("Localizar")?><input type="file" name="user_file" class="form-control" required />
										</span>
                                            <span class="filename"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>
                                    <?php echo _("O cabeçalho do arquivo deve conter: pin, user, department")?>
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="user_file-help" class="help-block fpbx-help-block"><?php echo _("Arquivo para Importar")?></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-default"><?php echo _("Enviar")?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        $('#pinuser').bootstrapTable({
            exportDataType: $(this).val(),
            exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
            exportOptions: {
                fileName: 'exportPinUser',
                jspdf: {
                    orientation: 'p',
                    format: 'a4',
                    margins: {left:10, right:10, top:20, bottom:20},
                    autotable: {
                        theme: 'striped',
                        styles: {
                            cellWidth: 'auto',
                            minCellWidth: '1',
                            overflow: 'linebreak',
                        },
                        tableWidth: 'auto',
                    }
                }
            }
        });
    });
</script>