<?php
use WebImage\Node\DataTypes\Type;
return [
	'types' => [
		[
			'qname' => 'WebImage.Types.Root',
			'isAbstract' => true
//			'model' => ['name' => 'nodes', 'dataSaver' => 'CWI_CNODE_Root']
		],
		[
			'qname' => 'WebImage.Types.Base',
			'parent' => 'WebImage.Types.Root',
			'friendlyName' => 'Title',
			'model' => ['name' => 'nodes'],
			'isFinal' => false,
			'isAbstract' => true,
			'properties' => [
				['key' => 'name', 'name' => 'Name', 'searchable' => true, 'type' => 'WebImage.DataTypes.String'],
				['key' => 'created', 'name' => 'Created', 'type' => 'WebImage.DataTypes.DateTime', 'isReadOnly' => true],
				['key' => 'created_by', 'name' => 'Created By', 'type' => 'WebImage.DataTypes.Integer', 'isReadOnly' => true],
				['key' => 'status', 'name' => 'Status', 'type' => 'WebImage.DataTypes.String', 'isReadOnly' => true],
				['key' => 'type_qname', 'name' => 'Type QName', 'type' => 'WebImage.DataTypes.String', 'isReadOnly' => true],
				['key' => 'uuid', 'name' => 'UUID', 'type' => 'WebImage.DataTypes.String', 'isReadOnly' => true],
				['key' => 'version', 'name' => 'Version', 'type' => 'WebImage.DataTypes.Integer', 'isReadOnly' => true],
				['key' => 'updated', 'name' => 'Updated', 'type' => 'WebImage.DataTypes.DateTime', 'isReadOnly' => true],
				['key' => 'updated_by', 'name' => 'Updated', 'type' => 'WebImage.DataTypes.Integer', 'isReadOnly' => true]
			],
			'config' => ['modelKey' => 'nodes']
		],
		[
			'qname' => 'WebImage.Types.Type',
			'parent' => 'WebImage.Types.Base',
			'config' => ['modelKey' => 'node_types'],
//			'properties' => [
//				['key' => 'config', 'name' => 'Config', 'type' => 'WebImage.DataTypes.Text'],
//				//['key' => 'node_id', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				['key' => 'is_extension', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				['key' => 'name', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				['key' => 'parent', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				['key' => 'plural_name', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				['key' => 'qname', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//				//['key' => 'table_key', 'name' => '', 'type' => 'WebImage.DataTypes.String'],
//			]
		],
		[
			'qname' => 'WebImage.Types.HierarchyNode',
			'parent' => 'WebImage.Types.Base',
			'properties' => [
				['key' => 'parent', 'name' => 'Parent', 'type' => 'WebImage.DataTypes.Node']
			],
//			'extensions' => [
//				['qname' => 'WebImage.TypeExtensions.Created']
//			]
		],
		[
			'name' => 'Folder',
			'pluralName' => 'Folders',
			'qname' => 'WebImage.Types.Folder',
			'parent' => 'WebImage.Types.HierarchyNode',
//			'properties' => [
//				['key' => 'name', 'name' => 'Name', 'type' => 'WebImage.DataTypes.String']
//			]
		],
		[
			'name' => 'Content',
			'pluralName' => 'Content',
			'isFinal' => false,
			'qname' => 'WebImage.Types.Content',
			'parent' => 'WebImage.Types.Base',
			'model' => ['name' => 'node_content'],
			'enableLocales' => false,
			'enableProfiles' => false,
			'extensions' => [
				['name' => 'WebImage.Types.OwnableExtension']
			],
			'properties' => [
				['key' => 'body', 'name' => 'Body', 'searchable' => true]
			]
		],

	],
	'extensions' => [
		['name' => 'Ownable', 'pluralName' => 'Ownables', 'qname' => 'WebImage.Types.OwnableExtension', 'model' => ['name' => 'node_publishable']],
		['name' => 'Authorable', 'pluralName' => 'Authorables', 'qname' => 'WebImage.Types.AuthorableExtension', 'associations' => [
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
		]],
		['type' => 'WebImage.DataTypes.TypeRef', 'name' => 'Type References', 'modelField' => ['type' => Type::STRING ]]
//		['type' => 'string', 'alias' => 'WebImage.DataTypes.String'],
//		['type' => 'text', 'alias' => 'WebImage.DataTypes.Text'],
//		['type' => 'integer', 'alias' => 'WebImage.DataTypes.Integer'],
//		['type' => 'date', 'alias' => 'WebImage.DataTypes.Date'],
//		['type' => 'datetime', 'alias' => 'WebImage.DataTypes.DateTime'],
//		['type' => 'boolean', 'alias' => 'WebImage.DataTypes.Boolean'],
//		['type' => 'noderef', 'alias' => 'WebImage.DataTypes.NodeRef']
	]
];