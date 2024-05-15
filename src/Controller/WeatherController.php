<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

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
        $city = 'Paris';
        $weatherData = $this->weatherService->getWeatherData($city);
        $temperature = $weatherData['main']['temp'];

        $lat = $weatherData['coord']['lat'];
        $lon = $weatherData['coord']['lon'];
        $forecastData = $this->weatherService->getForecastData($lat, $lon,$city);
        $forecast = $forecastData['list'];

        $now = time();
        $next24Hours = $now + 24 * 60 * 60;

        $forecastNext24Hours = array_filter($forecast, function ($entry) use ($now, $next24Hours) {
            $forecastTime = strtotime($entry['dt_txt']);
            return $forecastTime >= $now && $forecastTime <= $next24Hours;
        });

        $chartData = [
            'labels' => array_map(function ($entry) { return $entry['dt_txt']; }, $forecastNext24Hours),
            'temperature' => array_map(function ($entry) { return $entry['main']['temp']; }, $forecastNext24Hours),
            'windSpeed' => array_map(function ($entry) { return $entry['wind']['speed']; }, $forecastNext24Hours)
        ];

        return $this->render('weather/index.html.twig', [
            'city' => $city,
            'temperature' => $temperature,
            'forecast' => $forecast,
            'chartData' => $chartData
        ]);
    }
}