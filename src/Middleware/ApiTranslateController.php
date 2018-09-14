<?php

namespace RcmI18n\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Filter\StringTrim;
use Zend\Filter\StripTags;
use Zend\I18n\Translator\TranslatorInterface;

/**
 * @author James Jervis - https://github.com/jerv13
 */
class ApiTranslateController implements MiddlewareInterface
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * getTranslator
     *
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->translator;
    }

    /**
     * __invoke
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable|null          $next
     *
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        DelegateInterface $delegate = null
    ) {
        $translator = $this->getTranslator();

        // @todo Might be a better way to prevent spamming
        // We ignore events so we don't get spammed.
        $translator->disableEventManager();

        $trimFilter = new StringTrim();

        $stripTagsFilter = new StripTags();

        $namespace = (string)$request->getAttribute('rcmi18n-namespace', 'default');

        $translationParams = $request->getQueryParams();

        $translations = [];

        foreach ($translationParams as $message) {
            $message = (string)urldecode($message);
            // Clean
            $message = $stripTagsFilter->filter($message);
            $message = $trimFilter->filter($message);

            $translations[$message] = $translator->translate($message, $namespace);
        }

        return new JsonResponse($translations);
    }
}
