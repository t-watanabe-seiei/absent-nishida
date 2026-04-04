# 欠席連絡システム

保護者がオンラインで欠席・遅刻連絡を行い、担任へ自動通知するWebアプリケーション。

## 技術スタック

| 区分 | 技術 |
|------|------|
| バックエンド | Laravel 12 |
| フロントエンド | Vue.js 3 (Composition API) + Vite |
| データベース | SQLite |
| スタイリング | Tailwind CSS |
| 認証 | セッション認証 + メール2段階認証 |
| 状態管理 | Pinia |

---

## セットアップ

```bash
# 1. 依存パッケージインストール
composer install
npm install

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# .env のメール設定を変更（開発中は log）
# MAIL_MAILER=log

# 3. データベース作成
touch database/database.sqlite
php artisan migrate

# 4. フロントエンドビルド
npm run build
# または開発時
npm run dev

# 5. 管理者アカウント作成（CSVインポートを使用）
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
- 生徒一覧（クラス・氏名で検索・フィルタ）
- スーパー管理者は全生徒、担任は自分のクラスの生徒のみ操作可

### クラス管理
- クラスの登録・編集・削除
- クラス一覧（年度・クラス名でフィルタ）
- 各クラスに担任名・担任メールアドレスを設定

### 保護者管理
- 保護者の登録・編集・削除
- 保護者一覧

### 欠席連絡管理
- 本日の欠席・遅刻一覧（担任はクラス絞り込み）
- 欠席連絡の月次一覧・統計

---

## 保護者機能

### 欠席・遅刻連絡
- 区分（欠席 / 遅刻）の選択
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
| `admins` | 管理者（スーパー管理者・担任） |
| `classes` | クラス（担任・年度情報を含む） |
| `students` | 生徒（クラスへの外部キー） |
| `parents` | 保護者（生徒への外部キー・認証情報） |
| `absences` | 欠席・遅刻連絡 |
| `two_factor_codes` | 2段階認証コード（一時保存） |

---

## 注意事項

- SQLite の外部キー制約は有効化されている（`PRAGMA foreign_keys = ON`）
- 保護者の `parent_initial_password` は bcrypt でハッシュ化して保存
- 2段階認証コードは使用後・期限切れ後に自動削除
- 開発環境ではメール送信を `log` ドライバーで代替（`storage/logs/laravel.log` に出力）
