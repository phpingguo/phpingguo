<?php
namespace Phpingguo\System\Request;

use Phpingguo\System\Core\AuraDIWrapper;
use Phpingguo\System\Core\Client;
use Phpingguo\System\Core\Config;
use Phpingguo\System\Core\Server;
use Phpingguo\System\Exceptions\SystemFatalErrorException;
use Phpingguo\System\Exceptions\UnsupportedOperationException;
use Phpingguo\System\Exts\Lib\Common\Arrays;
use Phpingguo\System\Exts\Lib\Common\String as CString;

/**
 * クライアントが要求した情報を解析するクラスです。
 * 
 * @final [継承禁止クラス]
 * @author hiroki sugawara
 */
final class RequestParser
{
    // ---------------------------------------------------------------------------------------------
    // private fields
    // ---------------------------------------------------------------------------------------------
    private $req_data = null;
    
    // ---------------------------------------------------------------------------------------------
    // public class methods
    // ---------------------------------------------------------------------------------------------
    /**
     * RequestParser クラスのインスタンスを取得します。
     * 
     * @param Boolean $reanalyze [初期値=false]	リクエストデータの再解析を行うかどうか
     * 
     * @return RequestParser 初回呼び出し時は新しいインスタンスを、それ以降の時は生成済みのインスタンスを返します。
     */
    public static function getInstance($reanalyze = false)
    {
        $instance = AuraDIWrapper::init()->get(__CLASS__);
        
        if (empty($instance->req_data) || $reanalyze === true) {
            $instance->set($instance->getParseData());
        }
        
        return $instance;
    }
    
    // ---------------------------------------------------------------------------------------------
    // public member methods
    // ---------------------------------------------------------------------------------------------
    /**
     * クライアントが要求した情報を取得します。
     * 
     * @return RequestData クライアントが要求した情報を返します。
     */
    public function get()
    {
        return $this->req_data;
    }
    
    // ---------------------------------------------------------------------------------------------
    // private member methods
    // ---------------------------------------------------------------------------------------------
    /**
     * クライアントが要求した情報を設定します。
     * 
     * @param RequestData $data	クライアントが要求した情報
     */
    private function set(RequestData $data)
    {
        $this->req_data = $data;
    }
    
    /**
     * クライアントのリクエストを解析したデータを取得します。
     * 
     * @throws SystemFatalErrorException	不正なリクエストデータの場合
     * 
     * @return RequestData クライアントのリクエストを解析したデータを返します。
     */
    private function getParseData()
    {
        $path_info_list = array_values(array_filter(explode('/', Server::PATH_INFO('')), 'strlen'));
        
        if (Arrays::checkSize($path_info_list, 3, 0) === false) {
            throw new SystemFatalErrorException();
        }
        
        $method = Server::REQUEST_METHOD();
        
        list($module, $version, $scene, $params) = $this->parseRequest($method, $path_info_list);
        
        return new RequestData($method, $module, $version, $scene, $params);
    }
    
    /**
     * クライアントのリクエスト情報を解析します。
     * 
     * @param String $method		クライアントがリクエストしたメソッド
     * @param Array $path_info_list	クライアントがリクエストしたパス情報配列
     * 
     * @return Array(String, Float, String, Array) モジュール名、APIバージョン、シーン名、パラメータ配列
     * の四つの要素から成る配列を返します。
     */
    private function parseRequest($method, array $path_info_list)
    {
        $version = empty($path_info_list) === false ? $this->getApiVersion($path_info_list) : null;
        $indexor = is_null($version) ? 0 : 1;
        
        $module = $this->getApiElementValue($path_info_list, $indexor, null);
        $scene  = $this->getApiElementValue($path_info_list, $indexor + 1, null);
        $params = $this->getApiParameters($method);
        
        return [ $module, $version, $scene, $params ];
    }
    
    /**
     * クライアントがリクエストした API のバージョンを取得します。
     * 
     * @param Array $path_info_list	リクエストデータのパス情報配列
     * 
     * @throws UnsupportedOperationException	バージョニングURLを許容していないにも関わらず使用した、または、
     * バージョン番号セパレータが存在しなかった場合
     * 
     * @return Float|null クライアントがリクエストした API のバージョンを返します。
     */
    private function getApiVersion(array $path_info_list)
    {
        list($result, $separator, $matches) = $this->searchNumberSeparator($path_info_list[0]);
        
        if (empty($matches[1])) {
            return null;
        } elseif (Config::get('sys.versioning.allowed', false) === false) {
            // バージョニングURLを許容していないにも関わらず使用した場合
            throw new UnsupportedOperationException('Versioning url address');
        } elseif (strpos($matches[1], $separator) === false) {
            // バージョン番号セパレータが存在しなかった場合
            throw new UnsupportedOperationException(
                'Versioning Number Separator except for "' . $separator . '"'
            );
        }
        
        return floatval(str_replace($separator, '.', $matches[1]));
    }
    
    /**
     * クライアントがリクエストした API のバージョン番号を指定した文字列から検索します。
     * 
     * @param String $target	バージョン番号を検索する文字列
     * 
     * @return Array(Boolean, String, Array) クライアントがリクエストした API のバージョン番号に関する情報を返します。
     */
    private function searchNumberSeparator($target)
    {
        $separator = Config::get('sys.versioning.num_separator', '.');
        $pattern   = '/^v([0-9]+(' . preg_quote($separator) . '|[^0-9]*)[0-9]+)$/i';
        $matches   = [];
        
        $result = preg_match($pattern, $target, $matches);
        
        return [ $result, $separator, $matches ];
    }
    
    /**
     * クライアントがリクエストした API の要素の値を取得します。
     * 
     * @param Array $path_info_list	リクエストデータのパス情報配列
     * @param Integer $indexor		パス情報配列の参照する位置を示すインデクサ
     * @param mixed $default_value	インデクサの位置に値が存在しない場合に使用するデフォルト値
     * 
     * @return String クライアントがリクエストした API の要素の値を返します。
     */
    private function getApiElementValue(array $path_info_list, $indexor, $default_value)
    {
        return strtolower(Arrays::getValue($path_info_list, $indexor, $default_value));
    }
    
    /**
     * クライアントがリクエストした API のパラメータを取得します。
     * 
     * @param HttpMethod|String $method	クライアントがリクエストした HTTP メソッド
     * 
     * @return Array クライアントがリクエストした API のパラメータの一覧を返します。
     */
    private function getApiParameters($method)
    {
        $request_params = Client::getParameters($method);
        
        // リクエストインジェクション攻撃の対策
        // 配列型パラメータ値を受け取ると MonboDB に対する攻撃が可能になるので、
        // 配列型パラメータは全て削除する
        Arrays::removeEach(
            Config::get('sys.security.remove_req_array_params', true),
            function ($value) {
                return is_array($value);
            },
            $request_params
        );
        
        return $request_params;
    }
}