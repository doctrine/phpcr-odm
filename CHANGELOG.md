Changelog
=========

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
