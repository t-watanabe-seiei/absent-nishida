# 欠席連絡システム

保護者がオンラインで欠席・遅刻・早退連絡を行い、担任へ自動メール通知するWebアプリケーション。  
管理者（スーパー管理者・担任）は欠席状況をリアルタイムで参照・管理できる。

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
  └─ Pinia ストア (auth / admin / parent)
       └─ Laravel API (セッション認証)
            ├─ 管理者ルート  /api/admin/...
            ├─ 保護者ルート  /api/parent/...
            └─ 共通ルート   /api/register/...
```

- フロントエンドは1枚のHTMLファイル(`app.blade.php`)＋Vue Routerで画面遷移するSPA構成
- APIはすべて `/api/` 以下で提供（Laravelセッション認証）
- WebルートはSPAキャッチオール（`/{any}` → `app.blade.php`）

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
| `/admin/classes` | クラス一覧・管理 |
| `/admin/students` | 生徒一覧・管理 |
| `/admin/parents` | 保護者一覧・管理 |
| `/admin/absences` | 欠席連絡一覧 |
| `/admin/absences/today` | 本日の欠席・遅刻一覧 |
| `/admin/import` | CSVインポート |

**管理者種別：**
- **スーパー管理者**（`is_super_admin=true`）: 全クラスのデータを管理
- **担任**（`is_super_admin=false`）: 自分の担当クラスのデータのみ参照・管理

### 保護者

| URL | 説明 |
|-----|------|
| `/parent/login` | 保護者ログイン |
| `/parent/register-email` | 初回ログイン時のメール登録 |
| `/parent/verify-2fa` | 2段階認証コード入力 |
| `/parent/dashboard` | ダッシュボード（欠席連絡一覧） |
| `/parent/absences/new` | 欠席・遅刻連絡登録 |

---

## 認証フロー

### 管理者
```
メールアドレス + パスワード → ログイン成功 → ダッシュボード
```

### 保護者（初回ログイン）
```
initial_email + initial_password
  → メールアドレス登録（parent_email）
    → 認証コード送信（6桁・10分有効）
      → コード入力 → ダッシュボード
```

### 保護者（2回目以降）
```
initial_email + initial_password
  → 認証コード送信（登録済み parent_email 宛）
    → コード入力 → ダッシュボード
```

---

## 管理者機能

### 生徒管理
- 生徒の登録・編集・削除
- 生徒一覧（クラス・氏名・学年で検索・フィルタ）
  - スーパー管理者: **学年 → クラス** の2段階フィルタ
  - 担任: 自分のクラスの生徒のみ操作可

### クラス管理
- クラスの登録・編集・削除
- クラス一覧（年度・クラス名でフィルタ）
- 各クラスに担任名・担任メールアドレスを設定

### 保護者管理
- 保護者の登録・編集・削除
- 保護者一覧（生徒との1対多紐付け）

### 欠席連絡管理
- 本日の欠席・遅刻一覧（担任はクラス絞り込み）
- 日付範囲・クラス・区分でフィルタ可能
- 欠席連絡の月次一覧・統計グラフ・クラス別集計

### ダッシュボード
- クラス数・生徒数・保護者数・本日の欠席/遅刻数を表示
- 担任は自分のクラスのみの集計値を表示

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

### 認証メール再設定
- ダッシュボードから2段階認証用メールアドレスを変更可能
  1. 新しいメールアドレスを入力 → 確認コード送信
  2. 受信した6桁コードを入力 → 変更完了

---

## クラス一覧

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

> `class_id` はシステム内部の識別子で変更不可。`class_name` は表示名。

---

## CSVインポート

管理画面（`/admin/import`）から各種データを一括登録できる。

### 生徒データ（`/api/admin/import/students`）
```csv
seito_id,seito_name,seito_number,class_id,seito_initial_email
1001,山田太郎,1,1TOKUSHIN,1001@seiei.ac.jp
```
- `seito_id` 重複時は上書き更新

### 保護者データ（`/api/admin/import/parents`）
```csv
seito_id,parent_name,parent_initial_email,parent_initial_password
1001,山田一郎,yamada@example.com,password123
```
- `parent_initial_password` はインポート時に自動的に bcrypt ハッシュ化される

### 管理者データ（`/api/admin/import/admins`）
```csv
name,email,password,class_id,is_super_admin
スーパー管理者,admin@seiei.ac.jp,password,,true
田中先生,tanaka@seiei.ac.jp,password,1TOKUSHIN,false
```

### クラスデータ（`/api/admin/import/classes`）
```csv
class_id,class_name,teacher_name,teacher_email,year_id
1TOKUSHIN,1特進,田中先生,tanaka@seiei.ac.jp,2026
```
- `class_id` 重複時は担任・年度情報を上書き更新

### 担任データ（`/api/admin/import/teachers`）
```csv
class_id,teacher_name,teacher_email
1TOKUSHIN,田中先生,tanaka@seiei.ac.jp
```

### 生徒クラス一括更新（`/api/admin/import/student-classes`）
```csv
seito_id,class_id,seito_number
1001,2TOKUSHIN,3
1002,2SHINGAKU,1
```
- 年度切り替え時に生徒のクラス配属と出席番号を一括更新する際に使用
- `seito_id` または `class_id` が存在しない行はスキップ（エラーにならない）

---

## 年度切り替え手順

毎年4月の新学年度開始時に以下の順序で操作する。

### STEP 1: クラスデータ更新
担任変更・クラス名変更がある場合は新年度のクラスCSVをインポートする。

```csv
class_id,class_name,teacher_name,teacher_email,year_id
1TOKUSHIN,1特進,新担任名,newtanaka@seiei.ac.jp,2026
```

管理画面 → CSVインポート → **クラスインポート**

### STEP 2: 生徒クラス一括更新
進級した生徒の `class_id` と `seito_number`（出席番号）を更新する（例: 1年→2年へ進級）。

```csv
seito_id,class_id,seito_number
1001,2TOKUSHIN,3
1002,2SHINGAKU,1
```

管理画面 → CSVインポート → **生徒クラス一括更新**

> ⚠️ STEP 1（クラスデータ更新）を先に完了させてから実行すること。

### STEP 3: 新入生インポート
通常の生徒CSVインポートで1年生を追加する。

---

## ディレクトリ構成

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 管理者機能コントローラー
│   │   │   │   ├── ClassController.php
│   │   │   │   ├── StudentController.php
│   │   │   │   ├── ParentController.php
│   │   │   │   ├── AbsenceController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── CsvImportController.php
│   │   │   │   └── ImportController.php
│   │   │   ├── Auth/           # 認証コントローラー
│   │   │   │   ├── AdminLoginController.php
│   │   │   │   └── ParentLoginController.php
│   │   │   └── Parent/         # 保護者機能コントローラー
│   │   │       └── AbsenceController.php
│   │   └── Middleware/
│   │       ├── AdminAuth.php
│   │       ├── ParentAuth.php
│   │       └── TwoFactorVerified.php
│   ├── Models/
│   │   ├── ClassModel.php      # classes テーブル
│   │   ├── Student.php         # students テーブル
│   │   ├── ParentModel.php     # parents テーブル
│   │   ├── Absence.php         # absences テーブル
│   │   ├── Admin.php           # admins テーブル
│   │   └── TwoFactorCode.php   # two_factor_codes テーブル
│   └── Services/
│       ├── CsvImportService.php        # CSV処理
│       ├── TwoFactorService.php        # 2FA処理
│       └── AbsenceNotificationService.php  # メール通知
├── database/
│   ├── migrations/             # マイグレーションファイル
│   └── database.sqlite         # SQLiteデータベース
├── resources/js/
│   ├── pages/
│   │   ├── admin/              # 管理者画面コンポーネント
│   │   ├── parent/             # 保護者画面コンポーネント
│   │   └── auth/               # 認証画面コンポーネント
│   ├── stores/
│   │   ├── auth.js             # 認証状態管理（Pinia）
│   │   ├── admin.js            # 管理者API呼び出し
│   │   └── parent.js           # 保護者API呼び出し
│   └── router/index.js         # Vue Router 設定
└── routes/
    ├── api.php                 # APIルート定義
    └── web.php                 # Webルート（SPAエントリーポイント）
```

---

## データベース設計

| テーブル名 | 説明 |
|-----------|------|
| `admins` | 管理者（スーパー管理者・担任）。`is_super_admin` フラグで権限区別 |
| `classes` | クラス（担任・年度情報を含む）。`class_id` が主キー（例: `1TOKUSHIN`） |
| `students` | 生徒（`class_id` で `classes` に外部キー） |
| `parents` | 保護者（`seito_id` で生徒に紐付け・認証情報保持） |
| `absences` | 欠席・遅刻・早退連絡（`is_deleted` で論理削除） |
| `two_factor_codes` | 2段階認証コード（10分有効・使用後即削除） |

### 主なリレーション

```
classes ─< students ─< parents
                   └─< absences
admins >── classes
```

### admins テーブル主要カラム

| カラム | 説明 |
|--------|------|
| `email` | ログイン用メールアドレス |
| `password` | bcrypt ハッシュ |
| `class_id` | 担当クラス（NULL = スーパー管理者） |
| `is_super_admin` | true/false |

### students テーブル主要カラム

| カラム | 説明 |
|--------|------|
| `seito_id` | 生徒番号（一意） |
| `seito_name` | 氏名 |
| `seito_number` | 出席番号 |
| `class_id` | 所属クラスID |
| `seito_initial_email` | 初期メール（未使用。将来の生徒ポータル向け） |

### absences テーブル主要カラム

| カラム | 説明 |
|--------|------|
| `seito_id` | 対象生徒 |
| `division` | 欠席 / 遅刻 / 早退 |
| `absence_date` | 欠席日 |
| `reason` | 理由 |
| `scheduled_time` | 登校予定時刻（遅刻の場合のみ） |
| `is_deleted` | 論理削除フラグ |

---

## APIエンドポイント一覧

### 認証

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `/api/admin/login` | 管理者ログイン |
| POST | `/api/admin/logout` | 管理者ログアウト |
| GET | `/api/admin/me` | ログイン中の管理者情報 |
| POST | `/api/parent/login` | 保護者ログイン |
| POST | `/api/parent/verify-2fa` | 2FA コード検証 |
| POST | `/api/parent/resend-2fa` | 2FA コード再送信 |

### 管理者API（要認証）

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `/api/admin/dashboard/stats` | ダッシュボード統計 |
| GET/POST | `/api/admin/classes` | クラス一覧・登録 |
| GET/PUT/DELETE | `/api/admin/classes/{id}` | クラス詳細・更新・削除 |
| GET/POST | `/api/admin/students` | 生徒一覧・登録 |
| GET/PUT/DELETE | `/api/admin/students/{id}` | 生徒詳細・更新・削除 |
| GET/POST | `/api/admin/parents` | 保護者一覧・登録 |
| GET/PUT/DELETE | `/api/admin/parents/{id}` | 保護者詳細・更新・削除 |
| GET | `/api/admin/absences` | 欠席連絡一覧 |
| GET | `/api/admin/absences/stats` | 欠席統計 |
| GET | `/api/admin/absences/today` | 本日の欠席一覧 |
| POST | `/api/admin/import/students` | 生徒CSVインポート |
| POST | `/api/admin/import/parents` | 保護者CSVインポート |
| POST | `/api/admin/import/admins` | 管理者CSVインポート |
| POST | `/api/admin/import/student-classes` | 生徒クラス一括更新 |

### 保護者API（要認証）

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `/api/parent/me` | ログイン中の保護者情報 |
| GET/POST | `/api/parent/absences` | 欠席連絡一覧・登録 |
| GET/PUT/DELETE | `/api/parent/absences/{id}` | 欠席連絡詳細・更新・削除 |

---

## 注意事項

- SQLite の外部キー制約は有効化されている（`PRAGMA foreign_keys = ON`）
- 保護者の `parent_initial_password` は bcrypt でハッシュ化して保存
- 2段階認証コードは使用後・期限切れ後に自動削除
- 開発環境ではメール送信を `log` ドライバーで代替（`storage/logs/laravel.log` に出力）
- クラス名「情報会計」は2026年4月より「情会」に変更（`class_id` は `JOHO` のまま）
