Changelog
=========

2013-05-13
----------

 * #279: Cleanup mapping names
   The option to overwrite what PHPCR property a value is stored in
   (analogue to the ORM "column") is now called "property" instead of
   "name" for all mapping drivers. For Child mappings, the annotation
   and yml fields are now called "nodeName" instead of "name", for XML
   it is the node-name attribute.
   The XML and yml mappings now use "name" instead of "fieldName" to
   identify the property of the model class they are mapping.

   For the XML mappings, mixins are now collected inside a <mixins>
   element and the attribute specifying the mxin type name is renamed
   from "name" to "type". The document primary type attribute is fixed
   from "nodeType" to "node-type" and parentdocument became
   parent-document.

2013-03-06
----------

 * #252: Split Referrers mapping into MixedReferrers and Referrers.
   The MixedReferrers has the semantics of what was until now Referrers, but
   does not have the `filter` attribute anymore. A read only view of all kinds
   of documents that reference this document.
   Referrers now requires the `referringDocument` that identifies the Document
   class that refers to this Document, and `referencedBy` to name the referring
   field of the Document. Referrers now supports the cascading options, just
   like ReferenceMany.
   For mixed situations, there is still DocumentManager::getReferrers() to get
   all documents for a certain reference property name.

2013-01-18
----------

 * Removed DocumentRepository->getDocumentsByQuery as it is no longer needed.
   createQuery returns you an ODM query that can find documents directly.
   To get documents from a PHPCR query there is DocumentManager->getDocumentsByPhpcrQuery()

2013-01-13
----------

 * DocumentManager->getQueryBuilder now returns an ODM QueryBuilder and
   NOT the PHPCR QueryBuilder. The PHPCR QueryBuilder is still available via.
   DocumentManager->getPhpcrQueryBuilder().
 * The results of ->execute() on the new ODM query are Documents by default.
   It is possible to obtain the PHPCR nodes by using ->getPhpcrNodeResult() or
   ->execute(Query::HYDRATE_PHPCR);
 * CreateQuery($statement, $language) has NOT been implemented in the new query builder.
   It is, however, still available in the DocumentManager.
 * DocumetManager->getDocumentsByQuery renamed to getDocumentsByPhpcrQuery()
