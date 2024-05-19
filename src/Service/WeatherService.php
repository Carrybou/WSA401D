<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Cocur\Slugify\Slugify;

class WeatherService
{
    private $httpClient;
    private $apiKey;
    private $cacheDir;
    private $slugify;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $projectDir)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->cacheDir = $projectDir . '/public/data';
        $this->slugify = new Slugify();
    }

    public function getWeatherData(string $city): array
    {
        $citySlug = $this->slugify->slugify($city);
        $cacheFile = $this->cacheDir . '/' . $citySlug . '.json';

        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - 5 * 60))) {
            $data = json_decode(file_get_contents($cacheFile), true);
        } else {
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }

            if (is_numeric($city) && strlen($city) == 5) {
                $city .= ',fr';
            }

            $response = $this->httpClient->request('GET', "https://api.openweathermap.org/data/2.5/weather", [
                'query' => [
                    'q' => $city,
                    'units' => 'metric',
                    'appid' => $this->apiKey,
                    'lang' => 'fr',
                ]
            ]);

            $data = $response->toArray();


            file_put_contents($cacheFile, json_encode($data));
        }

        return $data;
    }

    public function getForecastData(float $lat, float $lon, string $city): array
    {
        $citySlug = $this->slugify->slugify($city);
        $cacheFile = $this->cacheDir . '/' . $citySlug . '_forecast.json';

        if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - 5 * 60))) {
            $data = json_decode(file_get_contents($cacheFile), true);
        } else {
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            $response = $this->httpClient->request('GET', "https://api.openweathermap.org/data/2.5/forecast", [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => 'metric',
                    'appid' => $this->apiKey,
                    'lang' => 'fr',
                ]
            ]);

            $data = $response->toArray();

            file_put_contents($cacheFile, json_encode($data));
        }

        return $data;
    }
}
