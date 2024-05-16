<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $columnPreferences = [];

        $token = $this->tokenStorage->getToken();
        if ($token && $user = $token->getUser()) {
            $favoriteCities = array_filter([
                $user->getFavoriteCity1(),
                $user->getFavoriteCity2(),
                $user->getFavoriteCity3(),
            ]); // Remove any null entries.
            $columnPreferences = $user->getColumnPreferences() ?: [];
        }

        if ($selectedCity) {
            try {
                $data = $this->weatherService->getWeatherData($selectedCity);
                if (isset($data['main']['temp'])) {
                    $forecastData = $this->weatherService->getForecastData($data['coord']['lat'], $data['coord']['lon'], $selectedCity)['list'];
                    
                    // Adding the new data structure
                    $weatherData[$selectedCity] = [
                        'temperature' => $data['main']['temp'],
                        'humidity' => $data['main']['humidity'],
                        'pressure' => $data['main']['pressure'],
                        'wind_speed' => $data['wind']['speed'],
                        'wind_direction' => $data['wind']['deg'],
                        'cloudiness' => $data['clouds']['all'],
                        'description' => $data['weather'][0]['description'],
                        'icon' => $data['weather'][0]['icon'],
                        'forecast' => array_map(function($item) {
                            return [
                                'day_of_week' => (new \DateTime($item['dt_txt']))->format('l'),
                                'time' => (new \DateTime($item['dt_txt']))->format('H:i'),
                                'icon' => $item['weather'][0]['icon'],
                                'temp_min' => $item['main']['temp_min'],
                                'temp_max' => $item['main']['temp_max'],
                                'temp' => $item['main']['temp'],
                                'description' => $item['weather'][0]['description'],
                                'wind_speed' => $item['wind']['speed'],
                                'wind_direction' => $item['wind']['deg'],
                                'cloudiness' => $item['clouds']['all'],
                                'humidity' => $item['main']['humidity'],
                                'pressure' => $item['main']['pressure']
                            ];
                        }, $forecastData)
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
            'columnPreferences' => $columnPreferences,
        ]);
    }

    #[Route('/save_column_preferences', name: 'save_column_preferences', methods: ['POST'])]
    public function saveColumnPreferences(Request $request): JsonResponse
    {
        $preferences = json_decode($request->getContent(), true)['preferences'];

        $token = $this->tokenStorage->getToken();
        if ($token && $user = $token->getUser()) {
            $user->setColumnPreferences($preferences);
            $this->entityManager->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }
}
