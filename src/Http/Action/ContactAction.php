<?php

declare(strict_types = 1);

namespace App\Http\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface;
use Xtreamwayz\HTMLFormValidator\FormFactory;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\InputFilter\Factory as InputFilterFactory;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;

class ContactAction implements MiddlewareInterface
{
    private $template;

    private $inputFilterFactory;

    private $mailTransport;

    private $logger;

    private $config;

    public function __construct(
        TemplateRendererInterface $template,
        InputFilterFactory $inputFilterFactory,
        TransportInterface $mailTransport,
        LoggerInterface $logger,
        array $config
    ) {
        $this->template           = $template;
        $this->inputFilterFactory = $inputFilterFactory;
        $this->mailTransport      = $mailTransport;
        $this->logger             = $logger;
        $this->config             = $config;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        /* @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        // Generate csrf token
        if (! $session->get('csrf')) {
            $session->set('csrf', md5(random_bytes(32)));
        }

        // Generate form and inject csrf token
        $form = new FormFactory($this->template->render('app::contact-form', [
            'token' => $session->get('csrf'),
        ]), $this->inputFilterFactory);

        // Validate form
        $validationResult = $form->validateRequest($request);
        if ($validationResult->isValid()) {
            // Get filter submitted values
            $data = $validationResult->getValues();

            $this->logger->notice('Sending contact mail to {from} <{email}> with subject "{subject}": {body}', $data);

            // Create the message
            $message = new Message();
            $message->setFrom($this->config['from'])
                ->setReplyTo($data['email'], $data['name'])
                ->setTo($this->config['to'])
                ->setSubject('[xtreamwayz-contact] ' . $data['subject'])
                ->setBody($data['body']);

            $this->mailTransport->send($message);

            // Display thank you page
            return new HtmlResponse($this->template->render('app::contact-thank-you'), 200);
        }

        // Display form and inject error messages and submitted values
        return new HtmlResponse($this->template->render('app::contact', [
            'form' => $form->asString($validationResult),
        ]), 200);
    }
}
