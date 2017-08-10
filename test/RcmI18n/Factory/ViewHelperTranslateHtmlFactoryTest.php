<?php

namespace RcmI18nTest\ViewHelper;

use RcmI18n\Factory\ViewHelperTranslateHtmlFactory;
use Zend\I18n\Translator\LoaderPluginManager;
use Zend\ServiceManager\ServiceManager;

require __DIR__ . '/../../autoload.php';

class ViewHelperTranslateHtmlFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \RcmI18n\Factory\ViewHelperTranslateHtmlFactory
     */
    function testCreateService()
    {
        $sm = new ServiceManager();
        $sm->setService(
            'RcmHtmlPurifier',
            $this->getMockBuilder('\HtmlPurifier')
                ->disableOriginalConstructor()
                ->setMethods(['purify'])
                ->getMock()
        );
        $viewSm = new LoaderPluginManager();
        $viewSm->setServiceLocator($sm);
        $unit = new ViewHelperTranslateHtmlFactory();
        $this->assertInstanceOf(
            'RcmI18n\ViewHelper\TranslateHtml',
            $unit->createService($viewSm)
        );
    }
}