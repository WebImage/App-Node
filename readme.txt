Interesting fluid java form builder:
https://j2html.com/

Rules
ON CREATE ASSOCIATION Node.Types.ContactNote
UPDATE Node.Type.Contact SET LastContacted = DATE()

Views:
    TableColumn
    Detail
    Form

    DataType
    Type.DataType
    Property.DataType

    PropertyRenderer::render($node, $propertyKey)
        $value = $node->getPropertyValue($propertyKey);
        $value = $dataTypeRenderer->render('WebImage.DataTypes.DateTime', $value);
        $value = $typeRenderer->render('WebImage.Type.Company', 'WebImage.DataTypes.DateTime', $value);
        $value = $propertyRenderer->render('WebImage.TypeCompany', $propertyKey, 'DataType', $value);)

        // Context rendering (e.g. column, form, view)

        // What if we want to render DateTime as "23 days ago"
        // How to add render with link
        VIEW: node/datatypes/datetime.php

        class DateTimeDataTypeRenderer extends DataTypeRenderer

        return $value;

        WebImage.DataTypes.DateTime -> new DateTime()->format('m/d/Y'


Fix default for node.created (date.now())

$node->getPropertyValue('nodeRef') => Node
    - Internally, translates 0000-0000-0000-00001 => new Node

$node->setPropertyValue('nodeRef', $otherNode)
    - Internally, translates Node by using $otherNode->getUuid()

NodeRefValueTranslator || DataTypeValueMapper ||
    ->valueForDisplay() || ->get(): NodeRef
    ->valueForStorage() || ->set(NodeRef $nodeRef)

DataTypeValueMapper::get($node, $property);
DataTypeValueMapper::set($node, $property, $value);
$dataTypeService->getValueMapper

$node['names'] = ['Name'];
$node['nodeRef'] = new
\WebImage\Node\DataTypes\ValueMappers\NodeRefValueMapper


template.php
    $render($nodeRef) => <?= $nodeService->getNodeByUuid($nodeRef->getUuid(), $nodeRef->getVersion())->getPropertyValue('name')) ?>

Getting form value to node
    <input type="hidden" name="contact_uuid">
    <input type="hidden" name="contact_version">
    <a href="#" onclick="Node.Chooser('WebImage.Types.Activity.contact')">Choose</a>
    --------------------
    | Search...        |
    --------------------
    | Results          |
    | Results          |
    --------------------
    function Chooser() {
        $.get('/crm/chooser', 'WebImage.Types.Activity.contact', function(node) {
            contact_uuid = node.node_uuid;
            contact_version = node.version;
            label = node.name;
        });
    }

In node service, return
LazyProperty($uuids,
LazyMultiValueProperty