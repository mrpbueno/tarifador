<?php
// ======================================================================
// SECURE VARIABLE DEFINITION
// ======================================================================
// FreePBX's load_view() function extracts array keys into variables.
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
                                    <label class="control-label" for="body"><?php echo _("Start Date/Time") ?></label>
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
                        <span id="calldate-help" class="help-block fpbx-help-block"><?php echo _("Select start date/time")?></span>
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
                                    <label class="control-label" for="body"><?php echo _("Source") ?></label>
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
                            <?php echo _("Enter the source number or <BR>")?>
                            <?php echo _("Use Asterisk regular expressions.<br>")?>
                            <?php echo _("Example: _2[ZZ9]NX.<br>")?>
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
            <!--END src-->
            <!--accountcode-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("User") ?></label>
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
                        <span id="accountcode-help" class="help-block fpbx-help-block"><?php echo _("Enter the username")?></span>
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
                                    <label class="control-label" for="body"><?php echo _("End Date/Time") ?></label>
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
                        <span id="endDate-help" class="help-block fpbx-help-block"><?php echo _("Select end date/time")?></span>
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
                                    <label class="control-label" for="body"><?php echo _("Destination") ?></label>
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
                            <?php echo _("Enter the destination number or <BR>")?>
                            <?php echo _("Use Asterisk regular expressions.<br>")?>
                            <?php echo _("Example: _2[ZZ9]NX.<br>")?>
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
            <!--END src-->
            <!--disposition-->
            <div class="element-container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-5">
                                    <label class="control-label" for="body"><?php echo _("Status") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="disposition"></i>
                                </div>
                                <div class="col-md-7">
                                    <select class="form-control" name="disposition" id="disposition">
                                        <option value=""><?php echo _("All") ?></option>
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
                        <span id="disposition-help" class="help-block fpbx-help-block"><?php echo _("Select the call status")?></span>
                    </div>
                </div>
            </div>
            <!--END disposition-->
        </div>
    </div>
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> <?php echo _("Search")?></button>
            </div>
        </div>
    </div>
</form>