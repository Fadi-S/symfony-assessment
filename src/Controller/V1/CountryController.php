<?php
declare(strict_types=1);

namespace App\Controller\V1;

use App\Entity\Country;
use App\Entity\Currency;
use App\Repository\CountryRepository;
use App\Repository\CurrencyRepository;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('countries')]
class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly CurrencyRepository $currencyRepository,
        private readonly ValidatorInterface $validator
    )
    {
    }

    private function getCountryData(Country $country): array
    {
        return [
            'uuid' => $country->getUuid(),
            'name' => $country->getName(),
            'region' => $country->getRegion(),
            'subRegion' => $country->getSubRegion(),
            'demonym' => $country->getDemonym(),
            'population' => $country->getPopulation(),
            'independant' => $country->isIndependant(),
            'flag' => $country->getFlag(),
            'currency' => $country->getCurrency() ? [
                'name' => $country->getCurrency()->getName(),
                'symbol' => $country->getCurrency()->getSymbol(),
                'code' => $country->getCurrency()->getCode(),
            ] : null,
        ];
    }

    /**
     * List All Countries.
     */
    #[Route('/list', methods: ['GET'])]
    public function getCountries(): Response
    {
        $countries = $this->countryRepository->findAll();

        return $this->json(array_map(
            fn(Country $country) => $this->getCountryData($country),
            $countries
        ));
    }


    /**
     * View a single Country.
     */
    #[Route('/{country}', methods: ['GET'])]
    public function getCountry(string $country): Response
    {
        $country = $this->countryRepository->byUUID($country);

        return $this->json($this->getCountryData($country));
    }

    private function setCountryData(Country $country, array $data): void
    {
        $country->setName($data['name']);
        $country->setRegion($data['region']);
        $country->setSubRegion($data['subRegion']);
        $country->setDemonym($data['demonym']);
        $country->setPopulation($data['population']);
        $country->setIndependant($data['independant']);
        $country->setFlag($data['flag']);
        $currencyData = $data['currency'] ?? null;
        if ($currencyData) {
            $currency = $this->currencyRepository->getCurrencyByCode($currencyData['code']);

            if(!$currency) {
                $currency = new Currency();
                $currency->setCode($currencyData['code']);
                $currency->setName($currencyData['name']);
                $currency->setSymbol($currencyData['symbol']);
                $this->currencyRepository->save($currency);
            }

            $country->setCurrency($currency);
        }
    }

    private function hasErrors(Country $country) : array|false
    {
        $errors = $this->validator->validate($country);

        $count = $errors->count();
        if ($count > 0) {
            $errorsArr = [];
            for ($i=0; $i < $count; $i++) {
                $error = $errors->get($i);
                $errorsArr[$error->getPropertyPath()] = $error->getMessage();
            }

            return $errorsArr;
        }

        return false;
    }

    /**
     * Create a new Country.
     */
    #[OA\RequestBody(
        description: 'Country data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'region', type: 'string'),
                new OA\Property(property: 'subRegion', type: 'string'),
                new OA\Property(property: 'demonym', type: 'string'),
                new OA\Property(property: 'population', type: 'integer'),
                new OA\Property(property: 'independant', type: 'boolean'),
                new OA\Property(property: 'flag', type: 'string'),
                new OA\Property(
                    property: 'currency',
                    properties: [
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'symbol', type: 'string'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    #[Route('/', methods: ['POST'])]
    public function addCountry(Request $request): Response
    {
        $parameters = json_decode($request->getContent(), true);

        $country = new Country();

        $this->setCountryData($country, $parameters);
        $country->setUuid(Uuid::v1()->toString());

        $errors = $this->hasErrors($country);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->countryRepository->save($country);

        return $this->json($this->getCountryData($country));
    }

    /**
     * Update Country.
     */
    #[OA\RequestBody(
        description: 'Country data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'region', type: 'string'),
                new OA\Property(property: 'subRegion', type: 'string'),
                new OA\Property(property: 'demonym', type: 'string'),
                new OA\Property(property: 'population', type: 'integer'),
                new OA\Property(property: 'independant', type: 'boolean'),
                new OA\Property(property: 'flag', type: 'string'),
                new OA\Property(
                    property: 'currency',
                    properties: [
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'symbol', type: 'string'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    #[Route('/{country}', methods: ['PATCH'])]
    public function updateCountry(Request $request, string $country): Response
    {
        $parameters = json_decode($request->getContent(), true);

        $country = $this->countryRepository->byUUID($country);
        if (!$country) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        $this->setCountryData($country, $parameters);

        $errors = $this->hasErrors($country);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->countryRepository->save($country);

        return $this->json($this->getCountryData($country));
    }


    /**
     * Delete Country.
     */
    #[Security(name: 'Bearer')]
    #[Route('/{country}', methods: ['DELETE'])]
    public function deleteCountry(string $country): Response
    {
        $country = $this->countryRepository->byUUID($country);

        if (!$country) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        $this->countryRepository->delete($country);

        return $this->json(['message' => 'Country deleted successfully']);
    }
}