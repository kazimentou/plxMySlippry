<?php
/**
 * Plugin plxMySlippry
 * @author	Stephane F
 **/

# https://github.com/booncon/slippry
include 'lib/class.plx.slippry.php';

class plxMySlippry extends plxPlugin {
	const HOOKS = array(
		0 => array(
			'ThemeEndHead',
			'ThemeEndBody',
			'MySlippry',
		),
		1 => array(
			'AdminMediasTop',
			'AdminMediasPrepend',
		),
	);

	const BEGIN_CODE = '<?php # ' . __CLASS__ . 'plugin' . PHP_EOL;
	const END_CODE = PHP_EOL . '?>';

	public $slippry = null; # objet slippry

	public function __construct($default_lang) {

		# appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);

		# droits pour accèder à la page config.php et admin.php du plugin
		$this->setConfigProfil(PROFIL_ADMIN);
		$this->setAdminProfil(PROFIL_ADMIN);

		# déclaration des hooks
		foreach(self::HOOKS[defined('PLX_ADMIN') ? 1 : 0] as $hook) {
			$this->addHook($hook, $hook);
		}

		$this->slippry = new slippry($default_lang);
		$this->slippry->getSlides();
	}

	public function AdminMediasTop() {
		echo self::BEGIN_CODE;
?>
$selectionList = array_merge(
	$selectionList,
	array('MySlippry' => array('slippry_add' => '<?php $this->lang('ADD_TO_SLIDESHOW'); ?>'))
);
<?php
		echo self::END_CODE;
	}

	public function AdminMediasPrepend() {

		if(isset($_POST['selection']) AND $_POST['selection']=='slippry_add' AND isset($_POST['idFile'])) {
			$this->slippry->editSlides($_POST);
			header('Location: medias.php');
			exit;
		}

	}

	public function MySlippry() {
		if(empty($this->slippry->aSlides)) {
			return;
		}

		# On a que des slides actifs côté site
?>
<div class="sy-box">
	<ul id="slippry" class="sy-list">
<?php
		$popup = $this->getParam('openwin');
		foreach($this->slippry->aSlides as $id=>$slide) {
			$src = plxUtils::strCheck($slide['url']);
			$alt = plxUtils::strCheck($slide['description']);
			$onclick = 'target="_blank"';
			if(!empty($slide['onclick'])) {
				$href = $slide['onclick'];
				if($popup) {
					$onclick = 'onclick="window.open(this, \'_blank\'); return false"';
				}
			} else {
				$href = '#slide' . intval($id);
			}
?>
		<li>
			<a <?= $onclick ?> href="<?= $href ?>">
				<img src="<?= $src ?>" alt="<?= $alt ?>" />
			</a>
		</li>
<?php

		}
?>
	</ul>
</div>
<?php
	}

	public function ThemeEndHead() {
?>
<link rel="stylesheet" href="<?= PLX_PLUGINS ?>plxMySlippry/slippry/slippry.css" media="screen" />
<?php
		if(intval($this->getParam('maxwidth')) > 0) {
?>
<style>
	div.sy-box { max-width: <?= $this->getParam('maxwidth') ?>px !important; }
</style>
<?php
		}
	}

	public function ThemeEndBody() {

		if($this->getParam('jquery')) {
?>
<script>
	if (typeof jQuery == "undefined") {
		document.write('<script src="<?= PLX_PLUGINS ?>plxMySlippry/slippry/jquery-3.1.1.min.js"><\/script>');
	}
</script>
<?php
	}
?>
<script src="<?= PLX_PLUGINS ?>plxMySlippry/slippry/slippry.min.js"></script>
<script>
$(function() {
	var slippry = $("#slippry").slippry({
		transition: '<?= $this->getParam('transition') ?>',
		speed: <?= intval($this->getParam('speed')) ?>,
		pager: <?= ($this->getParam('pager') == 1) ? 'true' : 'false' ?>,
		controls: <?= ($this->getParam('controls') == 1) ? 'true' : 'false' ?>,
		adaptiveHeight: <?= ($this->getParam('adaptiveHeight') == 1) ? 'true' : 'false' ?>,
	})
});
</script>
<?php
	}
}
