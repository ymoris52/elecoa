# Moodleプラグイン

Moodleで、ELECOAパッケージ、SCORMパッケージ(1.2と2004)、cmi5コースを動作させることができるプラグインです。
活動モジュールとブロック(成績表用)が含まれています。
Moodleプラグインは、GNU General Public Licenseのもとで公開しています。

インストール方法は、以下の通りです。

1. 配布物のディレクトリ構造を保ったまま、Moodleのインストールディレクトリに配置します。つまり、`blocks/elecoa_grades`を`blocks`ディレクトリの下に、`mod/elecoa`を`mod`ディレクトリの下に配置します。
2. Moodleに管理者としてログインすると、新規インストールするプラグインが検出されます。Moodleの指示に従ってインストールを完了させます。

アンインストールの手順は、通常のプラグインと同様です。
活動モジュール(`mod_elecoa`)をアンインストールすると、活動のインスタンス(コース上に追加した活動)や学習履歴も削除されます。
旧バージョンのプラグインをアップデートする場合、通常はアンインストールせずに上書きインストールしてください。

cmi5コースを動作させるためには、ソースコードを編集してプラグインの設定を行う必要があります。
`mod/elecoa/core/CMI5Config.php`で`$CMI5CFG->endpoint_base_url`と`$CMI5CFG->lrs_authorization`に代入している値を書き換えてください。
`$CMI5CFG->endpoint_base_url`は、LRSのエンドポイントです。
`$CMI5CFG->lrs_authorization`は、LRSの認可トークン(authorization token)です。
本プラグインでは、AUはMoodleを経由してLRSと通信します。
LRSはMoodleを信頼することを前提に、Moodle-LRS間の通信には固定の認可トークンを用います。
