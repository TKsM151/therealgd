<?php

namespace Raddit\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Raddit\AppBundle\Entity\Theme;
use Raddit\AppBundle\Entity\User;

class ThemeRepository extends EntityRepository {
    /**
     * @param int $page
     * @param int $maxPerPage
     *
     * @return Pagerfanta|Theme[]
     */
    public function findAllPaginated(int $page, int $maxPerPage = 25) {
        $qb = $this->createQueryBuilder('t')
            ->join('t.author', 'a')
            ->orderBy('LOWER(a.username)', 'ASC')
            ->addOrderBy('LOWER(t.name)', 'ASC');

        $themes = new Pagerfanta(new DoctrineORMAdapter($qb, false, false));
        $themes->setMaxPerPage($maxPerPage);
        $themes->setCurrentPage($page);

        return $themes;
    }

    /**
     * @param string|null $username
     * @param string|null $name
     *
     * @return Theme|null
     */
    public function findOneByUsernameAndName($username, $name) {
        if ($username === null || $name === null) {
            return null;
        }

        return $this->createQueryBuilder('t')
            ->where('t.author = (SELECT IDENTITY(u) FROM '.User::class.' WHERE username = :username)')
            ->andWhere('t.name = :name')
            ->setParameter('username', $username)
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleResult();
    }
}