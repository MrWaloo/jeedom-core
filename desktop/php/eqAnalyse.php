<?php
global $JEEDOM_INTERNAL_CONFIG;
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$list = array();
$eqLogicsAll = eqLogic::all();
foreach ($eqLogicsAll as $eqLogic) {
	if (!$eqLogic->hasRight('r')) {
		continue;
	}
	$battery_type = str_replace(array('(', ')'), array('', ''), $eqLogic->getConfiguration('battery_type', ''));
	if ($eqLogic->getIsEnable() && $eqLogic->getStatus('battery', -2) != -2) {
		array_push($list, $eqLogic);
	}
}
usort($list, function ($a, $b) {
	return ($a->getStatus('battery') < $b->getStatus('battery')) ? -1 : (($a->getStatus('battery') > $b->getStatus('battery')) ? 1 : 0);
});


$remove_history = jeedom::getRemovehistory();
sendVarToJs('jeephp2js.removeHistory', $remove_history);
?>

<div class="row row-overflow">
	<div class="hasfloatingbar col-xs-12">
		<div class="floatingbar">
			<div>
				<a id="bt_massConfigureEqLogic" class="btn btn-sm"><i class="fas fa-cogs"></i> {{Configuration}}</a>
			</div>
		</div>

		<ul class="nav nav-tabs reportModeHidden" role="tablist" id="ul_tabBatteryAlert">
			<li id="tab_batteries" role="presentation" class="active"><a data-target="#battery" aria-controls="battery" role="tab" data-toggle="tab"><i class="fas fa-battery-full"></i> <span class="hidden-992">{{Batteries}}</span></a></li>
			<li id="tab_alerts" role="presentation"><a data-target="#alertEqlogic" aria-controls="alertEqlogic" role="tab" data-toggle="tab"><i class="fas fa-exclamation-triangle"></i> <span class="hidden-992">{{Equipements en alerte}}</span></a></li>
			<li id="tab_actionCmd" role="presentation"><a data-target="#actionCmd" aria-controls="actionCmd" role="tab" data-toggle="tab"><i class="fas fa-cogs"></i> <span class="hidden-992">{{Actions définies}}</span></a></li>
			<li id="tab_alertCmd" role="presentation"><a data-target="#alertCmd" aria-controls="alertCmd" role="tab" data-toggle="tab"><i class="fas fa-bell"></i> <span class="hidden-992">{{Alertes définies}}</span></a></li>
			<li id="tab_pushCmd" role="presentation"><a data-target="#pushCmd" aria-controls="pushCmd" role="tab" data-toggle="tab"><i class="fas fa-upload"></i> <span class="hidden-992">{{Push définis}}</span></a></li>
			<li id="tab_deadCmd" role="presentation"><a data-target="#deadCmd" aria-controls="deadCmd" role="tab" data-toggle="tab"><i class="fab fa-snapchat-ghost"></i> <span class="hidden-992">{{Commandes orphelines}}</span></a></li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="battery">
				<div class="input-group" style="margin-bottom:5px;">
					<input class="form-control roundedLeft" placeholder="{{Rechercher | nom | :not(nom}}" id="in_search" />
					<div class="input-group-btn">
						<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i> </a>
					</div>
				</div>
				<div class="batteryListContainer">
					<?php
					foreach ($list as $eqLogic) {
						echo $eqLogic->batteryWidget();
					}
					?>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="alertEqlogic">
				<div class="alertListContainer posEqWidthRef">
					<?php
					$hasAlert = false;
					foreach ($eqLogicsAll as $eqLogic) {
						if ($eqLogic->getAlert() == '') {
							continue;
						}
						$hasAlert = true;
						echo $eqLogic->toHtml('dashboard');
					}
					if (!$hasAlert) {
						echo '<br/><div class="alert alert-success">{{Aucun équipement en Alerte.}}</div>';
					}
					?>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="actionCmd">
				<table class="table table-condensed dataTable" id="table_Action">
					<thead>
						<tr>
							<th>{{Equipement}}</th>
							<th>{{Commande}}</th>
							<th style="width:190px;">{{Type}}</th>
							<th>{{Exécution}}</th>
							<th style="width:80px;">{{Actions}}</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($eqLogicsAll as $eqLogic) {
							$div = '';
							foreach (($eqLogic->getCmd('info')) as $cmd) {
								if (count($cmd->getConfiguration('actionCheckCmd', array())) > 0) {
									$div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>{{Action sur état}}</td>';
									$div .= '<td>{{Si}} ' . $cmd->getConfiguration('jeedomCheckCmdOperator') . ' ' . $cmd->getConfiguration('jeedomCheckCmdTest');
									if ($cmd->getConfiguration('jeedomCheckCmdTime', 0) > 0) {
										$div .= ' {{plus de}} ' . $cmd->getConfiguration('jeedomCheckCmdTime') . ' {{minutes}}';
									}
									$div .=  ' {{alors}} : ';
									$actions = '';
									foreach (($cmd->getConfiguration('actionCheckCmd')) as $actionCmd) {
										$actions .= scenarioExpression::humanAction($actionCmd) . '<br/>';
									}
									$div .= trim($actions);
									$div .= '</td>';
									$div .= '<td>';
									$div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
									$div .= '</td>';
									$div .= '</tr>';
								}
							}
							if ($div != '') echo $div;
							$div = '';
							foreach (($eqLogic->getCmd('action')) as $cmd) {
								if (count($cmd->getConfiguration('jeedomPreExecCmd', array())) > 0) {
									$div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>{{Pré-exécution}}</td><td>';
									$actions = '';
									foreach ($cmd->getConfiguration('jeedomPreExecCmd') as $actionCmd) {
										$actions .= '<div>' . scenarioExpression::humanAction($actionCmd) . '</div>';
									}
									$div .= trim($actions);
									$div .= '</td>';
									$div .= '<td>';
									$div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
									$div .= '</td>';
									$div .= '</tr>';
								}
								if (count($cmd->getConfiguration('jeedomPostExecCmd', array())) > 0) {
									$div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>{{Post-exécution}}</td><td>';
									$actions = '';
									foreach (($cmd->getConfiguration('jeedomPostExecCmd')) as $actionCmd) {
										$actions .= '<div>' . scenarioExpression::humanAction($actionCmd) . '</div>';
									}
									$div .= trim($actions);
									$div .= '</td>';
									$div .= '<td>';
									$div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
									$div .= '</td>';
									$div .= '</tr>';
								}
								if ($cmd->getConfiguration('actionConfirm')){
									$div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>{{Confirmation}}';
									if ($cmd->getConfiguration('actionCodeAccess')) {
										$div .= ' {{avec code}}';
									}
									$div .= '</td><td>';
									$div .= 'Confirmation de l\'action';
									if ($cmd->getConfiguration('actionCodeAccess')) {
										$div .= ' {{avec code}}';
									}
									$div .= '</td>';
									$div .= '<td>';
									$div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
									$div .= '</td>';
									$div .= '</tr>';
								}
								if ($cmd->getConfiguration('actionCodeAccess') && !$cmd->getConfiguration('actionConfirm')) {
								    $div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>{{Confirmation}} {{avec code}}</td><td>';
								    $div .= '{{Code de confirmation de l\'action}}';
								    $div .= '</td>';
								    $div .= '<td>';
								    $div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
								    $div .= '</td>';
								    $div .= '</tr>';
								}
							}
							if ($div != '') echo $div;
						}
						?>
					</tbody>
				</table>
			</div>
			<div role="tabpanel" class="tab-pane" id="pushCmd">
				<table class="table table-condensed dataTable" id="table_Push">
					<thead>
						<tr>
							<th>{{Equipement}}</th>
							<th>{{Commande}}</th>
							<th>{{Type}}</th>
							<th style="width:80px;">{{Action}}</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($eqLogicsAll as $eqLogic) {
							$div = '';
							foreach (($eqLogic->getCmd('info')) as $cmd) {
								$timelineEnable = $cmd->getConfiguration('timeline::enable', false);
								$pushEnable = $cmd->getConfiguration('jeedomPushUrl', '');
								$influxEnable = $cmd->getConfiguration('influx::enable', false);
								if ($timelineEnable || $pushEnable != '' || $influxEnable) {
									$div .= '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td><td>' . $cmd->getName() . ' (' . $cmd->getId() . ')</td><td>';
									if ($timelineEnable) {
										$folder = '';
										if ($cmd->getConfiguration('timeline::folder', '') != '') {
											$folder = ' {{sur le folder}} ' . $cmd->getConfiguration('timeline::folder', '');
										}
										$div .= '<div>- {{Timeline active}}' . $folder . '</div>';
									}
									if ($influxEnable) {
										$nameCmd = $cmd->getName();
										$nameEqLogic = $eqLogic->getName();
										if ($cmd->getConfiguration('influx::namecmd', '') != '') {
											$nameCmd = $cmd->getConfiguration('influx::namecmd');
										}
										if ($cmd->getConfiguration('influx::nameEq', '') != '') {
											$nameEqLogic = $cmd->getConfiguration('influx::nameEq');
										}
										$div .= '<div>- {{Influx actif}} : ' . $nameCmd . '-' . $nameEqLogic . '</div>';
									}
									if ($pushEnable != '') {
										$div .= '<div>- {{Push actif sur}} : ' . $pushEnable . '</div>';
									}
									$div .= '</td>';
									$div .= '<td>';
									$div .= '<a class="btn btn-default btn-xs cmdAction pull-right" data-action="configure" data-cmd_id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a>';
									$div .= '</td>';
									$div .= '</tr>';
								}
							}
							if ($div != '') echo $div;
						}
						?>
					</tbody>
				</table>
			</div>
			<div role="tabpanel" class="tab-pane" id="alertCmd">
				<table class="table table-condensed dataTable" id="table_Alert">
					<thead>
						<tr>
							<th>{{Equipement}}</th>
							<th>{{Alertes}}</th>
							<th style="width:180px;">{{Timeout}}</th>
							<th>{{Seuils batterie}}</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($eqLogicsAll as $eqLogic) {
							$hasSomeAlerts = 0;
							$listCmds = array();
							foreach ($eqLogic->getCmd('info') as $cmd) {
								foreach ($JEEDOM_INTERNAL_CONFIG['alerts'] as $level => $value) {
									if (!$value['check']) {
										continue;
									}
									if ($cmd->getAlert($level . 'if', '') != '') {
										$hasSomeAlerts += 1;
										if (!in_array($cmd, $listCmds)) {
											$listCmds[] = $cmd;
										}
									}
								}
							}
							if ($eqLogic->getConfiguration('battery_warning_threshold', '') != '') {
								$hasSomeAlerts += 1;
							}
							if ($eqLogic->getConfiguration('battery_danger_threshold', '') != '') {
								$hasSomeAlerts += 1;
							}
							if ($eqLogic->getTimeout('')) {
								$hasSomeAlerts += 1;
							}
							if ($hasSomeAlerts != 0) {
								$tr =  '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td>';
								$tr .= '<td>';
								foreach ($listCmds as $cmdalert) {
									foreach ($JEEDOM_INTERNAL_CONFIG['alerts'] as $level => $value) {
										if (!$value['check']) {
											continue;
										}
										if ($cmdalert->getAlert($level . 'if', '') != '') {
											$during = $cmdalert->getAlert($level . 'during', '') == '' ? ' {{effet immédiat}}' : ' {{pendant plus de}} ' . $cmdalert->getAlert($level . 'during') . ' {{minute(s)}}';
											$tr .= ucfirst($level) . ' {{si}} ' . jeedom::toHumanReadable(str_replace('#value#', '<b>' . $cmdalert->getName() . '</b>', $cmdalert->getAlert($level . 'if', ''))) . $during . '</br>';
										}
									}
								}
								$tr .= '</td>';
								$tr .= '<td>';
								if ($eqLogic->getTimeout('') != '') {
									$tr .= $eqLogic->getTimeout('') . ' {{minute(s)}}';
								}
								$tr .= '</td>';
								$tr .= '<td>';
								if ($eqLogic->getConfiguration('battery_danger_threshold', '') != '') {
									$tr .= '<label class="col-xs-6 label label-danger">{{Danger}} ' . $eqLogic->getConfiguration('battery_danger_threshold', '') . ' % </label>';
								}
								if ($eqLogic->getConfiguration('battery_warning_threshold', '') != '') {
									$tr .= '<label class="col-xs-6 label label-warning">{{Warning}} ' . $eqLogic->getConfiguration('battery_warning_threshold', '') . ' % </label>';
								}
								$tr .= '</td></tr>';
								echo $tr;
							}
						}
						?>
					</tbody>
				</table>
			</div>
			<div role="tabpanel" class="tab-pane" id="deadCmd">
				<table class="table table-condensed dataTable" id="table_deadCmd">
					<thead>
						<tr>
							<th style="width:180px;">{{Type}}</th>
							<th>{{Détail}}</th>
							<th>{{Commande}}</th>
							<th style="width:180px;">{{Utilisation}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'eqAnalyse', 'js'); ?>
