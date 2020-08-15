<?php
namespace Heccjj\SkatingBundle\Lib\Search;

/*
 * 无搜索环境
 */
class SearchNull implements SearchInterface
{
	function createIndex($type='')
	{

	}

	function search($Q, $limit=30, $page=1)
	{
		return array(
			'nodes' => null, 
			'count' => 0,
			'pages' => 0,
			'curpage' => 0,
			'words_search' => $Q,
			'words_hot' => '',
			'words_related' => ''
			);
	}

    function addDoc($nid){
        
    }

	function delDoc($nid)
	{		

	}

    function updateDoc($nid)
	{

	}

}