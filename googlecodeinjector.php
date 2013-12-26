<?php defined('_JEXEC') or die;

/**
 * File       googlecodeinjector.php
 * Created    12/26/13 2:19 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.plugin.plugin');

class plgSystemGooglecodeinjector extends JPlugin
{

	function plgSystemGooglecodeinjector(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->app  = JFactory::getApplication();
		$this->doc  = JFactory::getDocument();
		$this->root = JURI::root();
		$this->uri  = JURI::getInstance();
	}

	function onAfterRender()
	{

		if ($this->app->isAdmin())
		{
			return true;
		}

		$buffer     = JResponse::getBody();
		$currentUri = $this->uri->toString(array('scheme', 'host', 'path'));
		$segments   = explode('/', str_replace($this->root, '', $currentUri));
		$matches[]  = $this->root . '*';
		$match      = null;

		foreach ($segments as $segment)
		{
			$match .= $segment . '/';
			$matches[] = $this->root . $match . '*';
		}

		$matches[] = $currentUri;

		$buffer = '<pre style="background:white">' . print_r($matches, true) . '<br/>' . $this->root . '</pre>' . $buffer;

		JResponse::setBody($buffer);

		return true;
	}
}
