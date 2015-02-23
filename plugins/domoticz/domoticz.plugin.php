<?php

require_once('DomoticzCmd.class.php');
require_once('DomoticzApi.class.php');
require_once('DomoticzPlugin.class.php');

/*
@name domoticz
@author S.Guernion <email@gmail.com>
@author M.OU <nospam@free.fr>
@link https://github.com/sguernion/myPI/tree/master/yana-server/plugins/domoticz
@licence CC by nc sa
@version 1.1.1-snapshot
@description Permet la commande vocale des interrupteurs domoticz
*/


function domoticz_vocal_command(&$response,$actionUrl){
	global $conf;
	$vocal_sep = ",";
	$domoticz = new DomoticzCmd();
	$domoticzCmd = $domoticz->loadAll(array('vocal'=>true));

	if (is_array($domoticzCmd)){
		foreach($domoticzCmd as $row){
			if($row->getCmdOn() != "" ){
				$phrasesOn = explode($vocal_sep,$row->getCmdOn());
				foreach($phrasesOn as $cmdOn){
					$response['commands'][] = array(
					'command'=>$conf->get('VOCAL_ENTITY_NAME').", ".$cmdOn,
					'url'=> $actionUrl.'?action=domoticz_action_'.$row->getCategorie().'&state=On'.'&idx='.$row->getIdx().'&name='.urlencode ($row->getDevice()),
					'confidence'=>$row->getConfidence(),
					'categorie'=>'Domoticz'
					);	
				
				}
			}else{
				
			}
		
			
			if($row->getCmdOff() != null){
				$phrasesOff = explode($vocal_sep,$row->getCmdOff());
				foreach($phrasesOff as $cmdOff){
				$response['commands'][] = array(
					'command'=>$conf->get('VOCAL_ENTITY_NAME').", ".$cmdOff,
					'url'=> $actionUrl.'?action=domoticz_action_'.$row->getCategorie().'&state=Off'.'&idx='.$row->getIdx().'&name='.urlencode ($row->getDevice()),
					'confidence'=>$row->getConfidence(),
					'categorie'=>'Domoticz'
					);
				}
			}
		}
	}
}


function domoticz_action(){
	global $_,$conf,$myUser;
	
	$phrases['switchlight']['Off'] = 'je viens d eteindre {NAME}';
	$phrases['switchlight']['On'] = 'je viens d allumer {NAME}';
	$phrases['switchscene']['On'] = 'mode {NAME} actif';
	$phrases['switchscene']['Off'] = 'mode {NAME} inactif';
	$phrases['temperature'] = 'il fait {VALUE}';
	$phrases['variable'] = 'la valeur est {VALUE}';
	$phrases['variable'] = 'la valeur est {VALUE}';
	
	
	$domoticzPlugin = new DomoticzPlugin($conf,$phrases);
	$domoticzPlugin->actions($_,$myUser);
}

function domoticz_plugin_preference_menu(){
	global $_;
	echo '<li '.(@$_['block']=='domoticz'?'class="active"':'').'><a  href="setting.php?section=preference&block=domoticz"><i class="fa fa-angle-right"></i> Serveur Domoticz</a></li>';
}


function domoticz_plugin_preference_page(){
	global $myUser,$_,$conf;
	if((isset($_['section']) && $_['section']=='preference' && @$_['block']=='domoticz' )  ){
		if($myUser!=false){
	
	?>

		<div class="span9 userBloc">
			<form class="form-inline" action="action.php?action=domoticz_plugin_setting" method="POST">
			<legend>Informations sur le serveur Domoticz</legend>
			
			    <label>Ip du serveur Domoticz</label><br/>
			    <input type="text" class="input-xlarge" name="ip" value="<?php echo $conf->get('ip','domoticz');?>" placeholder="localhost">	
			    <br/><br/><label>Port du serveur Domoticz</label><br/>
			    <input type="text" class="input-large" name="port" value="<?php echo $conf->get('port','domoticz');?>" placeholder="8080">					
			    <br/><br/><label>Nom de l'utilisateur</label><br/>
			    <input type="text" class="input-large" name="user" value="<?php echo $conf->get('user','domoticz');?>" placeholder="toto">					
			    <br/><br/><label>Mot de passe</label><br/>
			    <input type="password" class="input-large" name="pswd" value="<?php echo $conf->get('pswd','domoticz');?>" placeholder="********">					
			    <br/><br/><button type="submit" class="btn">Sauvegarder</button>
	    </form>
		<?php 
			if(!function_exists('curl_version')){
				echo '<strong>Important: </strong>Pour profiter de ce plugin il vous faut installer CURL  <code>sudo apt-get install php5-curl</code><BR><BR>';
				}
			?>
		</div>

<?php }else{ 	header('location:index.php?connexion=ko');

		}
	}
}

function domoticz_plugin_menu(){
	global $_,$conf;
	
	$domoticzApi = new DomoticzApi($conf);
	echo '<li '.((isset($_['section']) && $_['section']=='domoticz') ?'class="active"':'').'><a href="setting.php?section=domoticz"><img src="'.$domoticzApi->getUrl().'/images/logo.png'.'" width="16px" height="16px" alt=">" /> Domoticz</a></li>';
}

function domoticz_widget_plugin_menu(&$widgets){
	$widgets[] = array(
		    'uid'      => 'domoticz_widget',
		    'icon'     => 'fa fa-sun-o',
		    'label'    => 'Domoticz Sunrise',
		    'background' => '#50597B', 
		    'color' => '#fffffff',
		    'onLoad'   => 'action.php?action=domoticz_widget_load&bloc=sunrise',
			'onDelete'   => 'action.php?action=domoticz_widget_delete&bloc=sunrise'
		);
	$widgets[] = array(
		    'uid'      => 'domoticz_widget_devices',
		    'icon'     => 'fa fa-play-circle-o',
		    'label'    => 'Domoticz Devices',
		    'background' => '#50597B', 
		    'color' => '#fffffff',
		    'onLoad'   => 'action.php?action=domoticz_widget_load&bloc=devices',
			'onEdit'   => 'action.php?action=domoticz_widget_edit&bloc=devices',
			'onDelete'   => 'action.php?action=domoticz_widget_delete&bloc=devices'
		);
}

function domoticz_plugin_page(){
	global $myUser,$_,$conf;
	$vocal_sep = ',';
	if((isset($_['section']) && $_['section']=='domoticz')  ){
		if($myUser!=false){
		
		$domoticzApi = new DomoticzApi($conf);
			
	?>
		<div class="span9 userBloc">
		<h1><img src="<?php echo $domoticzApi->getUrl().'/images/logo.png'; ?>" width="48px" height="48px" /> Domoticz</h1>
		<p>Votre syst&egrave;me domotique	</p>
		<ul class="nav nav-tabs">
			<li <?php echo (!isset($_['block']) || $_['block']=='cmd'?'class="active"':'')?> > <a href="setting.php?section=domoticz&amp;block=cmd"><i class="fa fa-angle-right"></i> Commandes Vocales</a></li>
			<li <?php echo (isset($_['block']) && $_['block']=='new'?'class="active"':'')?> > <a href="setting.php?section=domoticz&amp;block=new"><i class="fa fa-angle-right"></i> Nouvelles Commandes</a></li>
			<li <?php echo (isset($_['block']) && $_['block']=='edit'?'class="active"':'class="disabled"')?> > <a href="setting.php?section=domoticz&amp;block=edit"><i class="fa fa-angle-right"></i> Modification</a></li>

		</ul>
		 <?php 
		 if((isset($_['section']) && $_['section']=='domoticz' && (@$_['block']=='cmd'  || @$_['block']==''))  ){
				if($myUser!=false){
		 
				$DomoticzManager = new DomoticzCmd();
				$DomoticzCmds = $DomoticzManager->populate();
				
					?>
		
			<table class="table table-striped table-bordered table-hover">
					<thead>
						<tr>
							<!--th>Type</th -->
							<th>Device</th>
							
							<th>Commandes vocale On</th>
							<th>Commandes vocale Off</th>
							<th>Confidence</th>
							<th>Actions</th>
						</tr>
						
					</thead>
					
					<?php 	
							
							if (is_array($DomoticzCmds)){
							foreach($DomoticzCmds as $row){
									?>
									<tr>
										<!--td><?php //echo $row->getType(); 
										?></td -->
										<td><?php echo $row->getDevice(); ?></td>
										<td><?php echo ($row->getCmdOn()!=""?$conf->get('VOCAL_ENTITY_NAME').", ":'-').$row->getCmdOn();?></td>
										<td><?php echo ($row->getCmdOff()!=""?$conf->get('VOCAL_ENTITY_NAME').", ":'-').$row->getCmdOff();?></td>
										<td><?php echo $row->getConfidence(); ?></td>
										<td><a class="btn" href="action.php?action=domoticz_enable&idx=<?php echo $row->getIdx(); ?>" >
										<?php 
										if ($row->getVocal()) 
											{ echo '<i class="fa fa-microphone fa-lg" style="color:#84C400"  title="D&eacute;sactive l\�&eacute;coute de cette commande"></i>';} 
										else
											{ echo '<i class="fa fa-microphone-slash fa-lg" style="color:#C1004F" title="Active l\�&eacute;coute de cette commande"></i>';}
										?>
										</a>
										<a class="btn" href="setting.php?section=domoticz&amp;block=edit&idx=<?php echo $row->getIdx(); ?>"><i class="fa fa-pencil-square-o fa-lg"></i></a>
										<a class="btn" href="action.php?action=domoticz_delete&idx=<?php echo $row->getIdx(); ?>"><i class="fa fa-trash-o fa-lg"  style="color:#C1004F"></i></a></td>
									</tr>
									
									<?php
							}
							} ?>
					
			</table>
		 <?php
		 }}
		 
		  if((isset($_['section']) && $_['section']=='domoticz' && @$_['block']=='edit' )  ){
				if($myUser!=false && isset($_['idx'])){
					$domoticz = new DomoticzCmd();
					$domoticz = $domoticz->load(array('idx'=>$_['idx']));
					?>
					<div class="span9 userBloc">
						<form class="form-inline" action="action.php?action=domoticz_edit" method="POST">
						<legend>Modification de la Commande Vocale</legend>
							<input type="hidden"  name="idx" value="<?php echo $domoticz->getIdx();?>" >
							<label>Devices : </label><?php echo $domoticz->getDevice();?>	
							<br/><label>Commande On (s&eacutepar&eacutees par un <?php echo $vocal_sep;?> ) : </label><br/>
							<?php echo $conf->get('VOCAL_ENTITY_NAME').", " ?> <input type="text" class="input-large" name="cmdOn" value="<?php echo $domoticz->getCmdOn();?>" >	
							<?php
								foreach(explode($vocal_sep,urldecode($domoticz->getCmdOn())) as $cmdOn){
							?>
								<p><?php echo $cmdOn; ?> <a href="">x</a> </p>
							<?php } ?>		
							<?php if($domoticz->getType() != 'Scene' && $domoticz->getCategorie() != 'mesure' && $domoticz->getCategorie() != 'utility' && $domoticz->getCategorie() != 'variable')
							{ ?>							
							<br/><label>Commande Off (s&eacutepar&eacutees par un <?php echo $vocal_sep;?> ) : </label><br/>
							<?php echo $conf->get('VOCAL_ENTITY_NAME').", " ?> <input type="text" class="input-large" name="cmdOff" value="<?php echo $domoticz->getCmdOff();?>" >		
							<?php
								foreach(explode($vocal_sep,urldecode($domoticz->getCmdOff())) as $cmdOff){
							?>
								<p><?php echo $cmdOff; ?> <a href="">x</a> </p>
							<?php }} ?>	
							
							<br/><label>R&eacute;ponses On (s&eacutepar&eacutees par un <?php echo $vocal_sep;?>) : </label><br/>
							<input type="text" class="input-large" name="reponsesOn" value="<?php echo $domoticz->getReponsesOn();?>" >	{NAME} est remplac&eacute par le nom du device, {VALUE} est remplac&eacute par la valeur du device				
							<?php
								foreach(explode($vocal_sep,urldecode($domoticz->getReponsesOn())) as $reponse){
							?>
								<p><?php echo $reponse; ?> <a href="">x</a> </p>
							<?php } ?>
							<?php if($domoticz->getType() != 'Scene' && $domoticz->getCategorie() != 'mesure' && $domoticz->getCategorie() != 'utility' && $domoticz->getCategorie() != 'variable')
							{ ?>
							<br/><label>R&eacuteponses Off (s&eacutepar&eacutees par un <?php echo $vocal_sep;?>) : </label><br/>
							<input type="text" class="input-large" name="reponsesOff" value="<?php echo $domoticz->getReponsesOff();?>" >
							<?php
								foreach(explode($vocal_sep,urldecode($domoticz->getReponsesOff())) as $reponse){
							?>
								<p><?php echo $reponse; ?> <a href="">x</a> </p>
							<?php }} ?>
							
							<br/><label>Confidence :</label><br/>
							<select name="confidence" id="confidence">
                                <?php for($confidence=1; $confidence<=9; $confidence++){ ?>        
									
                                    <option value="0.<?php echo $confidence ?>" <?php echo ('0.'.$confidence == $domoticz->getConfidence())?"selected":""; ?>>0.<?php echo $confidence; ?></option>
                                <?php } ?>
                            </select>   							
							<br/><br/><button type="submit" class="btn">Sauvegarder</button>
						</form>
					</div>
					<?php
				}
		  }
		 
		 
		 if((isset($_['section']) && $_['section']=='domoticz' && @$_['block']=='new' )  ){
				if($myUser!=false){
					?>
		
			<table class="table table-striped table-bordered table-hover">
					<thead>
						<tr>
							<th>Type</th>
							<th>Device</th>
							
							<th>Commandes vocale On</th>
							<th>Commandes vocale Off</th>
							<th>Confidence</th>
							<th>Actions</th>
						</tr>
						
					</thead>
					
					<?php 	
							
							$devices = $domoticzApi->getDevices();
							
							
					
							if (is_array($devices)){
							foreach($devices as $row2){
							
								$domoticz = new DomoticzCmd();
								$domoticzCmd = $domoticz->loadAll(array('idx'=>$row2['idx']));
								if($domoticzCmd == null){
									?>
									<tr>
										<td><?php echo $row2['Type']; ?></td>
										<td><?php echo $row2['Name']; ?></td>
										<td><?php 
										if($row2['Type'] == 'Scene')
										{
											echo $conf->get('VOCAL_ENTITY_NAME').', mode '.$row2['Name'];
										}else if($row2['categorie'] == 'mesure'){	
											echo $conf->get('VOCAL_ENTITY_NAME').',  '.$row2['Name'];
										}else if($row2['categorie'] == 'variable' || $row2['categorie'] == 'utility'){	
											echo $conf->get('VOCAL_ENTITY_NAME').',  valeur '.$row2['Name'];
										}else {
											echo $conf->get('VOCAL_ENTITY_NAME').', allume '.$row2['Name'];
										}
										
										?></td>
										<td><?php
										if($row2['Type'] != 'Scene' && $row2['categorie'] != 'mesure' && $row2['categorie'] != 'utility' && $row2['categorie'] != 'variable')
										{
											echo $conf->get('VOCAL_ENTITY_NAME').', eteint '.$row2['Name'];
										}
										?></td>
										<td>0.8</td>
										<td><a class="btn" href="action.php?action=domoticz_add&idx=<?php echo $row2['idx']; ?>" title="Active ou d�sactive l��coute de cette commande">
										<i class="fa fa-plus fa-lg"></i>
										</a></td>
									</tr>
									
									<?php
								}
							}
							} ?>
					
			</table>
		<br/>
		<?php }} ?>
	  
		</div>

<?php }else{ 	header('location:index.php?connexion=ko');

		}
	}
}


Plugin::addJs('/js/main.js',true);
Plugin::addHook("setting_menu", "domoticz_plugin_menu");  
Plugin::addHook("setting_bloc", "domoticz_plugin_page"); 
Plugin::addHook("preference_menu", "domoticz_plugin_preference_menu"); 
Plugin::addHook("preference_content", "domoticz_plugin_preference_page"); 
Plugin::addHook("widgets", "domoticz_widget_plugin_menu");

Plugin::addHook("action_post_case", "domoticz_action");    
Plugin::addHook("vocal_command", "domoticz_vocal_command");
?>
