<?php
// ======================================================================
// DEFINIÇÃO SEGURA DE VARIÁVEIS
// ======================================================================
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id !== false && $id !== null) {
    $itemid        = htmlspecialchars($itemid, ENT_QUOTES, 'UTF-8');
    $name          = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $telco         = htmlspecialchars($telco, ENT_QUOTES, 'UTF-8');
    $dial_pattern  = htmlspecialchars($dial_pattern, ENT_QUOTES, 'UTF-8');
    $rate          = htmlspecialchars($rate, ENT_QUOTES, 'UTF-8');
    $start         = htmlspecialchars($start, ENT_QUOTES, 'UTF-8');
    $end           = htmlspecialchars($end, ENT_QUOTES, 'UTF-8');
} else {
    $itemid        = '';
    $name          = '';
    $telco         = '';
    $dial_pattern  = '';
    $rate          = '';
    $start         = '';
    $end           = '';
}
?>
<h3><?php echo ($itemid ? _("Editar tarifa") : _("Nova tarifa")) ?></h3>
<form autocomplete="off"
      action=""
      method="post"
      class="fpbx-submit" id="hwform"
      name="hwform"
      data-fpbx-delete="config.php?display=tarifador&page=rate&action=delete&id=<?php echo $id?>">
    <input type="hidden" name="view" value="form">
    <input type="hidden" name='action' value="<?php echo $id?'edit':'add' ?>">
    <input type="hidden" name="rate" value="<?php echo $itemid; ?>">
    <!--Name-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Nome") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text" maxlength="50" class="form-control maxlen" id="name" name="name" value="<?php echo $name?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="name-help" class="help-block fpbx-help-block"><?php echo _("Digite o nome da tarifa")?></span>
            </div>
        </div>
    </div>
    <!--END Name-->
    <!--Telco-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Operadora") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="telco"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text" maxlength="50" class="form-control maxlen" id="telco" name="telco" value="<?php echo $telco?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="telco-help" class="help-block fpbx-help-block"><?php echo _("Digite o nome da operadora")?></span>
            </div>
        </div>
    </div>
    <!--END Telco-->
    <!--dial_pattern-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Padrão de discagem") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="dial_pattern"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text" maxlength="50" class="form-control maxlen" id="dial_pattern" name="dial_pattern" value="<?php echo $dial_pattern?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="dial_pattern-help" class="help-block fpbx-help-block">
                    <?php echo _("Utilize expressões regulares do Asterisk.<br>")?>
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
    <!--END dial_pattern-->
    <!--Rate-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Tarifa") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="rate"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="number" step="any" maxlength="50" class="form-control maxlen" id="rate" name="rate" value="<?php echo $rate?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="rate-help" class="help-block fpbx-help-block"><?php echo _("Digite a tarifa por minuto")?></span>
            </div>
        </div>
    </div>
    <!--END Rate-->
    <!--Start-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Início da vigência") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="start"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="date" maxlength="50" class="form-control maxlen" id="start" name="start" value="<?php echo $start?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="start-help" class="help-block fpbx-help-block"><?php echo _("Selecione o início da vigência da tarifa")?></span>
            </div>
        </div>
    </div>
    <!--END Start-->
    <!--End-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Fim da vigência") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="end"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="date" maxlength="50" class="form-control maxlen" id="end" name="end" value="<?php echo $end?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="end-help" class="help-block fpbx-help-block"><?php echo _("Selecione o fim da vigência da tarifa")?></span>
            </div>
        </div>
    </div>
    <!--END End-->
</form>