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


class PlgContentInlineContact extends JPlugin
{
	public $params;

	public function __construct(& $subject, $config)
	{
		$lang =& JFactory::getLanguage();
		$lang->load('plg_content_inlinecontact',JPATH_ADMINISTRATOR);
		parent::__construct($subject, $config);
	}

	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (JString::strpos($article->text, '{inlinecontactlist') !== false)
		{
			$regex = "/{inlinecontactlist\s(\d*?)\s(\d*?)}/";
			$article->text = preg_replace_callback($regex, array(&$this, 'contactlist_replace_cb'), $article->text);
		}

		if (JString::strpos($article->text, '{/inlinecontact}') !== false)
		{
			$regex = "/{inlinecontact\s(\d*?)}([\S\s]*?){\/inlinecontact}/";
			$article->text = preg_replace_callback($regex, array(&$this, 'contact_replace_cb'), $article->text);
		}

		return true;
	}


	protected function contact_replace_cb( &$matches ) {

		$db = JFactory::getDBO();

		if (!is_numeric($matches[1]))
		{
			return '[invalid contact id]';
		}
		$contact_id = $matches[1];
		$article_text =  $matches[2];


		//Get all default fields of the contact
		$query = 'SELECT * FROM #__contact_details WHERE id =' .$contact_id;
		$db->setQuery( $query );
		$contact = $db->loadObject();

		if (!$contact) {
			return '[contact not found]';
		}

		//Get all custom fields of the contact
		$query = 'SELECT `j32_fields`.`name`, `j32_fields`.`label`, `j32_fields`.`type`, `j32_fields_values`.item_id, `j32_fields_values`.`value` FROM `j32_fields` LEFT JOIN `j32_fields_values` ON `j32_fields_values`.`field_id` = `j32_fields`.`id` AND `j32_fields_values`.`item_id` = ' .$contact_id;
		$db->setQuery( $query );

		$customfields = $db->loadObjectList();


		//Default attribute names and types
		$attributes = array('id', 'name', 'alias', 'con_position', 'address', 'suburb', 'state', 'country', 'postcode', 'telephone', 'fax', 'misc', 'image', 'email_to', 'mobile', 'webpage');
		$types = array('integer', 'text', 'text', 'text', 'textarea', 'text', 'text', 'text', 'text', 'text', 'text', 'editor', 'media', 'email', 'text', 'URL');

		$find = array();
		$replace = array();

		foreach ($attributes as $key => $attribute)
		{
			//TODO: label
			$this->build_replace_array($find,$replace,$attribute,JText::_('PLG_INLINECONTACT_DEFLABEL_'.strtoupper($attribute)),$contact->$attribute,$types[$key]);
		}

		foreach ($customfields as $customfield)
		{
			$this->build_replace_array($find,$replace,$customfield->name,$customfield->label,$customfield->value,$customfield->type);
		}

		return str_replace($find,$replace,$article_text);
	}



	private function build_replace_array(&$find,&$replace,$name,$label,$value,$type='')
	{
		//Handle different field types
		if ($type == 'media')
		{
			$imgPath = JURI::root() . $value;
			if (is_file( JPATH_BASE . '/'. $value )) {
				$value = '<img src="'. $imgPath .'" title="' . $label . '" alt="' . $label . '" />';
			}
		}

		//value only
		$find[] = "[$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' : $value;

		//label only
		$find[] = "[l_$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' :$label;

		//label and value
		$find[] = "[lv_$name]";
		$replace[] = ($this->params->get('hideempty') && $value == '') ? '' : $label.': '.$value;
	}


	protected function contactlist_replace_cb( &$matches )
	{

		if (!array_key_exists(1,$matches) || !is_numeric($matches[1]))
		{
			return '[invalid category id]';
		}
		$category_id = $matches[1];

		if (!array_key_exists(2,$matches) || !is_numeric($matches[2]))
		{
			return '[invalid category id]';
		}
		$template_id = $matches[2];
		$templates = $this->params->get('templates');

		if (!is_object($templates) || !property_exists($templates,$template_id))
		{
			return '[invalid template id]';
		}

		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__contact_details WHERE catid =' .$category_id;
		$db->setQuery( $query );
		$contacts = $db->loadObjectList();

		if (!$contacts) {
			return '[no contact found in category]';
		}

		$output = $templates->$template_id->b;
		foreach ($contacts as $contact)
		{
			$matches = array('',$contact->id,$templates->$template_id->t);
			$output .= $this->contact_replace_cb( $matches );
		}
		$output .= $templates->$template_id->a;

		return $output;
	}
}
