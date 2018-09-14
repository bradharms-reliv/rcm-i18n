<?php

namespace RcmI18n\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rcm\Service\SiteService;
use RcmI18n\Model\Locales;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @author James Jervis - https://github.com/jerv13
 */
class LocaleController implements MiddlewareInterface
{
    /**
     * @var Locales
     */
    protected $locales;

    /**
     * @var SiteService
     */
    protected $siteService;

    /**
     * @param Locales     $locales
     * @param SiteService $siteService
     */
    public function __construct(
        Locales $locales,
        SiteService $siteService
    ) {
        $this->locales = $locales;
        $this->siteService = $siteService;
    }

    /**
     * process
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface|null $delegate
     *
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        DelegateInterface $delegate = null
    ) {
        $site = $this->siteService->getCurrentSite(
            $request->getUri()->getHost()
        );

        return new JsonResponse(
            [
                'locales' => $this->locales->getLocales(),
                'currentSiteLocale' => $site->getLocale()
            ]
        );
    }
}
