<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

class LocaleChooserTest extends PHPCRTestCase
{

    /**
     * @var LocaleChooser
     */
    protected $localeChooser;
    protected $orderEn = array('de');
    protected $orderDe = array('en');
    protected $mockMetadata;

    public function setUp()
    {
        $this->mockMetadata = $this->getMockBuilder('\Doctrine\ODM\PHPCR\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $this->localeChooser = new LocaleChooser(array('en' => $this->orderEn, 'de' => $this->orderDe), 'en');
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

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Translation\MissingTranslationException
     */
    public function testGetFallbackLocalesNonexisting()
    {
        $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'notexisting');
    }

    public function testGetDefaultLocalesOrder()
    {
        $this->localeChooser->setLocale('de'); // default should not use current locale but default locale
        $orderEn = $this->localeChooser->getDefaultLocalesOrder();
        $this->assertEquals(array('en', 'de'), $orderEn);
    }

    public function testGetLocale()
    {
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('en', $locale);
        $this->localeChooser->setLocale('de');
        $locale = $this->localeChooser->getLocale();
        $this->assertEquals('de', $locale);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Translation\MissingTranslationException
     */
    public function testSetLocaleNonexisting()
    {
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
}