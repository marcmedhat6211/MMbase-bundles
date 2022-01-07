<?php

namespace App\UserBundle\Repository;

use App\UserBundle\Entity\User;
use App\Utils\Validate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @param string $role
     *
     * @return array
     */
    public function findByRole(string $role): array
    {
        $statement = $this->createQueryBuilder("u")
            ->select('u')
            ->where('u.roles LIKE :roles')
            ->andWhere('u.deleted IS NULL')
            ->orderBy('u.id', 'DESC')
            ->setParameter('roles', '%"'.$role.'"%');
        return $statement->getQuery()->getResult();
    }

    private function getStatement()
    {
        return $this->createQueryBuilder('u');
    }

    private function filterOrder(QueryBuilder $statement, \stdClass $search)
    {
        $sortSQL = [
            "u.id",
            "u.username",
            "u.email",
            "u.phone",
            "u.status",
            "u.lastLogin",
        ];

        if (isset($search->ordr) and Validate::notNull($search->ordr)) {
            $dir = $search->ordr['dir'];
            $columnNumber = $search->ordr['column'];
            if (isset($columnNumber) and array_key_exists($columnNumber, $sortSQL)) {
                $statement->addOrderBy($sortSQL[$columnNumber], $dir);
            }
        } else {
            $statement->addOrderBy($sortSQL[0]);
        }
    }

    private function filterWhereClause(QueryBuilder $statement, \stdClass $search)
    {
        if (isset($search->string) and Validate::notNull($search->string)) {
            $statement->andWhere('u.id LIKE :searchTerm '
                .'OR u.username LIKE :searchTerm '
                .'OR u.email LIKE :searchTerm '
            );
            $statement->setParameter('searchTerm', '%'.trim($search->string).'%');
        }

        if (isset($search->id) and $search->id > 0) {
            $statement->andWhere('u.id = :id');
            $statement->setParameter('id', $search->id);
        }

        if (isset($search->ids) and is_array($search->ids) and count($search->ids) > 0) {
            $statement->andWhere('u.id IN (:ids)');
            $statement->setParameter('ids', $search->ids);
        }

        if (isset($search->deleted) and in_array($search->deleted, array(0, 1))) {
            if ($search->deleted == 1) {
                $statement->andWhere('u.deleted IS NOT NULL');
            } else {
                $statement->andWhere('u.deleted IS NULL');
            }
        }
    }

    private function filterPagination(QueryBuilder $statement, $startLimit = null, $endLimit = null)
    {
        if ($startLimit === null or $endLimit === null) {
            return false;
        }
        $statement->setFirstResult($startLimit)
            ->setMaxResults($endLimit);
    }

    private function filterCount(QueryBuilder $statement)
    {
        $statement->select("COUNT(DISTINCT u.id)");
        $statement->setMaxResults(1);

        $count = $statement->getQuery()->getOneOrNullResult();
        if (is_array($count) and count($count) > 0) {
            return (int)reset($count);
        }

        return 0;
    }

    public function filter($search, $count = false, $startLimit = null, $endLimit = null)
    {
        $statement = $this->getStatement();
        $this->filterWhereClause($statement, $search);

        if ($count == true) {
            return $this->filterCount($statement);
        }

        $statement->groupBy('u.id');
        $this->filterPagination($statement, $startLimit, $endLimit);
        $this->filterOrder($statement, $search);

        return $statement->getQuery()->execute();
    }
}
