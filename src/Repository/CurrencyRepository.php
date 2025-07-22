<?php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    public function getAllIndexedByCode(): array
    {
        return $this->createQueryBuilder('currency', 'currency.code')->getQuery()->getResult();
    }

    public function getCurrencyByCode($code) : ?Currency
    {
        return $this->findOneBy([
            'code' => $code,
        ]);
    }

    public function save(Currency $currency) : Currency
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($currency);
        $entityManager->flush();

        return $currency;
    }
}
