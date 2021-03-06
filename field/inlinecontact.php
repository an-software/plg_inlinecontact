<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 *
 * @copyright   Copyright (C) Alexander Niklaus. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://www.an-software.net
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');


class JFormFieldInlineContact extends JFormField
{

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected $type = 'inlinecontact';

	/**
	 * @return string
	 * @since 1.0.0
	 */
	public function getInput()
	{


		$templatecount = $this->form->getValue('templatecount', 'params');
		$templates     = $this->form->getValue('templates', 'params');

		$output = '<div class="row">';

		for ($i = 1; $i <= $templatecount; $i++)
		{
			$output .= '<div class="col-3">';
			if (is_array($templates))
			{
				$exists = array_key_exists($i, $templates);
			}
			else
			{
				$exists = false;
			}

			$output .= '<strong>Template ' . $i . ':</strong><br>' . JText::_('PLG_INLINECONTACT_BEFORE_LABEL') . '<br><textarea class="form-control" id="jform_params_templates' . $i . '" type="text" name="jform[params][templates][' . $i . '][b]">' . (($exists) ? $templates[$i]['b'] : '') . '</textarea>';
			$output .= '<br>' . JText::_('PLG_INLINECONTACT_TEMPLATE_LABEL') . '<br><textarea class="form-control" id="jform_params_templates' . $i . '" type="text" name="jform[params][templates][' . $i . '][t]">' . (($exists) ? $templates[$i]['t'] : '') . '</textarea>';
			$output .= '<br>' . JText::_('PLG_INLINECONTACT_AFTER_LABEL') . '<br><textarea class="form-control" id="jform_params_templates' . $i . '" type="text" name="jform[params][templates][' . $i . '][a]">' . (($exists) ? $templates[$i]['a'] : '') . '</textarea>';

			$output .= '</div>';
			if ($i % 4 == 0)
			{
				$output .= '</div><hr><div class="row-fluid">';
			}
		}
		$output .= '</div>';

		return $output;
	}
}
