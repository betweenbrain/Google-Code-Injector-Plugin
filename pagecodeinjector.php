<?php defined('_JEXEC') or die;

/**
 * File       pagecodeinjector.php
 * Created    12/26/13 2:19 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.plugin.plugin');

class plgSystemPagecodeinjector extends JPlugin
{

	function plgSystemPagecodeinjector(&$subject, $config)
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
		$query   = ' SELECT pages.url as url, pages.published as published, codes.publish_up as publish_up, codes.publish_down as publish_down, codes.code as code'
			. ' FROM #__page_code_pages as pages'
			. ' LEFT JOIN #__page_code_codes as codes'
			. ' ON codes.id = pages.codeId'
			. ' WHERE url IN (\'' . implode('\',\'', $matches) . '\')'
			. ' AND pages.published = 1';
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();
		$code = $this->matchRow($matches, $rows);

		if ($code)
		{
			$replacement = '<script>' . $code . '</script>' . "\n" . '</body>';
			$buffer      = str_replace('</body>', $replacement, $buffer);
			JResponse::setBody($buffer);

			return true;
		}
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
		$config         = JFactory::getConfig();
		$now            = JFactory::getDate()->toUnix();
		$reverseMatches = array_reverse($matches);
		$tzoffset       = $config->getValue('config.offset');

		foreach ($reverseMatches as $reverseMatch)
		{
			foreach ($rows as $row)
			{
				$publish_up   = ($row->publish_up === '0000-00-00 00:00:00') ? 0 : JFactory::getDate($row->publish_up, $tzoffset)->toUnix();
				$publish_down = ($row->publish_down === '0000-00-00 00:00:00') ? $now + 1 : JFactory::getDate($row->publish_down, $tzoffset)->toUnix();

				if ($publish_up <= $now && $now < $publish_down)
				{
					if ($reverseMatch == $row->url)
					{
						return $row->code;
					}
					elseif (strpos($row->url, $reverseMatch))
					{
						return $row->code;
					}
				}
			}
		}
	}

}
