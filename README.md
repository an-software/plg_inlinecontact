InlineContact
=============

With this plugin you can insert contact details of one or more contacts directly into the content of an article or module.

Features
--------
+ Insert contact details at specific postion
+ Manage multiple list or single contact templates
+ Supports Joomla custom fields
+ Configurable list ordering (defined order / featured first / sortname)
+ Non-/Featured only lists
+ Translated labels
+ Uses the core Joomla updater.
+ Can be extended by an editor button
+ Editor integration via additional [component](https://github.com/an-software/com_inlinecontact) and [plugin](https://github.com/an-software/plg_inlinecontactxtd)

How it works
------------
The plugin replaces all placeholders between the tags `{inlinecontact contactId} any text/ HTML containing placeholders {/inlinecontact}`, where contactId must be replaced with the respective id of the contact.

`contactId`: ID of the contact 

Also, contact categories can be displayed as a list (e.g. as a simple HTML table) using the `{inlinecontactlist categoryId templateNumber sortMode featuredMode}` tag.

`categoryId`: ID of the category\
`templateNumber`: Number of the template from the plugin settings\
`sortMode`: Sorting of the list, optional, default (0): order as specified; 1: featured first; 2: sort by sortname\
`featuredMode`: optional, default (0): order as specified in Joomla; 1: only featured; 2: only not featured

Predefined templates can also be stored for individual contacts. They can later be pasted directly into the editor via the `{inlinecontact contactId templateNumber}` tag.

`contactId`: ID of the contact\
`templateNumber`: Number of the template from the plugin settings

The templates can be specified as text or HTML and can contain any placeholders.

Available Placeholders
----------------------

The following default placeholders are available.\
All user defined fields are also available as placeholders via their respective names.

[id]\
[name]\
[alias]\
[con_position]\
[address]\
[suburb]\
[state]\
[country]\
[postcode]\
[telephone]\
[fax]\
[misc]\
[image]\
[email_to]\
[mobile]\
[webpage]

The above placeholders only return the value. Each placeholder can be prefixed with further prefixes:

**[l_*]** : outputs only the associated label (translated automatically)\
**[lv_*]** : outputs both the value and the associated label

All labels, including those of your own fields (if there is a language override), are automatically translated into the required language.

### Extended Placeholders

It's possible to extend any of the above placeholders by using following syntax.

```
[placeholder ? default value | any %s html]
[placeholder ?= any default value html | any %s html]
```

Both extra parameters are optional. So it is possible to use `[placeholder ? default value ]` or `[placeholder | any %s html ]` as well.

`? default value` The value after the question mark is used as default value when the normal value is empty. Only the value is replaced and used in a possible template.

`| any %s html` The value after the pipe can be any custom html template specific to just the single placeholder. %s is mandatory and must exist exactly once. It is later replaced by the supposed value of the placeholder.

`?= any default value html` If the question mark is followed by an `=`, then the entire html template will be replaced if the original value is empty.

? or | inside the default value or html template can be escaped by using a backslash: `\? \|`


Documentation
-------------
A detailed documentation is available at https://an-software.net/en/software/inlinecontact/
