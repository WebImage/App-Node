CWI_
cnode
CWI_XML_Traversal
CWI_CNODE
CNode
// comment = Load required libraries
IXmlObject (No )
is_null
is_string?
Collection

UML Association Notation - http://www.agilemodeling.com/artifacts/classDiagram.htm
0..1 Zero or one [0-9](..([0-9]+))?
1 One only
0..* Zero or more
1..* One or more
n Only n > 1
0..n Zero to n
1..n 1 to n

Repository Services:

getRepositories() -> [{ Id repositoryId, String repositoryId }]
getRepositoryInfo(Id repositoryId) -> { Id repositoryId, String repositoryName, String repositoryDescription, String vendorName, String productName, String productVersion, Id rootFolderId, List<Capabilities> capabilities, String latestChangeLogToken, String cmisVersionSupported, URI thinClientURI, Boolean changesIncomplete, <Array> Enum changesOnType, Enum supportedPermissions.{basic,repository,both}, Enum propagation, <Array> Permission permissions, <Array> PermissionMapping mapping, String principalAnonymous, String principalAnyone, <Array> RepositoryFeatures extendedFeatures
getTypeChildren(Id repositoryId) -> { Id typeId, Boolean includePropertyDefinitions, Integer maxItems, Integer skipCount) -> { <Array> Object-Type types, Boolean hasMoreItems, Integer numItems)
getTypeDescendants(Id repositoryId, Id typeId=null, Integer depth=-1, Boolean includePropertyDefinitions=false) -> <Array> Object-Type types
getTypeDeﬁnition(Id repositoryId, Id typeId) -> Object-Type type (cmis:document, cmis:folder, cmis:relationship, cmis:policy, cmis:item, cmis:secondary)
createType(Id repositoryId, Object-Type type) -> Object-Type type
updateType(Id repositoryId, Object-Type type) -> Object-Type type
deleteType(Id repositoryId, Object-Type type)

Navigation Services:  // for traversing folder hierarchy

getChildren(Id repositoryId, Id folderId, Integer maxItems=null, Integer skipCount=null, String orderBy=null, String filter=null, Enum includeRelationships=null, String renditionFilter, Boolean includeAllowableActions=null, Boolean includePathSegment=null) -> [ { <Array> objects objects (more at 2.2.3.1.2 Outputs), Boolean hasMoreItems, Integer numItems)
getDescendents(Id repositoryId, Id folderId, Integer depth=-1, String filter=null, Enum includeRelationships=null, String renditionFilter, Boolean includeAllowableActions, Boolean includePathSegment) -> <Array> objects objects
getFolderTree // not implemented
getFolderParent(Id repositoryId, Id folderId, String filter=null) -> Object folder
getObjectParents(Id repositoryId, Id objectId, String filter=null, String renditionFilter=null, Boolean includeAllowableActions=null, Boolean includeRelativePathSegment=null) -> { <Array> objects objects, Boolean hasMoreItems, Integer numItems }
getCheckedOutDocs // not implemented

Object Services:

createDocument(Id repositoryId, properties, folderId=null, ContentStream contentStream=null, versioningState.{none, checkedout, major, minor), ID policies=[], ACE addACEs=[], ACE removeACEs) -> Id objectId
createDocumentFromSource(Id repositoryId, sourceId, Properties modifiedProperties=[], folder, versioningState=ENUM, ID[] policies, ACE[] addACEs, ACE[] removeACEs) -> Id objectId
createFolder(Id repositoryId, Property[] properties, Id folderId, Id[] policies=[], ACE[] addACEs=[], ACE[] removeACEs[]) -> Id objectId
createRelationship(Id repositoryId, <Array> Property properties, <Array> Id policies=null, <Array> ACE addACEs=null, <Array> ACE removeACEs=null) -> Id objectId
createPolicy(Id repositoryId, <Array> Property properties, Id folderId=null, <Array> Id policies=null, <Array> ACE addACEs=null, <Array> ACE removeACEs=null) -> Id objectId
createItem(Id repositoryId, properties, folderId=null, Id policies=[], ACE addACEs=[], ACE removeACEs) -> Id objectId
getAllowableActions(Id repositoryId, Id objectId) -> <Array> AllowableActions AllowableActions (see http://docs.oasis-open.org/cmis/CMIS/v1.1/os/CMIS-v1.1-os.html#x1-1590006)
getObject(Id repositoryId, Id objectId), String filter=null /* 2.2.1.2.1 */, Enum includeRelationships=null /* 2.2.1.2.2 */, Boolean includePolicyIds=null /* 2.2.1.2.3 */, String renditionFilter=null /* 2.2.1.2.4 */, Boolean includeACL=null /* 2.2.1.2.5 */, Boolean includeAllowableActions=null /* 2.2.1.2.6 */) -> <Array> Properties properties, <Array> Relationships relationships, <Array> PolicyId policies, <Array> Renditions renditions, ACL acl, AllowableActions allowableActions
getProperties(Id repositoryId, Id objectId, String filter=null) -> <Array> Properties properties
getObjectByPath // not implemented
getContentStream(Id repositoryId, Id objectId, Id streamId=null) -> Stream> ContentStream contentStream
getRenditions(Id repositoryId, Id objectId, String renditionFilter=null /* key or mime/type */, Integer maxItems=null, Integer skipCount=null) -> <Array> Renditions rendition
updateProperties(Id repositoryId, Id objectId, <Array> Properties properties, String changeToken=null) -> { Id objectId, String changeToken }
bulkUpdateProperties(Id repositoryId, <Array> <Id, String> objectIdAndChangeToken, <Array> Properties properties=null, <Array> Id addSecondaryTypeIds=null, <Array> Id removeSecondaryTypeIds=null, <Array> Id removeSecondaryTypeIds=null) -> <Array> <Id, Id, String> objectIdAndChangeToken
moveObject(Id repositoryId, Id objectId, Id targetFolderId, Id sourceFolderId) -> Id objectId
deleteObject(id repositoryId, Id objectId, Boolean allVersions=TRUE)
deleteTree(Id repositoryId, Id folderId, Boolean allVersions=true, Enum unfileObjects.{unfile, deletesinglefiled, delete}=delete, Boolean continueOnFailure=false) -> <Array> Id failedToDelete
setContentStream(Id repositoryId, Id objectId, ContentStream contentStream, Boolean overwriteFlag=true, String changeToken=null) -> { Id objectId, String changeToken }
appendContentStream(Id repositoryId, Id objectId, ContentStream contentStream, Boolean isLastChunk=false, String changeToken=null) -> { Id objectId, String changeToken }
deleteContentStream(Id repositoryId, Id objectId, String changeToken=null) -> { Id objectId, String changeToken }

Multi-filing Services

addObjectToFolder(Id repositoryId, Id objectId, Id folderId, Boolean allVersions=true)
removeObjectFromFolder(Id repositoryId, Id objectId, Id folderId=null)

Discovery Services:

query(Id repositoryId, String statement, Boolean searchAllVersions=false, Enum includeRelationships, String renditionFilter=null, Boolean includeAllowableActions=false, Integer maxItems, Integer skipCount) -> { <Array> Object queryResults, Boolean hasMoreItems, Integer numItems)
getContentChanges(Id repositoryId, String changeLogToken=null, Boolean includeProperties=false, Boolean includePolicyIds=false, Boolean includeACL, Integer maxItems) -> { <Array> ChangeEvents changeEvents, <Array> Id policyIds, String latestChangeLogToken, Boolean hasMoreItems, Integer numItems }

Versioning Services:

checkOut(Id repositoryId, Id objectId) -> { Id objectId, Boolean contentCopied } // creates private working copy
cancelCheckOut(Id repositoryId, Id objectId) // deletes private working copy
checkIn(Id repositoryId, Id objectId, major=true, <Array> Property properties=[], <contentStream> contentStream, String checkinComment, <Array> Id policies, <Array> ACE addACEs, <Array> ACE removeACEs) -> Id objectId
getObjectOfLatestVersion // not implemented
getPropertiesOfLatestVersion // not implemented
getAllVersions(Id repositoryId, Id versionSeriesId, String filter=null, Boolean includeAllowableActions=false) -> <Array> ObjectResults objects

Relationship Services:
getObjectRelationships(Id repositoryId, Id objectId, Boolean includeSubRelationshipTypes=false,, Enum relationshipDirection (source, target, either), Id typeId=null, Integer maxItems=null, Integer skipCount=null, String filter=null, Boolean includeAllowableActions=false) -> { <Array> Object objects, Boolean hasMoreItems, Integer numItems }

Policy Services:

applyPolicy(Id repositoryId, Id policyId, Id objectId)
removePolicy(Id repositoryId, Id policyId, Id objectId)
getAppliedPolicies(Id repositoryId, Id policyId, String filter) -> <Array> Object objects

ACL Services
applyACL(Id repositoryId, Id objectId, <Array> AccessControlEntryType addACEs=[], <Array> AccessControlEntryType removeACEs=[], Enum ACLPropagation (objectonly, propagate, repositorydetermined) =null) -> { <Array> AccessControlEntryType acl, Boolean exact }
getACL(Id repositoryId, Id objectId, Boolean onlyBasicPermissions) -> { <Array> AccessControlEntryType acl, Boolean exact }
