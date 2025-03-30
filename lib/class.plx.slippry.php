<?php

class slippry {
	const XML_HEADER = '<?xml version="1.0" encoding="' . PLX_CHARSET . '"?>' . PHP_EOL;
	public $config = null; # fichier des données
	public $aSlides = array(); # tableau des slides

	public function __construct($default_lang) {
		if(defined('PLX_MYMULTILINGUE')) {
			$lang = plxMyMultiLingue::_Lang();
			if(!empty($lang) AND defined('PLX_ADMIN')) {
				$default_lang = $lang;
			}
		}
		$this->config = PLX_ROOT.PLX_CONFIG_PATH . 'plugins/slippry.config.' . $default_lang . '.xml';
	}

	/**
	 * Méthode qui parse le fichier des slides et alimente le tableau aSlides
	 *
	 * @param	filename	emplacement du fichier XML des slides
	 * @return	null
	 * @author	Stéphane F
	 **/
	public function getSlides() {

		if(!is_file($this->config)) return;

		# Mise en place du parseur XML
		$data = implode('',file($this->config));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		if(isset($iTags['slide']) AND isset($iTags['url'])) {
			$nb = sizeof($iTags['url']);
			$size=ceil(sizeof($iTags['slide'])/$nb);
			for($i=0;$i<$nb;$i++) {
				$attributes = $values[$iTags['slide'][$i*$size]]['attributes'];
				$number = $attributes['number'];
				$this->aSlides[$number] = array(
					# Onclick
					'onclick' => plxUtils::getValue($values[$iTags['onclick'][$i]]['value']),
					# Recuperation de la description
					'description' => plxUtils::getValue($values[$iTags['description'][$i]]['value']),
					# Recuperation de lien de l'image
					'url' => plxUtils::getValue($values[$iTags['url'][$i]]['value']),
					# Récuperation état activation de la catégorie dans le menu
					'active' => isset($attributes['active']) ? $attributes['active'] : '1',
				);
			}
		}
	}

	/**
	 *  Méthode qui retourne le prochain id d'un slide
	 *
	 * @return	string		id d'un nouveau slide sous la forme 001
	 * @author	Stephane F.
	 **/
	 public function nextIdSlide() {
		if(empty($this->aSlides)) {
			return '001';
		}

		$ids = array_keys($this->aSlides);
		rsort($ids);
		return str_pad(intval($ids[0]) + 1, 3, '0', STR_PAD_LEFT);
	}

	/**
	 * Méthode qui édite le fichier XML des slides selon le tableau $content
	 *
	 * @param	content	tableau multidimensionnel des catégories
	 * @param	action	permet de forcer la mise àjour du fichier
	 * @return	string
	 * @author	Stephane F
	 **/
	public function editSlides($content, $action=false) {

		# suppression
		if(isset($content['selection']) AND $content['selection']=='delete' AND !empty($content['idSlide'])) {
			foreach($content['idSlide'] as $slide_id) {
				# suppression du parametre
				unset($this->aSlides[$slide_id]);
			}
			$action = true;
		}

		# ajout d'un nouveau slide à partir du gestionnaire de médias
		if(isset($content['selection']) AND !empty($content['selection']) AND isset($content['idFile'])) {
			if($content['folder']=='.') {
				$content['folder']='';
			}
			$plxAdmin = plxAdmin::getInstance();
			$folder = $plxAdmin->aConf['medias'] . $content['folder'];
			foreach($content['idFile'] as $filename) {
				$url = $folder . $filename;
				$mimetype = mime_content_type(PLX_ROOT . $url);
				if(empty($mimetype) or !preg_match('#^image\/#', $mimetype)) {
					continue;
				}

				$slide_id = $this->nextIdSlide();
				$this->aSlides[$slide_id] = array(
					'url'			=> $url,
					'description'	=> '',
					'ordre'			=> intval($slide_id),
					'active'		=> 1,
					'onclick'		=> '',
				);
			}
			$action = true;
		}

		# mise à jour de la liste
		elseif(!empty($content['update'])) {
			foreach($content['slideNum'] as $slide_id) {
				if(!empty($content[$slide_id.'_url'])) {
					$this->aSlides[$slide_id] = array(
						'url'			=> trim($content[$slide_id.'_url']),
						'description'	=> trim($content[$slide_id.'_description']),
						'ordre'			=> intval($content[$slide_id.'_ordre']),
						'active'		=> intval($content[$slide_id.'_active']),
						'onclick'		=> trim($content[$slide_id.'_onclick']),
					);
				}
			$action = true;
			}
			# On va trier les clés selon l'ordre choisi
			if(sizeof($this->aSlides)>1) {
				uasort(
					$this->aSlides,
					function($a, $b) {
						return ($a['ordre'] > $b['ordre']);
					}
				);
			}
		}

		# Sauvegarde dans le fichier XML
		if($action) {
			ob_start();
?>
<document>
<?php
			foreach($this->aSlides as $slide_id => $slide) {
?>
	<slide number="<?= $slide_id ?>" active="<?= $slide['active'] ?>">
		<url><![CDATA[<?= plxUtils::cdataCheck($slide['url']) ?>]]></url>
		<onclick><![CDATA[<?= plxUtils::cdataCheck($slide['onclick']) ?>]]></onclick>
		<description><![CDATA[<?= plxUtils::cdataCheck($slide['description']) ?>]]></description>
	</slide>
<?php
			}
?>
</document>
<?php
			# On écrit le fichier
			if(plxUtils::write(self::XML_HEADER . ob_get_clean(), $this->config))
				return plxMsg::Info(L_SAVE_SUCCESSFUL);
			else {
				# Echec. On remonte la sauvegarde
				$this->aSlides = $save;
				return plxMsg::Error(L_SAVE_ERR.' '.$this->config);
			}
		}
	}

}
