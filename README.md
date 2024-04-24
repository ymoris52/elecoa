# ELECOA

## Instructions of the SCORM 2004-compliant Moodle plugins

### Installation

1. Copy `moodle/blocks/elecoa_grades` directory to the `blocks` directory of your Moodle program directory.
2. Copy `moodle/mod/elecoa` directory to the `mod` directory of your Moodle program directory.
3. Log in as admin and follow the instructions displayed.

### Usage

- Add the "ELECOA/SCORM Grades" block to your course.
- When adding a SCORM 1.2/2004 package to your course, select "ELECOA/SCORM package" (not "SCORM package") in the "Add an activity or resource" dialogue box.

## ELECOAとは

ELECOAは、機能拡張性とコンテンツの流通再利用性を両立したeラーニングシステムのアーキテクチャです。

教育の品質向上や内容の豊富化のためには、eラーニングコンテンツの流通再利用が不可欠です。
そのためには、コンテンツにSCORMのような標準規格を採用し、相互運用性を持たせることが望まれます。
一方で、学習効果の向上のため、コンテンツには様々な機能が求められます。
ここで、規格で定められていない範囲の機能を追加・拡張すると、既存のコンテンツが動作しなくなることがあるほか、流通再利用性が確保できなくなります。
SCORMも利用できる機能が規格として定められているため、同様の問題を抱えています。

そこで、「*教材オブジェクト*」という概念を導入することにより、機能拡張性と流通再利用性が両立可能なeラーニングシステムのアーキテクチャを提案しています。
教材オブジェクトとは、従来のシステムのコンテンツとプラットフォームの間に位置し、様々な教育的機能を実現するプログラム部品です。
従来のシステムにおいてプラットフォームが持っていた教育的な機能を分離したもの、と考えることができます。
コンテンツは、対応づけられた教材オブジェクトが持つ機能によって動作し、プラットフォームとの通信は教材オブジェクトが担います。
既存機能の拡張や新規機能の追加は、新たな教材オブジェクトを導入することで実現します。
このため、新たな機能が既存のコンテンツに影響を与えず、機能拡張性が確保されます。
また、教材オブジェクトとともにコンテンツを流通させることにより、流通再利用性が確保されます。

ここで公開しているソフトウェアは、SCORM 2004 3rd Editionのシーケンシング機能やcmi5を実装した教材オブジェクト群を含んでおり、SCORMパッケージやcmi5コースを動作させることができます。

## ソフトウェア・関連配布物

- [ELECOA Player (player)](player/README.md)
- [Moodleプラグイン (moodle)](moodle/README.md)
- [分散マルチプラットフォーム学習環境 (multiplatform)](multiplatform/README.md)
- [テスト用コンテンツ (test)](test/README.md)

[ELECOA PlayerとMoodleプラグインの更新履歴はこちら](History.md) (GitHubで管理する以前のバージョンの履歴も含む)

## 連絡先

- 仲林清
- 森本容介 (ymoris52 [at] gmail.com)

ここで公開しているソフトウェアは、千葉工業大学情報科学部と放送大学の共同研究の成果物です。
本研究は、科研費の助成を受けています(2008-2010:20500820, 2011-2013:23300307, 2014-2016:26280128, 2017-2020:17H00774)。
