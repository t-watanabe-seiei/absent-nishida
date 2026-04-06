# タスクリスト

## 機能 A: 管理者CSVインポートへの class_id / is_super_admin 対応

### A-1 [ ] CsvImportService::importAdmins() を修正
- ファイル: app/Services/CsvImportService.php
- バリデーションに class_id / is_super_admin を追加
- is_super_admin 正規化ロジック追加
- class_id の存在確認 + warnings 追記ロジック追加
- updateOrCreate に class_id / is_super_admin を追加
- 戻り値に warnings[] を追加

### A-2 [ ] CsvImportController::downloadTemplate() のテンプレート更新
- ファイル: app/Http/Controllers/Admin/CsvImportController.php
- admins テンプレートのヘッダーを5列に変更
- サンプル行を2行（担任・スーパー管理者）に変更

### A-3 [ ] CsvImport.vue の列説明 UI 更新
- ファイル: resources/js/pages/admin/CsvImport.vue
- 管理者データカードの ul に class_id / is_super_admin の説明を追加

---

## 機能 B: 欠席記録 CSV ダウンロード

### B-1 [ ] AbsenceController に buildAbsenceQuery() と export() を追加
- ファイル: app/Http/Controllers/Admin/AbsenceController.php
- buildAbsenceQuery() に既存 index() のクエリ部分を切り出し
- index() を buildAbsenceQuery() 使用に変更
- export() メソッドを新規追加（全件取得 + CSV ストリーミング）
- CSVインジェクション対策を含む

### B-2 [ ] routes/api.php にエクスポートルートを追加
- ファイル: routes/api.php
- /absences/export ルートを /absences/{id} より前に追加

### B-3 [ ] absences/List.vue にダウンロードボタンと downloadCsv() を追加
- ファイル: resources/js/pages/admin/absences/List.vue
- データテーブル直上にボタン追加
- downloading ref を追加
- downloadCsv() 関数を追加（axios blob + createObjectURL）

---

## 共通

### C-1 [ ] PHP 構文チェック
- php -l app/Services/CsvImportService.php
- php -l app/Http/Controllers/Admin/AbsenceController.php
- php -l app/Http/Controllers/Admin/CsvImportController.php

### C-2 [ ] npm run build でフロントエンドをビルド

### C-3 [ ] README.md 更新
- 管理者CSVインポートの列説明に class_id / is_super_admin を追記
- 欠席連絡管理のセクションに CSV ダウンロード機能を追記

---

## 機能 C: お知らせ機能（管理者 → 保護者）

### C-DB-1 [ ] announcements マイグレーション作成・実行
- ファイル: database/migrations/2026_..._create_announcements_table.php
- カラム: id, admin_id(FK), title, body, target_class_ids(json), target_parent_ids(json nullable), notify_by_email(bool default false), expires_at(datetime), timestamps

### C-DB-2 [ ] announcement_reads マイグレーション作成・実行
- ファイル: database/migrations/2026_..._create_announcement_reads_table.php
- カラム: id, announcement_id(FK), parent_id(FK), read_at(datetime), timestamps
- unique(announcement_id, parent_id)

### C-DB-3 [ ] announcement_attachments マイグレーション作成・実行
- ファイル: database/migrations/2026_..._create_announcement_attachments_table.php
- カラム: id, announcement_id(FK), original_name, stored_path, timestamps

### C-DB-4 [ ] system_settings マイグレーション + シーダー作成・実行
- ファイル: database/migrations/2026_..._create_system_settings_table.php
- カラム: id, key(string unique), value(string), timestamps
- シーダーで announcement_enabled = '0' を投入

### C-M-1 [ ] Announcement モデル作成
- ファイル: app/Models/Announcement.php
- fillable, casts(array), relations: admin(), reads(), attachments()
- scopeActive(): expires_at > now()

### C-M-2 [ ] AnnouncementRead モデル作成
- ファイル: app/Models/AnnouncementRead.php
- fillable, casts(datetime), relations: announcement(), parent()

### C-M-3 [ ] AnnouncementAttachment モデル作成
- ファイル: app/Models/AnnouncementAttachment.php
- fillable, relations: announcement()

### C-M-4 [ ] SystemSetting モデル作成
- ファイル: app/Models/SystemSetting.php
- fillable, static getValue(), static setValue()

### C-S-1 [ ] AnnouncementNotificationService 作成
- ファイル: app/Services/AnnouncementNotificationService.php
- notifyParents(Announcement $announcement): void
- target_parent_ids で絞り込み、各保護者の parent_email に Mail::raw() で送信

### C-AC-1 [ ] Admin/AnnouncementController 作成
- ファイル: app/Http/Controllers/Admin/AnnouncementController.php
- index(): 一覧（担任はクラスフィルタ、スーパー管理者は全件）+ ページネーション
- store(): バリデーション + 担任クラスチェック + DB保存 + PDF保存（最大5件）+ メール通知
- show(): 詳細取得（担任クラスチェック）
- update(): 編集（権限チェック + 担任クラスチェック）
- destroy(): 削除（権限チェック + storage ファイル削除）
- readStatus(): 既読者一覧
- addAttachment(): 添付ファイル追加（5件上限チェック）
- removeAttachment(): 添付ファイル削除

### C-AC-2 [ ] Admin/SettingController 作成
- ファイル: app/Http/Controllers/Admin/SettingController.php
- index(): スーパー管理者チェック + 設定一覧返却
- update(): スーパー管理者チェック + announcement_enabled 更新

### C-PC-1 [ ] Parent/AnnouncementController 作成
- ファイル: app/Http/Controllers/Parent/AnnouncementController.php
- index(): 機能チェック + 対象フィルタ + 自動既読登録 + 一覧返却
- read(): 明示的既読登録
- downloadAttachment(): 対象チェック + PDF ストリーミング

### C-R-1 [ ] routes/api.php にお知らせ・設定ルート追加
- 管理者グループ: announcements CRUD + reads + attachments
- 保護者グループ: announcements index + read + downloadAttachment

### C-V-1 [ ] admin/announcements/List.vue 作成
- お知らせ一覧テーブル（件名・有効期限・既読数・操作）
- 新規作成ボタン

### C-V-2 [ ] admin/announcements/Form.vue 作成（作成・編集共用）
- 件名・本文・有効期限・対象クラス・個別保護者・メール通知・PDF添付
- 編集時: 既存添付ファイル表示 + 個別削除

### C-V-3 [ ] admin/announcements/Detail.vue 作成
- お知らせ詳細 + 既読/未読一覧

### C-V-4 [ ] admin/Settings.vue 作成
- スーパー管理者のみアクセス可
- お知らせ機能 ON/OFF トグル

### C-V-5 [ ] parent/Dashboard.vue お知らせセクション追加
- mounted() で /api/parent/announcements を呼び出し
- お知らせカード表示（件名・本文・有効期限・PDF）

### C-V-6 [ ] AdminLayout.vue ナビリンク追加
- 「お知らせ」リンク（全管理者）
- 「設定」リンク（スーパー管理者のみ）

### C-V-7 [ ] router/index.js ルート追加
- /admin/announcements, create, :id, :id/edit
- /admin/settings

### C-CHECK-1 [ ] PHP 構文チェック
- php -l 全新規/修正 PHP ファイル

### C-CHECK-2 [ ] npm run build でフロントエンドビルド

### C-DOC-1 [ ] README.md にお知らせ機能・設定機能を追記
