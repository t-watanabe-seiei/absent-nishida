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
