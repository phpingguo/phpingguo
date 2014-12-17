<?php
namespace Phpeel\System\Filter\Input;

use Phpeel\System\Module\BaseModule;

/**
 * クライアントからの入力に適用するフィルタの共通処理を定義するインターフェイスです。
 *
 * @author hiroki sugawara
 */
interface IFilter
{
    /**
     * フィルタ処理を実行します。
     *
     * @param BaseModule $module フィルタ処理を適用するモジュールのインスタンス
     *
     * @return BaseModule フィルタ処理済みのモジュールのインスタンス
     */
    public function execute(BaseModule $module);
}