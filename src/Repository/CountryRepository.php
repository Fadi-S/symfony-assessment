<?php

namespace App\Repository;

use App\Entity\Country;
use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function getAllIndexedByName(): array
    {
        return $this->createQueryBuilder('country', 'country.name')->getQuery()->getResult();
    }

    public function save(Country $country) : Country
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($country);
        $entityManager->flush();

        return $country;
    }

    public function byUUID(string $country): ?Country
    {
        return $this->findOneBy([
            'uuid' => $country,
        ]);
    }

    public function delete(Country $country): bool
    {
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->remove($country);
            $entityManager->flush();
            return true;
        } catch (\Exception $ignored) {
            return false;
        }
    }

    public function getAllCountryNames() : array
    {
        $countries = $this->createQueryBuilder('country')
            ->select('country.name')
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($country) => $country['name'], $countries);
    }

    public function deleteByNames(array $names)
    {
        $qb = $this->createQueryBuilder('country');
        $qb->delete()
            ->where($qb->expr()->in('country.name', ':names'))
            ->setParameter('names', $names);

        return $qb->getQuery()->execute();
    }
}
