<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\ODM\PHPCR\Translation\MissingTranslationException;
use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class LocaleChooserTest extends PHPCRTestCase
{
    /**
     * @var LocaleChooser
     */
    private $localeChooser;

    private $orderEn = ['de'];

    private $orderDe = ['en'];

    /**
     * @var ClassMetadata|MockObject
     */
    private $mockMetadata;

    public function setUp(): void
    {
        $this->mockMetadata = $this->createMock(ClassMetadata::class);
        $this->localeChooser = new LocaleChooser(['en' => $this->orderEn, 'de' => $this->orderDe], 'en');
    }

    public function testGetFallbackLocales()
    {
        $orderDe = $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'de');
        $this->assertEquals($this->orderDe, $orderDe);
        $orderEn = $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'en');
        $this->assertEquals($this->orderEn, $orderEn);
        $this->localeChooser->setLocale('de');
        $orderDe = $this->localeChooser->getFallbackLocales(null, $this->mockMetadata);
        $this->assertEquals($this->orderDe, $orderDe);
    }

    public function testSetFallbackLocalesMerge()
    {
        $this->localeChooser->setFallbackLocales('de', ['fr'], false);
        $this->assertEquals(['fr', 'en'], $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'de'));
    }

    public function testSetFallbackLocalesReplace()
    {
        $this->localeChooser->setFallbackLocales('de', ['fr'], true);
        $this->assertEquals(['fr'], $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'de'));
    }

    public function testGetFallbackLocalesNonexisting()
    {
        $this->expectException(MissingTranslationException::class);
        $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'notexisting');
    }

    public function testGetDefaultLocalesOrder()
    {
        $this->localeChooser->setLocale('de'); // default should not use current locale but default locale
        $orderEn = $this->localeChooser->getDefaultLocalesOrder();
        $this->assertEquals(['en', 'de'], $orderEn);
    }

    public function testGetLocale()
    {
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('en', $locale);
        $this->localeChooser->setLocale('de');
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('de', $locale);
    }

    public function testSetLocaleNonexisting()
    {
        $this->expectException(MissingTranslationException::class);
        $this->localeChooser->setLocale('nonexisting');
    }

    public function testGetDefaultLocale()
    {
        $locale = $this->localeChooser->getDefaultLocale();
        $this->assertEquals('en', $locale);
    }

    public function testSetLocaleRegionNotConfigured()
    {
        $this->localeChooser->setLocale('en_GB');
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('en', $locale);
    }

    public function testSubRegion()
    {
        $orderEnGB = ['en', 'de'];
        $this->localeChooser = new LocaleChooser(['en_GB' => $orderEnGB, 'en' => $this->orderEn, 'de' => $this->orderDe], 'en');

        $order = $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'en_GB');
        $this->assertEquals($orderEnGB, $order);

        $this->localeChooser->setLocale('en_GB');
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('en_GB', $locale);
        $order = $this->localeChooser->getDefaultLocalesOrder();
        $this->assertEquals($orderEnGB, $order);
    }
}
