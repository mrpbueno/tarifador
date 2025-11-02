<?php
// ======================================================================
// DEFINIÇÃO SEGURA DE VARIÁVEIS
// ======================================================================
// A função load_view() do FreePBX extrai as chaves do array de dados em variáveis.
use FreePBX\modules\Tarifador\Utils\Sanitize;

$src_value         = Sanitize::stringOutput($_POST['src'] ?? '');
$dst_value         = Sanitize::stringOutput($_POST['dst'] ?? '');
$disposition_value = Sanitize::stringOutput($_POST['disposition'] ?? '');
$accountcode_value = Sanitize::stringOutput($_POST['accountcode'] ?? '');
$userName_value    = Sanitize::stringOutput($_POST['userName'] ?? '');
$startDate_value   = Sanitize::stringOutput($_POST['startDate'] ?? date('Y-m-d'));
$startTime_value   = Sanitize::stringOutput($_POST['startTime'] ?? '00:00');
$endDate_value     = Sanitize::stringOutput($_POST['endDate'] ?? date('Y-m-d'));
$endTime_value     = Sanitize::stringOutput($_POST['endTime'] ?? '23:59');
?>
<form autocomplete="off" action="" method="post" class="fpbx-submit" id="call-form" name="call-form">
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
                                    <input type='date' class="form-control" id="startDate" name="startDate" value="<?php echo $startDate_value; ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type='time' class="form-control" id="starTime" name="startTime" value="<?php echo $startTime_value; ?>">
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
                                    <input type="text" class="form-control" id="src" name="src" value="<?php echo $src_value; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="src-help" class="help-block fpbx-help-block">
                            <?php echo _("Digite o número de origem ou <BR>")?>
                            <?php echo _("Utilize expressões regulares do Asterisk.<br>")?>
                            <?php echo _("Exemplo: _2[ZZ9]NX.<br>")?>
                            <?php echo _("<b>Regras:</b><br>")?>
                            <?php echo _("<b>X</b> = corresponde a qualquer dígito entre 0-9<br>")?>
                            <?php echo _("<b>Z</b> = corresponde a qualquer dígito entre 1-9<br>")?>
                            <?php echo _("<b>N</b> = corresponde a qualquer dígito entre 2-9<br>")?>
                            <?php echo _("<b>[1237-9]</b> = corresponde a qualquer dígito ou letra entre colchetes (neste exemplo: 1,2,3,7,8,9)<br>")?>
                            <?php echo _("<b>.</b> = curinga, corresponde a um ou mais dígitos<br>")?>
                        </span>
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
                                    <select class="form-control" name="accountcode" id="accountcode">
                                        <option <?php if(!empty($accountcode_value)) echo"selected"; ?>
                                                value="<?php echo $accountcode_value?>">
                                            <?php echo $userName_value; ?>
                                        </option>
                                    </select>
                                    <input type="hidden" name="userName" value="<?php echo $userName_value?>" id="userName">
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
                                    <input type='date' class="form-control" id="endDate" name="endDate" value="<?php echo $endDate_value; ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type='time' class="form-control" id="endTime" name="endTime" value="<?php echo $endTime_value; ?>">
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
                                    <input type="text" class="form-control" id="dst" name="dst" value="<?php echo $dst_value; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="dst-help" class="help-block fpbx-help-block">
                            <?php echo _("Digite o número de destino ou <BR>")?>
                            <?php echo _("Utilize expressões regulares do Asterisk.<br>")?>
                            <?php echo _("Exemplo: _2[ZZ9]NX.<br>")?>
                            <?php echo _("<b>Regras:</b><br>")?>
                            <?php echo _("<b>X</b> = corresponde a qualquer dígito entre 0-9<br>")?>
                            <?php echo _("<b>Z</b> = corresponde a qualquer dígito entre 1-9<br>")?>
                            <?php echo _("<b>N</b> = corresponde a qualquer dígito entre 2-9<br>")?>
                            <?php echo _("<b>[1237-9]</b> = corresponde a qualquer dígito ou letra entre colchetes (neste exemplo: 1,2,3,7,8,9)<br>")?>
                            <?php echo _("<b>.</b> = curinga, corresponde a um ou mais dígitos<br>")?>
                        </span>
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
                                        <option value=""><?php echo _("Todos") ?></option>
                                        <option <?php if($disposition_value == "ANSWERED") echo"selected"; ?> value="ANSWERED"><?php echo _("ANSWERED") ?></option>
                                        <option <?php if($disposition_value == "NO ANSWER") echo"selected"; ?> value="NO ANSWER"><?php echo _("NO ANSWER") ?></option>
                                        <option <?php if($disposition_value == "BUSY") echo"selected"; ?> value="BUSY"><?php echo _("BUSY") ?></option>
                                        <option <?php if($disposition_value == "FAILED") echo"selected"; ?> value="FAILED"><?php echo _("FAILED") ?></option>
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