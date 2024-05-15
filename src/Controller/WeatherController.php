<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Exception;

class WeatherController extends AbstractController
{
    private WeatherService $weatherService;
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(WeatherService $weatherService, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->weatherService = $weatherService;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'weather_home')]
    public function index(Request $request): Response
    {
        $weatherData = [];
        $error = null;
        $favoriteCities = ['London']; // Default city if no user is logged in or no favorite cities are set.
        $selectedCity = $request->query->get('city') ?: $request->request->get('city');
        $favoriteAction = $request->request->get('favorite_action');

        $token = $this->tokenStorage->getToken();
        if ($token && $user = $token->getUser()) {
            $favoriteCities = array_filter([
                $user->getFavoriteCity1(),
                $user->getFavoriteCity2(),
                $user->getFavoriteCity3(),
            ]); // Remove any null entries.
        }

        if ($selectedCity) {
            try {
                $data = $this->weatherService->getWeatherData($selectedCity);
                if (isset($data['main']['temp'])) {
                    $weatherData[$selectedCity] = [
                        'temperature' => $data['main']['temp'],
                        'forecast' => $this->weatherService->getForecastData($data['coord']['lat'], $data['coord']['lon'], $selectedCity)['list'],
                    ];
                } else {
                    throw new Exception("Invalid data received from the weather API.");
                }

                if ($user && $request->isMethod('post')) {
                    if ($favoriteAction === 'add') {
                        if (!in_array($selectedCity, $favoriteCities)) {
                            if (count($favoriteCities) >= 3) {
                                $error = "Vous devez retirer une ville de vos favoris avant de pouvoir en ajouter une nouvelle. <a class='close-pop-up-here' href='#' id='close-error'>(Cliquer ici pour fermer)</a>";
                            } else {
                                // Ajouter la ville aux favoris
                                if (!$user->getFavoriteCity1()) {
                                    $user->setFavoriteCity1($selectedCity);
                                } elseif (!$user->getFavoriteCity2()) {
                                    $user->setFavoriteCity2($selectedCity);
                                } else {
                                    $user->setFavoriteCity3($selectedCity);
                                }
                                $this->entityManager->flush();
                            }
                        }
                    } elseif ($favoriteAction === 'remove') {
                        // Retirer la ville des favoris
                        if ($user->getFavoriteCity1() === $selectedCity) {
                            $user->setFavoriteCity1(null);
                        } elseif ($user->getFavoriteCity2() === $selectedCity) {
                            $user->setFavoriteCity2(null);
                        } elseif ($user->getFavoriteCity3() === $selectedCity) {
                            $user->setFavoriteCity3(null);
                        }
                        $this->entityManager->flush();
                    }
                }
            } catch (Exception $e) {
                if (!$weatherData) {
                    // N'affiche l'erreur que s'il n'y a pas de données trouvées
                    $error = "Une erreur s'est produite lors de la récupération des données météorologiques. Veuillez réessayer.";
                }
            }
        }

        return $this->render('weather/index.html.twig', [
            'favoriteCities' => $favoriteCities,
            'selectedCity' => $selectedCity,
            'weatherData' => $weatherData,
            'error' => $error,
        ]);
    }
}
