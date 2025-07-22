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
        private readonly CountryRepository $countryRepository,
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

        $existingCountries = $this->countryRepository->getAllIndexedByName();
        $existingCurrencies = $this->currencyRepository->getAllIndexedByCode();
        $apiCountryNames = [];

        $newCurrencies = [];

        foreach ($countriesResponse as $countryResponse) {
            $countryName = $countryResponse['name']['common'];
            $country = $existingCountries[$countryName] ?? null;
            $apiCountryNames[] = $countryName;

            if (!$country) {
                $country = new Country();
                $country->setUuid(Uuid::v1()->toString());
                $country->setName($countryName);
                $this->entityManager->persist($country);
                $existingCountries[$countryName] = $country;
            }

            $country->setRegion($countryResponse['region'] ?? null);
            $country->setSubregion($countryResponse['subregion'] ?? null);
            $country->setPopulation($countryResponse['population'] ?? 0);
            $country->setFlag($countryResponse['flag'] ?? null);
            $country->setIndependant($countryResponse['independent'] ?? false);

            $demonym = null;
            if (isset($countryResponse['demonyms']['eng'])) {
                $demonym = $countryResponse['demonyms']['eng']['f']
                    ?? $countryResponse['demonyms']['eng']['m']
                    ?? null;
            }
            $country->setDemonym($demonym ?? "null");

            $currencyCode = array_key_first($countryResponse['currencies'] ?? []);
            $currency = null;

            if ($currencyCode) {
                $currency = $existingCurrencies[$currencyCode] ?? $newCurrencies[$currencyCode] ?? null;

                if (!$currency) {
                    $currencyData = $countryResponse['currencies'][$currencyCode];
                    $currency = new Currency();
                    $currency->setCode($currencyCode);
                    $currency->setName($currencyData['name'] ?? '');
                    $currency->setSymbol($currencyData['symbol'] ?? null);

                    $this->entityManager->persist($currency);
                    $newCurrencies[$currencyCode] = $currency;
                }
            }
            $country->setCurrency($currency);
        }

        $this->deleteCountries($apiCountryNames);

        $this->entityManager->flush();
        $this->entityManager->clear();

        return Command::SUCCESS;
    }

    private function deleteCountries(array $apiCountryNames): void
    {
        $existingCountryNames = $this->countryRepository->getAllCountryNames();

        if ($obsoleteNames = array_diff($existingCountryNames, $apiCountryNames)) {
            $this->countryRepository->deleteByNames($obsoleteNames);
        }
    }
}