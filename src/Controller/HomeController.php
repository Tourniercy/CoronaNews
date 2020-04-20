<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;


class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        $client = HttpClient::create();

        $response = $client->request('GET', 'https://pomber.github.io/covid19/timeseries.json');
        $data = $response->getContent();

        $decodedData = json_decode($data, true);

        $countries = array_keys($decodedData);

        $response = $client->request('GET', 'http://newsapi.org/v2/top-headlines?country=fr&category=health&q=covid&apiKey=154a3122f10644b5ada441ea0aa94fe3');

        $statusCode = $response->getStatusCode();

        $contentType = $response->getHeaders()['content-type'][0];

        $content = $response->getContent();

        $content = $response->toArray();

        return $this->render('home/index.html.twig', [
          'data' => $decodedData,
          'countries' => $countries,
          'articles' => $content,
        ]);
    }
}
