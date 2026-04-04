# 設計書

## 機能 A: 管理者CSVインポートへの class_id / is_super_admin 対応

### A-1. CsvImportService::importAdmins() の変更

#### 変更箇所
app/Services/CsvImportService.php の importAdmins() メソッド

#### 変更内容

1. バリデーションに class_id（nullable/string）と is_super_admin（nullable）を追加
2. is_super_admin の正規化ロジック:
   - "true" / "1" / "yes" (大文字小文字不問) → boolean true
   - それ以外（空・"false"・"0" 等） → boolean false
3. class_id の処理:
   - 空欄 → null（警告なし）
   - 存在するクラスID → そのままセット
   - 存在しないクラスID → null でセット ＋ warnings[] に追記
4. updateOrCreate の attributes に class_id / is_super_admin を追加
5. 戻り値に warnings[] キーを追加

#### 戻り値変更

: [ success, errors, total ]
: [ success, errors, warnings, total ]

warnings 例:
[
  { "row": 3, "message": "class_id "INVALID" が存在しないため null で登録しました" }
]

### A-2. CsvImportController::downloadTemplate() の変更

admins テンプレートの定義を変更:
: headers => [name, email, password]
: headers => [name, email, password, class_id, is_super_admin]
        sample  => [管理者名, admin@seiei.ac.jp, seiei2026, 1TOKUSHIN, false]
                   （スーパー管理者サンプル行も追加: [スーパー管理者, super@seiei.ac.jp, seiei2026, , true]）
 2行のサンプルを出力する

### A-3. CsvImport.vue の UI 更新

.claude_workflow .editorconfig .env .env.example .gitattributes .github .gitignore 2FA_VERIFICATION_GUIDE.md CLAUDE.md CSV_IMPORT_GUIDE.md PARENT_CSV_FORMAT_CHANGE.md PRESENTATION.md README.md TESTING_REPORT.md app artisan bootstrap code composer.json composer.lock config database expires_at lang node_modules package-lock.json package.json parent_email parent_email, parent_name phpunit.xml public resources routes storage tests vendor vite.config.js  ul リストに以下を追加:
- class_id (担当クラスID - 担任の場合)
- is_super_admin (true/false)

---

## 機能 B: 欠席記録 CSV ダウンロード

### B-1. AbsenceController::export() メソッド追加

#### 処理フロー

1. 認証チェック（既存ミドルウェアで保証済み）
2. フィルタークエリ構築（index() と同一ロジック）
   - get() でページネーションなし全件取得
3. CSV ヘッダー行出力
4. 各レコードを CSV 行に変換してストリーミング出力

#### 出力 CSV 仕様

ls:
,学年,クラス,出席番号,氏名,区分,理由,予定時刻

#
ls :
- 日付: absence_date を YYYY/MM/DD 形式
- 学年: class_name の先頭1文字 + "年" （例: "1情会" → "1年"）
- クラス: student.classModel.class_name
- 出席番号: student.seito_number
- 氏名: student.seito_name
- 区分: division
- 理由: reason（改行・カンマを考慮しダブルクォート囲み）
- 予定時刻: scheduled_time（空の場合は空文字）

#ls
 UTF-8 BOM (ï»¿) を先頭に付与

#### レスポンスヘッダー

Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="absences_YYYY-MM-DD.csv"

#### 実装上の重点

index() との差分は paginate() → get() のみ。
.claude_workflow .editorconfig .env .env.example .gitattributes .github .gitignore 2FA_VERIFICATION_GUIDE.md CLAUDE.md CSV_IMPORT_GUIDE.md PARENT_CSV_FORMAT_CHANGE.md PRESENTATION.md README.md TESTING_REPORT.md app artisan bootstrap code composer.json composer.lock config database expires_at lang node_modules package-lock.json package.json parent_email parent_email, parent_name phpunit.xml public resources routes storage tests vendor vite.config.js  private メソッド buildAbsenceQuery() に切り出して共通化する。

### B-2. ルート追加 (routes/api.php)

--host=0.0.0.0 absences ルート群に追加:
Route::get("/absences/export", [AdminAbsenceController::class, "export"]);

 /absences/{id} の前に定義すること（Laravelのルート優先順位のため）

### B-3. absences/List.vue の UI 変更

#### ダウンロードボタン追加位置

#Ls
ls:

<div class="flex justify-end mb-2">
  <Button variant="secondary" @click="downloadCsv" :disabled="downloading">
    {{ downloading ? "ダウンロード中..." : "CSVダウンロード" }}
  </Button>
</div>

#### downloadCsv() 関数

1. downloading = true
2. 現在の filters（date_from, date_to, class_name, division, grade）と showAllClasses をパラメータに
3. axios.get("/api/admin/absences/export", { params, responseType: "blob" }) で取得
4. URL.createObjectURL(blob) で一時URLを作成
5. <a> 要素を動的生成して .click() → 自動ダウンロード
6. URL.revokeObjectURL() で解放
7. downloading = false（finally）

---

## 変更ファイル一覧と変更規模

| ファイル | 変更種別 | 変更規模 |
|---------|---------|---------|
| app/Services/CsvImportService.php | 修正 | importAdmins() 約30行追記・変更 |
| app/Http/Controllers/Admin/CsvImportController.php | 修正 | テンプレート定義3行変更 |
| app/Http/Controllers/Admin/AbsenceController.php | 修正 | export()追加 約50行、buildAbsenceQuery()切り出し |
| routes/api.php | 修正 | 1行追加 |
| resources/js/pages/admin/CsvImport.vue | 修正 | 列説明2行追加 |
| resources/js/pages/admin/absences/List.vue | 修正 | ボタン追加 + downloadCsv()追加 約25行 |
| README.md | 修正 | CSVインポート仕様・エクスポート機能の説明追記 |

---

## 設計上の判断・注意点

1. buildAbsenceQuery() の切り出し
   index() と export() でフィルター条件が完全に同一であるため、
   private メソッドに切り出してDRYにする。
   これにより将来のフィルター追加時の修正箇所が1箇所になる。

2. メモリ効率
   全件取得のため大量データでメモリを消費しうるが、
   学校の欠席データは月数百件程度が上限と想定。
   streamDownload() を使うことでメモリ使用を最小化する。

3. CSVインジェクション対策
   セルの先頭が = / + / - / @ で始まる場合はタブ文字を先頭に付加する。
   理由フィールドはユーザー入力のため特に注意が必要。

4. ルート定義順序
   /absences/export は /absences/{id} より前に定義しないと
   "export" が {id} として解釈される。必ず先に定義する。
