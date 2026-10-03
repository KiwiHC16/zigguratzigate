<?php
if (!isConnect('admin')) {
	throw new Exception('Error 401 Unauthorized');
}
$plugin = plugin::byId('zigguratzigate');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span>{{Configuration}}</span>
			</div>
		</div>

		<legend><i class="fas fa-network-wired"></i> {{Mes passerelles}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Passerelle}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<div class="row">
					<div class="col-sm-7">
						<form class="form-horizontal">
							<fieldset>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Nom de la passerelle}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la passerelle}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Objet parent}}</label>
									<div class="col-sm-6">
										<select class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucun}}</option>
											<?php
											foreach (jeeObject::all() as $object) {
												echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label"></label>
									<div class="col-sm-8">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>

								<legend><i class="fas fa-broadcast-tower"></i> {{Radio}}</legend>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Clé}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device" placeholder="/dev/ttyUSB0"/>
									</div>
									<div class="col-sm-12 col-sm-offset-4 help-block">{{Port série de la clé OpenThread RCP, ou tcp://127.0.0.1:9000 pour le simulateur}}</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Vitesse}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="baudrate" placeholder="460800"/>
									</div>
									<label class="col-sm-2 control-label">{{Contrôle de flux}}</label>
									<div class="col-sm-3">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="flowControl">
											<option value="software">{{Logiciel (Sonoff MG24)}}</option>
											<option value="hardware">{{Matériel}}</option>
											<option value="none">{{Aucun}}</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Canal}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="channel" placeholder="25"/>
									</div>
									<label class="col-sm-2 control-label">{{Puissance (dBm)}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="txPower" placeholder="8"/>
									</div>
									<div class="col-sm-12 col-sm-offset-4 help-block">{{Canal utilisé seulement à la création du réseau}}</div>
								</div>

								<legend><i class="fas fa-plug"></i> {{Abeille}}</legend>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Écoute}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="listen" placeholder="127.0.0.1:20108"/>
									</div>
									<div class="col-sm-12 col-sm-offset-4 help-block">{{Dans Abeille : ZiGate en WIFI sur cette adresse}}</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Attente au démarrage (s)}}</label>
									<div class="col-sm-3">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="warmUp" placeholder="35"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label"></label>
									<div class="col-sm-8">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="allowErase"/>{{Autoriser « effacer le PDM » d'Abeille (supprime le réseau)}}</label>
									</div>
								</div>

								<legend><i class="fas fa-file-alt"></i> {{État et observation}}</legend>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Fichier d'état}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="stateFile" placeholder="{{Vide : data/bridge<id>.json du plugin}}"/>
									</div>
									<div class="col-sm-12 col-sm-offset-4 help-block">{{Clé réseau, compteurs, appareils : l'équivalent du PDM. À sauvegarder, ne pas partager.}}</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Capture Wireshark}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="capture" placeholder="{{Vide : pas de capture (ex: 0.0.0.0:17761)}}"/>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="width: 300px;">{{Nom}}</th>
							<th style="width: 130px;">{{Type}}</th>
							<th>{{Options}}</th>
							<th style="width: 150px;"></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'zigguratzigate', 'js', 'zigguratzigate'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
