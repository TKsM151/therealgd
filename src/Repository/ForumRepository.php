<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\ForumSubscription;
use App\Entity\Moderator;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method Forum|null findOneByCanonicalName(string $canonicalName)
 */
final class ForumRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Forum::class);
    }

    /**
     * @param int    $page
     * @param string $sortBy one of 'name', 'title', 'submissions',
     *                       'subscribers', optionally with 'by_' prefix
     *
     * @return Pagerfanta|Forum[]
     */
    public function findForumsByPage(int $page, string $sortBy) {
        if (!preg_match('/^(?:by_)?(name|title|submissions|subscribers)$/', $sortBy, $matches)) {
            throw new \InvalidArgumentException('invalid sort type');
        }

        $qb = $this->createQueryBuilder('f');

        switch ($matches[1]) {
        case 'subscribers':
            $qb->addSelect('COUNT(s) AS HIDDEN subscribers')
                ->leftJoin('f.subscriptions', 's')
                ->orderBy('subscribers', 'DESC');
            break;
        case 'submissions':
            $qb->addSelect('COUNT(s) AS HIDDEN submissions')
                ->leftJoin('f.submissions', 's')
                ->orderBy('submissions', 'DESC');
            break;
        case 'title':
            $qb->orderBy('LOWER(f.title)', 'ASC');
            break;
        }

        $qb->addOrderBy('f.canonicalName', 'ASC')->groupBy('f.id');

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    /**
     * @param User $user
     *
     * @return string[]
     */
    public function findSubscribedForumNames(User $user) {
        /* @noinspection SqlDialectInspection */
        $dql =
            'SELECT f.id, f.name FROM '.Forum::class.' f WHERE f IN ('.
                'SELECT IDENTITY(fs.forum) FROM '.ForumSubscription::class.' fs WHERE fs.user = ?1'.
            ') ORDER BY f.canonicalName ASC';

        $names = $this->getEntityManager()->createQuery($dql)
            ->setParameter(1, $user)
            ->getResult();

        return array_column($names, 'name', 'id');
    }

    /**
     * Get the names of the featured forums.
     *
     * @return string[]
     */
    public function findFeaturedForumNames() {
        $names = $this->createQueryBuilder('f')
            ->select('f.id')
            ->addSelect('f.name')
            ->where('f.featured = TRUE')
            ->orderBy('f.canonicalName', 'ASC')
            ->getQuery()
            ->execute();

        return array_column($names, 'name', 'id');
    }

    /**
     * @param User $user
     *
     * @return string[]
     */
    public function findModeratedForumNames(User $user) {
        /* @noinspection SqlDialectInspection */
        $dql = 'SELECT f.id, f.name FROM '.Forum::class.' f WHERE f IN ('.
            'SELECT IDENTITY(m.forum) FROM '.Moderator::class.' m WHERE m.user = ?1'.
        ') ORDER BY f.canonicalName ASC';

        $names = $this->getEntityManager()->createQuery($dql)
            ->setParameter(1, $user)
            ->getResult();

        return array_column($names, 'name', 'id');
    }

    public function findForumNames($names) {
        /** @noinspection SqlDialectInspection */
        $dql = 'SELECT f.id, f.name FROM '.Forum::class.' f '.
            'WHERE f.canonicalName IN (?1) '.
            'ORDER BY f.canonicalName ASC';

        $names = $this->_em->createQuery($dql)
            ->setParameter(1, $names)
            ->getResult();

        return array_column($names, 'name', 'id');
    }

    /**
     * @param string|null $name
     *
     * @return Forum|null
     */
    public function findOneByCaseInsensitiveName($name) {
        if ($name === null) {
            // for the benefit of param converters which for some reason insist
            // on calling repository methods with null parameters.
            return null;
        }

        return $this->findOneByCanonicalName(Forum::canonicalizeName($name));
    }
}