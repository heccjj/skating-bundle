<?php
namespace Heccjj\SkatingBundle\Lib;

/**
* created by wangbinandi@gmail.com at 2008-12-29 21:12
* 汉字拼音首字母工具类
*  注： 英文的字串：不变返回(包括数字)    eg .abc123 => abc123
*      中文字符串：返回拼音首字符        eg. 王小明 => WXM
*      中英混合串: 返回拼音首字符和英文   eg. 我i我j => WIWJ
*  eg.
*  $py = new PinYin();
*  $result = $py->getfirstchar('王小明');
*/

class PinYin_BK
{
  protected $db;
  
  public function __construct(){
    global $kernel;
    $this->doctr =$kernel->getContainer()->get('doctrine');
    //$this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $this->db = $kernel->getContainer()->get('database_connection');
  }
  
  /*
   * $con条件, 如：$con=array('idcard'=>$idcard);
   */
  public function updatePY($table, $field, $con){
    //获取字段内容
     $qb = $this->db->createQueryBuilder() 
        -> select("*") 
        -> from($table, 't');
        
     foreach($con as $k=>$v)
     {
       $qb->where("$k=:$k");
       $param = array($k=>$v);
     }
    $str = $this->db->fetchAssoc($qb->getSQL(), $param);
    
    //生成首字母
     $value[$field.'_PY']=$this->getfirstchar($str);
     
     //写回
     $this->db->update($table, $value, $con);
  }

  /*取字符串拼音首字母*/
  function _get($str){     
    $fchar = ord($str{0});  
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($str{0});  
    $s1 = @iconv("UTF-8","gb2312", $str);  
    $s2 = @iconv("gb2312","UTF-8", $s1);  
    if($s2 == $str){$s = $s1;}
    else{$s = $str;}  
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;  
    if($asc >= -20319 and $asc <= -20284) return "A";  
    if($asc >= -20283 and $asc <= -19776) return "B";  
    if($asc >= -19775 and $asc <= -19219) return "C";  
    if($asc >= -19218 and $asc <= -18711) return "D";  
    if($asc >= -18710 and $asc <= -18527) return "E";  
    if($asc >= -18526 and $asc <= -18240) return "F";  
    if($asc >= -18239 and $asc <= -17923) return "G";  
    if($asc >= -17922 and $asc <= -17418) return "I";  
    if($asc >= -17417 and $asc <= -16475) return "J";  
    if($asc >= -16474 and $asc <= -16213) return "K";  
    if($asc >= -16212 and $asc <= -15641) return "L";  
    if($asc >= -15640 and $asc <= -15166) return "M";  
    if($asc >= -15165 and $asc <= -14923) return "N";  
    if($asc >= -14922 and $asc <= -14915) return "O";  
    if($asc >= -14914 and $asc <= -14631) return "P";  
    if($asc >= -14630 and $asc <= -14150) return "Q";  
    if($asc >= -14149 and $asc <= -14091) return "R";  
    if($asc >= -14090 and $asc <= -13319) return "S";  
    if($asc >= -13318 and $asc <= -12839) return "T";  
    if($asc >= -12838 and $asc <= -12557) return "W";  
    if($asc >= -12556 and $asc <= -11848) return "X";  
    if($asc >= -11847 and $asc <= -11056) return "Y";  
    if($asc >= -11055 and $asc <= -10247) return "Z";  
    return null;  
  }  
   
  function getfirstchar($zh){  
       $ret = "";  
       $s1 = iconv("UTF-8","gb2312", $zh);  
       $s2 = iconv("gb2312","UTF-8", $s1);  
       if($s2 == $zh){$zh = $s1;}  
       for($i = 0; $i < strlen($zh); $i++){  
           $s1 = substr($zh,$i,1);  
           $p = ord($s1);  
           if($p > 160){  
               $s2 = substr($zh,$i++,2);  
               $ret .= $this->_get($s2);  
           }else{  
               $ret .= $s1;  
           }  
       }  
       return $ret;  
  } 
}

class PinYin
{
    private $_pinyins = array(
        176161 => 'A',
        176197 => 'B',
        178193 => 'C',
        180238 => 'D',
        182234 => 'E',
        183162 => 'F',
        184193 => 'G',
        185254 => 'H',
        187247 => 'J',
        191166 => 'K',
        192172 => 'L',
        194232 => 'M',
        196195 => 'N',
        197182 => 'O',
        197190 => 'P',
        198218 => 'Q',
        200187 => 'R',
        200246 => 'S',
        203250 => 'T',
        205218 => 'W',
        206244 => 'X',
        209185 => 'Y',
        212209 => 'Z',
    );
    private $_charset = null;
    /**
     * 构造函数, 指定需要的编码 default: utf-8
     * 支持utf-8, gb2312
     *
     * @param unknown_type $charset
     */
    public function __construct( $charset = 'utf-8' )
    {
        $this->_charset    = $charset;
    }
    /**
     * 中文字符串 substr
     *
     * @param string $str
     * @param int    $start
     * @param int    $len
     * @return string
     */
    private function _msubstr ($str, $start, $len)
    {
        $start  = $start * 2;
        $len    = $len * 2;
        $strlen = strlen($str);
        $result = '';
        for ( $i = 0; $i < $strlen; $i++ ) {
            if ( $i >= $start && $i < ($start + $len) ) {
                if ( ord(substr($str, $i, 1)) > 129 ) $result .= substr($str, $i, 2);
                else $result .= substr($str, $i, 1);
            }
            if ( ord(substr($str, $i, 1)) > 129 ) $i++;
        }
        return $result;
    }
    /**
     * 字符串切分为数组 (汉字或者一个字符为单位)
     *
     * @param string $str
     * @return array
     */
    private function _cutWord( $str )
    {
        $words = array();
         while ( $str != "" )
         {
            if ( $this->_isAscii($str) ) {/*非中文*/
                $words[] = $str[0];
                $str = substr( $str, strlen($str[0]) );
            }else{
                 $word = $this->_msubstr( $str, $i, 1 );
                $words[] = $word;
                 $str = substr( $str,  strlen($word) );
            }
         }
         return $words;
    }
    /**
     * 判断字符是否是ascii字符
     *
     * @param string $char
     * @return bool
     */
    private function _isAscii( $char )
    {
        return ( ord( substr($char,0,1) ) < 160 );
    }
    /**
     * 判断字符串前3个字符是否是ascii字符
     *
     * @param string $str
     * @return bool
     */
    private function _isAsciis( $str )
    {
        $len = strlen($str) >= 3 ? 3: 2;
        $chars = array();
        for( $i = 1; $i < $len -1; $i++ ){
            $chars[] = $this->_isAscii( $str[$i] ) ? 'yes':'no';
        }
        $result = array_count_values( $chars );
        if ( empty($result['no']) ){
            return true;
        }
        return false;
    }
    /**
     * 获取中文字串的拼音首字符
     *
     * @param string $str
     * @return string
     */
    public function getfirstchar( $str )
    {
        if ( empty($str) ) return '';
        if ( $this->_isAscii($str[0]) && $this->_isAsciis( $str )){
            return $str;
        }
        $result = array();
        if ( $this->_charset == 'utf-8' ){
            $str = iconv( 'utf-8', 'gb2312', $str );
        }
        $words = $this->_cutWord( $str );
        foreach ( $words as $word )
        {
            if ( $this->_isAscii($word) ) {/*非中文*/
                $result[] = $word;
                continue;
            }
            $code = ord( substr($word,0,1) ) * 1000 + ord( substr($word,1,1) );
            /*获取拼音首字母A--Z*/
            if ( ($i = $this->_search($code)) != -1 ){
                $result[] = $this->_pinyins[$i];
            }
        }
        return strtoupper(implode('',$result));
    }
    private function _getChar( $ascii )
    {
        if ( $ascii >= 48 && $ascii <= 57){
            return chr($ascii);  /*数字*/
        }elseif ( $ascii>=65 && $ascii<=90 ){
            return chr($ascii);   /* A--Z*/
        }elseif ($ascii>=97 && $ascii<=122){
            return chr($ascii-32); /* a--z*/
        }else{
            return '~'; /*其他*/
        }
    }

    /**
     * 查找需要的汉字内码(gb2312) 对应的拼音字符( 二分法 )
     *
     * @param int $code
     * @return int
     */
    private function _search( $code )
    {
        $data = array_keys($this->_pinyins);
        $lower = 0;
        $upper = sizeof($data);
        if ( $code < $data[0] ) return -1;
        for (;;) {
            if ( $lower > $upper ){
                return $data[$lower-1];
            }
            $tmp = (int) round(($lower + $upper) / 2);
            if ( !isset($data[$tmp]) ) return $data[$middle];
            else $middle = $tmp;
            if ( $data[$middle] < $code ){
                $lower = (int)$middle + 1;
            }else if ( $data[$middle] == $code ) {
                return $data[$middle];
            }else{
                $upper = (int)$middle - 1;
            }
        }
    }
}

