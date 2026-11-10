# デプロイ
GitHub Actions の「デプロイ」（`.github/workflows/deploy.yml`）を手動で起動すると、前回デプロイしたコミットからの差分だけを lftp（SFTP か FTPS）で送る。

- 前回のコミットはサーバの辞書フォルダの `.deploy-state` に記録する（本体の `.htaccess` で外から読めないよう塞いでいる）
- 中身は比較しないため、サーバ側の手での変更は検出されない。初回や手でサーバを最新にした直後は `mode=init` で一度実行する
- サーバからは何も消さない。サーバにあってリポジトリに無いファイルはログに一覧を出し、1件以上なら警告する。不要なら手で消す

## 送らないもの
| 対象 | 理由 | サーバにあるときの報告 |
| --- | --- | --- |
| `idyer.json` | 別リポジトリで管理 | しない |
| `logger/` | サーバの `dictLog.csv` が実データ。`idyer_logger.php` を直したら手で上げる | しない |
| `Endrata-bold.woff` `Endrata-bold.ttf` | リポジトリに無い。手で上げる | しない |
| `cache/` | サーバ側で生成 | しない |
| `_css/` `_local/` `*.bat` `tests/` `docs/` `README.md` `.github/` `.gitignore` | 表示に使わない。`tests/` は公開すると Web から実行できてしまう | する |

リポジトリ外で管理するファイルをサーバに増やしたら、`deploy.yml` の `server_managed` に足せば報告から外れる。

## 設定
リポジトリの Settings で `production` 環境（master ブランチ限定）を作り、次を設定する。

| 種類 | 名前 | 内容 |
| --- | --- | --- |
| Secret | `DEPLOY_HOST` | 接続先ホスト |
| Secret | `DEPLOY_USER` | ユーザー名 |
| Secret | `DEPLOY_PASSWORD` | パスワード（SFTP を鍵認証するなら不要） |
| Secret | `DEPLOY_SSH_KEY` | SFTP の秘密鍵（パスワード認証なら不要） |
| Secret | `DEPLOY_KNOWN_HOSTS` | SFTP のサーバ公開鍵（`ssh-keyscan` の出力） |
| Variable | `DEPLOY_PATH` | サーバ上の辞書フォルダ |
| Variable | `DEPLOY_PROTOCOL` | `sftp` か `ftps`。既定は `sftp` |
| Variable | `DEPLOY_PORT` | 既定以外のポートのときだけ |

`DEPLOY_PATH` は Secret にしないこと。Secret と同じ文字列はログで伏せられ、`/` などが読めなくなる。
