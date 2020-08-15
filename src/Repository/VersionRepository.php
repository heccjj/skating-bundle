<?php

namespace Heccjj\SkatingBundle\Repository;

use Heccjj\SkatingBundle\Entity\NodeVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NodeVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NodeVersion::class);
    }

    public function getNodeVersions($nid)
	{
		$param = array(
			'nid' => $nid
			);		
		$qb = $this->createQueryBuilder('n')
			->where("n.nid=:nid");
		
		$qb->setParameters($param);
		
		return $qb->getQuery()->execute();
	}

	public function getNodeVersion($id)
	{
		$param = array(
			'id' => $id
			);		
		$qb = $this->createQueryBuilder('n')
			->where("n.id=:id");
		
		$qb->setParameters($param);
		
		return $qb->getQuery()->getOneOrNullResult();
    }
    
    /**
	 * 取出内容版本
	 */
	public function hasNodeVersion($nid)
	{
		$param = array(
			'nid' => $nid
		    );		
		$qb = $this->createQueryBuilder('n')
		    ->select('count(n.nid) as nvs')
			->where("n.nid=:nid");
		
		$qb->setParameters($param);

		$result = $qb->getQuery()->getOneOrNullResult();
				
		return $result['nvs'];
	}

}
