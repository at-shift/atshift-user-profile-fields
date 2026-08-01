<div align="center">
  <img src="assets/plugin-icons/atshift-user-profile-fields-icon-256.png" width="128" height="128" alt="atshift User Profile Fields">
  <h1>atshift User Profile Fields</h1>
  <p><strong>WordPressのユーザープロフィール画面を、美しく、より実用的に。</strong></p>
  <p>
    <a href="https://upf.at-shift.net/">公式サイト</a> ·
    <a href="https://upf.at-shift.net/guide/">導入ガイド</a> ·
    <a href="https://upf.at-shift.net/output/">リファレンス</a> ·
    <a href="https://github.com/at-shift/atshift-user-profile-fields/releases">ダウンロード</a> ·
    <a href="https://upf.at-shift.net/en/">English</a>
  </p>
</div>

## 概要

atshift User Profile Fieldsは、WordPressのユーザー追加・編集画面に必要なプロフィール項目を追加し、標準項目と一緒に使いやすい順序と構成へ整理するプラグインです。

不要な標準項目を隠し、ラベル、概要、必須入力、入力形式、並び順をサイトの運用に合わせて設定できます。管理者がユーザー情報を登録・更新する日常的な運用を、迷いにくい自然な入力画面へ整えます。

## 主な機能

- テキスト、テキストエリア、メール、URL、電話番号、数値、画像などのプロフィール項目
- チェックボックス、ラジオボタン、セレクトメニュー
- WordPress標準プロフィール項目の並び替えと表示設定
- グループ、ボックス、条件分岐、アコーディオンによる画面構成
- 必須入力、入力値の検証、フィールドごとの概要表示
- ドラッグ操作によるフィールドとフィールドセットの並び替え
- 新規ユーザー追加画面と既存ユーザー編集画面への適用
- JSONによるフィールドセットの書き出し・読み込み
- 日本語・英語の標準プロフィールプリセット

## 動作環境

- WordPress 6.0以上
- PHP 7.4以上

## インストール

1. [Releases](https://github.com/at-shift/atshift-user-profile-fields/releases)からZIPファイルをダウンロードします。
2. WordPress管理画面の「プラグイン」→「プラグインを追加」→「プラグインのアップロード」からZIPファイルをアップロードします。
3. プラグインを有効化します。
4. 管理メニューの「atshift User Profile Fields」→「フィールド管理」を開きます。

詳しい手順は[フィールドの追加手順](https://upf.at-shift.net/guide/)をご覧ください。

## はじめかた

フィールド管理画面では、次のいずれかの方法でプロフィール画面を作成できます。

1. 一からフィールドセットを作成する
2. 用意されている標準プロフィールセットを使う
3. 別のサイトなどで書き出したセットファイルを読み込む

フィールドセットを保存した後、「ユーザー」→「ユーザー一覧」または「新規ユーザーを追加」から、ラベル、概要、入力欄、必須表示、並び順を確認してください。

## 保存した値の取得

追加したフィールドの値は、専用ヘルパーまたはWordPressのユーザーメタAPIで取得できます。

```php
$value = atshift_upf_get_user_field( 'company', $user_id );
```

通常の管理画面での利用では、値の出力を意識する必要はほぼありません。ユーザー一覧や独自の公開プロフィールなど、フロントエンドへユーザー情報を表示する場合は[表示・出力リファレンス](https://upf.at-shift.net/output/)をご確認ください。

## Proアドオン

無料版で作成したフィールドセットをそのまま土台にして、必要なサイトだけPro機能を追加できます。Proアドオンは、このリポジトリに含まれる無料版と併用します。

Proアドオンでは、ユーザー分類、表示・編集権限、公開プロフィール、ユーザー一覧、CSV運用などを追加できます。

- [Proアドオンの機能](https://upf.at-shift.net/pro/)
- [Proアドオンの料金](https://upf.at-shift.net/price/)
- [Proショートコードリファレンス](https://upf.at-shift.net/shortcodes/)

## ドキュメント

| 内容 | 日本語 | English |
| --- | --- | --- |
| 公式サイト | [upf.at-shift.net](https://upf.at-shift.net/) | [English home](https://upf.at-shift.net/en/) |
| フィールドの追加 | [導入ガイド](https://upf.at-shift.net/guide/) | [Setup guide](https://upf.at-shift.net/en/guide/) |
| 値の取得・表示 | [表示・出力](https://upf.at-shift.net/output/) | [Display and output](https://upf.at-shift.net/en/output/) |
| Proショートコード | [リファレンス](https://upf.at-shift.net/shortcodes/) | [Shortcode reference](https://upf.at-shift.net/en/shortcodes/) |

## 不具合報告

再現手順、WordPressとPHPのバージョン、利用しているプラグインのバージョンを添えて、[GitHub Issues](https://github.com/at-shift/atshift-user-profile-fields/issues)からお知らせください。

## ライセンス

[GPL-2.0-or-later](LICENSE)
