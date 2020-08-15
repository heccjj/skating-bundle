<?php
namespace Heccjj\SkatingBundle\Lib\Search;

interface SearchInterface
{
	function createIndex($type='');
	function search($Q, $limit=30, $page=1);
    function addDoc($nid);
	function delDoc($nid);
	function updateDoc($nid);
}
