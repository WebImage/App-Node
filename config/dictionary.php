<?php
use WebImage\Node\DataTypes\Type;
return [
	'types' => [
		[
			'qname' => 'WebImage.Types.Root',
			'model' => ['name' => 'nodes', 'dataSaver' => 'CWI_CNODE_Root']
		],
		[
			'qname' => 'WebImage.Types.Type',
			'model' => ['name' => 'node_types']
		],
		[
			'isSubClassable' => true,
//			'parent' => 'cwi:Root',
			'qname' => 'WebImage.Types.Base',
			'parent' => 'WebImage.Types.Root',
			'friendlyName' => 'Title',
			'model' => ['name' => 'node_base'],
			'properties' => [
				// CustomTitltePropertyClass would need to extends a lower level property
				['name' => 'title', 'friendlyName' => 'Title', 'searchable' => true, 'propertyClass' => 'CustomTitlePropertyClass',
					'type' => 'string', 'dataType' => 'string']
			]
		],
		[
			'isSubClassable' => true,
			'qname' => 'WebImage.Types.Content',
			'parent' => 'WebImage.Types.Base',
			'model' => ['name' => 'node_content'],
			'friendlyName' => 'Content',
			'enableLocales' => false,
			'enableProfiles' => false,
			'extensions' => [
				['name' => 'WebImage.Types.OwnableExtension']
			],
			'properties' => [
				['name' => 'body', 'friendlyName' => 'Body', 'searchable' => true]
			]
		],

	],
	'extensions' => [
		['qname' => 'WebImage.Types.OwnableExtension', 'model' => ['name' => 'node_publishable']],
		['qname' => 'WebImage.Types.AuthorableExtension', 'associations' => [
			[
				'qname' => 'WebImage.Types.AuthorExtension',
				'source' => [
					'mandatory' => false,
					'many' => true
				],
				'target' => [
					'class' => 'WebImage.Types.Base',
					'mandatory' => true,
					'many' => true
				]
			]
		]]
	],
	'dataTypes' => [
		['type' => 'WebImage.DataTypes.String', 'name' => 'Single Line', 'formElement' => 'text', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.DataTypes.Text', 'name' => 'Multi Line', 'formElement' => 'textarea', 'phpType' => 'string', 'modelField' => ['type' => Type::TEXT]],
		['type' => 'WebImage.DataTypes.Integer', 'name' => 'Integer', 'formElement' => 'number', 'phpType' => 'int', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.DataTypes.Date', 'name' => 'Date', 'formElement' => 'date', 'phpType' => 'string', 'modelField' => ['type' => Type::DATE]],
		['type' => 'WebImage.DataTypes.DateTime', 'name' => 'Date/Time', 'formElement' => 'datetime', 'phpType' => 'string', 'modelField' => ['type' => Type::DATETIME]],
		['type' => 'WebImage.DataTypes.Boolean', 'name' => 'True/False', 'formElement' => 'toggle', 'phpType' => 'boolean', 'modelField' => ['type' => Type::BOOLEAN]],
//		['type' => 'WebImage.DataTypes.QName', 'formElement' => 'text', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.DataTypes.NodeRef', 'name' => 'Reference', 'formElement' => 'text', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.DataTypes.ChildAssocRef', 'name' => 'Child Association', 'formElement' => 'text', 'modelField' => ['type' => Type::INTEGER]], //CWI_REPO_SERVICE_ChildAssociationRef'],
		['type' => 'WebImage.DataTypes.AssocRef', 'name' => 'Association Ref', 'formElement' => 'text', 'phpClassName' => 'CWI_REPO_SERVICE_AssociationRef'],
//		['type' => 'WebImage.DataTypes.Category', 'name' => 'Category', 'formElement' => 'select', 'phpType' => 'string'],
		['type' => 'WebImage.DataTypes.User', 'name' => 'User', 'formElement' => 'text', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.DataTypes.File', 'name' => 'File', 'formElement' => 'upload', 'phpClassName' => 'CWI_CNODE_DATATYPE_File', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.DataTypes.EmbeddedMedia', 'name' => 'Embedded Media', 'formElement' => 'text', 'modelFields' => [
				['name' => 'embed', 'type' => Type::TEXT],
				['name' => 'value', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'provider', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'data', 'type' => Type::TEXT]
		]],
		['type' => 'WebImage.DataTypes.Link', 'name' => 'Link', 'formElement' => 'link', 'modelFields' => [
			['name' => 'url', 'type' => Type::STRING, 'options' => ['length' => '255']],
			['name' => 'title', 'type' => Type::STRING, 'options' => ['length' => '255']]
		]],
		['type' => 'WebImage.DataTypes.Address', 'name' => 'Address', 'formElement' => 'address', 'modelFields' => [
			['name' => 'street1', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'street2', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'city', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'state', 'type' => Type::STRING, 'options' => ['length' => 3, 'notnull' => false]],
			['name' => 'country', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'zip', 'type' => Type::STRING, 'options' => ['length' => 10, 'notnull' => false]],
		]]
	]
];