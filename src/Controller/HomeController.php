<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;


class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(Request $request)
    {

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://pomber.github.io/covid19/timeseries.json');
        $data = $response->getContent();

        $decodedData = json_decode($data, true);

        $countries = array_keys($decodedData);

        $defaultCountry = array_key_first($decodedData);

        foreach($decodedData[$defaultCountry] as $value){

          $infected = $value['confirmed'];
          $recovered = $value['recovered'];
          $deaths = $value['deaths'];
        }

        $array = [];

        foreach($decodedData as $key => $value){

          $lastData = end($value);
          array_push($lastData, $key);
          $array[$key] = $lastData;
        }

        $fatality = ($deaths / $infected) * 100;

        // NEWS SECTION

        $response = $client->request('GET', 'http://newsapi.org/v2/top-headlines?country=fr&category=health&q=covid&apiKey=154a3122f10644b5ada441ea0aa94fe3');

        $content = $response->toArray();

        return $this->render('home/index.html.twig', [
          'data' => $array,
          'infected' => $infected,
          'recovered' => $recovered,
          'deaths' => $fatality,
          'countries' => $countries,
          'articles' => $content,
        ]);
    }

    /**
    * @Route("/changeCountry", name="changeCountry")
    */
    public function changeCountry(Request $request)
    {

        $country = $request->request->get('country');

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://pomber.github.io/covid19/timeseries.json');
        $data = $response->getContent();

        $decodedData = json_decode($data, true);

          foreach($decodedData[$country] as $value){
            $infected = $value['confirmed'];
            $recovered = $value['recovered'];
            $deaths = $value['deaths'];
          }

        $fatality = ($deaths / $infected) * 100;
        return $this->json(['infected' => $infected,'recovered' => $recovered,'deaths' => $deaths,'fatality' => round($fatality,2)]);
    }
}
