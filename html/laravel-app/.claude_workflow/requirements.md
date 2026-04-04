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
