<?php
namespace Heccjj\SkatingBundle\Lib;

use Heccjj\SkatingBundle\Entity\Node;
use Heccjj\SkatingBundle\Entity\Tag;
use Heccjj\SkatingBundle\Entity\Meta;
use Heccjj\SkatingBundle\Entity\Version;

/**
 * 前台查询获取某目录下相关node的函数，包含其子目录中的node，以便在twig中调用，与后台查询相比要注意：
 * 1. 必须是已发布内容：status:published
 * 2. 当前时间要小于发布结束时间：date()<=node->publishEndAt
 * 3. 不能是已经删除文件
 */

class Common
{	
	public function __construct(){
		global $kernel;
		$this->doctrine = $kernel->getContainer()->get('doctrine');
		$this->conn = $kernel->getContainer()->get('database_connection');
	}

	//////////////////////////////
	/**
	 * 指定路径，生成路径面包屑
	 */
	public function breadcrumb($dir)
	{
		$items = explode('/', $dir);
		
		$dir = '';
		$crumb = array();
		$crumb[]=array('path' => '/', 'title'=>'首页');

		$repo = $this->doctrine->getRepository(Node::class);
		foreach ($items as $item) {
			if($item == '')
				continue;
			
			$dir .= '/'. $item;
			$node = $repo->findOneByPath($dir);
			$crumb[]=array('path' => $dir,
						   'title' => $node->title );
		}
		
		return $crumb;				
	}
	
	/**
	 * 获取内容完整信息
	 */
	public function getNodeDetail($nid)
	{
		$repo = $this->doctrine->getRepository(Node::class);
		$node = $repo->find($nid);
		if(!$node)
			return false;
		$nodetype = $node->getType();
        
		$entity_class = "Heccjj\\SkatingBundle\\Entity\\" . $nodetype;
		if(!class_exists($entity_class))
			$entity_class = "App\\Entity\\" . $nodetype;

		$repo = $this->doctrine->getRepository($entity_class);
		$node = $repo->find($nid);
		if(!$node)
			return false;

		//status, is_deleted和publishEndAt不满足条件则丢弃
		if($node->status != 'published')
			return false;

		if($node->getIsDeleted())
			return false;

		if($node->publishEndAt != null and $node->publishEndAt < new \DateTime())
			return false;
		
		return $node;
	}
	
	/*
     * 由id获取节点
     */
	public function getNodeByID($nid)
	{		
		return $this->getNodeDetail($nid);
	}

	public function getNodeByPath($path)
	{
		$repo = $this->doctrine->getRepository(Node::class);
		
		//根目录特殊处理
		if($path == '/')
			return array('title'=>'根目录', 'nodetype'=>'folder', 'name'=>'root', 'author'=>'administrator');
		
		$dir = dirname($path);
		$slug = basename($path);
		
		$qb = $repo->createQueryBuilder('n')
			->where("n.dir = :dir and n.slug= :slug")
			->andWhere('n.status= :status')
			->andWhere('n.isDeleted= :isDeleted')
			->andWhere("n.publishAt IS NOT NULL AND CURRENT_TIMESTAMP() > n.publishAt AND (n.publishEndAt IS NULL OR CURRENT_TIMESTAMP() < n.publishEndAt)")
			->setParameter('dir', $dir)
			->setParameter('slug', $slug)
			->setParameter('status', 'published')
			->setParameter('isDeleted', false)
			->setMaxResults(1)
			;

		$node = $qb->getQuery()->getOneOrNullResult();
		if( !$node )
			return false;
		
		return $this->getNodeDetail($node->getId());
	}

	/** 
	 * 获取version
	 * todo:注意是否需要检查是否发布，是否删除等
	 * 
	 */
	public function getVersionByID($id){
		$repo = $this->doctrine->getRepository(Version::class);

		$qb = $repo->createQueryBuilder('n')
			->where("n.id=:id")
			->setParameter('id', $id)
			->setMaxResults(1)
			;

		$nv = $qb->getQuery()->getOneOrNullResult();
		if( !$nv )
			return false;
		
		return $nv->getNode();
	}

	/**
	 * 获取某目录下相关node的函数，以便在twig中调用
	 * * dir: string or []
	 * nodetype: string
	 * limit: int
	 * tags: []
	 * sub: bool
	 * metas: []
	 * page: int
	*/
	public function getlist($dirs, $nodetype = '', $limit = 5, $tags = array(), $sub = false, $metas = array(), $page = null)
	{
		$repo = $this->doctrine->getRepository(Node::class);
		$param = array('status' => 'published', 
					   'isDeleted' => false,
					   'index' => 'index', 
					);		
		$qb = $repo->createQueryBuilder('n')
				->where("n.slug <> :index");  //index用于显示目录，不可搜索
		
		//目录可以是一个字符串，也可以是一个数组。如果值前面有！表示不包含该目录
		if(!is_array($dirs))  //不是数组，则转换为数组，以便于处理
			$dirs = array($dirs);
		
		$i = 0;
		$str_con = '';
		foreach($dirs as $dir)
		{
			//如果以!开头，则应排除
			$exclude = false;	
			$pattern = '/^!/';
			if(preg_match($pattern, $dir) == 1){
				$dir = substr($dir, 1);
				$exclude = true;
			}
			
			//如果路径不是以"/"开始，则加上"/"
			$pattern = '/^\//';
			if(preg_match($pattern, $dir) != 1)
				$dir = "/" . $dir;

			//是否包含子目录
			if($sub){
				if($exclude)
					$str_con .= " AND n.dir NOT LIKE :dir$i";
				else
					$str_con .= " OR n.dir LIKE :dir$i";
				
				$param["dir$i"] = $dir.'%';		
			}else{
				if($exclude)
					$str_con .= " AND n.dir <> :dir$i";
				else
					$str_con .= " OR n.dir = :dir$i";
				
				$param["dir$i"] = $dir;		
			}
			$i++;
		}

		//先要去除第一个"AND"或者"OR";
		$pattern = "/^\s+((AND)?(OR)?)/";
		$str_con = preg_replace($pattern, '', $str_con);
		
		$qb->andWhere($str_con);
		$qb->andWhere('n.status = :status');
		$qb->andWhere('n.isDeleted = :isDeleted');

		//当前日期要在发布期间之内
		$qb->andWhere("n.publishAt IS NOT NULL AND CURRENT_TIMESTAMP() > n.publishAt AND (n.publishEndAt IS NULL OR CURRENT_TIMESTAMP() < n.publishEndAt)");

		//如果没有指定类型，则搜索nodenews，nodenotice
		if( $nodetype == null )
		{			 
	  	    $qb->andWhere("n.typeName = 'nodenews' OR n.typeName = 'nodenotice' ");
		}else{
	  	    $qb->andWhere('n.typeName = :typeName');
			$param['typeName'] = 'node' . $nodetype;
		}

		if( $tags )
		{
			 foreach($tags as $k => $tag)
			 {
			 	$qb->andWhere("n.tagsText LIKE :tag$k");
				$param["tag$k"] = "%$tag%";
			 }  
		}

		if( $metas )
		{
			$qb->leftJoin('n.metas', 'm');
			foreach($metas as $k => $v)
			{
				$qb->andWhere("m.item=:item$k")
				   ->andWhere("m.value=:value$k");
				$param["item$k"] = $k;
				$param["value$k"] = $v;
			}
		}

		$qb->setParameters($param);

		//取条数，算页数
		if($page != null)
		{
			$qb->select('COUNT(n.id) as pages');
			$result = $qb->getQuery()->execute();
			if($result)
			{
				$items = $result[0]['pages'];
				$pages = ceil($items / $limit);
			}
			else
				$pages = 1;
			
			$qb->setFirstResult($limit * ($page-1) );
		}

		//取当前页列表
		$qb->select('n');
		$qb->setMaxResults( $limit )
			->orderBy('n.createAt', 'DESC');

		$nodes = $qb->getQuery()->execute();

		if($page == null)
			return $nodes;
		else
			return array('pages'=>$pages, 'nodes'=>$nodes, 'items'=>$items);
	}

	/**
	 * 合并两个列表，去除重复条目
	 */
	public function mergelist($nodes1=array(), $nodes2=array()){

		$nodes = $nodes1;
		$nids = array();
		foreach($nodes as $node){
			$nids[] = $node->id;
		}

		foreach($nodes2 as $node){
			if(!in_array($node->id, $nids)){
				$nids[] = $node->id;
				$nodes[] = $node;
			}
		}
		return $nodes;
	}

	/**
	 * 解析各种特殊内容
	 * Acc  File 
	 */
	public function renderContent($text){
		$text = $this->renderAcc($text);
		$text = $this->renderFile($text);

		return $text;
	}

	/*
	 *解析附件
	 * 图文字段特殊标记：
	 * 文件：{{acc:pid:nid:xxx}}/{{acc:pid:path:/a/b/c}}
	 * 
	 * 如：
	 * {{acc:8231:nid:8229}}
	 * {{acc:8231:path:/graduate/notice/files/202006058iah }}
	 *
	 */
	public function renderAcc($text){
		$pattern = '/\{\{acc:(.+):(.+):(.+)\}\}/U';
        $text = preg_replace_callback($pattern,
            function($match){
				$pid = $match[1];
                switch($match[2])
                {
                    case 'nid':
                        $node = $this->getNodeByID($match[3]);
                        break;
                        
                    case 'path':
                        $node = $this->getNodeByPath($match[3]);
                        break;
				}
				if(!$node)
					return '禁止访问';
				$file = '/getacc/' . $pid . '/' . $node->getId();
                $title = $node->getTitle();
                if(@$match[3] == ':url')
                    return $file;
                else
                    return "<a href='$file'>$title</a>";
            }   ,$text);
    
        return $text;
	}

	/*
	 * 将文本中的资源符号解码

	 * 图文字段特殊标记：
	 * 图片复杂：{{image:nid:xxx}}/{{image:path:/a/b/c}}
	 * 文件：{{file:nid:xxx}}/{{file:path:/a/b/c}}
	 * 
	 * nid:
	 * vid:

	 * 对于图片或者文件，替换时有简单和复杂两种：
	 * 简单即只生成URL:{{image:nid:xxx:url}}/{{image:path:/a/b/c:url}}
	 * 复杂则会加图片说明），默认为复杂
	 */	
	public function renderFile($text)
	{
		//处理图片
		$pattern='/\{\{image:([^:]+):([^:]+)(:[^:]+)?\}\}/U';
		$text=preg_replace_callback($pattern,
			function($match){
				switch($match[1])
				{
					case 'nid':
						$node = $this->getNodeByID($match[2]);
						break;
						
					case 'path':
						$node = $this->getNodeByPath($match[2]);
						break;

					case "vid":  //查看历史版本
						$node = $this->getVersionByID($match[2]);
						break;
				}

				if(!$node)
					return '禁止访问';
                if($node->fileSubPath)
				    $file = '/uploadfiles/'. $node->fileSubPath.'/'. $node->fileName;
                else
                    $file = '/uploadfiles/'. $node->fileName;
                
				$title = $node->title;
				$nid = $node->id;
				if(@$match[3] == ':url')
					return $file;
				else
					return "<div class='nodeimage'><img src='$file' width='550' /><br>$title</div>";
			}	,$text);
		
        //处理文件
        $pattern='/\{\{file:([^:]+):([^:]+)(:[^:]+)?\}\}/U';
        $text=preg_replace_callback($pattern,
            function($match){
                switch($match[1])
                {
                    case 'nid':
						$node = $this->getNodeByID($match[2]);
                        break;
                        
                    case 'path':
						$node = $this->getNodeByPath($match[2]);
                        break;
				}
				if(!$node)
					return '禁止访问';
                if($node->fileSubPath)
                    $file = '/uploadfiles/' . $node->fileSubPath . '/' . $node->fileName;
                else
                    $file = '/uploadfiles/' . $node->fileName;
                
                $title = $node->title;
                $nid = $node->id;
                if(@$match[3] == ':url')
                    return $file;
                else
                    return "<div class='nodefile'><a href='$file' />$title</a></div>";
            }   ,$text);
    
        return $text;
	}
	
	/*
	 * 分页：{{page:title:xxx}}
	 * 
	 * 返回当前页内容和标题列表
	 */
	public function renderPage($curpage){
    //处理分页
 		$pattern='/\{\{page:([^:]+):([^:]+)\}\}/';
		return preg_replace_callback($pattern,
			function($match){
				switch($match[1])
				{
					case 'nid':
						$node = $this->getNodeByID($match[2]);
						break;
						
					case 'path':
						$node = $this->getNodeByPath($match[2]);
						break	;
				}
				$file ='/newsite/uploadfiles/' . $node['file'];
				$title = $node['title'];
				$nid = $node['nid'];
				if($match[3] == 'url')
					return $file;
				else
					return "<div class='nodefile'><a href='$file' />$title</a></div>";
			}	,$text); 		
	}
	
	//获取某node的某meta，不存在则返回False
	public function getNodeMeta($nid, $item)
	{
		$repo = $this->doctrine->getRepository(Meta::class);		
		return $repo->findOneByIdItem($nid, $item);
	}
		
	public function substr($string, $start=0, $length=null)
	{
		mb_internal_encoding('utf-8');
		if($length)
			$str = mb_substr($string, $start, $length);
		else
			$str = mb_substr($string, $start);
		
		if(strlen($str) < strlen($string))
			if($start >= 0)
				return $str . '...';
			else
				return '...' . $str;
		else
			return $str;			
	}
	
	function randomAlphaNum($length){
		//$alphNums = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";       
		//$alphNums = "abcdefghijklmnopqrstuvwxyz";
		$alphNums = "0123456789abcdefghijklmnopqrstuvwxyz";
		$newString = str_shuffle(str_repeat($alphNums, rand(1, $length)));
		
		return substr($newString, rand(0,strlen($newString)-$length), $length);
	}

	/*
	 * 输出格式与parseTmptTag必须一致
	 * 	文件：
	    SkatingCmsBundle:Front:node_newsindex.html.twig
			Front:node_newsindex.html.twig
			
			模板是数据库中node系统：
			node:path:/abc
			node:nid:xyz
	 * 
	 * 如：news:node:path:/abc
	 *    notice:Front:node_newsindex.html.twig
	 */
	// public function getNodetypeTemplate($dir, $nodetype)
	// {
	// 	$repo = $this->doctrine->getRepository(Node::class);
		
	// 	if(substr_count($dir, '/') == 1)
	// 		$str_concat = "CONCAT('/' , n.name, '%')";
	// 	else
	// 		$str_concat = "CONCAT(n.dir , '/' , n.name, '%')";
		
	// 	$qb = $repo->createQueryBuilder()
	// 	        ->select('n.dir, n.name, m.item, m.value')
	// 			->from($model->node_table, 'n')
	// 			->join('n', $model->node_meta_table, 'm', 'n.nid=m.nid')
	// 			->where("'$dir' LIKE {$str_concat}")
	// 			->andwhere("m.item='template'")
	// 			->orderby('n.dir DESC, n.name');

    //     global $kernel;
    //     //$logger = $kernel->getContainer()->get('logger');
    //     //$logger->info($qb->getSQL());
	// 	$node = $this->db->fetchAll($qb->getSQL());
	// 	if(!$node)
	// 		return FALSE;

	// 	//$template_str = $node[0]['value'];  //第一个最长，是与当前对象最接近的。
		
	// 	//匹配模式
	// 	//$pattern="/$nodetype:([^\s,]+)[\s,]*/";

	// 	//$pm = preg_match($pattern, $template_str, $matches);
	// 	//if($pm <= 0)
	// 	//	return FALSE;
		
	// 	//return ($matches[1]);

    //     return $node[0]['value'];
	// }
	
	//获取指定nid数组的简要信息
	// public function getNodesBrief($node_nids = array())
	// {
	// 	if(!$node_nids)
	// 		return FALSE;
		
	// 	$model = new Nodemodel();
	// 	$qb = $this->db->createQueryBuilder()
	// 				->select('*')
	// 				->from($model->node_table, 'n');
		
	// 	$str = implode(",", $node_nids);
		
	// 	$qb->where("n.nid in($str)" )
	// 	   ->orderBy("FIELD(n.nid, $str)");
		
	// 	return $model->db->fetchAll($qb->getSQL());
	// }
	

	public function getFilethumb($filename){
		global $kernel;
        $filedir = $kernel->getContainer()->getParameter('upload_directory'); 

        $file=$filedir . $filename;

        if(file_exists($file . '.thumn1.png'))
            return $filename . '.thumn1.png';

        return $filename;
    }
	
	//将" "分隔的字符串转换为数组
	public function str2array($string)
	{
		$arr = explode(',', $string);
		//$arr = preg_split('/[\s,]+/', $string); //可以有多个空格
		return $arr;
	}
	
	
}
