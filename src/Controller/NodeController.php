<?php
namespace Heccjj\SkatingBundle\Controller;

use Heccjj\SkatingBundle\Entity\Node;
use Heccjj\SkatingBundle\Entity\Tag;
use Heccjj\SkatingBundle\Entity\Meta;
use Heccjj\SkatingBundle\Form\NodeType;
use Heccjj\SkatingBundle\Form\MetaType;

use Heccjj\SkatingBundle\Entity\Version;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Heccjj\SkatingBundle\Lib\Common;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Heccjj\SkatingBundle\Lib\Search\SearchXS as Search;

class NodeController extends AbstractController
{
    /**
     * @Route("/", name="heccjjskating_node")
     */
    public function index()
    {
        return $this->render('@HeccjjSkating/admin/node/index.html.twig', [
            'controller_name' => 'NodeController',
        ]);
    }

    /**
     * 列出内容旧版本
     * @Route("/listversions/{nid}", name="cms_admin_listversions")
     * 
     * @Security("is_granted('view')")
     */
    public function listNodeVersions($nid)
    {
        //$repo = $this->getDoctrine()->getRepository(NodeVersion::class);
        //$nvs = $repo->getNodeVersions($nid);
        $repo = $this->getDoctrine()->getRepository(Node::class);
        $nvs = $repo->find($nid)->getVersions();
        
        return $this->render('@HeccjjSkating/admin/node/list_versions.html.twig', ['nvs' => $nvs]);

    }

    /**
     * @Route("/detailversion/{id}", name="heccjjskating_detailversion", requirements={"id" = "\d+"})
     * 
     * @Security("is_granted('view', id)")
     */
    public function detailversion($id = null)
    {
        if(empty($id))
            return new Response("请输入正确的内容ID号！");
            
        $repo = $this->getDoctrine()->getRepository(Version::class);
        $nv = $repo->find($id);

        if(!$nv)
            return new Response("无此版本号！");
    
        $node = $nv->getNode();

        return $this->forward('Heccjj\SkatingBundle\Controller\FrontController::_displayNode',[
            'is_version'=> true,
            'node' => $node
        ]);
    }

    /**
     * @Route("/listindlg{dir}", name="heccjjskating_listindlg_node", requirements={"dir" = ".*"}))
     * 
     * 用于FSCKeditor浏览文件、图片
     * 由于与/list路径重叠，需要放在/list路径之前
     */
    public function list_in_dlg($dir = '', Request $request, SessionInterface $session, Common $common)
    {
		//如果没有指定目录，则试着用session目录，还不存在则用'/'
		if($dir == '')
			$dir = $session -> get('workdir', '/');
        
        //如果路径不是以"/"开头，则加上"/"
		$pattern = '/^\//';
		if(preg_match($pattern, $dir) != 1)
			$dir = "/" . $dir;

        $repo = $this->getDoctrine()->getRepository(Node::class);
        $nodes = $repo->findByDir($dir);

        //在浏览对话框中显示时，要追加$_SERVER['QUERY_STRING']以传递参数。
        $querystring = $_SERVER['QUERY_STRING'];
        $session->set('querystring', $querystring);
        
        return $this->render('@HeccjjSkating/admin/node/list_in_dlg.html.twig', [
            'nodes' => $nodes,
            'query' => $common,
            'dir' => $dir,
            'querystring' => $querystring
        ]);
    }

    /**
     * @Route("/list{dir}", name = "heccjjskating_list_node", requirements = {"dir" = ".*"}))
     * 
     * @Security("is_granted('view', dir)")
     */
    public function list($dir = '', Request $request, SessionInterface $session, Common $common)
    {
		//如果没有指定目录，则试着用session目录，还不存在则用'/'
		if($dir == '')
            $dir = $session -> get('workdir', '/');
        
        //如果路径不是以"/"开头，则加上"/"
		$pattern = '/^\//';
		if(preg_match($pattern, $dir) != 1)
			$dir = "/" . $dir;

        if($dir != $session->get('workdir'))
            $session->set('workdir', $dir);

        $include_deleted = $session->get('include_deleted');

        $repo = $this->getDoctrine()->getRepository(Node::class);
        $nodes = $repo->findByDir($dir, $include_deleted);

        $nodetypes = $this->getParameter('nodetypes');
        
        return $this->render('@HeccjjSkating/admin/node/list.html.twig', [
            'nodes' => $nodes,
            'nodetypes' => $nodetypes,
            'dir' => $dir,
            'query' => $common
        ]);
    }

    /**
     * @Route("/create/{nodetype}", name="heccjjskating_create_node", requirements={"nodetype" = "\S*"})
     * 
     * @Security("is_granted('edit', nodetype)")
     */
    public function create($nodetype='', Request $request, SessionInterface $session, Common $common, Search $search)
    {
        //$session = $this->get( 'session' );
        $dir = $session -> get('workdir');

        if($nodetype == '')
            $nodetype = $request->request->get('nodetype');

        if(empty($nodetype))
            return new Response("请输入正确的内容类型");

        $entity_class = "Heccjj\\SkatingBundle\\Entity\\" . $nodetype;
        if(!class_exists($entity_class))
            $entity_class = "App\\Entity\\" . $nodetype;

        $node = new $entity_class();

        $node->publishEndAt = null;

        $action = $this->generateUrl('heccjjskating_create_node', array('nodetype' => $nodetype));

        $form_class = "Heccjj\SkatingBundle\\Form\\". $nodetype. "Type";
        if(!class_exists($form_class))
            $form_class = "App\\Form\\". $nodetype. "Type";

        $form = $this->createform($form_class, $node, ['action'=> $action]);


        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $node = $form->getData();

            $node->setTypeName(strtolower($nodetype));
            $node->setDir($dir);
            $node->setStatus('drafted');
            $node->setCreateAt();
            $node->setUpdateAt();
            $node->setMetasNode();
            $author = $this->getUser();
            $node->setAuthor($author);

            //////////////////
            //处理上传文件
            if($nodetype == 'NodeFile')
            {
                $result = $this->dealUploadFile($nodetype, $form);
                if($result){
                    $node->setFileName($result['newFilename']);
                    $node->setFileMime($result['newFilemime']);
                }
            }
            //////////

            $em = $this->getDoctrine()->getManager();
            $em->persist($node);
            $em->flush();

            //添加到搜索索引
            if( \in_array($nodetype, $this->getParameter('nodetypes_seacheable')))
                $search->addDoc($node->getId());

            $this->addFlash('success', '添加成功！');
            return $this->redirect($this->generateUrl('heccjjskating_list_node'));
        }

        return $this->render('@HeccjjSkating/admin/node/create.html.twig', [
            'form' => $form->createView(),
            'dir' => $dir,
            'nodetype' => $this->getParameter('nodetypes')[$nodetype],
            'query' => $common
        ]);
        
    }

    /**
     * @Route("/edit/{nid}", name="heccjjskating_edit_node", requirements={"nid" = "\d+"})
     * 
     * @Security("is_granted('edit', nid)")
     */
    public function edit( Node $nid=null, Request $request, SessionInterface $session, Search $search)
    {
        $dir = $session -> get('workdir');

        if(empty($nid))
            return new Response("请输入正确的内容ID号！");
        
        $node = $this->getDoctrine()
            ->getRepository(Node::class)
            ->find($nid);
        $nodetype = $node->getType();
        
        $entity_class = "Heccjj\\SkatingBundle\\Entity\\" . $nodetype;
        if(!class_exists($entity_class))
            $entity_class = "App\\Entity\\" . $nodetype;

        $node = $this->getDoctrine()
            ->getRepository($entity_class)
            ->find($nid);

        $nodev = clone $node;

        $form_class = "Heccjj\SkatingBundle\\Form\\". $nodetype. "Type";
        if(!class_exists($form_class))
            $form_class = "App\\Form\\". $nodetype. "Type";
        
        $form = $this->createform($form_class, $node);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $node = $form->getData();
            $node->setUpdateAt();
            $author = $this->getUser();

            //////////////////
            //处理上传文件
            if($nodetype == 'NodeFile')
            {
                $oldDir = $this->getParameter('upload_directory');
                $fileSubPath = $node->getFileSubPath();
                if( !empty($fileSubPath))
                    $oldDir .= $fileSubPath . '/';

                $oldFile = $node->getFileName();

                $result = $this->dealUploadFile($nodetype, $form);
                if($result){
                    $node->setFileName($result['newFilename']);
                    $node->setFileMime($result['newFilemime']);

                    //保留旧文件，以便在旧版本里可以查看
                    /*//最后要删除原来的文件
                    try {
                        unlink($oldDir . $oldFile);
                        $this->addFlash('success', '删除文件！' . $oldDir . $oldFile);
                    } catch (FileException $e) {
                        $this->addFlash('error', '删除文件出错！');
                    }*/
                }
            }
            //////////

            //备份原数据
            $nv = new Version();
            $nv->setAuthor($author);
            $nv->setNode($nodev);
            $nv->setCreateAt();

            $node->addVersion($nv);

            $em = $this->getDoctrine()->getManager();
            $em->persist($node);
            $em->flush();

            //更新搜索索引
            if( \in_array($nodetype, $this->getParameter('nodetypes_seacheable')))
                $search->updateDoc($nid);

            $this->addFlash('success', '更改成功！');

            return $this->redirect($this->generateUrl('heccjjskating_list_node'));
        }

        return $this->render('@HeccjjSkating/admin/node/edit.html.twig', [
            'form' => $form->createView(),
            'dir' => $dir,
        ]);
        
    }

    /**
     * @Route("/delete/{nid}", name="heccjjskating_delete_node", requirements={"nodetype" = "\d+"})
     * 
     * @Security("is_granted('delete', nid)")
     */
    public function delete(Node $nid=null, Search $search)
    {
        if(empty($nid))
            return new Response("请输入正确的内容ID号！");
        
        $node = $this->getDoctrine()
            ->getRepository(Node::class)
            ->find($nid);

        $nodetype = $node->getType();
        $entity_class = "Heccjj\\SkatingBundle\\Entity\\" . $nodetype;
        if(!class_exists($entity_class))
            $entity_class = "App\\Entity\\" . $nodetype;

        $node = $this->getDoctrine()
            ->getRepository($entity_class)
            ->find($nid);

        $nodev = clone $node;
        $author = $this->getUser();

        $flash = $node->getTitle() . '<' .$node->getId() . '>' ;
        $em = $this->getDoctrine()->getManager();
        //$em->remove($node);  
        //不要删除记录，仅修改其状态isDeleted=true
        $node->setIsDeleted(true);

        //备份原数据
        $nv = new Version();
        $nv->setAuthor($author);
        $nv->setNode($nodev);
        $nv->setCreateAt();

        $node->addVersion($nv);

        $em->persist($node);
        $em->flush();

        //文件不作处理，以便在旧版本里可以查看

        //删除搜索索引
        if( \in_array($nodetype, $this->getParameter('nodetypes_seacheable')))
            $search->delDoc($nid);
            
        $this->addFlash('success', $flash . ' 删除成功！');
        
        return $this->redirect($this->generateUrl('heccjjskating_list_node'));
    }

    /**
     * @Route("/detail/{nid}", name="heccjjskating_detail_node", requirements={"nodetype" = "\d+"})
     * 
     * @Security("is_granted('view', nid)")
     */
    public function detail(Node $nid=null)
    {
        if(empty($nid))
            return new Response("请输入正确的内容ID号！");
        
        $node = $this->getDoctrine()
            ->getRepository(Node::class)
            ->find($nid);

        $nodetype = $node->getType();
        $entity_class = "Heccjj\\SkatingBundle\\Entity\\" . $nodetype;
        if(!class_exists($entity_class))
            $entity_class = "App\\Entity\\" . $nodetype;
        
        $node = $this->getDoctrine()
            ->getRepository($entity_class)
            ->find($nid);

        $response = $this->forward('Heccjj\SkatingBundle\Controller\FrontController::_displayNode',[
            'node' => $node,
            'nodetype' => $nodetype,
        ]);

        return $response;
    }

    /**
	 * @Route("/set_include_delete/{include_deleted}", name="heccjjskating_set_include_delete")
     * 
	 * 获取一个随机的文件名
	 */
    public function setIncludeDeleted( $include_deleted = false, SessionInterface $session){
        $include_deleted = ($include_deleted === 'true');
        if($session->get('include_deleted') != $include_deleted)
            $session ->set('include_deleted', $include_deleted);
        
        return $this->redirect($this->generateUrl('heccjjskating_list_node'));
    }

    /**
     * @Route("/tags.json", name="tags", defaults={"_format": "json"})
     */
    public function tagsAction()
    {
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy([], ['name' => 'ASC']);

        return $this->render('@HeccjjSkating/admin/tags.json.twig', ['tags' => $tags]);
    }

    /**
	 * @Route("/ajax_getnodeslug/", name="heccjjskating_ajax_getnodename")
	 * 获取一个随机的文件名
	 */
	public function ajax_getNodeSlugAction(Request $request)
	{
        $title = $request->request->get('title');

        if($title == ''){
            $common = new Common();
            $slug = date('Ymd') . $common->randomAlphaNum(4);
        }else{
            $slugger = new AsciiSlugger('zh');
            $slug = $slugger->slug($title);
        }
		
        return new Response($slug);
    }
    
    /**
	 * @Route("/ajax_setnodestatus/{nid}/{nodetype}", name="heccjjskating_ajax_setnodestatus", requirements={"nid" = "\d+"})
     * 
	 * 获取一个随机的文件名
	 */
	public function ajax_setNodeStatusAction($nid='', $nodetype='', Search $search)
	{
        if(empty($nid))
            return new Response("请输入正确的内容ID号！");
    
        $node = $this->getDoctrine()
            ->getRepository(Node::class)
            ->find($nid);
        
        $status = $node->getStatus();

        $node_status = $this->getParameter('node_status');
        $arr_tmp=[];   //状态列表（只有关键字）
        $i=0;          //当前状态指针
        $j=0;
        foreach($node_status as $key => $val){
            $arr_tmp[] = $key;
            if($status == $key)
                $i = $j;
            $j++;
        }
        
        $i++;
        if($i == count($arr_tmp))
            $i=0;

        $next_status = $arr_tmp[$i];

        $node->setStatus($next_status);
        if($next_status == 'published')
            $node->setPublishAt(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($node);
        $em->flush();

        //更新搜索索引
        if( \in_array($nodetype, $this->getParameter('nodetypes_seacheable'))){
            if($next_status != 'published')
                $search->delDoc($nid);
            else
                $search->updateDoc($nid);
        }

        return new Response($next_status);
    }

    /**
	 * @Route("/ajax_setnodeisdeleted/{nid}/{nodetype}", name="heccjjskating_ajax_setnodeisdeleted", requirements={"nid" = "\d+"})
     * 
	 */
	public function ajax_setNodeIsDeletedAction($nid='', $nodetype, Search $search)
	{
        if(empty($nid))
            return new Response("请输入正确的内容ID号！");
    
        $node = $this->getDoctrine()
            ->getRepository(Node::class)
            ->find($nid);
        
        $isdeleted = $node->getIsDeleted();

        $node->setIsDeleted(!$isdeleted);
        $em = $this->getDoctrine()->getManager();
        $em->persist($node);
        $em->flush();

        //更新搜索索引
        if( \in_array($nodetype, $this->getParameter('nodetypes_seacheable'))){
            if(!$isdeleted)
                $search->delDoc($nid);
            else
                $search->updateDoc($nid);
        }
        return new Response(!$isdeleted);
    }

    
    /**
     * 创建迅搜索引
     * @Route("/createindex", name="cms_admin_searchindex")
     * 
     * @Security("is_granted('edit')")
     */
    public function createIndexAction(Search $search)
    {

        $search->index->stopRebuild();   //第一次创建时用
        $search->index->beginRebuild();  //第一次创建时用，会消除原索引

        $search->createIndex('nodenews');
        $search->createIndex('nodenotice');

        $search->index->endRebuild();  //第一次创建时用

        return new Response("索引创建完毕！");
    }

    private function dealUploadFile( $nodetype, $form ){
        $uploadfile = $form->get('uploadfile')->getData();
        $fileSubPath = $form->get('fileSubPath')->getData();

        if (strtolower($nodetype) =='nodefile' && $uploadfile) {
            $common = new Common();
            $safeFilename = date('Ymd') . $common->randomAlphaNum(5);
            $originalFileExt = pathinfo($uploadfile->getClientOriginalName(), PATHINFO_EXTENSION);
            if($originalFileExt)
                $newFilename = $safeFilename . '.' . $originalFileExt;  //$uploadfile->guessClientExtension();  //$uploadfile->guessExtension();
            else
                $newFilename = $safeFilename;

            $mimeTypes = new MimeTypes();
            $newFilemime = $mimeTypes->guessMimeType($uploadfile);
            //$newFilemime = $uploadfile->getClientMimeType();
            $newDir = $this->getParameter('upload_directory');
            if( !empty($fileSubPath))
                $newDir .= $fileSubPath . '/';
            
            try {
                $uploadfile->move(
                    $newDir,
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', '移动文件出错！');
            }

            return [
                'newFilename' => $newFilename,
                'newFilemime' => $newFilemime
            ];
        }

        return false;
    }
    
}
