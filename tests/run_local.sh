#!/bin/bash
# ローカルで AiNA Works を起動して回帰テストを流す（MariaDB/MySQL と PHP 8.2+ が必要）
#   bash tests/run_local.sh
# 前提: config/config.php と .env をローカル用に用意（tests/README.md）
set -euo pipefail
cd "$(dirname "$0")/.."
mysql -e "DROP DATABASE IF EXISTS aina_works; CREATE DATABASE aina_works CHARACTER SET utf8mb4;"
mysql aina_works < sql/database.sql
php tests/seed.php
rm -rf storage/framework storage/logs
for f in $(git ls-files '*.php'); do php -l "$f" >/dev/null || { echo "syntax error: $f"; exit 1; }; done
php -S 127.0.0.1:8099 tests/mock_member_api.php >/tmp/aina-mock.log 2>&1 & MOCK=$!
php -S 127.0.0.1:8080 -t . tests/router.php >/tmp/aina-app.log 2>&1 & APP=$!
trap 'kill $MOCK $APP 2>/dev/null || true' EXIT
sleep 1
python3 tests/smoke_test.py http://127.0.0.1:8080
if grep -a -E "PHP (Warning|Notice|Fatal|Deprecated)|SQLSTATE" /tmp/aina-app.log; then
  echo "PHPの警告・SQLエラーが出ています（上記）"; exit 1
fi
