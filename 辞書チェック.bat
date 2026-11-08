@echo off
rem cmd はバッチを CP932 で読むので、このファイルは Shift_JIS で保存すること（UTF-8 だと日本語が化けて誤動作する）
setlocal enabledelayedexpansion
cd /d "%~dp0"

rem 本体の ブログチェック.bat（8080）と同時に動かせるよう、別の番号にする
set "PORT=8081"

rem PHP の場所は環境変数 PHP_EXE で上書きできる。無ければ PATH → C:\php → XAMPP の既定の場所の順に探す
if not defined PHP_EXE (
  for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE if exist "C:\php\php.exe" set "PHP_EXE=C:\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE (
  echo PHP が見つかりません。PHP を入れて PATH に通すか、
  echo 環境変数 PHP_EXE に php.exe のフルパスを設定してください。
  pause
  exit /b 1
)
for %%I in ("%PHP_EXE%") do set "PHP_DIR=%%~dpI"

rem 公式の zip 版は php.ini が無く拡張がひとつも読まれないので、読まれていないものだけ -d で足す。
rem php.ini で読み込み済みのものまで足すと「already loaded」の警告が毎回出る。
rem mbstring は必須（検索）。gd はホーム画面用アイコンの拡大（app_icon_maskable_scale が 1.0 より大きいとき）に使う
set "EXT_OPTS=-d extension_dir="%PHP_DIR%ext""
set "MISSING="
for %%E in (mbstring gd) do (
  "%PHP_EXE%" -m | findstr /i /x "%%E" >nul
  if errorlevel 1 (
    if exist "%PHP_DIR%ext\php_%%E.dll" (
      set "EXT_OPTS=!EXT_OPTS! -d extension=%%E"
    ) else (
      set "MISSING=!MISSING! %%E"
    )
  )
)
if defined MISSING echo 警告: 次の拡張が見つかりません:%MISSING%
"%PHP_EXE%" %EXT_OPTS% -r "exit(extension_loaded('mbstring') ? 0 : 1);"
if errorlevel 1 (
  echo mbstring 拡張を読み込めません。PHP の ext フォルダを確認してください。
  pause
  exit /b 1
)

rem 辞書データはリポジトリに無い（.gitignore）。無ければ検索ページ等がエラーになる
if not exist "idyer.json" (
  echo 警告: idyer.json がありません。辞書データをこのフォルダに置いてください。
  echo.
)

rem サイト直下のアイコンを本番と同じく本体から出すため、隣に zaslon-site があればそこをドキュメントルートにする
set "DOCROOT=%~dp0_local"
if exist "%~dp0..\zaslon-site\favicon.ico" set "DOCROOT=%~dp0..\zaslon-site"

echo PHP       : %PHP_EXE%
echo 辞書      : http://127.0.0.1:%PORT%/dict/dict.php
echo 止めるときはこのウィンドウで Ctrl+C
echo.

rem サーバの起動を待ってからブラウザを開く
start "" /b cmd /c "timeout /t 1 /nobreak >nul & start "" http://127.0.0.1:%PORT%/dict/dict.php"

rem LAN から見えないよう、必ず 127.0.0.1 で待ち受ける。/dict/ の対応付けは _local/router.php が行う
"%PHP_EXE%" %EXT_OPTS% -S 127.0.0.1:%PORT% -t "%DOCROOT%" ^
  -d date.timezone=Asia/Tokyo ^
  "%~dp0_local\router.php"

pause
