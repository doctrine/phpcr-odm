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
    protected $orderEn = array('en', 'de');
    protected $orderDe = array('de', 'en');
    protected $mockMetadata;

    public function setUp()
    {
        $this->mockMetadata = $this->getMockBuilder('\Doctrine\ODM\PHPCR\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $this->localeChooser = new LocaleChooser(array('en' => $this->orderEn,
                                                       'de' => $this->orderDe),
                                                 'en');
    }


    public function testGetPreferredLocalesOrder()
    {
        $orderDe = $this->localeChooser->getPreferredLocalesOrder(null, $this->mockMetadata, 'de');
        $this->assertEquals($this->orderDe, $orderDe);
        $orderEn = $this->localeChooser->getPreferredLocalesOrder(null, $this->mockMetadata, 'en');
        $this->assertEquals($this->orderEn, $orderEn);
        $this->localeChooser->setLocale('de');
        $orderDe = $this->localeChooser->getPreferredLocalesOrder(null, $this->mockMetadata);
        $this->assertEquals($this->orderDe, $orderDe);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetPreferredLocalesOrderNonexisting()
    {
        $this->localeChooser->getPreferredLocalesOrder(null, $this->mockMetadata, 'notexisting');
    }

    public function testGetDefaultLocalesOrder()
    {
        $this->localeChooser->setLocale('de'); // default should not use current locale but default locale
        $orderEn = $this->localeChooser->getDefaultLocalesOrder();
        $this->assertEquals($this->orderEn, $orderEn);
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
     * @expectedException InvalidArgumentException
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
}