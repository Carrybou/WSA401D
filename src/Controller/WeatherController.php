<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WeatherController extends AbstractController
{
    private $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    #[Route('/', name: 'weather_home')]
    public function index(): Response
    {
        $city = 'London';
        $weatherData = $this->weatherService->getWeatherData($city);
        $temperature = $weatherData['main']['temp'];

        $lat = $weatherData['coord']['lat'];
        $lon = $weatherData['coord']['lon'];
        $forecastData = $this->weatherService->getForecastData($lat, $lon,$city);
        $forecast = $forecastData['list'];

        return $this->render('weather/index.html.twig', [
            'city' => $city,
            'temperature' => $temperature,
            'forecast' => $forecast,
        ]);
    }
}