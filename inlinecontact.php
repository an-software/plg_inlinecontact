<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.inlinecontact
 *
 * @copyright   (C) Alexander Niklaus. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://an-software.net
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Contact\Site\Model\CategoryModel;
use Joomla\Component\Contact\Site\Model\ContactModel;
use Joomla\Component\Contact\Site\Model\FeaturedModel;
use Joomla\Database\DatabaseDriver;
use Joomla\String\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class PlgContentInlineContact extends CMSPlugin //implements SubscriberInterface
{
	/**
	 * @var    DatabaseDriver|null
	 *
	 * @since  2.1
	 */
	protected ?DatabaseDriver $db = null;

	/**
	 * @var CMSApplicationInterface|null
	 *
	 * @since 3.0.0
	 */
	protected ?CMSApplicationInterface $app = null;


	/**
	 * @var bool
	 *
	 * @since 2.0.0
	 */
	protected $autoloadLanguage = true;


	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 * @since 2.0.0
	 *
	 */
	/*public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepare' => 'onContentPrepare',
		];
	}*/

	/**
	 * @return bool
	 * @since 2.0.0
	 *
	 */
	/*public function onContentPrepare(Event $event)
	{
		$arguments = $event->getArguments();

		return $this->handleOnContentPrepare($arguments[0], $arguments[1], $arguments[2]);
	}*/


	/**
	 * Plugin that retrieves contact information for contact
	 *
	 * @param string    $context The context of the content being passed to the plugin.
	 * @param mixed    &$row     An object with a "text" property
	 * @param mixed     $params  Additional parameters. See {@see PlgContentContent()}.
	 * @param int|null  $page    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onContentPrepare(string $context, mixed &$row, mixed $params, ?int $page = 0): void
	{
		
		// return if no text is available
		if (empty($row->text))
		{
			return;
		}

		$lang      = $this->app->getLanguage();
		$extension = 'com_contact';
		$lang->load($extension);

		$this->handleOnContentPrepare($context, $row, $params, $page);
	}


	/**
	 * @param string   $context
	 * @param mixed    $article
	 * @param mixed    $params
	 * @param int|null $page
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function handleOnContentPrepare(string $context, mixed &$article, mixed &$params, ?int $page = 0): void
	{

		// replace list placeholders
		if (StringHelper::strpos($article->text, '{inlinecontactlist') !== false)
		{
			$regex         = "/{inlinecontactlist\s+(\d+)\s+(\d+)\s*(\d*)\s*(\d*)}/";
			$article->text = preg_replace_callback($regex, array(&$this, '_contactListReplaceCallback'), $article->text);
		}

		// replace normal opening / closing contact tags
		if (StringHelper::strpos($article->text, '{/inlinecontact}') !== false)
		{
			$regex         = "/{inlinecontact\s(\d*?)}([\S\s]*?){\/inlinecontact}/";
			$article->text = preg_replace_callback($regex, array(&$this, '_contactReplaceCallback'), $article->text);
		}

		// replace contact single template tag
		if (StringHelper::strpos($article->text, '{inlinecontact') !== false)
		{
			$regex         = "/{inlinecontact\s+(\d+)\s+(\d+)}/";
			$article->text = preg_replace_callback($regex, array(&$this, '_contactReplaceTemplateCallback'), $article->text);
		}


	}


	/**
	 * @param array         $matches
	 * @param stdClass|null $contact
	 *
	 * @return array|string
	 * @since 1.0.0
	 */
	protected function _contactReplaceCallback(array $matches, stdClass $contact = null): array|string
	{
		if (!array_key_exists(1, $matches) || !is_numeric($matches[1]))
		{
			return '[invalid contact id]';
		}
		$contactId = intval($matches[1]);

		if (!array_key_exists(2, $matches))
		{
			return '[content missing]';
		}
		$articleText = $matches[2];


		if (empty($contact))
		{
			$contact = $this->getContact($contactId);
		}

		if (!$contact)
		{
			return $this->params->get('contactnotfound', Text::_('PLG_INLINECONTACT_CONTACTNOTFOUND'));
		}


		//Default attribute names and types
		$attributes = ['id', 'name', 'alias', 'con_position', 'address', 'suburb', 'state', 'country', 'postcode', 'telephone', 'fax', 'misc', 'image', 'email_to', 'mobile', 'webpage'];
		$types      = ['integer', 'text', 'text', 'text', 'textarea', 'text', 'text', 'text', 'text', 'text', 'text', 'editor', 'media', 'email', 'text', 'URL'];
		$langKeys   = [false, false, false, 'POSITION', null, null, null, null, 'FIELD_INFORMATION_POSTCODE_LABEL', null, null, 'OTHER_INFORMATION', 'IMAGE_DETAILS', 'EMAIL_LABEL', null, null];


		$contactData = [];

		foreach ($attributes as $key => $attribute)
		{

			if ($langKeys[$key] === false)
			{
				$label = 'PLG_INLINECONTACT_DEFLABEL_' . strtoupper($attribute);
			}
			else
			{
				$langKey = $langKeys[$key] ?: strtoupper($attribute);
				$label   = 'COM_CONTACT_' . strtoupper($langKey);
			}

			$contactData[$attribute] = [
				'label' => Text::_($label),
				'value' => $contact->$attribute,
				'type'  => $types[$key]
			];

			if($types[$key] === 'media') {
				// add placeholder to get only value instead of rendered img tag
				$contactData[$attribute . '_raw'] = [
					'label' => Text::_($label),
					'value' => $contact->$attribute,
					'type'  => 'text'
				];
			}
		}

		if (is_array($contact->jcfields))
		{
			foreach ($contact->jcfields as $customField)
			{
				$contactData[$customField->name] = [
					'label' => Text::_($customField->label),
					'value' => $customField->value,
					'type'  => $customField->type
				];
			}
		}

		// /\[(l_|lv_)?([A-Za-z_-]+)\s*(\|(.+?)\|)?\s*("(.+?)")?\s*?\]/
		$articleText = str_replace("\xc2\xa0", ' ', $articleText);
		return preg_replace_callback('/\[(l_|lv_)?([A-Za-z_-]+)\s*(?:(\?=|[?|])\s*((?:\\\?|\\\||[^?|])+?))?(?:\s*(\?=|[?|])\s*(.+?))?\s*\]/', function ($matches) use ($contactData) {
			return $this->replaceContactPlaceholders($matches, $contactData);
		}, $articleText);
	}


	/**
	 * @param array $matches
	 *
	 * @return array|string
	 * @since 3.0.0
	 */
	protected function _contactReplaceTemplateCallback(array $matches): array|string
	{
		if (!array_key_exists(1, $matches) || !ctype_digit($matches[1]))
		{
			return '[invalid contact id]';
		}
		$contactId = intval($matches[1]);

		if (!array_key_exists(2, $matches) || !ctype_digit($matches[2]))
		{
			return '[invalid template id]';
		}

		$templateId   = intval($matches[2]);
		$templates    = $this->params->get('stemplates');
		$propertyName = 'stemplates' . ($templateId - 1);
		if (!is_object($templates) || !property_exists($templates, $propertyName))
		{
			return '[invalid template id]';
		}

		$contact = $this->getContact($contactId);

		$matches = array('', 0, $templates->$propertyName->content);

		return $this->_contactReplaceCallback($matches, $contact);
	}

	/**
	 *
	 * @param array $matches
	 *
	 * @return string
	 * @since 1.0.0
	 */
	protected function _contactListReplaceCallback(array $matches): string
	{

		if (!array_key_exists(1, $matches) || !is_numeric($matches[1]))
		{
			return '[invalid category id]';
		}
		$categoryId = intval($matches[1]);

		if (!array_key_exists(2, $matches) || !is_numeric($matches[2]))
		{
			return '[invalid category id]';
		}
		$templateId   = $matches[2];
		$templates    = $this->params->get('templates');
		$propertyName = 'templates' . ($templateId - 1);
		if (!is_object($templates) || !property_exists($templates, $propertyName))
		{
			return '[invalid template id]';
		}


		$sortMode = 0;
		if (array_key_exists(3, $matches) && ctype_digit($matches[3]))
		{
			$sortMode = intval($matches[3]);
			if ($sortMode !== 1 && $sortMode !== 2)
			{
				$sortMode = 0;
			}
		}

		$filterMode = 0;
		if (array_key_exists(4, $matches) && ctype_digit($matches[4]))
		{
			$filterMode = intval($matches[4]);
			if ($filterMode !== 1 && $filterMode !== 2)
			{
				$filterMode = 0;
			}
		}


		if ($filterMode === 1)
		{
			/** @var FeaturedModel $categoryModel */
			$categoryModel = $this->app->bootComponent('com_contact')->getMVCFactory()
				->createModel('Featured', 'Site', ['ignore_request' => true]);
		}
		else
		{
			/** @var CategoryModel $categoryModel */
			$categoryModel = $this->app->bootComponent('com_contact')->getMVCFactory()
				->createModel('Category', 'Site', ['ignore_request' => true]);
		}

		$categoryModel->setState('category.id', $categoryId);
		$categoryModel->setState('filter.published', 1);

		if ($filterMode !== 1)
		{
			if ($sortMode === 1)
			{
				$categoryModel->setState('list.ordering', 'featuredordering');
			}
			elseif ($sortMode === 2)
			{
				$categoryModel->setState('list.ordering', 'sortname');
			}
		}


		$contacts = $categoryModel->getItems();


		if (empty($contacts))
		{
			return $this->params->get('nocontact', Text::_('PLG_INLINECONTACT_NOCONTACT'));
		}

		$output = $templates->$propertyName->b;
		foreach ($contacts as $contact)
		{
			if ($filterMode === 2 && $contact->featured === 1)
			{
				continue;
			}

			$this->loadCustomFields($contact);

			$matches = array('', $contact->id, $templates->$propertyName->t);
			$output  .= $this->_contactReplaceCallback($matches, $contact);
		}
		$output .= $templates->$propertyName->a;

		return $output;
	}


	/**
	 * @param array $matches
	 * @param array $contactData
	 *
	 * @return string
	 * @since 3.0.0
	 */
	private function replaceContactPlaceholders(array $matches, array $contactData): string
	{

		if (empty($matches[2]) || !array_key_exists($matches[2], $contactData))
		{
			return '';
		}

		$value = $contactData[$matches[2]]['value'];
		$label = $contactData[$matches[2]]['label'];
		$type  = $contactData[$matches[2]]['type'];

		if ($type == 'media')
		{
			if (!empty($value))
			{
				$value = '<img src="' . $value . '" title="' . $label . '" alt="' . $label . '" />';
			}
		}


		$defaultValue = null;
		$template = '%s';
		$overrideTemplate = null;

		foreach([3,5] as $key) {
			if (isset($matches[$key]) && isset($matches[$key+1])) {
				if ($matches[$key] === '?') {
					// match is default value
					$defaultValue = str_replace(['\\?','\\|'],['?','|'],$matches[$key+1]);
				} elseif($matches[$key] === '?=') {
					// match is default value and overrides template
					$defaultValue = '';
					$overrideTemplate = str_replace(['\\?','\\|'],['?','|'],html_entity_decode(str_replace('%s','',trim($matches[$key+1]))));
				} elseif($matches[$key] === '|') {
					// match is default value
					$template = str_replace(['\\?','\\|'],['?','|'],html_entity_decode(trim($matches[$key+1])));
				}
			}
		}

		$hideEmpty = false;
		$emptyValue = empty($value);

		// default value if empty
		if ($emptyValue && $defaultValue !== null)
		{
			$value = $defaultValue;
		}
		else
		{
			// hide only if no default value is submitted
			$hideEmpty = $this->params->get('hideempty', false) && $emptyValue;
		}

		$labelMode = $matches[1] ?? 0;

		//value only
		$output = $value;
		$label  = '<span class="plginlcon-label">' . $label . '</span>';

		if ($labelMode === 'l_')
		{
			//label only
			$output = $label;
		}
		elseif ($labelMode === 'lv_')
		{
			//label and value
			$output = $label . ': ' . $output;
		}

		$deepRender = $this->params->get('deeprender', false);
		try
		{
			if ($emptyValue && $overrideTemplate !== null) {
				$output = $overrideTemplate;
			} else {
				$output = $hideEmpty ? '' : sprintf($template, $output);
			}
			if($deepRender) {
				$output = preg_replace_callback('/([^\\\\])\\$([a-zA-Z_]+)/', function ($matches) use ($contactData) {
					return $this->deepReplaceContactPlaceholders($matches, $contactData);
				}, $output);
			}
		}
		catch (Throwable)
		{
			$output = '[error in HTML template]';
		}

		return $output;
	}


	/**
	 * Replace variables inside the placeholders
	 *
	 * @param array $matches
	 * @param array $contactData
	 *
	 * @return string
	 *
	 * @since 3.1.0
	 */
	private function deepReplaceContactPlaceholders(array $matches, array $contactData): string
	{
		if (empty($matches[2]) || !array_key_exists($matches[2], $contactData))
		{
			if(isset($matches[0])) {
				return $matches[0];
			}
			return '';
		}

		return $matches[1].$contactData[$matches[2]]['value'];
	}
	
	/**
	 * Retrieve Contact
	 *
	 * @param int $contactId ID of the contact
	 *
	 * @return mixed
	 *
	 * @since 3.0.0
	 */
	protected function getContact(int $contactId): mixed
	{
		$model = new ContactModel();
		try
		{
			$model->setState('filter.published', 1);
			$contact = $model->getItem($contactId);
		}
		catch (Throwable)
		{
			return null;
		}

		// Get the custom fields
		$this->loadCustomFields($contact);


		return $contact;
	}

	/**
	 * Adds the custom fields to a contact object
	 *
	 * @param mixed|null $contact
	 *
	 * @since 3.0.0
	 */
	private function loadCustomFields(mixed &$contact = null): void
	{

		if (empty($contact))
		{
			return;
		}

		$contact->jcfields = [];

		try
		{
			$contact->jcfields = FieldsHelper::getFields('com_contact.contact', $contact);
		}
		catch (Throwable)
		{
		}
	}

}
