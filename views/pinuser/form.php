<?php
// ======================================================================
// SECURE VARIABLE DEFINITION
// ======================================================================
use FreePBX\modules\Tarifador\Utils\Sanitize;

$id         = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pin        = Sanitize::stringOutput($pin ?? _("Will be generated automatically"));
$user       = Sanitize::stringOutput($user ?? '');
$department = Sanitize::stringOutput($department ?? '');
$enabled    = Sanitize::stringOutput($enabled ?? '1');
$pinsets    = $pinsets ?? [];
?>
<h3><?php echo ($id ? _("Edit User PIN") : _("New User PIN")) ?></h3>
<form autocomplete="off"
      action=""
      method="post"
      class="fpbx-submit"
      id="hwform"
      name="hwform"
      data-fpbx-delete="config.php?display=tarifador&page=pinuser&action=delete&id=<?php echo $id?>">
    <input type="hidden" name="view" value="form">
    <input type="hidden" name='action' value="<?php echo $id?'edit':'add' ?>">
    <!--Pin-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("PIN") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="pin"></i>
                        </div>
                        <div class="col-md-9">
                            <?php echo $pin; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="pin-help" class="help-block fpbx-help-block"><?php echo _("User's PIN number")?></span>
            </div>
        </div>
    </div>
    <!--End Pin-->
    <!--Name-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Name") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="user"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text" maxlength="50" class="form-control maxlen" id="user" name="user" value="<?php echo $user?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="user-help" class="help-block fpbx-help-block"><?php echo _("Enter the PIN user's name")?></span>
            </div>
        </div>
    </div>
    <!--END Name-->
    <!--Department-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="body"><?php echo _("Department / Sector") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="department"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text"
                                   maxlength="50"
                                   class="typeahead form-control maxlen"
                                   id="department"
                                   name="department"
                                   data-provide="typeahead"
                                   value="<?php echo $department ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="department-help" class="help-block fpbx-help-block"><?php echo _("Enter the user's department / sector")?></span>
            </div>
        </div>
    </div>
    <!--END Department-->
    <!--enabled-->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="active"><?php echo _("Enabled?") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="active"></i>
                        </div>
                        <div class="col-md-9">
                            <i class="btn btn-<?php echo ($enabled == '1' ? 'success' : 'danger'); ?>">
                                <?php echo ($enabled == '1' ? _("Yes") : _("No")); ?>
                            </i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="active-help" class="help-block fpbx-help-block"><?php echo _("To disable the PIN, go to the <a href='config.php?display=pinsets'>PIN Sets</a> module")?></span>
            </div>
        </div>
    </div>
    <!--END enabled-->
    <!--Pinsets -->
    <div class="element-container">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="form-group">
                        <div class="col-md-3">
                            <label class="control-label" for="active"><?php echo _("PIN Set") ?></label>
                            <i class="fa fa-question-circle fpbx-help-icon" data-for="pinsets"></i>
                        </div>
                        <div class="col-md-9">
                           <ul>
                               <?php foreach ((array)$pinsets as  $pinset): ?>
                               <li>
                                   <a href="config.php?display=pinsets&view=form&itemid=<?php echo $pinset['pinsets_id'] ?>">
                                       <?php echo htmlspecialchars($pinset['description'], ENT_QUOTES, 'UTF-8'); ?>
                                   </a>
                               </li>
                               <?php endforeach; ?>
                           </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <span id="pinsets-help" class="help-block fpbx-help-block"><?php echo _("User's PIN Set")?></span>
            </div>
        </div>
    </div>
    <!--END Pinsets -->
</form>
<script>
    $(document).ready(function () {
        var path = "ajax.php?module=tarifador&command=getDepartment";
        $('input.typeahead').typeahead({
            source:  function (term, process) {
                return $.get(path, { term: term }, function (data) {
                    return process(data);
                });
            }
        });
    });
</script>