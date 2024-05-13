# ELECOA Player

ELECOA Playerは、ELECOAパッケージとSCORMパッケージ(1.2と2004)を動作させることができるeラーニングシステムです。
PHPが動作するWebサーバにインストールして使用します。
SCORMパッケージをアップロードして動作させるために必要十分な機能を実装しています。
ELECOA Playerは、三条項BSDライセンスのもとで公開しています。

インストール方法は、以下の通りです。

1. 本ディレクトリ以下のディレクトリ、ファイルを、Webサーバ上の適切なディレクトリに配置します。このディレクトリをインストールディレクトリと呼びます。インストールディレクトリは、Web公開領域である必要はありません。以下のファイル名・ディレクトリ名は、いずれもインストールディレクトリからの相対パスです。
2. `www`ディレクトリを、Webから参照できるようにします。
3. `init_www.php`で定義(`define`)している`web_base_path`の値を、`www`ディレクトリ(ELECOA Playerのフロントページ)のURLのパスに書き換えます。最後のスラッシュは不要です。Webのルートディレクトリに配置した場合は、`''`(空文字列)にします。
4. `log`ディレクトリ、`syslog`ディレクトリ、`www/contents`ディレクトリを、PHPスクリプトの実行(実効)ユーザから書き込み可能にします。

ELECOAパッケージは、展開した上で、`www/contents`ディレクトリに配置します。
SCORMパッケージは、ログイン後の画面からアップロードします。
SCORMパッケージはコンバータを通す必要がありますので、直接配置しても動作しません。
`www/contents`ディレクトリには、ユーザとPHPスクリプトの両方からコンテンツを配置しますので、(Unix系OSの場合は)スティッキービットを立てるなど工夫してください。

アップロードできるSCORMパッケージのサイズの上限は、`www/contentpkguploader.php`の`$MAXIMUM_FILESIZE`で設定します。
PHPの設定(`post_max_size`, `upload_max_filesize`)も確認してください。

`init_www.php`で定義(`define`)している`show_log`の値を`TRUE`に変更すると、ELECOA Playerでログが閲覧できるようになります。
ログを閲覧するには、ログイン後の画面に設置されるリンク「View log files」を選択してください。

ELECOA Playerは、[Releases](https://github.com/ymoris52/elecoa/releases)からもダウンロードできます。
[`elecoa_player.tar.bz2`](https://github.com/ymoris52/elecoa/releases/latest/download/elecoa_player.tar.bz2)または[`elecoa_player.zip`](https://github.com/ymoris52/elecoa/releases/latest/download/elecoa_player.zip)をダウンロードしてください。
