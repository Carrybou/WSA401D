<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WeatherController extends AbstractController
{
    private WeatherService $weatherService;
    private TokenStorageInterface $tokenStorage;

    public function __construct(WeatherService $weatherService, TokenStorageInterface $tokenStorage)
    {
        $this->weatherService = $weatherService;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/', name: 'weather_home')]
    public function index(Request $request): Response
    {
        $weatherData = [];
        $favoriteCities = ['London']; // Default city if no user is logged in or no favorite cities are set.
        $selectedCity = $request->request->get('city');

        $token = $this->tokenStorage->getToken();
        if ($token && $user = $token->getUser()) {
            $favoriteCities = array_filter([
                $user->getFavoriteCity1(),
                $user->getFavoriteCity2(),
                $user->getFavoriteCity3(),
            ]); // Remove any null entries.
        }

        if (!$selectedCity) {
            $selectedCity = $favoriteCities[0] ?? null;
        }

        if ($selectedCity) {
            $data = $this->weatherService->getWeatherData($selectedCity);
            $weatherData[$selectedCity] = [
                'temperature' => $data['main']['temp'],
                'forecast' => $this->weatherService->getForecastData($data['coord']['lat'], $data['coord']['lon'], $selectedCity)['list'],
            ];
        }

        return $this->render('weather/index.html.twig', [
            'favoriteCities' => $favoriteCities,
            'selectedCity' => $selectedCity,
            'weatherData' => $weatherData,
        ]);
    }
}
