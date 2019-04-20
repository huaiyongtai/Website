<?php

/**
 * 过滤关键字并替换
 * 单实例模式
 * 运行本类需要 iconv 扩展库支持
 * 功能：文本关键字过滤， 支持多种编码实现，默认处理，输出utf-8
 * 接口说明
 *      filterKeywords::getInstance() 获取一个对象实例
 *      setKeywords($keywords) 设置关键字字库 $keywords 一维数组
 *      setTextStream($text) 设置需要处理的文本串
 *      fillText()  返回处理好的字符串
 *      scanText()  查找关键字 
 *      setCharEncoding( $inputEncoding, $outputEncoding=, $internalEncoding ) 设置处理字符输入， 输出，处理过程中的字符编码
 *      setReplaceChar( $replaceChar='*' ) 设置替换字符
 *
 *
 * 使用实例
 *      $obj = filterKeywords::getInstance();
 *      $obj->setKeywords($keywords);
 *      $obj->setTextStream($text);
 *      $obj->fillText();
 *
 * 关键字字库，只能是一个一维数组
 */

namespace DevSxe\Lib;

class FilterKeywords
{
    public static $_resource = null;
    //关键字源
    private $_keywordsSoruce = array();
    //处理文本数据源
    private $_text = '';
    //替换字符
    private $_replaceChar = '*';
    //替换好的文本串
    private $_fillText='';
    //输入的字符编码
    private $_inputEncoding='';
    //输出的字符编码
    private $_outputEncoding='';
    //运行处理的字符编码
    private $_internalEncoding='';

    private  function __construct()
    {

    }


    /**
     +----------------------------------------------------------
     * 构造单例模式
     +----------------------------------------------------------
     * @param void
     +----------------------------------------------------------
     * @return class
     +----------------------------------------------------------
     */
    public static function getInstance()
    {
        if(self::$_resource == null)
        {
            self::$_resource = new filterKeywords();
            return self::$_resource;
        }
        return self::$_resource;
    }

    /**
     +----------------------------------------------------------
     * 设置处理字符输入， 输出，处理过程中的字符编码
     +----------------------------------------------------------
     * @param $inputEncoding    string  输入的字符编码
     +----------------------------------------------------------
     * @param $outputEncoding     string  输出的字符编码
     +----------------------------------------------------------
     * @param $internalEncoding     string  运行处理的字符编码
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function setCharEncoding( $inputEncoding='ISO-8859-1', $outputEncoding='UTF-8', $internalEncoding='UTF-8' )
    {
        if (PHP_VERSION_ID < 50600) {
            $this->_inputEncoding = $inputEncoding;
            $this->_outputEncoding = $outputEncoding;
            $this->_internalEncoding = $internalEncoding;
            iconv_set_encoding ( 'input_encoding' , $this->_inputEncoding );
            iconv_set_encoding ( 'output_encoding' , $this->_outputEncoding );
            iconv_set_encoding ( 'internal_encoding' , $this->_internalEncoding );
        } else {
            $this->_inputEncoding = $inputEncoding;
            $this->_outputEncoding = $outputEncoding;
            $this->_internalEncoding = $internalEncoding;
            ini_set('default_charset','UTF-8');
        }
        
    }

    /**
     +----------------------------------------------------------
     * 设置替换字符
     +----------------------------------------------------------
     * @param $replaceChar    string  输入的字符编码
     +----------------------------------------------------------
     * @param $internalEncoding     string  运行处理的字符编码
     +----------------------------------------------------------
     */
    public function setReplaceChar( $replaceChar='*' )
    {
        $this->_replaceChar = $replaceChar;
    }

    /**
     +----------------------------------------------------------
     * 设置关键字过滤库,参数为二维数组
     +----------------------------------------------------------
     * @param $keywords array
     +----------------------------------------------------------
     * @return true:false
     +----------------------------------------------------------
     */
    public function setKeywords( $keywords )
    {
        if( is_array($keywords) && count($keywords)>0 )
        {
            $_tmpStr = implode(',', $keywords);
            $_tmpStr = iconv($this->_inputEncoding,$this->_outputEncoding,$_tmpStr);
            $this->_keywordsSoruce = explode(',', $_tmpStr);
            return true;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 设置处理的数据源
     +----------------------------------------------------------
     * @param $text string
     +----------------------------------------------------------
     * @return true:false
     +----------------------------------------------------------
     */
    public function setTextStream( $text )
    {
        $this->_text = $text;
        $this->_text = iconv($this->_inputEncoding,$this->_outputEncoding,$this->_text);
    }

     /**
     +----------------------------------------------------------
     * 获取关键字列表数组，返回数组原型 array('江'=>array('k'=>array('江泽民'),'len'=>6))
     +----------------------------------------------------------
     * @param $text string
     +----------------------------------------------------------
     * @return true:false
     +----------------------------------------------------------
     */
    private function _getKeywordsList()
    {
        $keyDict=array();
        foreach($this->_keywordsSoruce as $k)
        {
            if(empty($k))continue;
            $key = iconv_substr ($k, 0, 1);
            $keyDict[$key]['k'][]=$k;
            if (!isset($keyDict[$key]['len'])) {
                $keyDict[$key]['len']=0;
            }
            $keyDict[$key]['len']= (iconv_strlen($k)>@$keyDict[$key]['len'])?iconv_strlen($k):$keyDict[$key]['len'];
        }
        //print "<pre>";
        //print_r($keyDict);
        return $keyDict;
    }

     /**
     +----------------------------------------------------------
     * 在文件查找关键字
     +----------------------------------------------------------
     * @param $void
     +----------------------------------------------------------
     * @return true:false
     +----------------------------------------------------------
     */
    public function scanText()
    {
        $this->_keywordsSoruce = $this->_getKeywordsList();
        for($i=0,$len=iconv_strlen($this->_text);$i<$len;$i++)
        {
            $key = iconv_substr($this->_text,$i,1);
            if( array_key_exists($key, $this->_keywordsSoruce) )
            {
                //进行替换处理
                $str = $this->_dealKeywords($this->_keywordsSoruce[$key],$i);
                if($str!==false)
                {
                    return true;
                }
            }
        }
        return false;
    }

     /**
     +----------------------------------------------------------
     * 填充文本，过滤符合的关键字，入口函数，对外接口
     +----------------------------------------------------------
     * @param $void
     +----------------------------------------------------------
     * @return strings
     +----------------------------------------------------------
     */
    public function fillText()
    {
        $this->_fillText = '';
        $this->_keywordsSoruce = $this->_getKeywordsList();
        for($i=0,$len=iconv_strlen($this->_text);$i<$len;$i++)
        {
            $key = iconv_substr($this->_text,$i,1);
            if( array_key_exists($key, $this->_keywordsSoruce) )
            {
                //进行替换处理
                $str = $this->_dealKeywords($this->_keywordsSoruce[$key],$i);
                if(!$str)
                {
                    $this->_fillText .= $key;
                }
                else
                {
                    $this->_fillText .= $str;
                }
            }
            else
            {
                $this->_fillText .= $key;
            }
        }
        return $this->_fillText;
    }

     /**
     +----------------------------------------------------------
     * 多模糊搜索到的关键字，进行具体匹配,按照最大模式匹配
     +----------------------------------------------------------
     * @param $keywords array 模糊匹配相关的关键字数组
     +----------------------------------------------------------
     * @return $void
     +----------------------------------------------------------
     */
    private function _dealKeywords( $keywords, &$offset)
    {
        $_text = iconv_substr( $this->_text, $offset, $keywords['len'] );
        $i = 0;
        $flag = false;
        foreach ( $keywords['k'] as $key )
        {
            //如果匹配到
            $len = iconv_strlen ($key);
            if( $flag )
            {
                if($len>$i)
                {
                    if(iconv_strpos($_text,$key)!==false)
                    {
                        $flag = true;
                        $i = ($len>$i)?$len:$i;
                    }
                }
            }
            else
            {
                if(iconv_strpos($_text,$key)!==false)
                {
                    $flag = true;
                    $i = $len;
                }
            }
        }
        if($i>1){
            $offset += $i-1;
            return str_repeat($this->_replaceChar, $i);
        }
        return false;
    }

    public function  __destruct()
    {

    }
}

?>
