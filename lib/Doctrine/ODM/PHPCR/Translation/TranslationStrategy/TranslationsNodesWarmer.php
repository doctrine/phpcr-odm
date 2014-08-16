<?php


namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

/**
 * There a situations when is not very suitable to fetch each
 * translation one by one, for example when fetching many
 * nodes/documents. So this interface takes care to server
 * a method to get many translations by one call.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
interface TranslationsNodesWarmer
{
    /**
     * This method will return all translations by the locale,
     * but the main purpose of it is to warm up all translation
     * nodes in one.
     *
     * @param NodeInterface[] $nodes
     * @param array $locales
     * @param SessionInterface $session
     * @return mixed
     */
    public function getTranslationsForNodes($nodes, $locales, SessionInterface $session);

} 