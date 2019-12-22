<form autocomplete="off" action="" method="post" class="fpbx-submit" id="hwform" name="hwform">
    <input type="hidden" name="action" id="action" value="search">
    <div class="row">
        <div class="col-md-6">
            <!--calldate start-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Data/Hora Início") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="calldate"></i>
                                </div>
                                <div class="col-md-4">
                                    <input type='date' class="form-control" id="startDate" name="startDate" value="<?php echo empty($_POST['startDate']) ? date('Y-m-d') : $_POST['startDate']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type='time' class="form-control" id="startime" name="startTime" value="<?php echo empty($_POST['startTime']) ? '00:00' : $_POST['startTime']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="calldate-help" class="help-block fpbx-help-block"><?php echo _("Selecione a data/hora de início")?></span>
                    </div>
                </div>
            </div>
            <!--END calldate start-->
            <!--src-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Origem") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="src"></i>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="src" name="src" value="<?php echo $_POST['src']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="src-help" class="help-block fpbx-help-block"><?php echo _("Digite o número de origem")?></span>
                    </div>
                </div>
            </div>
            <!--END src-->
            <!--accountcode-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Usuário") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="accountcode"></i>
                                </div>
                                <div class="col-md-7">
                                    <select class="accountcode form-control" name="accountcode"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="accountcode-help" class="help-block fpbx-help-block"><?php echo _("Digite o nome do usuário")?></span>
                    </div>
                </div>
            </div>
            <!--END accountcode-->
        </div>
        <div class="col-md-6">
            <!--calldate end-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Data/Hora Fim") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="endDate"></i>
                                </div>
                                <div class="col-md-4">
                                    <input type='date' class="form-control" id="endDate" name="endDate" value="<?php echo empty($_POST['endDate']) ? date('Y-m-d') : $_POST['endDate']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type='time' class="form-control" id="endTime" name="endTime" value="<?php echo empty($_POST['endTime']) ? '23:59' : $_POST['endTime']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="endDate-help" class="help-block fpbx-help-block"><?php echo _("Selecione a data/hora do fim")?></span>
                    </div>
                </div>
            </div>
            <!--END calldate end-->
            <!--dst-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Destino") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="dst"></i>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="dst" name="dst" value="<?php echo $_POST['dst']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="dst-help" class="help-block fpbx-help-block"><?php echo _("Digite o número de destino")?></span>
                    </div>
                </div>
            </div>
            <!--END src-->
            <!--disposition-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Estado") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="disposition"></i>
                                </div>
                                <div class="col-md-7">
                                    <select class="form-control" name="disposition" id="disposition">
                                        <option selected="selected" value=""><?php echo _("Todos") ?></option>
                                        <option value="ANSWERED"><?php echo _("Atendido") ?></option>
                                        <option value="BUSY"><?php echo _("Ocupado") ?></option>
                                        <option value="FAILED"><?php echo _("Falha") ?></option>
                                        <option value="NO ANSWER"><?php echo _("Não atendido") ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="disposition-help" class="help-block fpbx-help-block"><?php echo _("Selecione o estado da chamada")?></span>
                    </div>
                </div>
            </div>
            <!--END disposition-->
        </div>
    </div>
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> <?php echo _("Buscar")?></button>
            </div>
        </div>
    </div>
</form>