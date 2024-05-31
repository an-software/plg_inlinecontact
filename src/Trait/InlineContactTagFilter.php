<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Content.inlinecontact
 *
 * @copyright   (C) Alexander Niklaus. All rights reserved.
 * @license         GNU General Public License version 2 or later
 * @link            https://an-software.net
 */

namespace Joomla\Plugin\Content\InlineContact\Trait;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use function count;
use function is_array;

/**
 * Trait for adding additional tag filter to contact list
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 * @since       3.3.0
 */
trait InlineContactTagFilter
{

	/**
	 * @return DatabaseQuery
	 * @since 3.3.0
	 *
	 */
	protected function getListQuery(): DatabaseQuery
	{
		$db    = $this->getDatabase();
		$query = parent::getListQuery();

		// Filter by a single or group of tags.
		$tag = $this->getState('filter.tag');

		// Run simplified query when filtering by one tag.
		if (is_array($tag) && count($tag) === 1)
		{
			$tag = $tag[0];
		}

		if (($tag && is_array($tag)) || $tag = (int) $tag)
		{
			// base model changed in joomla 5.1, now a category is required without any option to disable that
			// workaround: remove it manually from where			
			$originalWhere = $query->where->getElements();

			$customWhere = array_filter($originalWhere, function ($item) {
				return !str_contains($item, 'catid');
			});
			$query->clear('where')->where($customWhere);
			$query->unbind(':acatid');
		}


		if ($tag && is_array($tag))
		{
			$tag = ArrayHelper::toInteger($tag);

			$subQuery = $db->getQuery(true)
				->select('DISTINCT ' . $db->quoteName('content_item_id'))
				->from($db->quoteName('#__contentitem_tag_map'))
				->where(
					[
						$db->quoteName('tag_id') . ' IN (' . implode(',', $query->bindArray($tag)) . ')',
						$db->quoteName('type_alias') . ' = ' . $db->quote('com_contact.contact'),
					]
				);

			$query->join(
				'INNER',
				'(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
				$db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
			);
		}
		elseif ($tag = (int) $tag)
		{
			$query->join(
				'INNER',
				$db->quoteName('#__contentitem_tag_map', 'tagmap'),
				$db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
			)
				->where(
					[
						$db->quoteName('tagmap.tag_id') . ' = :tag',
						$db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_contact.contact'),
					]
				)
				->bind(':tag', $tag, ParameterType::INTEGER);
		}

		return $query;
	}

}
