<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Country;
use App\Entity\Currency;
use App\Repository\CountryRepository;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountrySyncCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EntityManagerInterface $entityManager,
        private readonly CurrencyRepository $currencyRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('countries:sync');
        $this->setDescription('Synchronize the countries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = "https://restcountries.com/v3.1/all?fields=name,region,subregion,demonyms,population,independent,flag,currencies";

        $response = $this->client->request('GET', $url);

        $countriesResponse = $response->toArray();
        $currencies = [];

        foreach ($countriesResponse as $countryResponse) {
            $currencyResponse = $countryResponse['currencies'];
            $currencyKey = array_key_first($currencyResponse);

            $currency = $currencies[$currencyKey] ?? null;
            if(!$currency && $currencyKey) {
                $currency = new Currency();
                $currency->setName($currencyResponse[$currencyKey]["name"]);
                $currency->setSymbol($currencyResponse[$currencyKey]["symbol"]);
                $currency->setCode($currencyKey);
                $this->entityManager->persist($currency);
                $this->entityManager->flush();

                $currencies[$currencyKey] = $currency;
            }

            $country = new Country();
            $country->setName($countryResponse['name']['common']);
            $country->setCurrency($currency);
            $country->setRegion($countryResponse['region']);
            $country->setSubregion($countryResponse['subregion']);
            $country->setPopulation($countryResponse['population']);
            $country->setFlag($countryResponse['flag']);
            $country->setIndependant($countryResponse['independent']);
            $country->setDemonym($countryResponse['demonyms']['eng']['f'] ?? $countryResponse['demonyms']['eng']['m'] ?? "null");
            $uuid = Uuid::v1();

            $country->setUuid($uuid->toString());


            $this->entityManager->persist($country);
            $this->entityManager->flush();
        }

        return COMMAND::SUCCESS;
    }
}