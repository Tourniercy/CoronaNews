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

        // Liste des populations par code ISO pays

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://gist.githubusercontent.com/gwillem/6ca8a81048e6f3721c3bafc803d44a72/raw/4fb66d18178c1a0fdf101fb6b03c4d21929472da/iso2_population.json');
        $content = $response->getContent();
        $populationByCountry = json_decode($content, true);

        // Cas par pays

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://api.covid19api.com/summary');
        $countryTable = $response->toArray();
//        dd($countryTable['Global']);
        foreach ($countryTable['Countries'] as &$country) {
            $country['population'] = $populationByCountry[$country['CountryCode']];
        }

        // Cas du premier pays par jour
        $client = HttpClient::create();

        $response = $client->request('GET', 'https://pomber.github.io/covid19/timeseries.json');
        $data = $response->getContent();

        $dataByDays = json_decode($data, true);

        //Nom des pays

        $countries = array_keys($dataByDays);

        $defaultCountry = array_key_first($dataByDays);

        $infected = end($dataByDays[$defaultCountry])['confirmed'];
        $recovered = end($dataByDays[$defaultCountry])['recovered'];
        $deaths = end($dataByDays[$defaultCountry])['deaths'];

        $fatality = ($deaths / $infected) * 100;

        $array = [];

        foreach($dataByDays as $key => $value){

          $lastData = end($value);
          $lastData['country'] = $key;
          $array[$key] = $lastData;
        }


        // NEWS SECTION

        $response = $client->request('GET', 'http://newsapi.org/v2/top-headlines?country=fr&category=health&q=covid&apiKey=154a3122f10644b5ada441ea0aa94fe3');
        $articles = $response->toArray();
        return $this->render('home/index.html.twig', [

          'data' => $array,
          'stats' => $dataByDays[$defaultCountry],
          'infected' => $infected,
          'recovered' => $recovered,
          'deaths' => $fatality,
          'countries' => $countries,
          'articles' => $articles,
          'countryTable' => $countryTable  ,
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

        $infected = end($decodedData[$country])['confirmed'];
        $recovered = end($decodedData[$country])['recovered'];
        $deaths = end($decodedData[$country])['deaths'];

        $fatality = ($deaths / $infected) * 100;
        return $this->json(['infected' => $infected,'recovered' => $recovered,'deaths' => $deaths,'fatality' => round($fatality,2),'data' => $decodedData[$country]]);

    }
}
