<?php
$toast_message_json = isset($_SESSION['toast_message']) ? json_encode($_SESSION['toast_message']) : null;
if ($toast_message_json) { unset($_SESSION['toast_message']); }
?>
<div id="grid-container" <?php if ($toast_message_json): ?>data-toast='<?php echo $toast_message_json; ?>'<?php endif; ?>>
<div class="container-fluid">
	<h1><i class="fa fa-phone"></i> <?php echo _("Billing")?></h1>
    <h3><?php echo $title; ?></h3>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo $content; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>