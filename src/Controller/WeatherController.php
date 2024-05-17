<?php
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
        $defaultCities = ['Paris', 'Berlin', 'New York', 'Moscou', 'Tokyo', 'Sydney'];
        $weatherData = [];
        $error = null;
        $favoriteCities = [];
        $selectedCity = $request->query->get('city') ?: $request->request->get('city');
        $favoriteAction = $request->request->get('favorite_action');
        $columnPreferences = [];
        $chartData = ['labels' => [], 'temperature' => [], 'windSpeed' => []];

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if ($user) {
            $favoriteCities = array_filter([
                $user->getFavoriteCity1(),
                $user->getFavoriteCity2(),
                $user->getFavoriteCity3(),
            ]);
            $columnPreferences = $user->getColumnPreferences() ?: [];
        }

        if ($selectedCity) {
            try {
                $data = $this->weatherService->getWeatherData($selectedCity);
                if (isset($data['main']['temp'])) {
                    $forecastData = $this->weatherService->getForecastData($data['coord']['lat'], $data['coord']['lon'], $selectedCity)['list'];
                    $weatherData[$selectedCity] = $this->prepareCurrentWeatherData($data);
                    $weatherData[$selectedCity]['forecast'] = $this->prepareForecastData($forecastData);
                    $forecastDataGrouped = $this->aggregateForecastData($forecastData);

                    $chartData = $this->prepareChartData($forecastData); // Préparer les données du graphique

                } else {
                    throw new Exception("Invalid data received from the weather API.");
                }

                $this->handleFavoriteActions($request, $user, $selectedCity, $favoriteAction, $favoriteCities);
            } catch (Exception $e) {
                $error = "Une erreur s'est produite lors de la récupération des données météorologiques: " . $e->getMessage();
            }
        }

        // Charger les données météorologiques des villes par défaut
        foreach ($defaultCities as $city) {
            if (!isset($weatherData[$city])) {
                try {
                    $data = $this->weatherService->getWeatherData($city);
                    $weatherData[$city] = $this->prepareCurrentWeatherData($data);
                } catch (Exception $e) {
                    $error = "Une erreur s'est produite lors de la récupération des données météorologiques pour la ville de $city: " . $e->getMessage();
                }
            }
        }

        if (!$columnPreferences) {
            // Initialisation statique, vous pouvez ajuster ceci en fonction des besoins
            $columnPreferences = ['temp_min', 'temp_max', 'wind_speed', 'humidity', 'pressure'];
        }

        foreach ($favoriteCities as $city) {
            if (!isset($weatherData[$city])) {
                $data = $this->weatherService->getWeatherData($city);
                if (isset($data['main']['temp'])) {
                    $forecastData = $this->weatherService->getForecastData($data['coord']['lat'], $data['coord']['lon'], $city)['list'];

                    $weatherData[$city] = [
                        'temperature' => round($data['main']['temp']),
                        'humidity' => round($data['main']['humidity']),
                        'pressure' => round($data['main']['pressure']),
                        'wind_speed' => round($data['wind']['speed']),
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
                }
            }
        }

        return $this->render('weather/index.html.twig', [
            'favoriteCities' => $favoriteCities,
            'selectedCity' => $selectedCity,
            'weatherData' => $weatherData,
            'error' => $error,
            'columnPreferences' => $columnPreferences,
            'forecastDataGrouped' => $forecastDataGrouped ?? [],
            'chartData' => $chartData // Passer les données du graphique
        ]);
    }


    private function prepareChartData($forecastData)
    {
        $now = new \DateTime();
        $tomorrow = (clone $now)->modify('+24 hours');

        $labels = [];
        $temperatures = [];
        $windSpeeds = [];

        foreach ($forecastData as $data) {
            $dateTime = new \DateTime($data['dt_txt']);
            if ($dateTime >= $now && $dateTime <= $tomorrow) {
                $labels[] = $dateTime->format('H:i');
                $temperatures[] = $data['main']['temp'];
                $windSpeeds[] = $data['wind']['speed'];
            }
        }

        return [
            'labels' => $labels,
            'temperature' => $temperatures,
            'windSpeed' => $windSpeeds
        ];
    }

    private function prepareCurrentWeatherData($data)
    {
        return [
            'temperature' => $data['main']['temp'],
            'humidity' => $data['main']['humidity'],
            'pressure' => $data['main']['pressure'],
            'wind_speed' => $data['wind']['speed'],
            'wind_direction' => $data['wind']['deg'],
            'cloudiness' => $data['clouds']['all'],
            'description' => $data['weather'][0]['description'],
            'icon' => $data['weather'][0]['icon']
        ];
    }

    private function prepareForecastData($forecastData)
    {
        return array_map(function($item) {
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
        }, $forecastData);
    }

    private function aggregateForecastData($forecastData)
    {
        $dateWeek = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];

        $forecastDataGrouped = [];
        foreach ($forecastData as $item) {
            $dateTime = new \DateTime($item['dt_txt']);
            $dateW = $dateWeek[$dateTime->format('l')];

            if (!isset($forecastDataGrouped[$dateW])) {
                $forecastDataGrouped[$dateW] = [
                    'temp_min' => $item['main']['temp_min'],
                    'temp_max' => $item['main']['temp_max'],
                    'icons' => [$item['weather'][0]['icon']],
                    'wind_speed' => $item['wind']['speed'],
                    'humidity' => $item['main']['humidity'],
                    'pressure' => $item['main']['pressure']
                ];
            } else {
                $forecastDataGrouped[$dateW]['temp_min'] = min($forecastDataGrouped[$dateW]['temp_min'], $item['main']['temp_min']);
                $forecastDataGrouped[$dateW]['temp_max'] = max($forecastDataGrouped[$dateW]['temp_max'], $item['main']['temp_max']);
                $forecastDataGrouped[$dateW]['icons'][] = $item['weather'][0]['icon'];
            }
        }

        foreach ($forecastDataGrouped as $dateW => &$data) {
            $iconCounts = array_count_values($data['icons']);
            arsort($iconCounts);
            $data['icon'] = key($iconCounts);
            unset($data['icons']);
        }

        return $forecastDataGrouped;
    }

    private function handleFavoriteActions(Request $request, $user, $selectedCity, $favoriteAction, &$favoriteCities)
    {
        if ($user && $request->isMethod('post')) {
            if ($favoriteAction === 'add' && !in_array($selectedCity, $favoriteCities)) {
                if (count($favoriteCities) >= 3) {
                    $this->addFlash('error', 'Nombre maximum de villes favorites atteint.');
                } else {
                    $this->addCityToFavorites($user, $selectedCity);
                }
            } elseif ($favoriteAction === 'remove') {
                $this->removeCityFromFavorites($user, $selectedCity);
            }
            $this->entityManager->flush();
        }
    }

    private function addCityToFavorites($user, $city)
    {
        if (!$user->getFavoriteCity1()) {
            $user->setFavoriteCity1($city);
        } elseif (!$user->getFavoriteCity2()) {
            $user->setFavoriteCity2($city);
        } else {
            $user->setFavoriteCity3($city);
        }
    }


    private function removeCityFromFavorites($user, $city)
    {
        if ($user->getFavoriteCity1() === $city) {
            $user->setFavoriteCity1(null);
        } elseif ($user->getFavoriteCity2() === $city) {
            $user->setFavoriteCity2(null);
        } elseif ($user->getFavoriteCity3() === $city) {
            $user->setFavoriteCity3(null);
        }
    }

    #[Route('/save_column_preferences', name: 'save_column_preferences', methods: ['POST'])]
    public function saveColumnPreferences(Request $request): JsonResponse
    {
        $preferences = json_decode($request->getContent(), true)['preferences'];

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if ($user) {
            $user->setColumnPreferences($preferences);
            $this->entityManager->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }
}
