<?php
/**
 * Geminiの旧モデル名を現行モデルへ読み替える。
 *
 * 本番の config/config.php はサーバ側にありgit管理外なので、そこに古いモデル名が
 * 残っていても動くようにここで差し替える。2.5系は「過去に使ったことのあるAPIキー」
 * だけがアクセスできる状態になっており、キーを作り直すと呼べなくなる（2026-09-23）。
 */

const GEMINI_LEGACY_MODELS = [
    'gemini-2.5-flash'      => 'gemini-3.8-flash',
    'gemini-2.5-flash-lite' => 'gemini-3.5-flash-lite',
    'gemini-2.5-pro'        => 'gemini-3.8-flash',
    'gemini-2.0-flash'      => 'gemini-3.5-flash-lite',
    'gemini-1.5-flash'      => 'gemini-3.5-flash-lite',
];

const GEMINI_MODEL_DEFAULT = 'gemini-3.5-flash-lite';

function gemini_model(): string
{
    $model = defined('GEMINI_MODEL') && GEMINI_MODEL !== '' ? GEMINI_MODEL : GEMINI_MODEL_DEFAULT;
    return GEMINI_LEGACY_MODELS[$model] ?? $model;
}
