<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 *
 * @copyright   (C) Alexander Niklaus. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://an-software.net
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');


class JFormFieldInlineContactNumber extends JFormField
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
