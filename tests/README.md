# ローカル回帰テスト

本番（Lolipop）に出す前に、手元で「ログイン → 役割選択 → 応募 → 受諾 → 納品報告 → 検収 → レビュー」と、
管理画面の権限・添付ファイルの配信制限・ログイン試行制限などを通しで確かめるためのもの。
`tests/` は FTP デプロイの対象外（`.github/workflows/ftp-deploy.yml` の exclude）で、`.htaccess` でも外部から読めない。

## 用意するもの
- PHP 8.2 以上（pdo_mysql / gd / fileinfo / mbstring）と Composer（`composer install` で PHPMailer）
- MariaDB 10.4+ または MySQL 8（`root` で `mysql` が使えること）
- DBユーザー: `CREATE USER 'aina'@'localhost' IDENTIFIED BY 'aina'; GRANT ALL ON aina_works.* TO 'aina'@'localhost';`（127.0.0.1 も同様）

## 設定（Git に入らないファイル）
- `config/config.php` … `config/config.php.sample` をコピー
- `.env` … `.env.sample` をコピーし、次を設定
  ```
  SITE_URL=http://127.0.0.1:8080
  DB_HOST=127.0.0.1
  DB_USER=aina
  DB_PASS=aina
  AINA_API_BASE_URL=http://127.0.0.1:8099/api/external
  AINA_API_KEY=local-key
  MAIL_HOST=127.0.0.1
  MAIL_PORT=1025
  ADMIN_EMAILS=admin@example.com
  DEBUG=true
  ```

## 実行
```
bash tests/run_local.sh
```
DBを作り直して初期データを入れ、PHP全ファイルの構文チェック、アプリと会員APIの代役を起動し、`tests/smoke_test.py` を流す。
最後にサーバーログに PHP の警告や SQL エラーが無いことも確かめる。

テスト用アカウント（会員パスワードは `Member1!`、ローカル専用は `Passw0rd!`）
| メール | 種別 |
|---|---|
| admin@example.com | ローカル専用の管理者 |
| creator@example.com | 会員・クリエイター |
| client@example.com | 会員・依頼者 |
| norole@example.com | 会員・役割未設定 |
| quit@example.com | 退会済み会員（ローカルパスワードあり＝抜け道の再現用） |


## 状態コード・Cookie と配備対象の回帰テスト

`python3 tests/header_regression.py` は PHP と Python のみで実行できます。
一時ディレクトリ内に、先頭のバッファリングがなく末尾に空白を出す旧設定を再現します。
本番の設定・DB・秘密情報にはアクセスせず、実際の API と共通関数で未認証 401、
DB 停止時の 503、JSON の先頭、セッション更新と Cookie 属性、302、警告のログ出力を確認します。

配備対象は FTP アクションと同じ multimatch 5 のオプションで検証します。

```sh
npm install --prefix /tmp/aina-deploy-test --ignore-scripts --no-audit --no-fund multimatch@5.0.0 js-yaml@4.1.0
NODE_PATH=/tmp/aina-deploy-test/node_modules node tests/deploy_exclusions.cjs
```

PR の `Regression tests` は構文検査、上記テスト、使い捨て MySQL 上の既存の回帰テストを実行します。
配備や本番の秘密情報へのアクセスは行いません。
除外設定は今後の転送を防ぎますが、既に本番に残った `.git` の削除は別途確認が必要です。
