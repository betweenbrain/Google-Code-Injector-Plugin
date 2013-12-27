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
		$this->db   = JFactory::getDbo();
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

		$buffer  = JResponse::getBody();
		$matches = $this->createMatches();

		$query = 'SELECT *
			FROM ' . $this->db->nameQuote('#__google_codes') . '
			WHERE url IN (\'' . implode('\',\'', $matches) . '\')
			AND published = 1';
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();

		$code = $this->matchRow($matches, $rows);

		$buffer = '<pre style="background:white">' . print_r($code, true) . '<br/>' . $this->root . '</pre>' . $buffer;

		JResponse::setBody($buffer);

		return true;
	}

	/**
	 * Create array of possible URL patterns to match
	 *
	 * @return array
	 */
	private function createMatches()
	{
		$currentUri = $this->uri->toString(array('scheme', 'host', 'path'));
		$segments   = explode('/', str_replace($this->root, '', $currentUri));

		$matches[] = $this->root . '*';
		$match     = null;

		foreach ($segments as $segment)
		{
			$match .= $segment . '/';
			$matches[] = $this->root . $match . '*';
		}

		$matches[] = $currentUri;

		return $matches;
	}

	/**
	 * Matches possible URL patterns with rows returned from database.
	 *
	 * Begins checking with current URL first, working backwards
	 *
	 * @param $matches
	 * @param $rows
	 *
	 * @return mixed
	 */
	private function matchRow($matches, $rows)
	{
		$now            = JFactory::getDate()->toUnix();
		$reverseMatches = array_reverse($matches);
		$tzoffset       = $this->config->getValue('config.offset');

		foreach ($reverseMatches as $reverseMatch)
		{
			foreach ($rows as $row)
			{
				if ($reverseMatch == $row->url)
				{
					$publish_up   = ($row->publish_up === '') ? 0 : JFactory::getDate($row->publish_up, $tzoffset)->toUnix();
					$publish_down = ($row->publish_down === '') ? $now + 1 : JFactory::getDate($row->publish_down, $tzoffset)->toUnix();

					if ($publish_up <= $now && $now < $publish_down)
					{
						return $row->code;
					}
				}
			}
		}
	}

}
