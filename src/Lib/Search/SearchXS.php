<?php
namespace Heccjj\SkatingBundle\Lib\Search;

use Heccjj\SkatingBundle\Entity\Node;

define ( 'XS_APP_ROOT', dirname(__FILE__) );
//require '/opt/xunsearch/sdk/php/lib/XS.php';

/*
 * 基于迅搜
 */
class SearchXS implements SearchInterface
{
	//protected $em;
	protected $db;
	public $index;
	public $search;
	
	public function __construct(){
		global $kernel;
		$this->doctrine = $kernel->getContainer()->get('doctrine');
		
		$xs = new \XS('SearchXS');
		$this->index = $xs->index;
		$this->search = $xs->search;
	}
	
	/*
	 * 给某类node节点创建索引
	 */
	public function createIndex($type='')
	{
		$repo = $this->doctrine->getRepository(Node::class);
		$qb = $repo->createQueryBuilder('n')
				->where('n.status=:status');
		$param = array('status'=>'published');   //只允许搜索发布了的内容
		
		if($type != '')
		{
			$qb->andWhere('n.typeName=:type');
			$param['type'] = $type;
		}

		$qb->setParameters($param);
		$nodes = $qb->getQuery()->execute();

		if( !$nodes )
			return false;
		
		foreach($nodes as $node)
		{	
			$doc = new \XSDocument;
			$doc->setFields( (array)$node);
			$doc->setField('create_at', ($node->createdAt == null ) ? null : $node->createdAt->format('Y-m-d H:i:s'));
			$doc->setField('publish_end_at', ($node->publishEndAt == null )? "2038-01-01 00:00:00" : $node->publishEndAt->format('Y-m-d H:i:s'));
			$this->index->add($doc);
		}
	}
    
    /*
     * 添加一个文档到索引中
     */
    public function addDoc($nid)
    {
        $repo = $this->doctrine->getRepository(Node::class);
		$qb = $repo->createQueryBuilder('n')
				->where('n.id=:id')->setParameter('id', $nid)
				->andWhere('n.status=:status')->setParameter('status', 'published')//只允许搜索发布了的内容
				->setMaxResults(1)
            ;

        $node = $qb->getQuery()->getOneOrNullResult();
		if( !$node )
			return false;

		$doc = new \XSDocument();
		$doc->setFields((array)$node);		
		$doc->setField('create_at', ($node->createdAt == null ) ? null : $node->createdAt->format('Y-m-d H:i:s'));
		$doc->setField('publish_end_at', ($node->publishEndAt == null )? "2038-01-01 00:00:00" : $node->publishEndAt->format('Y-m-d H:i:s'));
		
		//如果文档已经存在，则用更新
		$this->search->setQuery("id:$nid"); // 设置搜索语句		
		$docs = $this->search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组中
		$count = $this->search->count(); // 获取搜索结果的匹配总数估算值

	    if($count > 0)
			$this->index->update($doc);
		else
			$this->index->add($doc);

    }

	public function delDoc($nid)
	{
		$this->index->del($nid);
	}
	
	public function updateDoc($nid)
	{
		$repo = $this->doctrine->getRepository(Node::class);
		$qb = $repo->createQueryBuilder('n')
				->where('n.id=:id')->setParameter('id', $nid)
				->andWhere('n.status=:status')->setParameter('status', 'published')//只允许搜索发布了的内容
				->setMaxResults(1)
			;

        $node = $qb->getQuery()->getOneOrNullResult();
		if(!$node)
			return false;

		$doc = new \XSDocument;
		$doc->setFields( (array)$node);
		$doc->setField('create_at', ($node->createdAt == null ) ? null : $node->createdAt->format('Y-m-d H:i:s'));
		$doc->setField('publish_end_at', ($node->publishEndAt == null )? "2038-01-01 00:00:00" : $node->publishEndAt->format('Y-m-d H:i:s'));

		$this->index->update($doc);
	}
	
	public function search($Q, $limit=30, $page=1, $publishrange='')
	{	
		$this->search->setQuery($Q);
		//$this->search->setFacets(array('title'));
		$this->search->addWeight('title', $Q);
		$this->search->setLimit($limit, $limit*($page-1));
		$this->search->setSort('create_at');
		
		/////////////
		////20171207
		//排除过期的内容
		if($publishrange != ''){
			$this->search->addRange('publish_end_at', $publishrange, null); // to 为null相当于 >= from
		}		
		/////////////
		
		$docs = $this->search->search();
		
		//转换数据结构
		$nodes = array();
		foreach($docs as $doc)
		{
		    //排除notetype为file、folder等
            $nodetype = $doc->f('nodetype');
            if( $nodetype=='nodefile' || $nodetype=='nodetemplate' || $nodetype=='nodefolder' )
                continue;

			$nodes[] = array(
					'id' => $doc->f('id'),
					'title' => $doc->f('title'),
					'dir' => $doc->f('dir'),
					'content' => $doc->f('content'),
					'create_at' => $doc->f('create_at')
					
			);
		}
		
		return array(
				'nodes' => $nodes, 
				'count' => $this->search->count(),
				'pages' => ceil($this->search->count()/$limit),
				'curpage' => $page,
				'words_search' => $Q,
				'words_hot' => $this->search->getHotQuery(5),
				'words_related' =>$this->search->getRelatedQuery($Q, 5)
				);
	}
	
}