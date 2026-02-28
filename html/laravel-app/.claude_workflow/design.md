# 設計書 - 欠席連絡システム

## 1. システムアーキテクチャ

### 1.1 全体構成
```
┌─────────────────────────────────────┐
│       フロントエンド (Vue.js)        │
│    + Tailwind CSS                   │
└──────────────┬──────────────────────┘
               │ HTTP/JSON
               ▼
┌─────────────────────────────────────┐
│    バックエンド (Laravel 12)         │
│    - API Routes                     │
│    - Controllers                    │
│    - Models                         │
│    - Middleware (Auth, 2FA)         │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│      データベース (SQLite)           │
└─────────────────────────────────────┘
```

### 1.2 技術選定理由

#### 1.2.1 Laravel 12
- 最新バージョンで長期サポート対象
- 標準で認証機能、バリデーション、メール送信機能を提供
- Eloquent ORMによる安全なデータベース操作

#### 1.2.2 Vue.js 3 + Composition API
- リアクティブなUI構築
- コンポーネント指向で再利用性が高い
- Laravel Viteと統合が容易

#### 1.2.3 SQLite
- セットアップが簡単
- 小規模〜中規模システムに最適
- ファイルベースで管理が容易

#### 1.2.4 Tailwind CSS
- ユーティリティファーストで開発速度が向上
- レスポンシブデザインが容易
- カスタマイズ性が高い

## 2. データベース詳細設計

### 2.1 ER図
```
┌─────────────┐      ┌─────────────┐
│   classes   │      │   students  │
├─────────────┤      ├─────────────┤
│ id (PK)     │◄────┤│ id (PK)     │
│ class_id    │      │ seito_id    │
│ class_name  │      │ seito_name  │
│ teacher_name│      │ seito_number│
│ teacher_email      │ class_id(FK)│
│ year_id     │      └──────┬──────┘
└─────────────┘             │
                            │
                            ▼
                    ┌─────────────┐
                    │   parents   │
                    ├─────────────┤
                    │ id (PK)     │
                    │ seito_id(FK)│
                    │ parent_name │
                    │ parent_rel..│
                    │ parent_tel  │
                    │ parent_ini..│
                    │ parent_email│
                    │ parent_pass.│
                    └──────┬──────┘
                           │
                           │
                    ┌──────┴──────┐
                    │  absences   │
                    ├─────────────┤
                    │ id (PK)     │
                    │ seito_id(FK)│
                    │ division    │
                    │ reason      │
                    │ scheduled_..│
                    │ absence_date│
                    └─────────────┘

┌─────────────┐
│   admins    │
├─────────────┤
│ id (PK)     │
│ name        │
│ email       │
│ password    │
└─────────────┘
```

### 2.2 マイグレーション実装順序
1. `create_admins_table` - 管理者テーブル
2. `create_classes_table` - クラステーブル
3. `create_students_table` - 生徒テーブル（外部キー: class_id）
4. `create_parents_table` - 保護者テーブル（外部キー: seito_id）
5. `create_absences_table` - 欠席連絡テーブル（外部キー: seito_id）
6. `create_two_factor_codes_table` - 2段階認証コードテーブル

### 2.3 インデックス設計
- `students.seito_id` - UNIQUE INDEX
- `students.class_id` - INDEX
- `classes.class_id` - UNIQUE INDEX
- `parents.seito_id` - INDEX
- `parents.parent_email` - UNIQUE INDEX
- `absences.seito_id` - INDEX
- `absences.absence_date` - INDEX
- `admins.email` - UNIQUE INDEX

## 3. バックエンド設計

### 3.1 ディレクトリ構成
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── StudentController.php
│   │   │   ├── ParentController.php
│   │   │   ├── ClassController.php
│   │   │   └── ImportController.php
│   │   ├── Auth/
│   │   │   ├── AdminLoginController.php
│   │   │   ├── ParentLoginController.php
│   │   │   └── TwoFactorController.php
│   │   └── Parent/
│   │       └── AbsenceController.php
│   ├── Middleware/
│   │   ├── AdminAuth.php
│   │   ├── ParentAuth.php
│   │   └── TwoFactorVerified.php
│   └── Requests/
│       ├── StoreStudentRequest.php
│       ├── UpdateStudentRequest.php
│       ├── StoreParentRequest.php
│       ├── StoreClassRequest.php
│       └── StoreAbsenceRequest.php
├── Models/
│   ├── Admin.php
│   ├── Student.php
│   ├── ParentModel.php
│   ├── ClassModel.php
│   ├── Absence.php
│   └── TwoFactorCode.php
└── Services/
    ├── CsvImportService.php
    └── TwoFactorService.php
```

### 3.2 ルーティング設計

#### 3.2.1 管理者ルート (prefix: /admin)
```php
// 認証
POST /admin/login
POST /admin/logout
POST /admin/verify-2fa

// 生徒管理
GET /admin/students
POST /admin/students
GET /admin/students/{id}
PUT /admin/students/{id}
DELETE /admin/students/{id}

// 保護者管理
GET /admin/parents
POST /admin/parents
GET /admin/parents/{id}
PUT /admin/parents/{id}
DELETE /admin/parents/{id}

// クラス管理
GET /admin/classes
POST /admin/classes
GET /admin/classes/{id}
PUT /admin/classes/{id}
DELETE /admin/classes/{id}

// CSVインポート
POST /admin/import/students
POST /admin/import/classes
POST /admin/import/teachers
POST /admin/import/parents
```

#### 3.2.2 保護者ルート (prefix: /parent)
```php
// 認証
POST /parent/login              // 初期認証（parent_initial_email/password）
POST /parent/register-email     // メールアドレス登録（初回のみ）
POST /parent/verify-2fa         // 2段階認証コード検証
POST /parent/logout             // ログアウト

// 欠席連絡（2段階認証後のみアクセス可）
GET /parent/absences            // 欠席連絡一覧
POST /parent/absences           // 欠席連絡登録
GET /parent/absences/{id}       // 欠席連絡詳細
PUT /parent/absences/{id}       // 欠席連絡更新
DELETE /parent/absences/{id}    // 欠席連絡削除
```

### 3.3 認証・認可設計

#### 3.3.1 保護者2段階認証フロー

**初回ログイン時:**
```
1. POST /parent/login (parent_initial_email, parent_initial_password)
   ↓
2. 認証成功 → parent_emailが未登録の場合
   ↓
3. POST /parent/register-email (parent_email) - メールアドレス登録
   ↓
4. 6桁の認証コードを生成 → DBに保存（有効期限10分）
   ↓
5. parent_emailに認証コードをメール送信
   ↓
6. POST /parent/verify-2fa (code) - コード検証
   ↓
7. コードが正しい場合、セッション確立
   ↓
8. 欠席連絡機能へアクセス可能
```

**2回目以降のログイン時:**
```
1. POST /parent/login (parent_initial_email, parent_initial_password)
   ↓
2. 認証成功 → parent_emailが登録済みの場合
   ↓
3. 6桁の認証コードを生成 → DBに保存（有効期限10分）
   ↓
4. parent_emailに認証コードをメール送信
   ↓
5. POST /parent/verify-2fa (code) - コード検証
   ↓
6. コードが正しい場合、セッション確立
   ↓
7. 欠席連絡機能へアクセス可能
```

**注意事項:**
- 毎回のログインで必ず2段階認証を実施
- parent_emailが未登録の場合はメール登録画面へリダイレクト
- 認証コードは使用後、またはexpires_at到達時に削除

#### 3.3.2 ガード設定
- `admin` ガード: 管理者用
- `parent` ガード: 保護者用

#### 3.3.3 ミドルウェア
- `AdminAuth`: 管理者認証チェック
- `ParentAuth`: 保護者認証チェック
- `TwoFactorVerified`: 2段階認証済みチェック

### 3.4 バリデーションルール

#### 3.4.1 生徒登録
```php
'seito_id' => 'required|string|unique:students',
'seito_name' => 'required|string|max:255',
'seito_number' => 'required|integer|min:1',
'class_id' => 'required|exists:classes,id',
```

#### 3.4.2 保護者登録
```php
'seito_id' => 'required|exists:students,seito_id',
'parent_name' => 'required|string|max:255',
'parent_relationship' => 'required|in:父,母,その他',
'parent_tel' => 'nullable|string|max:20',
'parent_initial_email' => 'required|email|unique:parents',
'parent_initial_password' => 'required|string|min:6',
```

#### 3.4.3 保護者CSVインポート
```php
'seito_id' => 'required|string|exists:students,seito_id',
'parent_name' => 'required|string|max:255',
'parent_initial_email' => 'required|email|unique:parents,parent_initial_email',
'parent_initial_password' => 'required|string',
```

#### 3.4.4 欠席連絡登録
```php
'seito_id' => 'required|exists:students,seito_id',
'division' => 'required|in:欠席,遅刻',
'reason' => 'required|string|max:500',
'scheduled_time' => 'required_if:division,遅刻|date_format:H:i',
'absence_date' => 'required|date',
```

## 4. フロントエンド設計

### 4.1 ディレクトリ構成
```
resources/
├── js/
│   ├── app.js
│   ├── components/
│   │   ├── Admin/
│   │   │   ├── StudentList.vue
│   │   │   ├── StudentForm.vue
│   │   │   ├── ParentList.vue
│   │   │   ├── ParentForm.vue
│   │   │   ├── ClassList.vue
│   │   │   ├── ClassForm.vue
│   │   │   └── CsvImport.vue
│   │   ├── Parent/
│   │   │   ├── AbsenceList.vue
│   │   │   └── AbsenceForm.vue
│   │   ├── Auth/
│   │   │   ├── AdminLogin.vue
│   │   │   ├── ParentLogin.vue
│   │   │   ├── ParentEmailRegister.vue
│   │   │   └── TwoFactorVerify.vue
│   │   └── Common/
│   │       ├── Header.vue
│   │       ├── Footer.vue
│   │       ├── Modal.vue
│   │       └── Table.vue
│   ├── layouts/
│   │   ├── AdminLayout.vue
│   │   └── ParentLayout.vue
│   └── router/
│       └── index.js
└── css/
    └── app.css
```

### 4.2 主要コンポーネント設計

#### 4.2.1 StudentList.vue
- 生徒一覧表示（テーブル形式）
- ソート・フィルタリング機能
- 編集・削除ボタン
- 新規登録ボタン

#### 4.2.2 AbsenceForm.vue
- 欠席/遅刻選択（ラジオボタン）
- 日付選択（カレンダー）
- 理由入力（テキストエリア）
- 登校予定時刻（遅刻の場合のみ表示）
- バリデーション表示

#### 4.2.3 CsvImport.vue
- ファイル選択
- プレビュー表示
- インポート実行ボタン
- エラー表示

#### 4.2.4 ParentEmailRegister.vue
- メールアドレス入力フォーム（初回ログイン時のみ表示）
- バリデーション表示（メール形式チェック）
- 登録ボタン
- 説明文（2段階認証コード送信先として使用される旨）

#### 4.2.5 TwoFactorVerify.vue
- 6桁認証コード入力フィールド
- コード送信済みメールアドレスの表示
- 検証ボタン
- 再送信ボタン
- 有効期限カウントダウン表示

### 4.3 状態管理

Pinia（Vue 3の推奨状態管理）を使用:
```
stores/
├── auth.js - 認証状態
├── admin.js - 管理者機能の状態
└── parent.js - 保護者機能の状態
```

### 4.4 レスポンシブブレークポイント
```
sm: 640px   - スマートフォン
md: 768px   - タブレット
lg: 1024px  - デスクトップ
xl: 1280px  - 大画面デスクトップ
```

## 5. CSV インポート設計

### 5.1 CSVフォーマット

#### 5.1.1 生徒データ
```csv
seito_id,seito_name,seito_number,class_id
S001,山田太郎,1,CL001
S002,佐藤花子,2,CL001
```

#### 5.1.2 クラスデータ
```csv
class_id,class_name,teacher_name,teacher_email,year_id
CL001,1年1組,田中先生,tanaka@school.jp,2025
CL002,1年2組,鈴木先生,suzuki@school.jp,2025
```

#### 5.1.3 教員データ（クラス担任として）
```csv
class_id,teacher_name,teacher_email
CL001,田中先生,tanaka@school.jp
CL002,鈴木先生,suzuki@school.jp
```

#### 5.1.4 保護者データ
```csv
seito_id,parent_name,parent_initial_email,parent_initial_password
S001,山田一郎,yamada-init@temp.school.jp,password123
S002,佐藤一郎,sato-init@temp.school.jp,password456
```
**注意事項:**
- CSVファイル内のparent_initial_passwordは平文
- インポート処理でbcrypt暗号化してDBに保存
- parent_emailは初回ログイン時に保護者が登録

### 5.2 インポート処理フロー
```
1. CSVファイルアップロード
   ↓
2. ファイル形式チェック（拡張子、サイズ）
   ↓
3. CSVパース
   ↓
4. データバリデーション
   ↓
5. エラーがあればプレビューで表示
   ↓
6. 問題なければDBトランザクション開始
   ↓
7. 一括挿入/更新
   ↓
8. コミット/ロールバック
```

## 6. セキュリティ設計

### 6.1 認証セキュリティ
- パスワード: bcrypt（cost=12）
- セッション: httponly, secure, samesite=strict
- CSRF トークン: 全POST/PUT/DELETEリクエスト
- 2段階認証コード: 10分間有効、6桁数字、使用後削除

### 6.2 認可設計
- 管理者: 全機能アクセス可
- 保護者: 自分の子供の欠席連絡のみ

### 6.3 入力サンプル検証
- XSS対策: Laravel自動エスケープ
- SQLインジェクション対策: Eloquent ORM使用
- ファイルアップロード: MIME type検証、サイズ制限（2MB）

## 7. メール設計

### 7.1 2段階認証メール
```
件名: 【欠席連絡システム】認証コード

本文:
{name} 様

ログイン認証コードは以下の通りです。

認証コード: {code}

このコードは10分間有効です。
```

### 7.2 パスワードリセットメール
```
件名: 【欠席連絡システム】パスワードリセット

本文:
{name} 様

パスワードリセットのリクエストを受け付けました。
以下のリンクからパスワードを再設定してください。

{reset_url}

このリンクは24時間有効です。
```

## 8. エラーハンドリング設計

### 8.1 HTTPステータスコード
- 200: 成功
- 201: 作成成功
- 400: バリデーションエラー
- 401: 認証エラー
- 403: 権限エラー
- 404: リソース不存在
- 422: バリデーションエラー（詳細付き）
- 500: サーバーエラー

### 8.2 エラーレスポンス形式
```json
{
  "message": "エラーメッセージ",
  "errors": {
    "field_name": ["エラー詳細"]
  }
}
```

## 9. テスト設計

### 9.1 単体テスト
- Model: リレーション、スコープ
- Validation: 各バリデーションルール
- Service: CSVインポート、2段階認証

### 9.2 機能テスト
- 認証フロー
- CRUD操作
- CSVインポート
- 権限チェック

## 10. パフォーマンス最適化

### 10.1 データベース
- Eager Loading（N+1問題回避）
- インデックス活用
- ページネーション（1ページ20件）

### 10.2 フロントエンド
- コンポーネントの遅延読み込み
- 画像最適化
- Viteによるビルド最適化

## 12. バグ修正設計（2026-02-28）: 初回ログインメール登録フロー

### 12.1 問題の根本原因
`auth.js` の `parentLogin` アクションが `requires_email_registration: true` を受け取っても無視し、
`直接ログイン成功` のコードパスを通るため、ストアもコンポーネントも2FA/メール登録画面に遷移しない。

### 12.2 修正対象ファイル（フロントエンドのみ）

| ファイル | 変更種別 | 内容 |
|---|---|---|
| `stores/auth.js` | 修正 | `needsEmailRegistration` 状態追加、`parentLogin` で処理分岐追加 |
| `pages/auth/ParentLogin.vue` | 修正 | `requires_email_registration` 時のルーティング処理追加 |
| `pages/auth/ParentEmailRegister.vue` | 新規作成 | メールアドレス登録画面 |
| `router/index.js` | 修正 | `/parent/register-email` ルート追加 |

### 12.3 ログインフロー設計（修正後）

```
POST /api/parent/login
        │
        ├─ requires_email_registration: true
        │       → /parent/register-email へ遷移
        │               │
        │               └─ POST /api/parent/register-email
        │                       → requires_2fa: true
        │                               → /parent/verify-2fa へ遷移
        │
        ├─ requires_2fa: true
        │       → /parent/verify-2fa へ遷移（2回目以降）
        │
        └─ それ以外（後方互換性）
                → /parent/dashboard へ遷移
```

### 12.4 auth.js ストア変更設計

```js
// state に追加
needsEmailRegistration: false,

// parentLogin アクション 分岐追加
if (response.data.requires_email_registration) {
  this.needsEmailRegistration = true;
  this.guard = 'parent';
  this.loginType = 'parent';
  return response.data;
}
```

### 12.5 ParentEmailRegister.vue 設計

- `parent_email` 入力フォーム（email型）
- POST `/api/parent/register-email`
- 成功後 → `router.push({ name: 'parent.verify2fa', query: { email } })`
- エラー表示（重複メール等）
- バリデーション: 必須・email形式

### 12.6 ルート追加設計

```js
{
  path: '/parent/register-email',
  component: GuestLayout,
  children: [{
    path: '',
    name: 'parent.registerEmail',
    component: ParentEmailRegister,
    meta: { guest: true }
  }]
}
```

## 11. 開発フェーズ計画

### Phase 1: 環境構築（完了）
- Laravel 12プロジェクト作成 ✓

### Phase 2: 基盤構築
- データベース設計・マイグレーション
- 認証システム構築
- 2段階認証実装

### Phase 3: 管理者機能
- 生徒CRUD
- 保護者CRUD
- クラスCRUD
- CSVインポート

### Phase 4: 保護者機能
- 欠席連絡CRUD
- 履歴表示

### Phase 5: フロントエンド
- Vue.js環境構築
- Tailwind CSS設定
- コンポーネント実装

### Phase 6: テスト・調整
- 機能テスト
- UIテスト
- パフォーマンステスト

## 12. 技術的課題と解決策

### 12.1 課題: 保護者の認証フロー管理
**解決策**: 
- parent_initial_email/parent_initial_password: ログイン認証用（parent_initial_passwordはbcrypt暗号化）
- parent_email: 2段階認証コード送信先（必須登録）
- 初回ログイン時にparent_emailが未登録の場合、メール登録画面へ誘導
- 2回目以降も同じ初期認証情報でログイン → 登録済みparent_emailに2段階認証コード送信

### 12.2 課題: 2段階認証のセッション管理
**解決策**:
- 一時セッション（2FA未完了）と本セッション（2FA完了）を分離
- ミドルウェアでチェック

### 12.3 課題: CSVインポートの大量データ処理
**解決策**:
- チャンク処理（1000件ずつ）
- トランザクション管理
- バックグラウンドジョブ（必要に応じて）

## 13. 未決定事項・今後の検討課題

1. メール送信方法（SMTP設定）
2. 本番環境のサーバー構成
3. バックアップ戦略
4. ログ管理方針
5. 複数子供を持つ保護者のUI設計
