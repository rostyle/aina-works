<?php
require_once 'config/config.php';

// セッションを破棄
session_destroy();

setFlash('success', 'ログアウトしました。');
redirect(url());
