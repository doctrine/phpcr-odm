<?php

namespace Doctrine\ODM\PHPCR\Tools\Helper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;
use Doctrine\ODM\PHPCR\Translation\Translation;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\ChildTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\NonTranslatedStrategy;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Migrate a field that has become translated or changed its translation
 * strategy.
 *
 * To avoid problems with large repositories, the converter is operating in a
 * batched mode. Call it repeatedly and call flush and clear between calls.
 *
 * You need to call `save()` on the PHPCR session, rather than `flush()` on
 * the document maanger. To avoid unexpected results, it is recommended to
 * `clear()` the document manager before starting to convert and after having
 * saved the PHPCR session.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class TranslationConverter
{
    /**
     * @var DocumentManagerInterface
     */
    private $dm;

    /**
     * @var int Number of documents to process per batch.
     */
    private $batchSize;

    /**
     * @var array
     */
    private $notices = array();

    /**
     * @param DocumentManagerInterface $dm
     * @param int                      $batchSize
     */
    public function __construct(DocumentManagerInterface $dm, $batchSize = 200)
    {
        $this->dm = $dm;
        $this->batchSize = $batchSize;
    }

    /**
     * Migrate content into the new translation format and remove the old properties.
     *
     * This does not commit the changes to the repository. Call save on the
     * PHPCR session *after each batch*. Calling flush on the document manager
     * *is not enough*.
     *
     * When translating, the new properties are copied into the languages
     * specified in $locales. When un-translating, the current locale and
     * language fallback is used, and $locales is ignored.
     *
     * To convert all fields into a translation, you can pass an empty array
     * for $fields and the information is read from the metadata. The fields
     * are mandatory when converting fields back to non-translated.
     *
     * To convert a single field from translated to non-translated, simply
     * specify that field.
     *
     * If you convert an existing translation, you need to specify the name of
     * the strategy that was previously used. The name is the one you would use
     * for DocumentManagerInterface::getTranslationStrategy, so "attribute" or
     * "child".
     *
     * The current strategy is read from the document metadata.
     *
     * Only documents that match $class exactly are converted, but not
     * descendants. You can query whether there where documents encountered
     * that could not be converted by calling getLastNotices() after each call
     * to convert().
     *
     * @param string $class                FQN of the document class
     * @param array  $locales              Locales to copy previously untranslated fields into.
     *                                     Ignored when untranslating a document.
     * @param array  $fields               List of fields to convert. Required when making a
     *                                     field not translated anymore
     * @param string $previousStrategyName Name of previous strategy or "none" if field was not
     *                                     previously translated
     *
     * @return boolean true if there are more documents to convert and this method needs to be
     *                      called again.
     *
     * @throws PHPCRExceptionInterface if the document can not be found.
     *
     * @see getLastNotices()
     */
    public function convert(
        $class,
        $locales,
        array $fields = array(),
        $previousStrategyName = NonTranslatedStrategy::NAME
    ) {
        /** @var ClassMetadata $currentMeta */
        $currentMeta = $this->dm->getClassMetadata($class);
        $currentStrategyName = $currentMeta->translator ?: NonTranslatedStrategy::NAME;

        // sanity check strategies
        if ($currentStrategyName === $previousStrategyName) {
            $message = 'Previous and current strategy are the same.';
            if (NonTranslatedStrategy::NAME === $currentStrategyName) {
                $message .= ' To untranslate a document, you need to specify the previous translation strategy';
            } else {
                $message .= sprintf(' Document is currently at %s', $currentStrategyName);
            }
            throw new InvalidArgumentException($message);
        }
        if (!count($locales) && NonTranslatedStrategy::NAME !== $currentStrategyName) {
            throw new InvalidArgumentException('When converting to translated content, the locales must be specified.');
        }

        $this->notices = array();
        $translated = null;
        foreach ($fields as $field) {
            $current = !empty($currentMeta->mappings[$field]['translated']);
            if (null !== $translated && $current !== $translated) {
                throw new InvalidArgumentException(sprintf(
                    'The list of specified fields %s contained both translated and untranslated fields. If you want to move back to untranslated, specify only the untranslated fields.',
                    implode(', ', $fields)
                ));
            }
            $translated = $current;
        }
        $partialUntranslate = false;
        if (false === $translated && NonTranslatedStrategy::NAME !== $currentStrategyName) {
            // special case, convert fields back to untranslated
            $partialUntranslate = true;
            $previousStrategyName = $currentStrategyName;
            $currentStrategyName = NonTranslatedStrategy::NAME;
            $currentMeta->translator = null;
        }

        $currentStrategy = NonTranslatedStrategy::NAME === $currentStrategyName
            ? new NonTranslatedStrategy($this->dm)
            : $this->dm->getTranslationStrategy($currentMeta->translator);

        if (NonTranslatedStrategy::NAME === $previousStrategyName) {
            $previousStrategy = new NonTranslatedStrategy($this->dm);
        } else {
            $previousStrategy = $this->dm->getTranslationStrategy($previousStrategyName);
        }

        if (!$fields) {
            if (NonTranslatedStrategy::NAME === $currentStrategyName) {
                throw new InvalidArgumentException('To untranslate a document, you need to specify the fields that where previously translated');
            }
            $fields = $currentMeta->translatableFields;
        }

        // trick query into using the previous strategy
        $currentMeta->translator = NonTranslatedStrategy::NAME === $previousStrategyName ? null : $previousStrategyName;

        if (NonTranslatedStrategy::NAME === $currentStrategyName) {
            $currentMeta->translatableFields = $fields;
            foreach ($fields as $field) {
                $currentMeta->mappings[$field]['translated'] = true;
            }
        }

        $qb = $this->dm->createQueryBuilder();
        $or = $qb->fromDocument($class, 'd')
            ->where()->orX();
        foreach ($fields as $field) {
            $or->fieldIsset('d.'.$field);
        }
        $qb->setMaxResults($this->batchSize);
        $documents = $qb->getQuery()->execute();

        // restore meta data to the real thing
        $currentMeta->translator = NonTranslatedStrategy::NAME === $currentStrategyName ? null : $currentStrategyName;
        if (NonTranslatedStrategy::NAME === $currentStrategyName) {
            $currentMeta->translatableFields = array();
            foreach ($fields as $field) {
                unset($currentMeta->mappings[$field]['translated']);
            }
        }

        // fake metadata for previous
        $previousMeta = clone $currentMeta;
        $previousMeta->translator = NonTranslatedStrategy::NAME === $previousStrategyName ? null : $previousStrategyName;
        // even when previously not translated, we use translatableFields for the NonTranslatedStrategy
        $previousMeta->translatableFields = $fields;

        foreach ($documents as $document) {
            if (ClassUtils::getClass($document) !== $class) {
                $path = $this->dm->getUnitOfWork()->getDocumentId($document);
                $this->notices[$path] = ClassUtils::getClass($document);

                continue;
            }
            $this->convertDocument(
                $document,
                $previousStrategy,
                $previousMeta,
                $currentStrategy,
                $currentMeta,
                $fields,
                $locales,
                $partialUntranslate
            );
        }

        return count($documents) === $this->batchSize;
    }

    /**
     * Get list of child documents that had to be skipped because their class
     * was not an exact match with the class being converted.
     *
     * @return array Map of path => class
     */
    public function getLastNotices()
    {
        return $this->notices;
    }

    /**
     * @param object                       $document           The document to convert
     * @param TranslationStrategyInterface $previousStrategy   Translation strategy to remove fields from old location
     * @param ClassMetadata                $previousMeta       Metadata for old translation strategy
     * @param TranslationStrategyInterface $currentStrategy    Translation strategy to save new translations
     * @param ClassMetadata                $currentMeta        Metadata for new translation strategy
     * @param array                        $fields             The fields to handle
     * @param array                        $locales            Target locales to copy translations to.
     * @param bool                         $partialUntranslate Whether we are only a subset of fields back to untranslated
     */
    private function convertDocument(
        $document,
        TranslationStrategyInterface $previousStrategy,
        ClassMetadata $previousMeta,
        TranslationStrategyInterface $currentStrategy,
        ClassMetadata $currentMeta,
        array $fields,
        array $locales,
        $partialUntranslate
    ) {
        $node = $this->dm->getNodeForDocument($document);

        $data = array();
        foreach ($fields as $field) {
            $data[$field] = $currentMeta->getFieldValue($document, $field);
        }

        if ($currentStrategy instanceof NonTranslatedStrategy) {
            $currentStrategy->saveTranslation($data, $node, $currentMeta, null);
        } else {
            foreach ($locales as $locale) {
                $currentStrategy->saveTranslation($data, $node, $currentMeta, $locale);
            }
        }

        if ($partialUntranslate && $previousStrategy instanceof ChildTranslationStrategy) {
            // the child translation strategy would remove the whole child node
            foreach ($previousStrategy->getLocalesFor($document, $node, $previousMeta) as $locale) {
                $translation = $node->getNode(Translation::LOCALE_NAMESPACE.':'.$locale);
                foreach ($fields as $field) {
                    $translation->setProperty($previousMeta->mappings[$field]['property'], null);
                }
            }
        } else {
            $previousStrategy->removeAllTranslations($document, $node, $previousMeta);
        }
    }
}
