<?php if(!defined('PLX_ROOT')) exit; ?>
<?php

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# On édite les catégories
if(!empty($_POST)) {
	$plxPlugin->slippry->editSlides($_POST);
	header('Location: plugin.php?p=' . $plugin);
	exit;
}

?>
<form class="inline-form" method="post" id="form_<?= $plugin ?>">
	<?= plxToken::getTokenPostMethod() ?>
	<div class="scrollable-table">
		<table  id="<?= $plugin ?>-table" class="full-width"  data-rows-num='name$="_ordre"'>
			<thead>
				<tr>
					<th class="checkbox"><input type="checkbox" onclick="checkAll(this.form, 'idSlide[]')" /></th>
					<th><?= L_ID ?></th>
					<th><?php $plxPlugin->lang('L_PICTURE') ?></th>
					<th><?php $plxPlugin->lang('L_URL_IMAGE'); ?><br /><?php $plxPlugin->lang('L_ONCLICK_IMAGE'); ?></th>
					<th><?php $plxPlugin->lang('L_DESCRIPTION_IMAGE'); ?></th>
					<th><?php $plxPlugin->lang('L_ACTIVE') ?></th>
					<th><?php $plxPlugin->lang('L_ORDER') ?></th>
				</tr>
			</thead>
			<tbody id="<?= $plugin ?>-table-tbody">
<?php
	$plxMediasRoot = PLX_ROOT . $plxAdmin->aConf['medias'];
	$pos = strlen($plxAdmin->aConf['medias']);

	# Initialisation de l'ordre
	$num = 0;
	# Si on a des infos
	if($plxPlugin->slippry->aSlides) {
		foreach($plxPlugin->slippry->aSlides as $k=>$v) { # Pour chaque catégorie
			$ordre = ++$num;
			$src = $plxMediasRoot . '.thumbs/' . substr($v['url'], $pos);
			if(!file_exists($src)) {
				$src = '';
			}
?>
				<tr>
					<td>
						<input type="checkbox" name="idSlide[]" value="<?= $k ?>" />
						<input type="hidden" name="slideNum[]" value="<?= $k ?>" />
					</td>
					<td><?= $k ?></td>
					<td><img class="thumb" src="<?= $src ?>" alt="<?= substr($v['url'], $pos) ?>" /></td>
					<td>
						<?php plxUtils::printInput($k.'_url', plxUtils::strCheck($v['url']), 'text', '-128'); ?><br />
						<?php plxUtils::printInput($k.'_onclick', plxUtils::strCheck($v['onclick']), 'text', '-128'); ?>
					</td>
					<td><?php plxUtils::printArea($k . '_description', plxUtils::strCheck($v['description']), 60, 3); ?></td>
					<td><?php plxUtils::printSelect($k.'_active', array('1'=>L_YES,'0'=>L_NO), $v['active']); ?></td>
					<td><?php plxUtils::printInput($k.'_ordre', $ordre, 'text', '3-3'); ?></td>
				</tr>
<?php
		}
	}
?>
			</tbody>
		</table>
	</div>
	<p class="in-action-bar" class="center">
		<?php plxUtils::printSelect('selection', array( '' => L_FOR_SELECTION, 'delete' => $plxPlugin->getLang('L_DELETE')), '') ?>
		<input type="submit" name="submit" value="<?= L_OK ?>" />&nbsp;&nbsp;&nbsp;
		<input type="submit" name="update" value="<?php $plxPlugin->lang('L_UPDATE') ?>" />
	</p>
</form>
<?php

/* ============= zoombox ============= */

?>
<div class="modal">
	<input id="modal" type="checkbox" name="modal" tabindex="1">
	<div id="modal__overlay" class="modal__overlay">
		<div id="modal__box" class="modal__box">
			<div id="loader">
				<span class="loader"></span>
			</div>
			<label for="modal">&#10006;</label>
			<img id="zoombox-img" />
		</div>
	</div>
</div>
<script>
	(function(id) {
		'use strict';
		const tbody = document.getElementById(id);
		if(!tbody) {
			console.error(id + ' element missing');
			return;
		}

		const mo = document.getElementById('modal__overlay');
		const toggle = document.getElementById('modal');
		const zoomboxImg = document.getElementById('zoombox-img');
		const loader = document.getElementById('loader');

		tbody.addEventListener('click', function(ev) {
		if((event.target.classList.contains('thumb') && event.target.tagName ==  'IMG')) {
			event.preventDefault();
			const src = event.target.src.replace(/\/\.thumbs?\//, '/');
			const title = src.replace(/.*\/([^\/]*)$/, '$1');
			loader.classList.add('show');
			zoomboxImg.alt = title;
			zoomboxImg.title = title;
			toggle.checked = true;
			const img = new Image;
			img.onload = function(ev) {
				// console.log('image loaded : ' + ev.target.src);
				if(ev.target.width < 250) {
					mo.classList.add('small');
				} else {
					mo.classList.remove('small');
				}
				zoomboxImg.src = src;
				loader.classList.remove('show');
				mo.classList.add('success');
			}
			img.onerror = function(ev) {
				loader.classList.remove('show');
				alert('⛔ Image not loaded from :\n' + src);
				done();
			}
			img.src = src;
			return;
		}

		});

	})('<?= $plugin ?>-table-tbody');
</script>
