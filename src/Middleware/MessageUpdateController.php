<?php

namespace RcmI18n\Middleware;

use Doctrine\ORM\EntityManager;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RcmHtmlPurifier\Api\Purify;
use RcmI18n\Api\Acl\IsAllowed;
use RcmI18n\Entity\Message;
use RcmI18n\Model\Locales;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @author James Jervis - https://github.com/jerv13
 */
class MessageUpdateController implements MiddlewareInterface
{
    /**
     * @var IsAllowed
     */
    protected $isAllowed;

    /**
     * @var Purify
     */
    protected $purify;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $messageRepository;

    /**
     * @var Locales
     */
    protected $locales;

    /**
     * @param IsAllowed     $isAllowed
     * @param Purify        $purify
     * @param EntityManager $entityManager
     * @param Locales       $locales
     */
    public function __construct(
        IsAllowed $isAllowed,
        Purify $purify,
        EntityManager $entityManager,
        Locales $locales
    ) {
        $this->isAllowed = $isAllowed;
        $this->purify = $purify;
        $this->entityManager = $entityManager;
        $this->messageRepository = $entityManager->getRepository(Message::class);
        $this->locales = $locales;
    }

    /**
     * process
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
        if (!$this->isAllowed(
            $request,
            ['privilege' => 'update']
        )
        ) {
            return new JsonResponse(null, 401);
        }
        $id = $request->getAttribute('rcmi18n-message-id');
        $locale = $request->getAttribute('rcmi18n-locale');
        $data = $request->getParsedBody();

        // @todo Validate input

        /**
         * White-list local and default test to make sure nothing funny is
         * making its way to the DB. All default text's used must already exist
         * in the en_US locale
         */
        $usMessage = $this->messageRepository->findOneBy(
            ['locale' => 'en_US', 'defaultText' => $data['defaultText']]
        );

        if (!$this->locales->localeIsValid($locale)) {
            return new JsonResponse(
                [
                    'error' => 'invalid locale'
                ],
                400
            );
        } elseif (!$usMessage instanceof Message) {
            return new JsonResponse(
                [
                    'error' => 'invalid defaultText'
                ],
                400
            );
        }

        /**
         * Purify text to make sure nothing funny is making its way to the DB
         */
        $cleanText = $this->purify($data['text']);

        $message = $this->messageRepository->findOneBy(
            ['locale' => $locale, 'messageId' => $id]
        );

        if ($message instanceof Message) {
            $message->setText($cleanText);
        } else {
            $message = new Message();
            $message->setLocale($locale);
            $message->setDefaultText($usMessage->getDefaultText());
            $message->setText($cleanText);

            $this->entityManager->persist($message);
        }
        $this->entityManager->flush($message);

        return new JsonResponse($message->jsonSerialize());
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $options
     *
     * @return bool
     */
    protected function isAllowed(
        ServerRequestInterface $request,
        array $options = []
    ) {
        return $this->isAllowed->__invoke(
            $request,
            $options
        );
    }

    /**
     * @param $html
     *
     * @return string
     */
    protected function purify($html)
    {
        return $this->purify->__invoke(
            $html
        );
    }
}
