<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ErrorController extends AbstractController
{
    #[Route('/error', name: 'app_error')]
    public function showError(): Response
    {
        // Redirection vers la page d'accueil
        return $this->redirectToRoute('weather_home');
    }
}
