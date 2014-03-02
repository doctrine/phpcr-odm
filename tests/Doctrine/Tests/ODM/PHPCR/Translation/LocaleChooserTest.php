<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

class LocaleChooserTest extends PHPCRTestCase
{

    /**
     * @var LocaleChooser
     */
    protected $localeChooser;
    protected $orderEn = array('de');
    protected $orderDe = array('en');
    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
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

    public function testSetFallbackLocalesMerge()
    {
        $this->localeChooser->setFallbackLocales('de', array('fr'), false);
        $this->assertEquals(array('fr', 'en'), $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'de'));
    }

    public function testSetFallbackLocalesReplace()
    {
        $this->localeChooser->setFallbackLocales('de', array('fr'), true);
        $this->assertEquals(array('fr'), $this->localeChooser->getFallbackLocales(null, $this->mockMetadata, 'de'));
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
