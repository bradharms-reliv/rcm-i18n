<?php

namespace RcmI18n\Middleware;

use Doctrine\ORM\EntityManager;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RcmI18n\Entity\Message;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @author James Jervis - https://github.com/jerv13
 */
class MessagesController implements MiddlewareInterface
{
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $messageRepository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->messageRepository = $entityManager->getRepository(Message::class);
    }

    /**
     * process
     *
     * @param ServerRequestInterface $request
     * @param Delegate|null          $delegate
     *
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate = null
    ) {
        $locale = $request->getAttribute('rcmi18n-locale');

        $defaultMessages = $this->messageRepository->findBy(['locale' => 'en_US']);
        $localeMessages = $this->messageRepository->findBy(['locale' => $locale]);

        $translations = [];
        foreach ($defaultMessages as $defaultMessage) {
            /** @var \RcmI18n\Entity\Message $defaultMessage */
            $defaultText = $defaultMessage->getDefaultText();

            $text = null;
            $messageId = null;

            /** @var Message $localeMessage */
            foreach ($localeMessages as $localeMessage) {
                if ($localeMessage->getDefaultText() == $defaultText) {
                    $text = $localeMessage->getText();
                    $messageId = $localeMessage->getMessageId();
                    break;
                }
            }

            $translations[] = [
                'locale' => $locale,
                'defaultText' => $defaultText,
                'messageId' => $messageId,
                'text' => $text
            ];
        }

        return new JsonResponse($translations);
    }
}
