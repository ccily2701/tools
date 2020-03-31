<?php
##############################################
#           名称：通用方法类
#           作者：crazycat
#           Q Q : 27012508
#           时间：2016-10-09
##############################################
namespace Shf;
class Functions
{

    #
    #   CURL工具
    #
    #   $url        网址
    #   $post_data  提交内容
    #   $is_SSL     证书
    #
    public function http_curl($url='',$para=array())
    {
        $ch = curl_init($url);

//        curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, false );

        if(!empty($para['post_data']))
        {
            curl_setopt($ch,CURLOPT_POST,1);
            if(is_array($para['post_data']))
            {
                curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($para['post_data']));
            }else
            {
                curl_setopt($ch,CURLOPT_POSTFIELDS, $para['post_data']);
            }
        }

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);

        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);

        if(!empty($para['proxy']))
        {
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);

            if(isset($para['proxy_type']) && $para['proxy_type']=='sock5')
            {
                curl_setopt($ch, CURLOPT_PROXYTYPE, 5); //sock5
            }else{
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //http
            }
            curl_setopt($ch,CURLOPT_PROXY,$para['proxy']);
        }

        if(!empty($para['user_agent']))
        {
            curl_setopt($ch, CURLOPT_USERAGENT,$para['user_agent']);

        }else
        {
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:31.0) Gecko/20100101 Firefox/31.0');
        }

        if(!empty($para['cookie']))
        {
            curl_setopt($ch,CURLOPT_COOKIE,$para['cookie']);

        }

        if(!empty($para['Referer']))
        {
            curl_setopt ($ch,CURLOPT_REFERER,$para['Referer']);
        }

        if(!empty($para['SSLCERT_PATH']))
        {
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $para['SSLCERT_PATH']);
        }

        if(!empty($para['SSLKEY_PATH']))
        {
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $para['SSLKEY_PATH']);
        }

        if(!empty($para['HEADER']))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $para['HEADER']);
        }

        if(!empty($para['ENCODING']))
        {
            curl_setopt($ch, CURLOPT_ENCODING, $para['ENCODING']);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER,1);


        $result = curl_exec($ch);

        $headerlen = curl_getinfo($ch,CURLINFO_HEADER_SIZE);

        $arr['header']=substr($result,0,$headerlen);
        $arr['body']=substr($result,$headerlen);
        $arr['code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $arr;

    }

    #
    # $str  微信昵称过滤
    #
    public function filter($str)
    {
        if($str)
        {
            $name = $str;
            $name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
            $name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
            $return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));
            if(!$return){
                return '';
            }
        }else
        {
            $return = '';
        }
        return $return;

    }
    #
    #   数组内的排列组合算法
    #
    #   $arr 数组
    #   $m   组合字符串长度
    #
    #
    public function getCombination($arr,$m,$str='')
    {
        $result = array();
        if ($m ==1)
        {
            return $arr;
        }

        foreach($arr as $k => $s)
        {

            $first_char = $s;
            $arr2 = $arr;
            unset($arr2[$k]);
            $arr1 = $this->getCombination($arr2,$m-1,$first_char);
            foreach($arr1 as $v)
            {
                $result[] = $first_char.$v;
            }
        }
        return $result;
    }

    //目录遍历
    public function eachDir($path='')
    {

        try{
            if(empty($path) or !is_dir($path))
            {
                throw new Exception("Error path", 1);

            }
        }catch(Exception $e)
        {
            die($e->getMessage());
        }


        $return = array();

        $headle = scandir($path);


        foreach($headle as $v)
        {

            if(is_dir($path.DIRECTORY_SEPARATOR.$v))
            {
                if($v!='.' and $v!='..' and $v!='.svn')
                {
                    $return = array_merge($return,eachDir($path.DIRECTORY_SEPARATOR.$v));
                }
            }else
            {
                $return[] = $path.DIRECTORY_SEPARATOR.$v;
            }
        }

        return $return;


    }
}

?>
