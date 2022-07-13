<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 *
 * @copyright   Copyright (C) Alexander Niklaus. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://www.an-software.net
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\String\StringHelper;

class PlgContentInlineContact extends CMSPlugin implements SubscriberInterface
{
	/**
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $params;

	/**
	 *
	 * @since 2.0.0
	 *
	 * @var bool
	 */
	protected $autoloadLanguage = true;


	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 * @since 2.0.0
	 *
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepare' => 'onContentPrepare',
		];
	}

	/**
	 * @return bool
	 * @since 2.0.0
	 *
	 */
	public function onContentPrepare(Event $event)
	{
		$arguments = $event->getArguments();

		return $this->handleOnContentPrepare($arguments[0], $arguments[1], $arguments[2]);
	}


	/**
	 * @param     $context
	 * @param     $article
	 * @param     $params
	 * @param int $page
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	private function handleOnContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (StringHelper::strpos($article->text, '{inlinecontactlist') !== false)
		{
			$regex         = "/{inlinecontactlist\s(\d*?)\s(\d*?)}/";
			$article->text = preg_replace_callback($regex, array(&$this, '_contactlistReplaceCb'), $article->text);
		}

		if (StringHelper::strpos($article->text, '{/inlinecontact}') !== false)
		{
			$regex         = "/{inlinecontact\s(\d*?)}([\S\s]*?){\/inlinecontact}/";
			$article->text = preg_replace_callback($regex, array(&$this, '_contactReplaceCb'), $article->text);
		}

		return true;
	}


	/**
	 * @param $matches
	 *
	 * @return array|string|string[]
	 * @since 1.0.0
	 *
	 */
	protected function _contactReplaceCb($matches)
	{

		$db = JFactory::getDBO();

		if (!is_numeric($matches[1]))
		{
			return '[invalid contact id]';
		}
		$contact_id   = intval($matches[1]);
		$article_text = $matches[2];


		//Get all default fields of the contact
		$query = 'SELECT * FROM #__contact_details WHERE published = 1 AND id = ' . $contact_id;
		$db->setQuery($query);
		$contact = $db->loadObject();

		if (!$contact)
		{
			return $this->params->get('contactnotfound', JText::_('PLG_INLINECONTACT_CONTACTNOTFOUND'));
		}

		//Get all custom fields of the contact
		$query = 'SELECT #__fields.name, #__fields.label, #__fields.type, #__fields_values.item_id, #__fields_values.value FROM #__fields LEFT JOIN #__fields_values ON #__fields_values.field_id = #__fields.id AND #__fields_values.item_id = ' . $contact_id;
		$db->setQuery($query);

		$customfields = $db->loadObjectList();


		//Default attribute names and types
		$attributes = array('id', 'name', 'alias', 'con_position', 'address', 'suburb', 'state', 'country', 'postcode', 'telephone', 'fax', 'misc', 'image', 'email_to', 'mobile', 'webpage');
		$types      = array('integer', 'text', 'text', 'text', 'textarea', 'text', 'text', 'text', 'text', 'text', 'text', 'editor', 'media', 'email', 'text', 'URL');

		$find    = array();
		$replace = array();

		foreach ($attributes as $key => $attribute)
		{
			//TODO: label
			$this->buildReplaceArray($find, $replace, $attribute, JText::_('PLG_INLINECONTACT_DEFLABEL_' . strtoupper($attribute)), $contact->$attribute, $types[$key]);
		}

		foreach ($customfields as $customfield)
		{
			$this->buildReplaceArray($find, $replace, $customfield->name, $customfield->label, $customfield->value, $customfield->type);
		}

		return str_replace($find, $replace, $article_text);
	}


	/**
	 *
	 * @param        $find
	 * @param        $replace
	 * @param        $name
	 * @param        $label
	 * @param        $value
	 * @param string $type
	 *
	 * @since 1.0.0
	 *
	 */
	private function buildReplaceArray(&$find, &$replace, $name, $label, $value, $type = '')
	{
		//Handle different field types
		if ($type == 'media')
		{
			if (!empty($value))
			{
				$value = '<img src="' . $value . '" title="' . $label . '" alt="' . $label . '" />';
			}
		}

		//value only
		$find[]    = "[$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' : $value;

		//label only
		$find[]    = "[l_$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' : '<span class="plginlcon-label">' . $label . '</span>';

		//label and value
		$find[]    = "[lv_$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' : '<span class="plginlcon-label">' . $label . '</span>: ' . $value;
	}


	/**
	 *
	 * @param $matches
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	protected function _contactlistReplaceCb($matches)
	{

		if (!array_key_exists(1, $matches) || !is_numeric($matches[1]))
		{
			return '[invalid category id]';
		}
		$category_id = intval( $matches[1] );

		if (!array_key_exists(2, $matches) || !is_numeric($matches[2]))
		{
			return '[invalid category id]';
		}
		$template_id = $matches[2];
		$templates   = $this->params->get('templates');

		if (!is_object($templates) || !property_exists($templates, $template_id))
		{
			return '[invalid template id]';
		}

		$db    = JFactory::getDBO();
		$query = 'SELECT * FROM #__contact_details WHERE published = 1 AND catid =' . $category_id . ' ORDER BY ordering';
		$db->setQuery($query);
		$contacts = $db->loadObjectList();

		if (!$contacts)
		{
			return $this->params->get('nocontact', JText::_('PLG_INLINECONTACT_NOCONTACT'));
		}

		$output = $templates->$template_id->b;
		foreach ($contacts as $contact)
		{
			$matches = array('', $contact->id, $templates->$template_id->t);
			$output  .= $this->_contactReplaceCb($matches);
		}
		$output .= $templates->$template_id->a;

		return $output;
	}
}
