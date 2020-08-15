<?php

namespace Heccjj\SkatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Heccjj\SkatingBundle\Lib\Common;
use Heccjj\SkatingBundle\Lib\Search\SearchNull;
use Heccjj\SkatingBundle\Lib\Search\SearchXS as Search;

class FrontController extends AbstractController
{
    /**
     * @Route("/search/{Q}",  name="cms_front_search",  requirements={"Q" = "[\s\S]*"})
     */
    public function searchAction($Q = '', Search $search) {
		if($Q=='' || $Q=='请输入关键字')
			exit;

        $page = 1;
        $pattern = '/\/p\/(\d+)$/';
        if (preg_match($pattern, $Q, $match)) {
            $page = $match[1];
            $Q = preg_replace($pattern, '', $Q);
        }

        $limit = 30;
		
		//去除过期的内容
		$from = date("Y-m-d H:i:s");		
		
        $result = $search -> search($Q, $limit, $page, $from);
		/////
		
        $query = new Common();
        return $this -> render('@HeccjjSkating/front/search_result.html.twig', [
            'node' => ['dir'=>''],
            'result' => $result,
            'query' => $query,
        ]);
    }

    function _displayNode($node, $page = null) {
        $viewdata = [];
    	if(!$node)  //节点不存在
			return $this -> render('@HeccjjSkating/front/error_noexist.html.twig', [
                'viewdata' => $viewdata,
            ]);

        //权限控制，如果节点is_inner==1，则只有内网地址可以访问
        if($node->isInner == 1){
            $client_ip = $this->getClientIP();
            if($client_ip == 'unknown'){
                $client_ip = $_SERVER['REMOTE_ADDR'];
            }
            $allow_ips = array('202.114.71.171', '202.114.71.241', '202.114.71.243');

            if(!in_array($client_ip, $allow_ips)){
                return $this -> render('@HeccjjSkating/front/error_noexist.html.twig', [
                    'viewdata' => $viewdata,
                ]);
            }
        }
		
        $nodetype = $node->typeName;
        $query = new Common();
        $viewdata['curpage'] = ($page == null) ? 1 : $page;
        
        //如果是新闻外部链接，则让用户跳转到外部链接
        //if( ($nodetype=='news')  and ($node['sourceurl'] != '') )
        if (isset($node->sourceUrl) && $node->sourceUrl != '')
            return $this -> redirect($node->sourceUrl);

        //如果是目录，则搜索是否有名为index的对象，有则用该对象代表目录显示
        $p_template = '';
        if ($nodetype == 'nodefolder') {
            if ($node->dir == '/')
                $path = $node->dir . $node->slug;
            else
               $path = $node->dir . '/' . $node->slug;

            $p_meta = $query -> getNodeMeta($node->id, 'template');
            //有模板元信息则用该模板显示
            if ($p_meta)
                $p_template = $p_meta->getValue();
            
            $node_folder_index = $query -> getNodeByPath($path . '/index');
            if ($node_folder_index) {
                //如果有index对象则直接显示该对象，但不能是notetemplate
                if ($node_folder_index->typeName != 'nodetemplate'){
                    $nodetype = $node_folder_index->getTypeName();
                    $node = $node_folder_index;
                }else{ //index为template直接显示
                    $node = $node_folder_index;
                    $nodetype = 'nodetemplate';
                }
            }
        }  

        //模板直接显示
        if ($nodetype == 'nodetemplate'){
            $template = $this->get('twig')->createTemplate($node->content);
            return new Response($template -> render([
                'node' => $node,
                'query' => $query,
                'viewdata' => $viewdata
            ]) );
        }

        $meta = $query -> getNodeMeta($node->id, 'template');
        //有模板元信息则用该模板显示
        if ($meta) {
            $template = $meta->getValue();
            return $this -> render($template, [
                'node' => $node,
                'query' => $query,
                'viewdata' => $viewdata
            ]);
        }

        //有父目录模板则用该模板显示
        //if ($template = $query -> getNodetypeTemplate($node->dir, $node->typeName)) {
        //    //$logger->info('Has dir template!');
        //    return $this -> render($template, $viewdata);
        //}

        if ($p_template) 
            return $this -> render($p_template, [
                'node' => $node,
                'query' => $query,
                'viewdata' => $viewdata
            ]);

        //找不到显示模板,以相应对象的默认显示模板进行显示
        return $this -> render('@HeccjjSkating/front/' . $node->typeName . '.html.twig', [
            'node' => $node,
            'query' => $query,
            //'dir' => $dir,
            'viewdata' => $viewdata
          ]
        );
    }

    public function renderStrAction($str, $viewdata=null) {
    	if(is_null($viewdata))
			$viewdata=$this->viewdata;
        try {
            return $this->render($str, $viewdata);
        } catch (\Twig_Error_Runtime $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
           //continue;
        }
        return new Response($str);
    }

    /**
     * @Route("/getacc/{pnid}/{accnid}", name="cms_front_getacc", requirements={"pnid" = "\d+", "accnid" = "\d+"})
     * 
     * pnid  调用文档nid
     * accnid 附件nid
     */
    public function getAccAction($pnid, $accnid) {
        $query = new Common();
        $pnode = $query->getNodeDetail($pnid);
        $accnode = $query->getNodeDetail($accnid);

        if(!$pnode or !$accnode)
            exit;

        $file_dir = trim($accnode->fileSubPath);
        if($file_dir)
            $file_dir = $this->getParameter('upload_directory') . $file_dir . '/';
        else
            $file_dir = $this->getParameter('upload_directory');

        $file_name = $accnode->getFileName();
        if( $file_name == '' or !file_exists($file_dir . $file_name))
            exit;
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename( $file_dir . $file_name) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($file_dir . $file_name) );

		// 读取文件
        readfile($file_dir . $file_name);
		
        exit;
    }


    /////////////////////////////////////////////////////
    //置于最后
    /////////////////////////////////////////////////////

    /**
     * @Route("/id/{nid}/page/{page}", name="heccjjskating_front_displayid_page", requirements={"nid" = "\d+", "page" ="\d+"})
     * 以id号方式显示某具体节点内容
     */
    /*public function displayIdPageAction($nid, $page) {
        //前台都用CmsQuery来读取
        $node = $this -> query -> getnodebyid($nid);

        return $this -> displayidAction($node, $page);
    }*/

    /**
     * @Route("/id/{nid}", name="heccjjskating_front_displayid", requirements={"nid" = "\d+"})
     * 以id号方式显示某具体节点内容
     */
    public function displayIdAction($nid, $page = null) {
        //前台都用CmsQuery来读取
        $query = new Common();
        $node = $query -> getnodebyid($nid);

        return $this -> _displayNode($node, $page);
    }

    /**
     * @Route("{path}/page/{page}", name="heccjjskating_front_displaypath_page", requirements={"path" = "\S*", "page" ="\d+"})
     */
    function displayPageAction($path = '', $page) {
        return $this->displayAction($path, $page);
    }
    
    //显示主页
    /**
     * @Route("/", name="heccjjskating_front_home")
     * 以id号方式显示某具体节点内容
     */
    public function homeAction(Common $common) {
        $viewdata = [];
        //$this->query->parseTmptTag('file:@HeccjjSkating/front/node_newsindex.html.twig');
        //$this->query->parseTmptTag('Front:node_newsindex.html.twig');
        //$this->query->parseTmptTag('node_newsindex.html.twig');

        //$this->query->parseTmptTag('node:path:/ab/c/d');
        //$this->query->parseTmptTag('node:nid:110');
        //echo $this->query->renderFile("fd  dsf  dd <img src='{{image:path:/ttt/hewang:url}}' /><br><img src='{{image:path:/ttt/hewang:url}}' />");
        return $this -> render('@HeccjjSkating/front/home.html.twig', [
            'viewdata'=>$viewdata,
            'query'=> $common,
        ]);
    }

    /**
     * 必须放在最后，因为把所有uri当成path
     * @Route("{path}", name="heccjjskating_front_displaypath", requirements={"path" = "\S*"})
     * 以path方式显示某具体节点内容
     */
    public function displayAction($path = '', $page = null) {
         $common = new Common();
        //如果路径不是以"/"开始，则加上"/"
        $pattern = '/^\//';
        if (preg_match($pattern, $path) != 1)
            $path = "/" . $path;

        //显示主页
        if ($path == "/")
            return $this -> forward("HeccjjSkating:Front:home");

        //前台都用CmsQuery来读取
        $node = $common->getNodeByPath($path);

        return $this->_displayNode($node, $page);
    }

    /**
     * 获取客户端IP地址
     * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed 
     */
    function getClientIP($type = 0, $adv = true) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

}
