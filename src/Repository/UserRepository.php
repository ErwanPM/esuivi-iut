<?php
/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 24/02/2018
 * Time: 19:00
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;


class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /*
     * Pour chercher un utilisateur (n'importe lequel) ayant un certain rôle
     */
    public function findOneByRole($role) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%')
            ->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }
}