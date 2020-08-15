<?php

namespace Heccjj\SkatingBundle\Repository;

use Heccjj\SkatingBundle\Entity\Node;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Node|null find($id, $lockMode = null, $lockVersion = null)
 * @method Node|null findOneBy(array $criteria, array $orderBy = null)
 * @method Node[]    findAll()
 * @method Node[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Node::class);
    }

    public function findByDir($dir, $include_deleted = false){
        //目录在前，其它内容在后
        $qb = $this->createQueryBuilder('n')
                ->where('n.dir = :dir')->setParameter('dir', $dir)
                ->andWhere('n.typeName = :typeName')->setParameter('typeName', 'nodefolder')
                ->orderBy('n.updateAt', 'DESC')
                ;

        if(!$include_deleted)
            $qb->andWhere('n.isDeleted=FALSE');
        
        $result_folder = $qb->getQuery()->execute();

        $qb = $this->createQueryBuilder('n')
                ->where('n.dir = :dir')->setParameter('dir', $dir)
                ->andWhere('n.typeName <> :typeName')->setParameter('typeName', 'nodefolder')
                ->orderBy('n.createAt', 'DESC')
                ;
        if(!$include_deleted)
            $qb->andWhere('n.isDeleted=FALSE');

        $result_other = $qb->getQuery()->execute();

        return array_merge($result_folder, $result_other);  //用 + 会丢失内容
    }

    public function findOneByPath($path, $include_deleted = false){
        //一级目录要避免'//slug'问题
        $dir = dirname($path);
        $slug = basename($path);
        
        $qb = $this->createQueryBuilder('n')
            ->where("n.dir = :dir and n.slug= :slug")
            ->setParameter('dir', $dir)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ;

        if(!$include_deleted)
            $qb->andWhere('n.isDeleted=FALSE');

        $query = $qb->getQuery();

        //return $query->getSingleResult();
        return $query->getOneOrNullResult();
    }

    public function getNodeVersions($nid)
	{
		$repo = $this->doctrine->getRepository(NodeVersions::class);
		$param = array(
			'nid' => $nid
			);		
		$qb = $repo->createQueryBuilder('n')
			->where("n.nid=:nid");
		
		$qb->setParameters($param);
		
		return $qb->getQuery()->execute();
	}

	public function getNodeVersion($id)
	{
		$repo = $this->doctrine->getRepository(NodeVersions::class);
		$param = array(
			'id' => $id
			);		
		$qb = $repo->createQueryBuilder('n')
			->where("n.id=:id");
		
		$qb->setParameters($param);
		
		return $qb->getQuery()->getOneOrNullResult();
	}

    // /**
    //  * @return Node[] Returns an array of Node objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Node
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
