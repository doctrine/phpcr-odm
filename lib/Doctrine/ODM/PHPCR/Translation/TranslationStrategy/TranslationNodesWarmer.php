<?php


namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

/**
 * When loading many translated documents, this could lead to a separate PHPCR request
 * for each translation. A translation strategy can implement this interface to be
 * noticed when many nodes are loaded and pre-fetch all of them in one go.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
interface TranslationNodesWarmer
{
    /**
     * This method will return all translations by the locale,
     * but the main purpose of it is to warm up all translation
     * nodes in one request to PHPCR.
     *
     * @param NodeInterface[] $nodes
     * @param array $locales
     * @param SessionInterface $session
     * @return mixed
     */
    public function getTranslationsForNodes($nodes, $locales, SessionInterface $session);

} 
