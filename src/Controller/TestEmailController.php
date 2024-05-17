<?php

// src/Controller/TestEmailController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestEmailController extends AbstractController
{
    #[Route('/test/email')]
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('melvinbecue@gmail.com')
            ->to('melbecop@gmail.com')
            ->subject('Test Email from Symfony')
            ->text('This is a test email.');

        $mailer->send($email);

        return new Response('Email sent!');
    }
}

