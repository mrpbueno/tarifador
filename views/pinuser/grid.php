<div id="buttons-toolbar">
    <form action="?display=tarifador&page=pinuser&action=sync" method="post">
    <div class="btn-group" role="group">
       <button type="submit"
               class="btn btn-default"
               title="<?php echo _("Sincronizar PINs")?>"
               data-toggle="modal"
               data-target="#sync">
           <i class="fa fa-refresh"></i>
       </button>

        <button type="button"
                class="btn btn-default"
                title="<?php echo _("Upload de CSV")?>"
                data-toggle="modal"
                data-target="#import">
            <i class="fa fa-upload"></i>
        </button>
        <button type="button"
                class="btn btn-default"
                title="<?php echo _("Download PDF")?>"
                onclick="exportToPDF('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')">
            <i class="fa fa-file-pdf-o"></i>
        </button>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download Excel")?>"
           download="<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>_ExportPinUser.xls"
           onclick="return ExcellentExport.excel(this, 'pinuser', 'pinuser');">
            <i class="fa fa-file-excel-o"></i>
        </a>
        <a type="button"
           class="btn btn-default"
           title="<?php echo _("Download CSV")?>"
           download="<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>_ExportPinUser.csv"
           onclick="return ExcellentExport.csv(this, 'pinuser');">
            <i class="fa fa-file-text-o"></i>
        </a>
    </div>
    </form>
</div>

<table id="pinuser"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=pinuser"
       data-cache="false"
       data-state-save="true"
       data-state-save-id-table="pinuser_grid"
       data-toolbar="#buttons-toolbar"
       data-maintain-selected="true"
       data-show-columns="true"
       data-toggle="table"
       data-pagination="true"
       data-search="true"
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

<script type="text/javascript" src="modules/tarifador/assets/js/views/pinuser.js"></script>