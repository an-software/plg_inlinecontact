<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Content.inlinecontact
 *
 * @copyright   (C) Alexander Niklaus. All rights reserved.
 * @license         GNU General Public License version 2 or later
 * @link            https://an-software.net
 */

namespace Joomla\Plugin\Content\InlineContact\Model;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\Contact\Site\Model\CategoryModel;
use Joomla\Plugin\Content\InlineContact\Trait\InlineContactTagFilter;
use function defined;

/**
 * Single item model for a contact to use extra filters
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 * @since       3.3.0
 */
class InlineContentCategoryModel extends CategoryModel
{
	use InlineContactTagFilter;
}
