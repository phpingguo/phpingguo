<?php
namespace Phpeel\System\Core;

use Phpeel\ApricotLib\Common\String as CString;
use Phpeel\CitronDI\AuraDIWrapper;
use Phpeel\System\Enums\FilterType;
use Phpeel\System\Request\RequestData;
use Phpeel\System\Variable\Server;

/**
 * フレームワークを統括するクラスです。
 *
 * @final [継承禁止クラス]
 * @author hiroki sugawara
 */
final class Supervisor
{
    // ---------------------------------------------------------------------------------------------
    // const fields
    // ---------------------------------------------------------------------------------------------
    const PATH_CONFIG_DI         = 'di_preset_services';
    const PATH_CONFIG_REGULATION = 'various_regulations';
    const PATH_CACHE_TWIG        = 'Twig';
    const PATH_CACHE_SMARTY      = 'Smarty';
    const DIS_SYSTEM             = 'system';
    const ENUM_CONTENT_TYPE      = 'ContentType';
    const ENUM_HTTP_METHOD       = 'HttpMethod';
    const ENUM_RESPONSE_CODE     = 'ResponseCode';
    const ENUM_X_FRAME_OPTIONS   = 'XFrameOptions';
    const ENUM_TEMPLATE_ENGINE   = 'TemplateEngine';
    const ENUM_MODULE_FILTER     = 'ModuleFilter';

    // ---------------------------------------------------------------------------------------------
    // private fields
    // ---------------------------------------------------------------------------------------------
    private static $project_path = null;
    private static $system_path  = null;
    private static $config_path  = null;
    private static $srv_env_path = null;
    private static $cache_path   = null;
    private static $apps_path    = null;
    private static $view_path    = null;

    // ---------------------------------------------------------------------------------------------
    // public static methods
    // ---------------------------------------------------------------------------------------------
    /**
     * フレームワークで使用可能なDIコンテナのインスタンスを取得します。
     *
     * @param String $service_group_name
     *
     * @return \Aura\Di\Container フレームワークで使用可能なDIコンテナのインスタンス
     */
    public static function getDiContainer($service_group_name)
    {
        return AuraDIWrapper::init(
            $service_group_name,
            static::getConfigPath(static::PATH_CONFIG_DI)
        );
    }

    /**
     * プロジェクトのルートディレクトリのファイルパスを取得します。
     *
     * @return String プロジェクトのルートディレクトリのファイルパス
     */
    public static function getProjectPath()
    {
        if (empty(static::$project_path)) {
            static::$project_path = CString::unionDirectoryPath(CString::unionDirectoryPath(__DIR__, '..'), '..');
        }
        
        return static::$project_path;
    }

    /**
     * フレームワークのシステムディレクトリのファイルパスを取得します。
     *
     * @return String フレームワークのシステムディレクトリのファイルパス
     */
    public static function getSystemPath()
    {
        if (empty(static::$system_path)) {
            static::$system_path = CString::unionDirectoryPath(static::getProjectPath(), 'phpeel');
        }
        
        return static::$system_path;
    }

    /**
     * アプリケーションのルートディレクトリのファイルパスを取得します。
     *
     * @return String アプリケーションのルートディレクトリのファイルパス
     */
    public static function getAppPath()
    {
        if (empty(static::$apps_path)) {
            static::$apps_path = CString::unionDirectoryPath(static::getProjectPath(), 'app');
        }
        
        return static::$apps_path;
    }

    /**
     * プロジェクトの設定ファイルがあるディレクトリのファイルパスを取得します。
     *
     * @param String $sub_dir_name [初期値=null] 設定ファイルがあるサブディレクトリの名前
     *
     * @return String プロジェクトの設定ファイルがあるディレクトリのファイルパス
     */
    public static function getConfigPath($sub_dir_name = null)
    {
        if (empty(static::$config_path)) {
            static::$config_path = CString::unionDirectoryPath(static::getProjectPath(), 'config');
        }
        
        return CString::unionDirectoryPath(static::$config_path, $sub_dir_name);
    }

    /**
     * 実行するサーバーごとの設定ファイルがあるディレクトリのファイルパスを取得します。
     * 
     * @param String $default [初期値='local'] サーバー環境名が取得できなかった場合に使用する値
     * 
     * @return String 実行するサーバーごとの設定ファイルがあるディレクトリのファイルパス
     */
    public static function getServerEnvPath($default = 'local')
    {
        if (empty(static::$srv_env_path)) {
            static::$srv_env_path = static::getConfigPath('server_environments');
        }
        
        return CString::unionDirectoryPath(
            static::$srv_env_path,
            Server::getValue(Server::SRV_ENV_NAME, $default)
        );
    }

    /**
     * アプリケーションのViewに該当するファイルがあるディレクトリのファイルパスを取得します。
     * 
     * @return String アプリケーションのViewに該当するファイルがあるディレクトリのファイルパス
     */
    public static function getViewPath()
    {
        if (empty(static::$view_path)) {
            static::$view_path = CString::unionDirectoryPath(static::getAppPath(), 'View');
        }
        
        return static::$view_path;
    }

    /**
     * プロジェクトのキャッシュファイルがあるディレクトリのファイルパスを取得します。
     *
     * @param String $sub_dir_name [初期値=null] キャッシュファイルがあるサブディレクトリの名前
     * 
     * @return String プロジェクトのキャッシュファイルがあるディレクトリのファイルパス
     */
    public static function getCachePath($sub_dir_name = null)
    {
        if (empty(static::$cache_path)) {
            static::$cache_path = CString::unionDirectoryPath(static::getAppPath(), 'Cache');
        }
        
        return CString::unionDirectoryPath(static::$cache_path, $sub_dir_name);
    }

    /**
     * 列挙型クラスの名前空間付きの完全修飾名を取得します。
     * 
     * @param String $enum_name 完全修飾名を取得する列挙型クラスの名前
     * 
     * @throws \InvalidArgumentException 有効な列挙型クラスではなかった場合
     * @return String 列挙型クラスの名前空間付きの完全修飾名
     */
    public static function getEnumFullName($enum_name)
    {
        return CString::concat("Phpeel\\System\\Enums\\", $enum_name);
    }

    /**
     * ウェブアプリケーションとして使用されるAPIのパスを取得します。
     * 
     * @param Float $api_ver      APIのバージョン番号
     * @param String $module_name モジュールの名前
     * @param String $scene_name  シーンの名前
     * 
     * @return String ウェブアプリケーションとして使用されるAPIのパス
     */
    public static function getApiPath($api_ver, $module_name, $scene_name)
    {
        return CString::concat(
            $api_ver ? CString::concat($api_ver, CString::concat(DIRECTORY_SEPARATOR, $module_name)) : $module_name,
            CString::concat(DIRECTORY_SEPARATOR, $scene_name)
        );
    }

    /**
     * APIバージョン番号に該当するディレクトリの名前を取得します。
     * 
     * @param RequestData $request_data クライアントがリクエストしたデータ
     * 
     * @return String APIバージョン番号に該当するディレクトリの名前
     */
    public static function getApiVerDirName(RequestData $request_data)
    {
        $request_ver  = floatval($request_data->getApiVersion());
        $default_ver  = floatval(Config::get('sys.versioning.default_num', 1.0));
        $is_strict    = Config::get('sys.versioning.strict_mode', false);
        $dir_separate = Config::get('sys.versioning.dir_separator', '.');
        
        if ($request_ver !== $default_ver || $is_strict === true) {
            return 'v' . str_replace('.', $dir_separate, sprintf('%.1F', $request_ver));
        }
        
        return '';
    }

    /**
     * あモデルフィルタクラスの名前空間の名前を取得します。
     * 
     * @param String $filter_type                   フィルタの種類を示す文字列
     * @param Boolean $with_separator [初期値=true] 名前空間の語尾に区切り文字「\」を付けるかどうか
     * 
     * @return String 指定したサブ名前空間を持つモデルフィルタクラスの名前空間の名前
     */
    public static function getModelFilterNamespace($filter_type, $with_separator = true)
    {
        return CString::concat(
            "Phpeel\\App\\Filters\\",
            CString::concat(
                FilterType::init($filter_type, FilterType::PRE)->getValue(),
                $with_separator ? "\\" : ''
            )
        );
    }
}
