<?php

namespace App\Tests\Controller\V1;

use App\Entity\Country;
use App\Entity\Currency;
use App\Repository\CountryRepository;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class CountryCRUDTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $jwtToken;
    private CountryRepository $countryRepository;
    private CurrencyRepository $currencyRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
//        $kernel = self::bootKernel(['environment' => 'test']);
//
//        $application = new Application($kernel);
//        $command = $application->find('doctrine:migrations:migrate');
//        $commandTester = new CommandTester($command);
//        $commandTester->execute(['n']);

        $this->client = static::createClient();
        $this->countryRepository = static::getContainer()->get(CountryRepository::class);
        $this->currencyRepository = static::getContainer()->get(CurrencyRepository::class);

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->entityManager->getConnection()->beginTransaction();

        // Authenticate and get JWT token
        $this->authenticate();
    }

    private function authenticate(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'admin', 'password' => '123456'])
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->jwtToken = $data['token'] ?? null;
    }

    private function createTestCountry(): Country
    {
        $currency = new Currency();
        $currency->setCode('TST');
        $currency->setName('Test Currency');
        $currency->setSymbol('T');
        $this->currencyRepository->save($currency);

        $country = new Country();
        $country->setUuid(Uuid::v1()->toString());
        $country->setName('Test Country');
        $country->setRegion('Test Region');
        $country->setSubRegion('Test Subregion');
        $country->setDemonym('Tester');
        $country->setPopulation(1000000);
        $country->setIndependant(true);
        $country->setFlag('🇹');
        $country->setCurrency($currency);
        $this->countryRepository->save($country);

        return $country;
    }

    public function testGetCountries(): void
    {
        $this->client->request('GET', '/api/v1/countries/list');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetCountry(): void
    {
        $country = $this->createTestCountry();

        $this->client->request('GET', '/api/v1/countries/' . $country->getUuid());
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($country->getName(), $data['name']);
    }

    public function testCreateCountry(): void
    {
        $data = [
            'name' => 'New Country',
            'region' => 'New Region',
            'subRegion' => 'New Subregion',
            'demonym' => 'New Demonym',
            'population' => 5000000,
            'independant' => true,
            'flag' => '🇳',
            'currency' => [
                'code' => 'NEW',
                'name' => 'New Currency',
                'symbol' => 'N'
            ]
        ];

        $this->client->request(
            'POST',
            '/api/v1/countries/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
            ],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('New Country', $responseData['name']);
        $this->assertEquals('New Currency', $responseData['currency']['name']);
    }

    public function testUpdateCountry(): void
    {
        $country = $this->createTestCountry();
        $data = [
            'name' => 'Updated Country',
            'population' => 2000000,
            'currency' => [
                'code' => 'UPD',
                'name' => 'Updated Currency',
                'symbol' => 'U'
            ]
        ];

        $this->client->request(
            'PATCH',
            '/api/v1/countries/' . $country->getUuid(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
            ],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Country', $responseData['name']);
        $this->assertEquals(2000000, $responseData['population']);
        $this->assertEquals('Updated Currency', $responseData['currency']['name']);
    }

    public function testDeleteCountry(): void
    {
        $country = $this->createTestCountry();

        $this->client->request(
            'DELETE',
            '/api/v1/countries/' . $country->getUuid(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $deletedCountry = $this->countryRepository->byUUID($country->getUuid());
        $this->assertNull($deletedCountry);
    }

    // Unhappy Path Tests

    public function testGetCountryNotFound(): void
    {
        $this->client->request('GET', '/api/v1/countries/invalid-uuid');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateCountryWithoutAuthentication(): void
    {
        $data = [
            'name' => 'Unauthorized Country',
            // ... other required fields
        ];

        $this->client->request(
            'POST',
            '/api/v1/countries/',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateCountryWithInvalidData(): void
    {
        $invalidData = [
            'name' => '',
            'population' => -100,
        ];

        $this->client->request(
            'POST',
            '/api/v1/countries/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
            ],
            json_encode($invalidData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('name', $responseData['errors']);
        $this->assertArrayHasKey('population', $responseData['errors']);
    }

    public function testUpdateCountryNotFound(): void
    {
        $data = ['name' => 'Non-existent Country'];

        $this->client->request(
            'PATCH',
            '/api/v1/countries/invalid-uuid',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
            ],
            json_encode($data)
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateCountryWithInvalidCurrency(): void
    {
        $country = $this->createTestCountry();
        $data = [
            'currency' => [
                'code' => '',
                'name' => 'Invalid Currency',
                'symbol' => 'I'
            ]
        ];

        $this->client->request(
            'PATCH',
            '/api/v1/countries/' . $country->getUuid(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken
            ],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('errors', json_decode($response->getContent(), true));
    }

    public function testDeleteCountryNotFound(): void
    {
        $this->client->request(
            'DELETE',
            '/api/v1/countries/invalid-uuid',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken]
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteCountryWithoutAuthentication(): void
    {
        $country = $this->createTestCountry();

        $this->client->request(
            'DELETE',
            '/api/v1/countries/' . $country->getUuid()
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->jwtToken = "";
    }
}