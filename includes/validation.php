<?php
/**
 * 統一されたバリデーション関数
 */

/**
 * バリデーション結果クラス
 */
class ValidationResult {
    public $isValid = true;
    public $errors = [];
    
    public function addError($field, $message) {
        $this->isValid = false;
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    public function getFirstError() {
        foreach ($this->errors as $field => $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
    
    public function getAllErrors() {
        $all = [];
        foreach ($this->errors as $messages) {
            $all = array_merge($all, $messages);
        }
        return $all;
    }
    
    public function getErrorsAsString($separator = ' ') {
        return implode($separator, $this->getAllErrors());
    }
}

/**
 * 必須チェック
 */
function validateRequired($value, $fieldName = 'この項目') {
    if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
        return "{$fieldName}を入力してください";
    }
    return null;
}

/**
 * メールアドレス検証
 */
function validateEmail($email, $fieldName = 'メールアドレス') {
    if (empty($email)) {
        return "{$fieldName}を入力してください";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "{$fieldName}の形式が正しくありません";
    }
    return null;
}

/**
 * 文字列長検証
 */
function validateLength($value, $min = null, $max = null, $fieldName = 'この項目') {
    if (is_null($value)) {
        return null; // 必須チェックは別途行う
    }
    
    $length = mb_strlen($value);
    
    if ($min !== null && $length < $min) {
        return "{$fieldName}は{$min}文字以上で入力してください";
    }
    
    if ($max !== null && $length > $max) {
        return "{$fieldName}は{$max}文字以内で入力してください";
    }
    
    return null;
}

/**
 * 数値範囲検証
 */
function validateRange($value, $min = null, $max = null, $fieldName = 'この項目') {
    if (is_null($value) || $value === '') {
        return null; // 必須チェックは別途行う
    }
    
    if (!is_numeric($value)) {
        return "{$fieldName}は数値で入力してください";
    }
    
    $num = (float)$value;
    
    if ($min !== null && $num < $min) {
        return "{$fieldName}は{$min}以上で入力してください";
    }
    
    if ($max !== null && $num > $max) {
        return "{$fieldName}は{$max}以下で入力してください";
    }
    
    return null;
}

/**
 * 整数検証
 */
function validateInteger($value, $fieldName = 'この項目') {
    if (is_null($value) || $value === '') {
        return null; // 必須チェックは別途行う
    }
    
    if (!is_numeric($value) || (int)$value != $value) {
        return "{$fieldName}は整数で入力してください";
    }
    
    return null;
}

/**
 * 正の整数検証
 */
function validatePositiveInteger($value, $fieldName = 'この項目') {
    $error = validateInteger($value, $fieldName);
    if ($error) {
        return $error;
    }
    
    if ((int)$value <= 0) {
        return "{$fieldName}は1以上の整数で入力してください";
    }
    
    return null;
}

/**
 * 選択肢検証
 */
function validateIn($value, $allowedValues, $fieldName = 'この項目') {
    if (is_null($value) || $value === '') {
        return null; // 必須チェックは別途行う
    }
    
    if (!in_array($value, $allowedValues, true)) {
        $allowed = implode('、', array_slice($allowedValues, 0, 5));
        if (count($allowedValues) > 5) {
            $allowed .= 'など';
        }
        return "{$fieldName}は{$allowed}のいずれかを選択してください";
    }
    
    return null;
}

/**
 * URL検証
 */
function validateUrl($url, $fieldName = 'URL') {
    if (empty($url)) {
        return null; // 必須チェックは別途行う
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return "{$fieldName}の形式が正しくありません";
    }
    
    return null;
}

/**
 * 日付検証
 */
function validateDate($date, $format = 'Y-m-d', $fieldName = '日付') {
    if (empty($date)) {
        return null; // 必須チェックは別途行う
    }
    
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        return "{$fieldName}の形式が正しくありません";
    }
    
    return null;
}

/**
 * ファイルサイズ検証
 */
function validateFileSize($file, $maxSizeBytes, $fieldName = 'ファイル') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return "{$fieldName}がアップロードされていません";
    }
    
    if ($file['size'] > $maxSizeBytes) {
        $maxSizeMB = round($maxSizeBytes / 1024 / 1024, 1);
        return "{$fieldName}のサイズは{$maxSizeMB}MB以下にしてください";
    }
    
    return null;
}

/**
 * ファイル拡張子検証
 */
function validateFileExtension($file, $allowedExtensions, $fieldName = 'ファイル') {
    if (!isset($file['name'])) {
        return "{$fieldName}が指定されていません";
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions, true)) {
        $allowed = implode('、', $allowedExtensions);
        return "{$fieldName}は{$allowed}形式のみアップロードできます";
    }
    
    return null;
}

/**
 * 比較検証（2つの値の比較）
 */
function validateCompare($value1, $value2, $operator = '>=', $fieldName1 = '値1', $fieldName2 = '値2') {
    if (is_null($value1) || is_null($value2)) {
        return null; // 必須チェックは別途行う
    }
    
    $num1 = (float)$value1;
    $num2 = (float)$value2;
    
    switch ($operator) {
        case '>=':
            if ($num1 < $num2) {
                return "{$fieldName1}は{$fieldName2}以上で入力してください";
            }
            break;
        case '<=':
            if ($num1 > $num2) {
                return "{$fieldName1}は{$fieldName2}以下で入力してください";
            }
            break;
        case '>':
            if ($num1 <= $num2) {
                return "{$fieldName1}は{$fieldName2}より大きい値を入力してください";
            }
            break;
        case '<':
            if ($num1 >= $num2) {
                return "{$fieldName1}は{$fieldName2}より小さい値を入力してください";
            }
            break;
    }
    
    return null;
}

/**
 * カスタム検証（コールバック関数を使用）
 */
function validateCustom($value, $callback, $fieldName = 'この項目') {
    if (is_null($value) || $value === '') {
        return null; // 必須チェックは別途行う
    }
    
    $result = $callback($value);
    if ($result !== true && is_string($result)) {
        return $result;
    }
    
    if ($result === false) {
        return "{$fieldName}の値が正しくありません";
    }
    
    return null;
}
