# 要件定義

## 目的

--host=0.0.0.0--------連絡システムに以下2機能を追加する。

---

## 機能 A：管理者CSVインポートへの class_id / is_super_admin 対応

### 現状

/admin/import の「管理者データ」インポートは以下の列のみ対応:
name, email, password
- class_id（担当クラス）と is_super_admin（権限フラグ）がインポートされない
- テンプレートCSVのヘッダーも3列しかない

### 要件

1. CSVに class_id・is_super_admin 列を追加して取り込む
2. 動作仕様：
   - is_super_admin が true / 1 / yes → スーパー管理者として登録
   - is_super_admin が空 / false / 0 → 担任として登録（デフォルト false）
   - class_id が存在するクラスID → 担当クラスとして設定
   - class_id が存在しないクラスID → 警告を出すが登録は続行（class_id = null で登録）
   - class_id が空 → null で登録（スーパー管理者の場合は正常）
3. メールアドレスが一致する管理者が既存の場合は上書き更新（既存挙動と同じ）
4. テンプレートCSV（admins_template.csv）のヘッダー・サンプル行も更新
5. フロントエンドの CsvImport.vue の「管理者データ」列説明も更新

### 成功基準

- class_id と is_super_admin がDBに正しく保存される
- 存在しない class_id は null で保存され warnings がレスポンスに含まれる
- テンプレートCSVに5列（name, email, password, class_id, is_super_admin）記載される

---

## 機能 B：欠席記録 CSV ダウンロード

### 現状

/admin/absences（欠席記録画面）は検索フィルター＋テーブル表示のみ。
CSVエクスポート機能はない。

### 要件

1. 欠席一覧テーブルの上部に「CSVダウンロード」ボタンを追加
2. 現在の検索フィルター条件（日付範囲・クラス・区分・学年）に合う全件を対象
   - ページネーションに関係なくすべての一致レコードを取得
3. ダウンロードCSVの列（順序固定）:
   日付, 学年, クラス, 出席番号, 氏名, 区分, 理由, 予定時刻
4. ファイル名：absences_YYYY-MM-DD.csv（ダウンロード実行日）
5. エンコーディング：UTF-8 with BOM（Excel で文字化けなし）
6. 権限スコープ：
   - スーパー管理者 → フィルター条件に合う全クラスのデータ
   - 担任 → 自分の担当クラスのデータのみ（show_all_classes も考慮）
7. 実装方式：バックエンドで CSV 生成し、ブラウザダウンロード（axios + Blob）

### 新規 API エンドポイント

GET /api/admin/absences/export
- 認証：既存の admin.auth + two_factor ミドルウェア
- クエリパラメータ：既存 /api/admin/absences と同じ（date_from, date_to, class_name, division, grade, show_all_classes）
- レスポンス：Content-Type: text/csv; charset=UTF-8 で CSV をストリーミング

### 成功基準

- CSVダウンロードボタンで現在のフィルター条件の全件CSVがダウンロードされる
- UTF-8 BOM 付きのため Excel で日本語が文字化けしない
- 担任が使用した場合、自分のクラス以外のデータが含まれない

---

## 対象ファイル概要（変更予定）

| ファイル | 機能 |
|---------|------|
| app/Services/CsvImportService.php | A: importAdmins() 修正 |
| app/Http/Controllers/Admin/CsvImportController.php | A: テンプレートヘッダー更新 |
| app/Http/Controllers/Admin/AbsenceController.php | B: export() メソッド追加 |
| routes/api.php | B: エクスポートルート追加 |
| resources/js/pages/admin/CsvImport.vue | A: 列説明 UI 更新 |
| resources/js/pages/admin/absences/List.vue | B: ダウンロードボタン追加 |
| README.md | A/B: 機能説明追記 |

---

## 機能 C：お知らせ機能（管理者 → 保護者）

### 背景・目的

README.md README.md updated
Ls 
"Readme.md updated"

### 要件

#### C-1. お知らせの作成・送信
1. 管理者がお知らせを作成し、対象保護者へ送信できる
2. お知らせは以下の項目を含む:
   - 件名（title）: 必須
   - 本文（body）: 必須
   - 有効期限（expires_at）: **必須**（期限切れは保護者ダッシュボードに非表示）
   - メール通知（notify_by_email）: 作成時に「保護者へメールでも通知するか」を選択できる
   - 対象クラス（target_class_ids）: JSON配列形式で保存
   - 対象保護者（target_parent_ids）: 特定保護者のみ指定する場合（NULL = クラス全員）
3. PDFファイルを複数添付可能（1ファイル最大10MB、PDFのみ、**最大5ファイル**）
4. チャンネル:
   - **スーパー管理者**: 全クラスから任意に選択 + クラス内の特定保護者も選択可
   - **担任**: 自分の担当 class_id のクラスに限定 + クラス内の特定保護者も選択可
5. 編集機能あり（作成後に件名・本文・対象クラス・有効期限などを変更可能）
   - 編集できるのは作成者またはスーパー管理者のみ

#### C-2. 保護者側でのお知らせ表示
1. 保護者ダッシュボードにお知らせセクションを追加
2. 表示条件:
   - お知らせ機能がON（system_settings テーブルで管理）
   - `expires_at` が現在日時より未来
   - 保護者の `seito_id` に紐づく生徒の `class_id` が `target_class_ids` に含まれる
     OR `target_parent_ids` に自分の id が含まれる
3. 表示後、既読テーブルに記録（自動既読）
4. PDF添付がある場合はダウンロードリンクを表示

#### C-3. 既読管理
1. 保護者がお知らせを表示した時点で既読登録（POST /api/parent/announcements/{id}/read）
2. 管理者はお知らせ一覧で、各お知らせの既読数/総送信先数を確認可能
3. お知らせ詳細ページで、誰が既読かの一覧を参照可能（管理者のみ）

#### C-4. 機能のON/OFF管理
1. スーパー管理者が設定画面（/admin/settings）でお知らせ機能をON/OFF切替
2. デフォルト: **OFF**
3. OFFにした場合:
   - 保護者ダッシュボードへのお知らせ表示を停止（既存お知らせも非表示）
   - 管理者はお知らせの作成・閲覧は引き続き可能
4. 設定は `system_settings` テーブルの `announcement_enabled` キーで管理

### 新規テーブル設計

#### announcements テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| admin_id | integer | 作成者の管理者ID（FK: admins.id） |
| title | string | 件名 |
| body | text | 本文 |
| target_class_ids | JSON | 対象クラスID配列（例: ["1TOKUSHIN","2JOHO"]） |
| target_parent_ids | JSON nullable | 個別指定保護者ID配列（NULL=クラス全員） |
| notify_by_email | boolean | 作成時にメール通知するか（デフォルト false） |
| expires_at | datetime | 有効期限（必須） |
| created_at / updated_at | timestamp | |

#### announcement_reads テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| announcement_id | integer | FK: announcements.id |
| parent_id | integer | FK: parents.id |
| read_at | datetime | 既読日時 |
| created_at / updated_at | timestamp | |

#### announcement_attachments テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| announcement_id | integer | FK: announcements.id |
| original_name | string | 元のファイル名 |
| stored_path | string | storage 内の保存パス |
| created_at / updated_at | timestamp | |

#### system_settings テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | integer | PK |
| key | string | 設定キー（例: announcement_enabled） |
| value | string | 設定値（例: "0" or "1"） |
| created_at / updated_at | timestamp | |

### 新規 API エンドポイント

#### 管理者側

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `/api/admin/announcements` | お知らせ一覧（担任は担当クラス宛の全件・スーパー管理者は全件） |
| POST | `/api/admin/announcements` | お知らせ作成（multipart/form-data でPDF同時アップロード可） |
| GET | `/api/admin/announcements/{id}` | お知らせ詳細 |
| PUT | `/api/admin/announcements/{id}` | お知らせ編集（作成者 or スーパー管理者のみ） |
| DELETE | `/api/admin/announcements/{id}` | お知らせ削除（作成者 or スーパー管理者のみ） |
| GET | `/api/admin/announcements/{id}/reads` | 既読状況一覧 |
| POST | `/api/admin/announcements/{id}/attachments` | 添付ファイル追加（multipart） |
| DELETE | `/api/admin/announcements/{id}/attachments/{attachId}` | 添付ファイル削除 |
| GET | `/api/admin/settings` | システム設定取得 |
| PUT | `/api/admin/settings` | システム設定更新（スーパー管理者のみ） |

#### 保護者側

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `/api/parent/announcements` | ダッシュボード用お知らせ一覧（有効期限内・機能ON時のみ） |
| POST | `/api/parent/announcements/{id}/read` | 既読登録 |
| GET | `/api/parent/announcements/{id}/attachments/{attachId}` | 添付PDFダウンロード |

### 成功基準
- 担任がお知らせを作成すると、自分の担当クラスの保護者のダッシュボードに表示される
- スーパー管理者は任意のクラス・保護者を指定してお知らせを送信できる
- 有効期限切れのお知らせは保護者ダッシュボードに表示されない
- 機能OFFの場合、保護者ダッシュボードにお知らせセクション自体が非表示になる
- 管理者は各お知らせの既読者数を確認できる
- PDFを最大5ファイル添付でき、保護者がダウンロードできる
- 担任が別クラスの保護者を対象にしようとしても弾かれる（権限チェック）
- お知らせ作成時に「メール通知する」を選択すると、対象保護者のメールに通知が届く
- 作成者またはスーパー管理者がお知らせを編集できる
- 担任のお知らせ一覧では、担当クラス宛の全お知らせが表示される（作成者不問）

### 対象ファイル概要（変更・新規予定）

| ファイル | 機能 | 種別 |
|---------|------|------|
| database/migrations/XXXX_create_announcements_table.php | C: テーブル作成 | 新規 |
| database/migrations/XXXX_create_announcement_reads_table.php | C: テーブル作成 | 新規 |
| database/migrations/XXXX_create_announcement_attachments_table.php | C: テーブル作成 | 新規 |
| database/migrations/XXXX_create_system_settings_table.php | C: テーブル作成 | 新規 |
| app/Models/Announcement.php | C: モデル | 新規 |
| app/Models/AnnouncementRead.php | C: モデル | 新規 |
| app/Models/AnnouncementAttachment.php | C: モデル | 新規 |
| app/Models/SystemSetting.php | C: モデル | 新規 |
| app/Http/Controllers/Admin/AnnouncementController.php | C: コントローラ（CRUD + 既読確認） | 新規 |
| app/Http/Controllers/Admin/SettingController.php | C: コントローラ | 新規 |
| app/Http/Controllers/Parent/AnnouncementController.php | C: コントローラ | 新規 |
| routes/api.php | C: ルート追加 | 修正 |
| resources/js/pages/admin/announcements/List.vue | C: お知らせ一覧 | 新規 |
| resources/js/pages/admin/announcements/Form.vue | C: お知らせ作成・編集 | 新規 |
| resources/js/pages/admin/announcements/Detail.vue | C: 既読確認 | 新規 |
| resources/js/pages/admin/Settings.vue | C: システム設定 | 新規 |
| resources/js/pages/parent/Dashboard.vue | C: お知らせ表示追加 | 修正 |
| resources/js/router/index.js | C: ルート追加 | 修正 |
| README.md | C: 機能説明追記 | 修正 |
