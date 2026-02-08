<?php
// ======================================================================
// SECURE VARIABLE DEFINITION
// ======================================================================
use FreePBX\modules\Tarifador\Utils\Sanitize;
$id            = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$itemid        = Sanitize::stringOutput($itemid ?? '');
$name          = Sanitize::stringOutput($name ?? '');
$telco         = Sanitize::stringOutput($telco ?? '');
$dial_pattern  = Sanitize::stringOutput($dial_pattern ?? '');
$rate          = Sanitize::stringOutput($rate ?? '');
$start         = Sanitize::stringOutput($start ?? '');
$end           = Sanitize::stringOutput($end ?? '');
?>

<h3><?php echo ($itemid ? _("Edit Rate") : _("New Rate")) ?></h3>
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
                            <label class="control-label" for="body"><?php echo _("Name") ?></label>
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
                <span id="name-help" class="help-block fpbx-help-block"><?php echo _("Enter the rate name")?></span>
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
                            <label class="control-label" for="body"><?php echo _("Carrier") ?></label>
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
                <span id="telco-help" class="help-block fpbx-help-block"><?php echo _("Enter the carrier name")?></span>
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
                            <label class="control-label" for="body"><?php echo _("Dial Pattern") ?></label>
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
                    <?php echo _("Use Asterisk regular expressions.<br>")?>
                    <?php echo _("<b>Rules:</b><br>")?>
                    <?php echo _("<b>X</b> = matches any digit between 0-9<br>")?>
                    <?php echo _("<b>Z</b> = matches any digit between 1-9<br>")?>
                    <?php echo _("<b>N</b> = matches any digit between 2-9<br>")?>
                    <?php echo _("<b>[1237-9]</b> = matches any digit or letter in brackets (e.g., 1,2,3,7,8,9)<br>")?>
                    <?php echo _("<b>.</b> = wildcard, matches one or more digits<br>")?>
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
                            <label class="control-label" for="body"><?php echo _("Rate") ?></label>
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
                <span id="rate-help" class="help-block fpbx-help-block"><?php echo _("Enter the rate per minute")?></span>
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
                            <label class="control-label" for="body"><?php echo _("Start Date") ?></label>
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
                <span id="start-help" class="help-block fpbx-help-block"><?php echo _("Select the rate's start date")?></span>
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
                            <label class="control-label" for="body"><?php echo _("End Date") ?></label>
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
                <span id="end-help" class="help-block fpbx-help-block"><?php echo _("Select the rate's end date")?></span>
            </div>
        </div>
    </div>
    <!--END End-->
</form>