<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 *
 * @copyright   (C) Alexander Niklaus. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://an-software.net
 */

namespace Joomla\Plugin\Content\InlineContact\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Number Field class for the InlineContact Plugin.
 *
 * @since  3.3.0
 */
class InlinecontactnumberField extends FormField
{

	/**
	 * @var string
	 * @since 3.0.0
	 */
	protected $type = 'inlinecontactnumber';


	/**
	 * @return string
	 * @since 3.0.0
	 */
	public function getInput(): string
	{
		$number = Text::_('PLG_INLINECONTACT_NUMBER_EMPTY');
		$matches = [];
		if (preg_match('/(\d+)/', $this->id, $matches)) {
			$number = intval($matches[1]) + 1;
		}
		return '<input type="text" readonly class="form-control" value="' . $number . '">';
	}

}
