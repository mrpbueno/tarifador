<?php include __DIR__."../../call/form.php"; ?>
<br>
<div class = "display full-border">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="#topDstCountChart"
                       title="<?php echo _("Download PDF")?>"
                       class="fa fa-bar-chart"
                       onclick="exportTopDstCountChart('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')"
                       aria-hidden="true"></a> <?php echo _("Call Destination (top 50)")?>
                </div>
                <div class="panel-body">
                    <div>
                        <canvas id="topDstCountChart" style="position: relative; height:40vh; width:80vw"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br/>
    <div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#topSrcCountChart"
                   title="<?php echo _("Download PDF")?>"
                   class="fa fa-bar-chart"
                   onclick="exportTopSrcCountChart('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')"
                   aria-hidden="true"></a> <?php echo _("Call Source (top 50)")?>
            </div>
            <div class="panel-body">
                <div>
                    <canvas id="topSrcCountChart" style="position: relative; height:40vh; width:80vw"></canvas>
                </div>
            </div>
        </div>
    </div>
    </div>

    <br/>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="#callsHourChart"
                       title="<?php echo _("Download PDF")?>"
                       class="fa fa-bar-chart"
                       onclick="exportCallsHourChart('<?php echo \FreePBX::Config()->get('FREEPBX_SYSTEM_IDENT'); ?>')"
                       aria-hidden="true"></a> <?php echo _("Call distribution by hour")?>
                </div>
                <div class="panel-body">
                    <div>
                        <canvas id="callsHourChart" style="position: relative; height:40vh; width:80vw"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<script type="text/javascript" src="modules/tarifador/assets/js/views/stats.js"></script>