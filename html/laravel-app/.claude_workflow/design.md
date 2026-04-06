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

---

## 機能 C: お知らせ機能（管理者 → 保護者）

### C-0. 設計方針

- 既存の `AbsenceNotificationService` と同じパターンで `AnnouncementNotificationService` を作成
- ファイルアップロードは `storage/app/announcements/{announcement_id}/` に保存（公開URL不要）
- 保護者向けダウンロードは Controller 経由で認証チェック済みんストリーミング
- JSON カラム（target_class_ids, target_parent_ids）は Eloquent の cast で自動変換
- 設定値は `SystemSetting::getValue('announcement_enabled', '0')` ヘルパーで取得

---

### C-1. DBマイグレーション

#### announcements テーブル

```php
Schema::create('announcements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
    $table->string('title');
    $table->text('body');
    $table->json('target_class_ids');          // ["1TOKUSHIN","2JOHO"]
    $table->json('target_parent_ids')->nullable(); // null = クラス全員, 配列 = 個別指定
    $table->boolean('notify_by_email')->default(false);
    $table->datetime('expires_at');
    $table->timestamps();
});
```

#### announcement_reads テーブル

```php
Schema::create('announcement_reads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
    $table->datetime('read_at');
    $table->timestamps();
    $table->unique(['announcement_id', 'parent_id']); // 重複防止
});
```

#### announcement_attachments テーブル

```php
Schema::create('announcement_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
    $table->string('original_name');    // 元のファイル名（表示用）
    $table->string('stored_path');      // storage/app/ 以下の相対パス
    $table->timestamps();
});
```

#### system_settings テーブル

```php
Schema::create('system_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('value');
    $table->timestamps();
});
// シーダーで announcement_enabled = '0' を投入
```

---

### C-2. Eloquent モデル

#### Announcement.php

```php
protected $fillable = ['admin_id','title','body','target_class_ids','target_parent_ids','notify_by_email','expires_at'];
protected function casts(): array {
    return [
        'target_class_ids'  => 'array',
        'target_parent_ids' => 'array',
        'notify_by_email'   => 'boolean',
        'expires_at'        => 'datetime',
    ];
}
// relations: admin(), reads() hasMany, attachments() hasMany
// scope: scopeActive() — expires_at > now
```

#### AnnouncementRead.php

```php
protected $fillable = ['announcement_id','parent_id','read_at'];
protected function casts(): array { return ['read_at' => 'datetime']; }
// relations: announcement(), parent() belongsTo(ParentModel)
```

#### AnnouncementAttachment.php

```php
protected $fillable = ['announcement_id','original_name','stored_path'];
// relations: announcement()
```

#### SystemSetting.php

```php
protected $fillable = ['key','value'];
// static メソッド getValue(string $key, string $default = ''): string
// static メソッド setValue(string $key, string $value): void
```

---

### C-3. Services/AnnouncementNotificationService.php

```
notifyParents(Announcement $announcement): void
  1. target_parent_ids が null → 対象クラスの全保護者を取得
     target_parent_ids が配列 → その ID の保護者のみ取得
  2. 各保護者の parent_email に対してメール送信（Mail::raw）
  3. 送信失敗はログ出力のみ（全体はロールバックしない）
```

---

### C-4. Admin/AnnouncementController.php

#### index()
- スーパー管理者: 全お知らせ
- 担任: target_class_ids に自分の class_id が含まれるお知らせ
  - JSON_EXTRACT / whereJsonContains() を使用
- with(['admin','attachments', reads_count]) でページネーション（20件）
- レスポンスに reads_count（既読数）と total_targets_count（送信先総数）を含める

#### store()
- バリデーション: title/body/expires_at/target_class_ids 必須
- 担任は target_class_ids が自分の class_id のみであることをチェック
- DB::transaction() で announcements レコード作成
- ファイルアップロードあれば announcement_attachments に保存
  - storage/app/announcements/{id}/ のディレクトリに配置
  - 上限: 5ファイル / 1ファイル最大10MB / PDFのみ
- notify_by_email = true なら AnnouncementNotificationService->notifyParents() を呼び出し

#### show()
- 指定IDのお知らせを返す（with: admin, attachments, reads → parent情報）
- 全管理者がアクセス可能（ただし担任は自分のクラス宛のみ）

#### update()
- 権限チェック: creator or super admin
- バリデーション: store() と同様
- 担任は target_class_ids に自分の class_id 以外を含めることをチェック

#### destroy()
- 権限チェック: creator or super admin
- storage に保存した添付ファイルを削除してから DB レコードを削除
- Storage::deleteDirectory("announcements/{id}") で一括削除

#### readStatus()
- announcement.reads に含まれる parent 情報を返す
- 誰が既読/未読かの一覧

#### addAttachment()
- 既存の添付ファイル数を確認（5件超えたらエラー）
- ファイルを storage/app/announcements/{id}/ に保存して DB 追記

#### removeAttachment()
- ファイルを storage から削除して DB レコードを削除

---

### C-5. Admin/SettingController.php

#### index()
- スーパー管理者チェック（スーパー管理者以外は403）
- SystemSetting::all() を key => value の連想配列で返す

#### update()
- スーパー管理者チェック（スーパー管理者以外は403）
- バリデーション: announcement_enabled は "0" or "1"
- SystemSetting::setValue('announcement_enabled', $value) で保存

---

### C-6. Parent/AnnouncementController.php

#### index()
- announcement_enabled が "0" の場合は空配列を返す
- 保護者の seito_id → 生徒の class_id を取得
- 有効期限内のお知らせから、以下いずれかに合致するものを取得:
  a) target_class_ids に class_id が含まれる AND target_parent_ids が null
  b) target_parent_ids に parent.id が含まれる
- with('attachments') で返す（本文も含む）
- **自動既読**: 一覧取得時に未読のものを一括で announcement_reads に INSERT

#### read()
- 既読登録（重複は updateOrCreate で処理）

#### downloadAttachment()
- セキュリティ: 保護者が対象かどうかを確認してからファイルを返す
- Storage::get("announcements/{announcementId}/{attachmentPath}") でストリーミング

---

### C-7. routes/api.php 変更

Admin グループ内に追加:
```php
// お知らせ管理
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::post('/announcements', [AnnouncementController::class, 'store']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);
Route::get('/announcements/{id}/reads', [AnnouncementController::class, 'readStatus']);
Route::post('/announcements/{id}/attachments', [AnnouncementController::class, 'addAttachment']);
Route::delete('/announcements/{id}/attachments/{attachId}', [AnnouncementController::class, 'removeAttachment']);
// 設定
Route::get('/settings', [SettingController::class, 'index']);
Route::put('/settings', [SettingController::class, 'update']);
```

Parent グループ内に追加:
```php
Route::get('/announcements', [ParentAnnouncementController::class, 'index']);
Route::post('/announcements/{id}/read', [ParentAnnouncementController::class, 'read']);
Route::get('/announcements/{id}/attachments/{attachId}', [ParentAnnouncementController::class, 'downloadAttachment']);
```

---

### C-8. Vue フロントエンド

#### admin/announcements/List.vue
- お知らせ一覧テーブル: 件名・有効期限・送信先・既読数/総数・操作ボタン
- 「新規作成」ボタン → /admin/announcements/create
- 各行から「詳細・既読確認」→ /admin/announcements/{id}
- 「編集」リンク → /admin/announcements/{id}/edit
- 「削除」ボタン (creator or super admin)

#### admin/announcements/Form.vue
- create/edit 共用（`$route.params.id` の有無で判定）
- 件名（text）/ 本文（textarea）/ 有効期限（date picker）
- 対象クラス: チェックボックス（担任は自分のクラスのみ 1チェック固定・無効化）
- 個別保護者指定: クラス選択後に動的に保護者リストを取得して表示（任意）
- メール通知: チェックボックス（デフォルト OFF）
- PDF添付: ファイル選択（最大5件、10MB制限、PDF のみ）
  - 編集時は既存添付ファイルのリスト＋個別削除ボタンを表示

#### admin/announcements/Detail.vue
- お知らせ詳細表示（件名・本文・有効期限・添付ファイル）
- 既読者リスト（parent_name, read_at）/ 未読者リスト
- 送信先総数と既読数を表示

#### admin/Settings.vue
- スーパー管理者のみアクセス可（ガード: isSuperAdmin チェック）
- お知らせ機能: ON/OFF トグルスイッチ
- 保存ボタン → PUT /api/admin/settings

#### parent/Dashboard.vue に追加
- お知らせフェッチ: mounted() で GET /api/parent/announcements
- お知らせセクション: 機能OFFまたは0件の場合はセクションごと非表示
- 各お知らせのカード表示: 件名・本文・有効期限・添付PDFリンク
- 取得後に未読の既読登録は API 側で自動処理（フロントでの追加リクエスト不要）

---

### C-9. AdminLayout.vue 変更

- 「お知らせ」ナビリンク: 全管理者に表示 → /admin/announcements
- 「設定」ナビリンク: `v-if="isSuperAdmin"` で表示 → /admin/settings

---

### C-10. 設計上の判断・注意点

1. **自動既読の実装**
   保護者がダッシュボードを開いた際、一覧取得 API の中で INSERT OR IGNORE 相当の処理  
   （`firstOrCreate`）で既読登録する。フロントからの追加リクエスト不要。

2. **target_parent_ids のセマンティクス**
   `null` = 対象クラス全員, `[]`（空配列）は「誰にも届かない」状態になるため、  
   フロントでは「クラスの全員」チェックボックスで null ↔ 配列を切り替える。

3. **担任の閲覧範囲**
   一覧: `whereJsonContains('target_class_ids', $admin->class_id)` でフィルタ  
   詳細取得時も同様チェックを行い、403 を返す。

4. **ファイル保存パス**
   `announcements/{announcement_id}/{Str::uuid()}.pdf`  
   announcement_id サブディレクトリで分けることで、削除時に `deleteDirectory` 1回で完結。

5. **メール通知の非同期化**
   現時点では同期送信（AbsenceNotificationService と同様）。  
   保護者数が多い場合はキューへの移行を検討するが、現フェーズでは同期で実装する。

6. **system_settings の初期値**
   マイグレーション時に `announcement_enabled = '0'` をシーダーで投入する。  
   フロントは GET /api/admin/settings でこの値を参照してトグルの初期位置を決定する。
