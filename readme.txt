Interesting fluid java form builder:
https://j2html.com/

Rules
ON CREATE ASSOCIATION Node.Types.ContactNote
UPDATE Node.Type.Contact SET LastContacted = DATE()


node_association_defs
- allow_duplicates
- assoc_type_qname
- dst_has_many
- dst_required
- dst_strict
- name
- src_has_many
- src_required
- src_strict

node_association_def_meta [ADD]
- assoc_type_qname
- name
- key
- value

node_type_associations
- assoc_type_qname
- node_type_id
- sortorder

node_associations
- assoc_type_qname
- src_node_id
- src_node_version
- tgt_node_id
- tgt_node_version

node_association_meta [ADD]
- assoc_type_qname
- src_node_id
- src_node_version
- tgt_node_id
- tgt_node_version
- key
- value