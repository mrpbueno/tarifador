<?php include __DIR__."../../call/form.php"; ?>

<br/>
<table id="stats"
       data-url="ajax.php?module=tarifador&command=getJSON&jdata=grid&page=stats"
       data-cache="false"
       data-query-params="queryParams"
       data-state-save="true"
       data-state-save-id-table="stats_grid"
       data-toolbar="#buttons-toolbar"
       data-maintain-selected="true"
       data-toggle="table"
       data-pagination="true"
       class="table table-sm">
    <thead>
    <tr>
        <th data-field="total"><?php echo _("Quantidade")?></th>
        <th data-field="minutes"><?php echo _("Minutos")?></th>
        <th data-field="avg"><?php echo _("Média")?></th>
    </tr>
    </thead>
</table>
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
                       aria-hidden="true"></a> <?php echo _("Destino das chamadas (top 50)")?>
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
                   aria-hidden="true"></a> <?php echo _("Origem das chamadas (top 50)")?>
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
                       aria-hidden="true"></a> <?php echo _("Distribuição das chamadas por hora")?>
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