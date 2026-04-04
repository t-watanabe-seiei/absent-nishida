# 要件定義書 - 欠席連絡システム

## 1. プロジェクト概要

### 1.1 目的
学校における欠席・遅刻連絡を保護者がオンラインで行えるシステムを構築する。
管理者（学校側）は生徒・クラス・教員情報を管理し、保護者からの連絡を確認できる。

### 1.2 技術スタック
- **バックエンド**: Laravel 12
- **フロントエンド**: Vue.js
- **データベース**: SQLite
- **スタイリング**: Tailwind CSS
- **認証**: Laravel標準認証 + メール2段階認証

### 1.3 主要ユーザー
- **管理者**: 学校職員（生徒・クラス・教員データの管理）
- **保護者**: 欠席・遅刻連絡を行うユーザー

## 2. 機能要件

### 2.1 認証機能
#### 2.1.1 保護者認証フロー
**初回ログイン:**
1. **初期ログイン**: parent_initial_email + parent_initial_password で認証
2. **メール登録**: 認証成功後、保護者自身のメールアドレス（parent_email）を登録
3. **2段階認証**: 登録されたparent_emailに6桁の認証コードを送信
4. **コード検証**: 6桁コードの入力と検証（有効期限: 10分）
5. **アクセス許可**: 認証成功後、欠席連絡機能へアクセス可能

**2回目以降のログイン:**
1. **初期ログイン**: parent_initial_email + parent_initial_password で認証
2. **2段階認証**: 登録済みのparent_emailに6桁の認証コードを送信
3. **コード検証**: 6桁コードの入力と検証（有効期限: 10分）
4. **アクセス許可**: 認証成功後、欠席連絡機能へアクセス可能

**注意事項:**
- 毎回のログインで2段階認証が必須
- parent_emailが未登録の場合は、初回ログインフローを実行
- 認証コードは使用後、または有効期限切れ後に自動削除

#### 2.1.2 管理者認証
- ログイン機能（メール + パスワード）
- ログアウト機能
- パスワードリセット機能

### 2.2 管理者機能
#### 2.2.1 生徒管理
- 生徒の登録
- 生徒の編集
- 生徒の削除
- 生徒一覧表示

#### 2.2.2 保護者管理
- 保護者の登録
- 保護者の編集
- 保護者の削除
- 保護者一覧表示

#### 2.2.3 クラス管理
- クラスの登録
- クラスの編集
- クラスの削除
- クラス一覧表示

#### 2.2.4 CSVインポート機能
- 生徒データのCSVインポート
- クラスデータのCSVインポート
- 教員データのCSVインポート（クラス担任情報として）
- 保護者データのCSVインポート
  - CSV形式: seito_id, parent_name, parent_initial_email, parent_initial_password (平文)
  - インポート処理でparent_initial_passwordをbcrypt暗号化してDBに保存
  - 必須フィールド: seito_id, parent_name, parent_initial_email, parent_initial_password

### 2.3 保護者機能
#### 2.3.1 認証・ログイン機能
**初回ログイン時:**
1. parent_initial_email と parent_initial_password（暗号化されたもの）で認証
2. 認証成功後、保護者自身のメールアドレス（parent_email）を登録
3. 登録されたparent_emailに6桁の認証コードを送信
4. 6桁コードの入力と検証（有効期限: 10分）
5. 認証成功後、欠席連絡機能へアクセス可能

**2回目以降のログイン時:**
1. parent_initial_email と parent_initial_password で認証
2. 登録済みのparent_emailに6桁の認証コードを送信
3. 6桁コードの入力と検証（有効期限: 10分）
4. 認証成功後、欠席連絡機能へアクセス可能

**重要:** 毎回のログインで2段階認証を実施。認証コードは必ず登録されたparent_emailに送信される。

#### 2.3.2 欠席連絡機能
- 欠席連絡の登録（欠席/遅刻区分、理由、登校予定時刻）
- 欠席連絡の編集
- 欠席連絡の削除
- 欠席連絡履歴の表示

## 3. データベース設計

### 3.1 生徒テーブル (students)
| カラム名 | データ型 | 説明 | 制約 |
|---------|---------|------|------|
| id | INTEGER | 主キー | PRIMARY KEY, AUTO_INCREMENT |
| seito_id | STRING | 生徒ID | UNIQUE, NOT NULL |
| seito_name | STRING | 生徒氏名 | NOT NULL |
| seito_number | INTEGER | 出席番号 | NOT NULL |
| class_id | INTEGER | クラスID（外部キー） | FOREIGN KEY |
| created_at | TIMESTAMP | 作成日時 | |
| updated_at | TIMESTAMP | 更新日時 | |

### 3.2 クラステーブル (classes)
| カラム名 | データ型 | 説明 | 制約 |
|---------|---------|------|------|
| id | INTEGER | 主キー | PRIMARY KEY, AUTO_INCREMENT |
| class_id | STRING | クラスID | UNIQUE, NOT NULL |
| class_name | STRING | クラス名 | NOT NULL |
| teacher_name | STRING | 担任名 | NOT NULL |
| teacher_email | STRING | 担任メール | NOT NULL |
| year_id | INTEGER | 年度 | NOT NULL |
| created_at | TIMESTAMP | 作成日時 | |
| updated_at | TIMESTAMP | 更新日時 | |

### 3.3 保護者テーブル (parents)
| カラム名 | データ型 | 説明 | 制約 |
|---------|---------|------|------|
| id | INTEGER | 主キー | PRIMARY KEY, AUTO_INCREMENT |
| seito_id | STRING | 生徒ID | FOREIGN KEY |
| parent_name | STRING | 保護者氏名 | NOT NULL |
| parent_relationship | STRING | 保護者区分（父・母・その他） | NOT NULL |
| parent_tel | STRING | 保護者電話番号 | |
| parent_initial_email | STRING | 初期メールアドレス（ログイン用） | NOT NULL, UNIQUE |
| parent_initial_password | STRING | 初期パスワード（bcryptハッシュ化、ログイン用） | NOT NULL |
| parent_email | STRING | 保護者登録メール（2段階認証送信先） | NULLABLE, UNIQUE |
| parent_password | STRING | 未使用（将来の拡張用） | NULLABLE |
| created_at | TIMESTAMP | 作成日時 | |
| updated_at | TIMESTAMP | 更新日時 | |

### 3.4 欠席連絡テーブル (absences)
| カラム名 | データ型 | 説明 | 制約 |
|---------|---------|------|------|
| id | INTEGER | 主キー | PRIMARY KEY, AUTO_INCREMENT |
| seito_id | STRING | 生徒ID | FOREIGN KEY |
| division | STRING | 区分（欠席/遅刻） | NOT NULL |
| reason | TEXT | 理由 | NOT NULL |
| scheduled_time | TIME | 登校予定時刻 | NULL（遅刻の場合のみ） |
| absence_date | DATE | 欠席日 | NOT NULL |
| created_at | TIMESTAMP | 作成日時 | |
| updated_at | TIMESTAMP | 更新日時 | |

### 3.5 管理者テーブル (admins)
| カラム名 | データ型 | 説明 | 制約 |
|---------|---------|------|------|
| id | INTEGER | 主キー | PRIMARY KEY, AUTO_INCREMENT |
| name | STRING | 管理者名 | NOT NULL |
| email | STRING | メールアドレス | UNIQUE, NOT NULL |
| password | STRING | パスワード（ハッシュ化） | NOT NULL |
| created_at | TIMESTAMP | 作成日時 | |
| updated_at | TIMESTAMP | 更新日時 | |

## 4. 非機能要件

### 4.1 レスポンシブデザイン
- スマートフォン、タブレット、PC全てに対応
- Tailwind CSSを使用したモバイルファースト設計

### 4.2 セキュリティ
- パスワードはbcryptでハッシュ化
- CSRF保護
- XSS対策
- SQLインジェクション対策
- 2段階認証によるセキュリティ強化

### 4.3 ユーザビリティ
- シンプルで直感的なUI
- わかりやすいエラーメッセージ
- フォームバリデーション

### 4.4 パフォーマンス
- ページロード時間: 2秒以内
- データベースクエリの最適化

## 5. 成功基準

### 5.1 機能面
- [ ] 管理者が生徒・クラス・保護者の登録、編集、削除ができる
- [ ] CSVファイルから生徒・クラス・教員データをインポートできる
- [ ] 保護者がログイン後、欠席・遅刻連絡を登録できる
- [ ] 2段階認証が正常に動作する
- [ ] レスポンシブデザインが全デバイスで正常に表示される

### 5.2 技術面
- [ ] SQLiteデータベースが正常に動作する
- [ ] Vue.jsコンポーネントが正常に動作する
- [ ] Tailwind CSSが適切に適用される
- [ ] Laravel 12の機能が正常に動作する

### 5.3 セキュリティ面
- [ ] 認証機能が正常に動作する
- [ ] パスワードが適切にハッシュ化される
- [ ] 2段階認証が正常に機能する

---

## 6. 追加要件 - 2026年度切り替え機能（2026/04/04追加）

### 6.1 目的
毎年4月の新学年度開始時に、クラスデータ（担任情報）と生徒のクラス配属を更新するための機能を追加する。

### 6.2 現状の課題と設計方針
- `classes.class_id` は `UNIQUE` 制約のため、年度をまたぐ同一IDの共存は不可
- クラスIDフォーマット（`1TOKUSHIN`, `2CHORI` など）は年度をまたいで同じ文字列を使用する
- 年度切り替えは「上書き更新」方式を採用（2025データを2026で上書き）
- 過去年度データは保持しない（`classes.year_id` で現在の年度のみ管理）

### 6.3 年度切り替えの操作順序（2026年度の場合）
1. **クラスデータ更新（CSV）**: 2026年度のクラスCSVをインポート（担任名・担任メール・year_id=2026 を上書き）
2. **生徒クラス更新（CSV）**: 全生徒の `class_id` を新クラスIDに変更（例: 1年→2年への進級）
3. **新入生インポート（CSV）**: 新1年生を既存の生徒インポート機能で追加

### 6.4 新機能要件: 生徒クラス一括更新

#### 6.4.1 機能概要
CSVファイルを使って、複数の生徒の `class_id` を一括で変更する。

#### 6.4.2 CSVフォーマット
| カラム名 | 説明 | 必須 |
|---------|------|------|
| seito_id | 生徒ID | 必須 |
| class_id | 新しいクラスID | 必須 |

#### 6.4.3 処理仕様
- `seito_id` がstudentsテーブルに存在しない場合はその行をスキップ
- `class_id` がclassesテーブルに存在しない場合はその行をスキップ
- 更新成功件数・スキップ件数・エラー件数をレスポンスとして返す
- トランザクション内で処理（部分成功を許容、エラー行のみスキップ）

#### 6.4.4 APIエンドポイント
- `POST /api/admin/import/student-classes`
- 認証: admin.auth + two_factor ミドルウェア
- リクエスト: multipart/form-data, `file` フィールドに CSV

#### 6.4.5 フロントエンド
- 既存の CSVインポートページ（`CsvImport.vue`）に「生徒クラス一括更新」セクションを追加
- 操作フローは他のインポート機能と同じ（ファイル選択→インポート実行→結果表示）

### 6.5 parents テーブルの方針
- 変更なし（seito_idの外部キーはそのまま有効）
- 保護者の連絡先・認証情報は年度をまたいで継続利用

### 6.6 成功基準
- [ ] 2026年度クラスCSVを既存インポート機能でインポートできる
- [ ] 生徒クラス一括更新CSVで全生徒の `class_id` を正しく更新できる
- [ ] 存在しない `seito_id` や `class_id` がCSVにある場合は適切にスキップされる
- [ ] インポート結果（成功/スキップ/エラー件数）がUIに表示される
- [ ] 権限管理が適切に実装される

## 6. 制約事項

### 6.1 技術的制約
- データベースはSQLiteのみ使用
- フロントエンドはVue.jsを使用
- スタイリングはTailwind CSSを使用

### 6.2 運用制約
- 管理者機能は認証済み管理者のみアクセス可能
- 保護者は自分の子供の欠席連絡のみ操作可能

## 7. 既存システムとの関連

- 新規システムのため、既存システムとの連携は不要

## 9. バグ修正・機能追加（2026-02-28）

### 9.1 初回ログイン時のメールアドレス登録フロー修正

#### 問題
保護者が `initial_email` + `parent_initial_password` で初回ログインした際、
`parent_email` が未登録にもかかわらずダッシュボードへ直接遷移してしまう。
バックエンドは `requires_email_registration: true` を返しているが、
フロントエンド（auth.js ストア）がそのレスポンスを処理していない。

#### 必要な対応
1. フロントエンドの auth.js ストアに `needsEmailRegistration` 状態を追加
2. `parentLogin` アクションで `requires_email_registration` レスポンスを処理
3. `ParentEmailRegister.vue` 画面を新規作成
   - メールアドレス入力フォーム
   - `/api/parent/register-email` へPOST
   - 成功後、2FA認証画面へ遷移
4. Vue Router に `/parent/register-email` ルートを追加
5. `ParentLogin.vue` から `requires_email_registration` 時に遷移処理を追加

#### 成功基準
- 初回ログイン時（parent_email未登録）はメール登録画面に遷移する
- メール登録後に2FAコードが送信される
- 2FA認証後にダッシュボードへ遷移する
- 2回目以降のログインは従来通り2FA画面に遷移する

## 10. 機能追加（2026-03-01）

### 10.1 欠席連絡時の担任メール通知

#### 現状
`AbsenceNotificationService::notifyTeacher()` は実装済みで `AbsenceController::store()` から呼ばれているが、
担任を `admins` テーブルから `class_id` で検索しているため、
管理者として登録されていない先生へは送信できない（実質的に動作していない）。
`classes` テーブルには既に `teacher_email` カラムが存在する。

#### 要件
- 保護者が欠席連絡を登録した際、対象クラスの `teacher_email`（classesテーブル）へ通知メールを送信する
- メール内容：生徒名・クラス名・欠席日・区分（欠席/遅刻）・理由・連絡時刻
- メール送信失敗しても欠席登録はエラーにしない（現行動作を維持）

#### 成功基準
- 欠席連絡登録後、`classes.teacher_email` 宛にメールが送信される
- `teacher_email` が空の場合はスキップしてログ出力のみ
- `admins` テーブルへの依存を除去

### 10.2 保護者ダッシュボードでの2FA用メールアドレス再設定

#### 要件
- `/parent/dashboard` に「2FA用メールアドレス変更」カードを追加
- フロー:
  1. 新しいメールアドレスを入力
  2. POST `/api/parent/request-email-change` → 新しいアドレスに確認コードを送信
  3. 6桁の確認コードを入力
  4. POST `/api/parent/confirm-email-change` → コード検証後に `parent_email` を更新
- バリデーション：必須・email形式・他保護者と重複不可

#### 成功基準
- 新しいメールアドレスに確認コードが届く
- コード検証成功後に `parent_email` が更新される
- 以降のログインで新しいアドレスに2FAコードが送信される

### 10.3 管理者CSVインポート：クラスデータ

#### 現状
- バックエンドは `ImportController::importClasses()`・ルート `/api/admin/import/classes` が実装済み
- `CsvImportService::importClasses()` も実装済み
- フロントエンド (`import/Index.vue`) にクラスインポートUIが存在しない
- `CsvImportController::downloadTemplate()` に `classes` テンプレートが未定義

#### 要件
- フロントエンドのCSVインポート画面にクラスデータインポートカードを追加
  - 対応CSVカラム: `class_id`, `class_name`, `teacher_name`, `teacher_email`, `year_id`
  - テンプレートダウンロード機能
  - ファイル選択・インポート実行・結果表示
- `CsvImportController::downloadTemplate()` に `classes` テンプレートを追加

#### 成功基準
- `classes_template.csv` がダウンロードできる
- CSVアップロード後、クラスデータがDBに登録/更新される

---

## 11. 機能修正 - 生徒クラス一括更新に seito_number を追加（2026/04/04）

### 11.1 背景
セクション 6.4 で実装した「生徒クラス一括更新」機能は `class_id` のみを更新対象としていたが、
年度切り替え時には出席番号（`seito_number`）も同時に更新が必要であることが判明した。

### 11.2 変更仕様

#### CSVフォーマット変更
| カラム名 | 説明 | 必須 | 変更 |
|---------|------|------|------|
| seito_id | 生徒ID | 必須 | 変更なし |
| class_id | 新しいクラスID | 必須 | 変更なし |
| seito_number | 新しい出席番号 | 必須 | **新規追加** |

#### 更新対象の変更
- 変更前: `class_id` のみ更新
- 変更後: `class_id` と `seito_number` を同時に更新

#### バリデーション追加
- `seito_number`: required|integer|min:1

#### スキップ条件（変更なし）
- `seito_id` が students テーブルに存在しない → スキップ
- `class_id` が classes テーブルに存在しない → スキップ

### 11.3 成功基準
- [ ] CSVに `seito_number` カラムを含めてインポートできる
- [ ] `class_id` と `seito_number` が同時に更新される
- [ ] `seito_number` が整数でない場合はバリデーションエラーになる
- [ ] フロントエンドのフォーマット説明が更新される
- [ ] README.md の CSVサンプルが更新される

## 8. 今後の拡張可能性

- 出席状況の統計機能
- 担任への通知機能
- 保護者への確認メール送信機能
- 年度切り替え機能
- 複数の子供を持つ保護者への対応
