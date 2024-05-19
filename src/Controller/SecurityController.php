<?php
namespace App\Controller;

use App\Form\ChangeFirstNameFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($lastUsername === null) {
            $lastUsername = '';
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/profil', name: 'app_profil')]
    public function updateFirstName(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $firstNameForm = $this->createForm(ChangeFirstNameFormType::class, $user);
        $firstNameForm->handleRequest($request);

        if ($firstNameForm->isSubmitted() && $firstNameForm->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Prénom mis à jour avec succès');
            return $this->redirectToRoute('weather_home');
        }

        return $this->render('security/update_firstname.html.twig', [
            'firstNameForm' => $firstNameForm->createView(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode sera interceptée par la déconnexion sur votre pare-feu.');
    }
}
