Changelog
=========

1.2.0
-----

1.2.0 final

1.2.0-rc6
---------

minor fixes related to translations

* **2014-10-07**: **BC break** dropped the pre/postBindTranslation events as they were previously
                  not clearly defined when they would be triggered. they are replaced by
                  preCreateTranslation which is triggered only before a new translation is added.
* **2014-10-06**: we no longer unload translations after flush(). furthermore when doing multiple
                  find() calls on a translated document we no longer fresh the state, so any
                  changes to the document state that have not been persisted will no longer be lost

1.2.0-rc5
---------

bug fixing in translation handling

1.2.0-rc4
---------

bug fixing in translation handling

1.2.0-rc3
---------

performance optimizations with translations

1.2.0-rc2
---------

* **2014-08-07**: properly handle fields mapped to nodename in DocumentRepository::findBy()
  Report error when trying to find or order by association fields, instead of building
  queries that won't work.

1.2.0-RC1
---------

prepare 1.2.0-RC1

* **2014-07-02**: convert all collections to PersistentCollections on flush
  This required a considerable refactoring in the collections and also resulted in some BC breaks.
  Most notably Collections are now only managing the documents that have explicitly been assigned
  or that where fetched initially from the PHPCR store.
  Use the new ``$collection->refresh()`` method to force an immediate refresh from the PHPCR store.
* **2014-06-13**: Added the endFlush event that happens after the flush has
  been terminated.
* **2014-06-13**: Fixed a bug in calculateChangeSet that led to subsequent
  flushes seeing already persisted changes again.

1.1.1
-----

Release 1.1.1

* **2014-06-03**: Fix ClassMetadata::newInstance() for PHP 5.5.13.
* **2014-05-10**: Cleanup on query builder, invalid alias will now be detected
  earlier.

1.1.0
-----

Release 1.1.0

* **2014-04-29**: The provided documents now use getParentDocument and
  setParentDocument to avoid clashes with domain parent logic and to be
  consistent with the @ParentDocument annotation. Deprecated the getParent and
  setParent in HierarchyInterface.

* **2014-04-19**: add events for translation lifecycle: pre-/postBindTranslation,
    pre-/postRemoveTranslation and postLoadTranslation and its callbacks

1.1.0-RC2
---------

Second release candidate 1.1

* **2014-03-31**: Make findTranslation() consistent with find()
* **2014-03-17**: Allow setting of the locales dynamically
* **2014-03-14**: Set UUID on documents on persist already.

1.1.0-RC1
---------

First release candidate 1.1

* **2014-03-14**: Lots of invalid PHPCR node name situations are now detected
  and fail early and with a clearer message.

* **2014-03-01**: The LocaleChooserInterface got a new method setFallbackLocales
  which allows to update the fallback for a specific locale.

* **2014-02-28**: Id strategy is also properly used when persisting children
  during a flush.

* **2014-02-02**: DocumentManager::find()/findMany now actually validate the
  requested class name. If the class name determined by the DocumentClassMapper
  is not instance of the requested class name, null is returned. As previously,
  you can pass `null` as first argument to find content regardless of its
  class.
  Previously, you sometimes got the content mapped into the requested class
  regardless of the content at $id, while in other situations you got a
  PHPCRException.

1.1.0-beta1
-----------

Beta release for the new features, still pending important fixes.

* **2014-01-08**: The identifier mapping got cleaned up. If you have documents
  with invalid mappings in your codebase, this will now be reported when you
  build the proxies. Before, you would have gotten an error later at runtime,
  when you try to actually use the invalid documents.

* **2014-01-05**: The Configuration class now supports setting a closure for
  the `UuidGenerator` to generate unique ids. If nothing is configured, the
  phpcr-utils UUIDHelper is used as before.

* **2013-12-21**: Document translations can be loaded even before the document
  is flushed. And a couple of bugfixes around loading translations.

* Lots of performance optimizations when loading collections.

1.0.1
-----

maintenance release of the 1.0 family with bugfixes

* **2013-11-01**: Enabled the doctrine:phpcr:mapping:info command. To actually
  use it, you need to update your cli-config.php file and add:

    $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver(
        new \Doctrine\Common\Annotations\AnnotationReader(),
         __DIR__ . '/lib/Doctrine/ODM/PHPCR/Document'
    );
    $config->setMetadataDriverImpl($driver);

1.0.0
-----

* **2013-10-10**: Depend on stable version of PHPCR

1.0.0-RC3
---------

* **2013-10-04**: Exception cleanup: Added a PHPCRExceptionInterface that all
  Exceptions implement. Standard exceptions now extend their base Exceptions.

  - Moved MissingTranslationException from Exception to Translation namespace
  - Removed Phpcr prefix from standard exceptions BadMethodCall and InvalidArgument
  - Added OutOfBounds and Runtime exceptions

1.0.0-RC2
---------

* **2013-09-27**: Cleaned up LocaleChooserInterface:
  - renamed getPreferredLocalesOrder to getFallbackLocales as this was not
    containing the primary language for a while now.
  - fixed getDefaultLocalesOrder to include the default locale again.
* **2013-09-27**: Removed deprecated custom events in favor of
  - Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs
  - Doctrine\Common\Persistence\Event\ManagerEventArgs
  - Doctrine\Common\Persistence\Event\LifecycleEventArgs
  Only the MoveEventArgs remain specific to PHPCR-ODM.
* **2013-09-27**: Removed deprecated legacy handling for the mapping that
  allowed using "name" instead of "property" to configure PHCPR property in mapping.

* **2013-09-26**: [Model] add HierarchyInterface for objects that resolve to
  nt:HierarchyNode, the method AbstractFile::addChild is
  changed to use the interface instead of AbstractFile as parameter.

1.0.0-RC1
---------

* **2013-09-13**: [QueryBuilder] Replaced query builder with new
  implementation. See documentation:
  http://docs.doctrine-project.org/projects/doctrine-phpcr-odm/en/latest/reference/query-builder.html

1.0.0-beta5
-----------

* **2013-08-16**: [Model] removed Doctrine\ODM\PHPCR\Document\Image and moved
  it to the CmfMediaBundle. Note that the CmfMediaBundle image is itself a file
  and no longer uses a file child.
* **2013-08-05**: [#314] properly validate nullable=false on flushing
  If you see exceptions about fields not being nullable, either adjust your
  mappings to say nullable=true or fix your code so they are not null.

1.0.0-beta4
-----------

* **2013-08-04**: [#304] fix various bugs around translation fallback
  Fixed translation loading to never return non-nullable
  fields set to null but fallback to the next. Also attribute translation will
  properly fall back again if a translation is not present.

* **2013-08-04**: [#305] Unset previously computed document change set if there are no changes
  fields set to null but fallback to the next. Also attribute translation will
  properly fall back again if a translation is not present.

1.0.0-beta3
-----------

* **2013-07-26**: [#301 & #299 & #296] use the referring property name and not the class field name
  This and a couple other PR fixed that we now use the configured PHPCR name
  when fetching referrers and defining references and not the document field
  name.

* **2013-07-23**: [#298] remove non-lifecycle-callbacks from schema, validate lifecyclce callbacks
  Cleanup and validation of lifecycle callbacks.

* **2013-07-02**: [#294] inherit document level options and allow mapped-superclass to have all options

* **2013-06-06**: [#291] fixed move by assignment

* **2013-05-27**: [#288] Event refactoring to use doctrine commons 2.4 events where possible
 * Use the doctrine common event argument classes where possible instead of
   custom classes. The classes `Doctrine\ODM\PHPCR\Event\LifecycleEventArgs`
   and `Doctrine\ODM\PHPCR\Event\ManagerEventArgs` and
   `Doctrine\ODM\PHPCR\Event\LoadClassMetadataEventArgs` are deprecated and
   will be deleted soon. Switch your code to depend on the classes in namespace
   `Doctrine\Common\Persistence\Event\` instead.
 * The only PHPCR-ODM specific event argument that will remain is MoveEventArgs
   because it has additional parameters and the move operation is PHPCR-ODM
   specific.

* **2013-05-13**: [#279] Cleanup mapping names
 * The option to overwrite what PHPCR property a value is stored in
   (analogue to the ORM "column") is now called "property" instead of
   "name" for all mapping drivers. For Child mappings, the annotation
   and yml fields are now called "nodeName" instead of "name", for XML
   it is the node-name attribute.
   The XML and yml mappings now use "name" instead of "fieldName" to
   identify the property of the model class they are mapping.
 * For the XML mappings, mixins are now collected inside a <mixins>
   element and the attribute specifying the mxin type name is renamed
   from "name" to "type". The document primary type attribute is fixed
   from "nodeType" to "node-type" and parentdocument became
   parent-document.

* **2013-03-06**: [#252] Split Referrers mapping into MixedReferrers and Referrers.
 * The MixedReferrers has the semantics of what was until now Referrers, but
   does not have the `filter` attribute anymore. A read only view of all kinds
   of documents that reference this document.
 * Referrers now requires the `referringDocument` that identifies the Document
   class that refers to this Document, and `referencedBy` to name the referring
   field of the Document. Referrers now supports the cascading options, just
   like ReferenceMany.
 * For mixed situations, there is still DocumentManager::getReferrers() to get
   all documents for a certain reference property name.

* **2013-01-18**: Removed DocumentRepository->getDocumentsByQuery as it is no longer needed.
   createQuery returns you an ODM query that can find documents directly.
 * To get documents from a PHPCR query there is DocumentManager->getDocumentsByPhpcrQuery()

* **2013-01-13**: Introduced ODM QueryBuilder and refactored PHPCR QueryBuilder
 * DocumentManager->getQueryBuilder now returns an ODM QueryBuilder and
   NOT the PHPCR QueryBuilder. The PHPCR QueryBuilder is still available via.
   DocumentManager->getPhpcrQueryBuilder().
 * The results of ->execute() on the new ODM query are Documents by default.
   It is possible to obtain the PHPCR nodes by using ->getPhpcrNodeResult() or
   ->execute(Query::HYDRATE_PHPCR);
 * CreateQuery($statement, $language) has NOT been implemented in the new query builder.
   It is, however, still available in the DocumentManager.
 * DocumentManager->getDocumentsByQuery renamed to getDocumentsByPhpcrQuery()