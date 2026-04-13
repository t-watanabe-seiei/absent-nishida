# 欠席連絡システム

保護者が生徒の欠席・遅刻・早退を登録すると、担任へ自動メール通知するWebアプリケーション。  
管理者はリアルタイムで欠席状況を参照・管理でき、保護者へのお知らせ送信（PDF添付対応）機能も備える。

## 技術スタック

| 区分 | 技術 |
|------|------|
| バックエンド | Laravel 12 (PHP 8.2+) |
| フロントエンド | Vue.js 3 (Composition API) + Vite |
| データベース | SQLite |
| スタイリング | Tailwind CSS |
| 認証 | セッション認証 + メール2段階認証 (2FA) |
| 状態管理 | Pinia |
| APIスタイル | REST (JSON) |

---

## アーキテクチャ概要

```
SPA (Vue.js 3)
  └─ Pinia ストア (auth)
       └─ Laravel API (セッション認証)
            ├─ 管理者ルート  /api/admin/...
            ├─ 保護者ルート  /api/parent/...
            └─ 共通ルート   /api/register/...
```

- フロントエンドは1枚のHTMLファイル（`app.blade.php`）＋ Vue Router で画面遷移する SPA 構成
- API はすべて `/api/` 以下で提供（Laravel セッション認証）
- Web ルートは SPA キャッチオール（`/{any}` → `app.blade.php`）

---

## セットアップ

```bash
# 1. 依存パッケージインストール
composer install
npm install

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# .env のメール設定を変更（開発中は log ドライバーでログ出力）
# MAIL_MAILER=log

# 3. データベース作成
touch database/database.sqlite
php artisan migrate

# 4. (任意) サンプルデータ投入
php artisan db:seed

# 5. フロントエンドビルド
npm run build
# または開発時（ホットリロード）
npm run dev

# 6. 管理者アカウント作成（CSVインポートを使用）
# 管理画面 → CSVインポート → 管理者データ
```

---

## ユーザー種別と画面構成

### 管理者（学校職員）

| URL | 説明 |
|-----|------|
| `/admin/login` | 管理者ログイン |
| `/admin/dashboard` | ダッシュボード（統計情報） |
| `/admin/classes` | クラス一覧・管理（スーパー管理者のみ） |
| `/admin/students` | 生徒一覧・管理 |
| `/admin/parents` | 保護者一覧・管理（スーパー管理者は編集・削除可、担任は閲覧のみ） |
| `/admin/absences` | 欠席連絡一覧 |
| `/admin/absences/today` | 本日の欠席・遅刻一覧 |
| `/admin/import` | CSVインポート（スーパー管理者のみ） |
| `/admin/announcements` | お知らせ管理（作成・一覧・既読確認） |
| `/admin/settings` | システム設定（スーパー管理者のみ） |

**管理者種別：**
- **スーパー管理者**（`is_super_admin=true`）: 全クラスのデータを管理、お知らせ機能のON/OFF切替可
- **担任**（`is_super_admin=false`）: 自分の担当クラスのデータのみ参照・管理
  - 生徒一覧: 閲覧のみ（編集・削除ボタン非表示）
  - 保護者一覧: 自クラスの保護者を閲覧のみ（編集・削除ボタン非表示）
  - 欠席連絡: 自クラスの欠席を閲覧＋管理者作成分を登録・編集・削除可

### 保護者

| URL | 説明 |
|-----|------|
| `/parent/login` | 保護者ログイン |
| `/parent/register-email` | 初回ログイン時のメール登録 |
| `/parent/verify-2fa` | 2段階認証コード入力 |
| `/parent/dashboard` | ダッシュボード（欠席連絡・お知らせ表示） |
| `/parent/absences` | 欠席連絡一覧 |
| `/parent/absences/create` | 欠席・遅刻連絡登録 |

---

## 認証フロー

### 管理者
```
email + パスワード → ログイン成功 → ダッシュボード
```

### 保護者（初回ログイン）
```
initial_email + initial_password
  → メールアドレス登録（parent_email: 2FA送信先）
    → 認証コード送信（6桁・10分有効）
      → コード入力 → ダッシュボード
```
> **兄弟がいる場合**: 各兄弟は別々の `initial_email` でログインし、
> 2FA送信先（`parent_email`）は同じ保護者のメールアドレスを複数レコードで共有できます。

### 保護者（2回目以降）
```
initial_email + initial_password
  → 認証コード送信（登録済み parent_email 宛）
    → コード入力 → ダッシュボード
```

---

## データベース構造

### 主要テーブル

| テーブル名 | 概要 |
|-----------|------|
| `admins` | 管理者（スーパー管理者・担任）情報 |
| `classes` | クラス情報（class_id, class_name, teacher_name, teacher_email, year_id） |
| `students` | 生徒情報（seito_id, seito_name, seito_number, class_id） |
| `parents` | 保護者情報（seito_id, parent_name, parent_email, 認証情報） |
| `absences` | 欠席連絡（seito_id, division, reason, absence_date, scheduled_time, is_admin_created） |
| `two_factor_codes` | 2段階認証コード（email, code, guard, expires_at） |
| `announcements` | お知らせ（admin_id, title, body, target_class_ids, target_parent_ids, notify_by_email, expires_at） |
| `announcement_reads` | お知らせ既読記録（announcement_id, parent_id, read_at） |
| `announcement_attachments` | 添付ファイルメタデータ（announcement_id, original_name, stored_path） |
| `system_settings` | システム設定（key, value）|

### admins テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| name | string | 管理者名 |
| email | string | メールアドレス（ログイン用） |
| password | string | ハッシュ化パスワード |
| class_id | string nullable | 担当クラスID（担任のみ） |
| is_super_admin | boolean | スーパー管理者フラグ（デフォルト false） |

### parents テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| seito_id | string | 生徒ID（FK: students.seito_id） |
| parent_name | string | 保護者氏名 |
| parent_relationship | string | 続柄（父/母/その他） |
| parent_tel | string nullable | 電話番号 |
| parent_initial_email | string **UNIQUE** | **ログイン用**メールアドレス（管理者が設定・一意） |
| parent_initial_password | string | ログイン用パスワード（bcrypt） |
| parent_email | string nullable | **2FA送信先**メールアドレス（保護者が初回ログイン時に設定） |
| parent_password | string nullable | 将来の拡張用 |

> **注意**: `parent_email`（2FA送信先）は兄弟で同じアドレスを共有できます。`parent_initial_email`（ログイン用）のみ一意制約があります。

### classes テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| class_id | string | クラスID（例: 1TOKUSHIN） |
| class_name | string | クラス名（例: 1特進） |
| teacher_name | string | 担任氏名 |
| teacher_email | string | 担任メールアドレス（通知送信先） |
| year_id | integer | 年度 |

---

## 管理者機能

### 生徒管理
- スーパー管理者: 生徒の登録・編集・削除、全クラス表示
- 担任: 自分のクラスの生徒のみ表示・閲覧（編集・削除ボタン非表示）
- 生徒一覧（クラス・氏名・学年で検索・フィルタ）
  - スーパー管理者: **学年 → クラス** の2段階フィルタ

### クラス管理
- クラスの登録・編集・削除
- クラス一覧（年度・クラス名でフィルタ）
- 各クラスに担任名・担任メールアドレスを設定

### 保護者管理
- スーパー管理者: 保護者の登録・編集・削除、全クラス表示
- 担任: 自分のクラスの保護者のみ表示・閲覧（編集・削除ボタン非表示）
- 保護者一覧（生徒との1対多紐付け）
- **兄弟対応**: 同じ保護者が兄弟の複数生徒を管理する場合、2FA用メールアドレス（`parent_email`）は兄弟間で同じアドレスを使用可能。ログイン用メール（`parent_initial_email`）は兄弟ごとに異なるアドレスで一意管理。

### 欠席連絡管理
- 本日の欠席・遅刻一覧（担任はクラス絞り込み）
- 日付範囲・クラス・区分でフィルタ可能
- 欠席連絡の月次一覧・統計グラフ・クラス別集計
- フィルター条件に合う全件を UTF-8 BOM 付き CSV でダウンロード可能（項目：日付・学年・クラス・出席番号・氏名・区分・理由・予定時刻）
- **管理者による欠席連絡入力（登録・編集・削除）**
  - スーパー管理者: 全クラスの生徒を対象に登録可
  - 担任: 自分のクラスの生徒のみ対象に登録可
  - 保護者が入力した欠席連絡は変更・削除不可（閲覧のみ）
  - 管理者自身が登録した欠席連絡のみ編集・削除可
  - `absences.is_admin_created = true` で識別

### お知らせ管理
- お知らせの作成・編集・削除（タイトル・本文・有効期限・対象クラス/保護者を指定）
- PDFファイルを最大5件添付可能（1ファイル最大10MB）
- 作成時にメール通知の有無を選択可能（対象保護者へ一括送信）
- スーパー管理者: 全クラスを自由に選択可能
- 担任: 自クラスのみ対象として送信可能
- 担任は自クラス宛の全お知らせを閲覧可能（既読/未読人数付き）
- 詳細画面で保護者ごとの既読/未読状況を確認可能

### システム設定
- スーパー管理者のみアクセス可能
- お知らせ機能の有効/無効を切り替え（デフォルト: 無効）

### ダッシュボード
- クラス数・生徒数・保護者数・本日の欠席/遅刻数を表示
- 担任は自分のクラスのみの集計値を表示

### CSVインポート

#### 管理者CSVインポート仕様

| 列 | 説明 |
|----|------|
| name | 管理者名（必須） |
| email | メールアドレス（必須・重複時は上書き更新） |
| password | パスワード（必須） |
| class_id | 担当クラスID（担任の場合・空欄可） |
| is_super_admin | true/1/yes → スーパー管理者、それ以外 → 担任（デフォルト false） |

- 存在しない `class_id` は `null` で登録し warnings に記録
- テンプレートCSVは `/api/admin/import/template/admins` でダウンロード

#### 生徒CSVインポート仕様

| 列 | 説明 |
|----|------|
| seito_id | 生徒ID（必須） |
| seito_name | 氏名（必須） |
| seito_number | 出席番号（必須） |
| class_id | クラスID（必須） |

#### 保護者CSVインポート仕様

| 列 | 説明 |
|----|------|
| seito_id | 生徒ID（必須） |
| parent_name | 保護者名（必須） |
| parent_email | メールアドレス（必須） |
| parent_relationship | 続柄 |
| parent_tel | 電話番号 |

---

## 保護者機能

### 欠席・遅刻連絡
- 区分（欠席 / 遅刻 / 早退）の選択
- 理由の入力
- 欠席日の選択
- 登校予定時刻（遅刻の場合のみ）
- 登録した連絡の編集・削除

### 通知
- 欠席連絡が登録されると、担任（`classes.teacher_email`）へ自動メール通知

### お知らせ受信
- ダッシュボードログイン時に有効なお知らせが自動表示
- 表示と同時に自動既読登録（管理者側で既読状況を確認可能）
- PDFファイルはダウンロードリンクから取得可能
- お知らせ機能が無効の場合は表示されない

### 認証メール再設定
- ダッシュボードから2段階認証用メールアドレスを変更可能
  1. 新しいメールアドレスを入力 → 確認コード送信
  2. 受信した6桁コードを入力 → 変更完了

---

## API エンドポイント一覧

### 管理者 `/api/admin/`

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `/admin/login` | ログイン |
| POST | `/admin/verify-2fa` | 2FA検証 |
| POST | `/admin/logout` | ログアウト |
| GET | `/admin/me` | 認証ユーザー情報取得 |
| GET | `/admin/dashboard/stats` | ダッシュボード統計 |
| GET/POST | `/admin/classes` | クラス一覧・登録 |
| GET/PUT/DELETE | `/admin/classes/{id}` | クラス詳細・更新・削除 |
| GET/POST | `/admin/students` | 生徒一覧・登録 |
| GET/PUT/DELETE | `/admin/students/{id}` | 生徒詳細・更新・削除 |
| GET/POST | `/admin/parents` | 保護者一覧・登録 |
| GET/PUT/DELETE | `/admin/parents/{id}` | 保護者詳細・更新・削除 |
| GET | `/admin/absences/stats` | 欠席統計 |
| GET | `/admin/absences/monthly` | 月次欠席一覧 |
| GET | `/admin/absences/today` | 本日の欠席一覧 |
| GET | `/admin/absences/export` | 欠席CSVダウンロード |
| GET | `/admin/absences` | 欠席一覧 |
| GET | `/admin/absences/{id}` | 欠席詳細 |
| POST | `/admin/absences` | 欠席連絡登録（管理者作成） |
| PUT | `/admin/absences/{id}` | 欠席連絡更新（管理者作成のもののみ） |
| DELETE | `/admin/absences/{id}` | 欠席連絡削除（管理者作成のもののみ・論理削除） |
| POST | `/admin/import/students` | 生徒CSVインポート |
| POST | `/admin/import/parents` | 保護者CSVインポート |
| POST | `/admin/import/admins` | 管理者CSVインポート |
| POST | `/admin/import/student-classes` | 生徒クラス紐付けインポート |
| GET | `/admin/import/template/{type}` | テンプレートCSVダウンロード |
| GET/POST | `/admin/announcements` | お知らせ一覧・作成 |
| GET/PUT/DELETE | `/admin/announcements/{id}` | お知らせ詳細・更新・削除 |
| GET | `/admin/announcements/{id}/reads` | 既読状況取得 |
| POST | `/admin/announcements/{id}/attachments` | 添付ファイル追加 |
| DELETE | `/admin/announcements/{id}/attachments/{attachId}` | 添付ファイル削除 |
| GET | `/admin/settings` | システム設定取得 |
| PUT | `/admin/settings` | システム設定更新 |

### 保護者 `/api/parent/`

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `/parent/login` | ログイン |
| POST | `/parent/register-email` | 初回メール登録 |
| POST | `/parent/verify-2fa` | 2FA検証 |
| POST | `/parent/resend-2fa` | 2FA再送信 |
| POST | `/parent/logout` | ログアウト |
| GET | `/parent/me` | 認証ユーザー情報取得 |
| POST | `/parent/request-email-change` | メール変更リクエスト |
| POST | `/parent/confirm-email-change` | メール変更確定 |
| GET/POST | `/parent/absences` | 欠席連絡一覧・登録 |
| GET/PUT/DELETE | `/parent/absences/{id}` | 欠席連絡詳細・更新・削除 |
| GET | `/parent/announcements` | お知らせ一覧取得（自動既読登録） |
| GET | `/parent/announcements/{id}/attachments/{attachId}` | 添付ファイルダウンロード |

---

## クラス一覧（サンプル）

| class_id | class_name | 備考 |
|----------|-----------|------|
| 1TOKUSHIN | 1特進 | |
| 1SHINGAKU | 1進学 | |
| 1CHORI | 1調理 | |
| 1JOHO | 1情会 | ※旧称「1情報会計」から変更 |
| 1FUKUSHI | 1福祉 | |
| 1SOGO1 | 1総合１ | |
| 1SOGO2 | 1総合２ | |
| 1SOGO3 | 1総合３ | |
| 2TOKUSHIN | 2特進 | |
| 2SHINGAKU | 2進学 | |
| 2CHORI | 2調理 | |
| 2JOHO | 2情会 | ※旧称「2情報会計」から変更 |
| 2FUKUSHI | 2福祉 | |
| 2SOGO1 | 2総合１ | |
| 2SOGO2 | 2総合２ | |
| 2SOGO3 | 2総合３ | |
| 3TOKUSHIN | 3特進 | |
| 3SHINGAKU | 3進学 | |
| 3CHORI | 3調理 | |
| 3JOHO | 3情会 | ※旧称「3情報会計」から変更 |
| 3FUKUSHI | 3福祉 | |
| 3SOGO1 | 3総合１ | |
| 3SOGO2 | 3総合２ | |
| 3SOGO3 | 3総合３ | |

---

## セキュリティ

- パスワードはBcryptでハッシュ化
- 保護者ログインは2段階認証（メール送信の6桁コード・10分有効）
- セッションはLaravelの標準セキュリティ機能で保護
- 管理者ミドルウェア（`AdminAuth`）で管理者専用ルートを保護
- 保護者ミドルウェア（`ParentAuth`）で保護者専用ルートを保護
- CSVインジェクション対策（出力フィールドの先頭文字をサニタイズ）

---

## ディレクトリ構成（主要部分）

```
laravel-app/
 app/
   ├── Http/
   │   ├── Controllers/
   │   │   ├── Admin/           # 管理者用コントローラ
   │   │   ├── Auth/            # 認証コントローラ
   │   │   └── Parent/          # 保護者用コントローラ
   │   ├── Middleware/          # AdminAuth, ParentAuth, TwoFactorVerified
   │   └── Requests/            # フォームリクエスト（バリデーション）
   ├── Models/                  # Eloquentモデル
   └── Services/                # ビジネスロジック（通知・CSVインポート等）
 database/
   ├── migrations/              # DBマイグレーション
   └── seeders/                 # シードデータ
 resources/
   └── js/
       ├── pages/
       │   ├── admin/           # 管理者画面Vue
       │   ├── parent/          # 保護者画面Vue
       │   └── auth/            # 認証画面Vue
       ├── stores/              # Piniaストア
       ├── layouts/             # レイアウトコンポーネント
       └── router/              # Vue Router設定
 routes/
   ├── api.php                  # API ルート定義
   └── web.php                  # SPAキャッチオール
 storage/
    └── app/               # アップロードファイル保存先
```

---

## 変更履歴

### 2026-04-13: 兄弟がいる保護者の parent_email 重複エラー修正

#### 概要

2FA用メールアドレス（`parent_email`）を登録しようとするとバリデーションエラーが発生していた問題を修正しました。

#### 根本原因

`parents` テーブルの `parent_email` カラムに `UNIQUE` 制約があり、同一メールアドレスを複数レコードに登録できなかった。

#### 対応内容

| # | ファイル | 変更内容 |
|---|---|---|
| E-1 | `database/migrations/2026_04_13_185317_remove_unique_from_parents_parent_email.php` | `parent_email` の UNIQUE インデックスを削除 |
| E-2 | `app/Http/Requests/StoreParentRequest.php` | unique チェック対象を `parent_initial_email` に変更 |
| E-3 | `app/Http/Requests/UpdateParentRequest.php` | 同上 |
| E-4 | `app/Http/Controllers/Admin/ParentController.php` | `store()`: 登録時に `parent_email = null` を明示セット |
| E-5 | `app/Http/Controllers/Admin/ParentController.php` | `update()`: ログイン用メール(`parent_initial_email`)のみ更新、`parent_email` は保護者設定を維持 |
| E-6 | `app/Http/Controllers/Auth/ParentLoginController.php` | `registerEmail()`: `parent_email` の unique バリデーション削除 |
| E-7 | `app/Http/Controllers/Auth/RegisterController.php` | `registerParent()`: 同上 |
| E-8 | `resources/js/pages/admin/parents/Form.vue` | 編集フォームのロード元を `parent_initial_email` に変更 |

#### 設計方針

- `parent_initial_email`（ログイン用）は **UNIQUE 制約を維持**。兄弟はそれぞれ異なるログインIDを持つ。
- `parent_email`（2FA送信先）は **UNIQUE 制約を削除**。兄弟が同じ保護者メールアドレスを共有できる。
- 管理者フォームで入力する「メールアドレス」は **ログイン用** として扱われる。2FA用メールは保護者が初回ログイン時に自分で設定する。
