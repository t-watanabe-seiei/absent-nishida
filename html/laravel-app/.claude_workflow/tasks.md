# タスクリスト - 欠席連絡システム

## 既存フェーズ（完了済み）
Phase 2〜5（基盤構築・管理者機能・保護者機能・フロントエンド）は実装完了。
以降は 2026/04/04 追加タスクのみ記載する。

---

## 🟠 Phase 7: README.md 整備 ＋ 年度切り替え機能実装

### タスク一覧

| ID | タスク名 | 状態 | 依存 |
|----|---------|------|------|
| T7-1 | README.md を作成（システム全体の説明） | [✓] | なし |
| T7-2 | CsvImportService に importStudentClasses() 追加 | [✓] | なし |
| T7-3 | CsvImportController に importStudentClasses() 追加 | [✓] | T7-2 |
| T7-4 | routes/api.php にルート追加 | [✓] | T7-3 |
| T7-5 | CsvImport.vue に「生徒クラス一括更新」セクション追加 | [✓] | T7-4 |
| T7-6 | 動作確認（php -l + tinker + 手動テスト） | [✓] | T7-5 |

---

### タスク T7-1: README.md 作成
- **優先度**: 高
- **対象ファイル**: `README.md`（白紙から全文書き起こし）
- **内容**:
  - [ ] プロジェクト概要・技術スタック
  - [ ] セットアップ手順（clone → composer install → .env設定 → migrate → npm run build）
  - [ ] ユーザー種別と認証フロー（管理者・保護者）
  - [ ] 管理者機能一覧（生徒/クラス/保護者 CRUD、CSVインポート各種）
  - [ ] 保護者機能一覧（欠席/遅刻連絡 CRUD、2FA、メール再設定）
  - [ ] CSVインポート操作手順（各フォーマット一覧）
  - [ ] 年度切り替え手順（STEP 1〜3）
  - [ ] ディレクトリ構成の概要
- **完了条件**: README.md が作成され、内容が正確である

---

### タスク T7-2: CsvImportService に importStudentClasses() 追加
- **優先度**: 最高
- **対象ファイル**: `app/Services/CsvImportService.php`
- **内容**:
  - [ ] `importStudentClasses(array $data): array` メソッドを追加
  - [ ] バリデーション: `seito_id` required|string, `class_id` required|string
  - [ ] `students` テーブルに `seito_id` が存在するか確認（なければ skipped）
  - [ ] `classes` テーブルに `class_id` が存在するか確認（なければ skipped）
  - [ ] `Student::where('seito_id', ...)->update(['class_id' => ...])` で更新
  - [ ] 戻り値: `['success', 'skipped', 'errors', 'total']`
  - [ ] `DB::beginTransaction()` で全体を囲み、例外時は rollback
  - [ ] `php -l app/Services/CsvImportService.php` で構文確認
- **完了条件**: 構文エラーなし、メソッドが正しく定義されている

---

### タスク T7-3: CsvImportController に importStudentClasses() 追加
- **優先度**: 最高
- **依存**: T7-2
- **対象ファイル**: `app/Http/Controllers/Admin/CsvImportController.php`
- **内容**:
  - [ ] `importStudentClasses(Request $request): JsonResponse` メソッドを追加
  - [ ] `$request->validate(['file' => 'required|file|mimes:csv,txt|max:2048'])`
  - [ ] `validateCSVFile()` → `parseCSV()` → `importStudentClasses()` の既存パターンに揃える
  - [ ] レスポンス: `message`（成功/スキップ件数入り）+ `result`
  - [ ] `php -l app/Http/Controllers/Admin/CsvImportController.php` で構文確認
- **完了条件**: 構文エラーなし、メソッドが正しく定義されている

---

### タスク T7-4: routes/api.php にルート追加
- **優先度**: 高
- **依存**: T7-3
- **対象ファイル**: `routes/api.php`
- **内容**:
  - [ ] admin 認証グループ（`admin.auth` + `two_factor` ミドルウェア）の CSVインポートセクションに追加
  - [ ] `Route::post('/import/student-classes', [CsvImportController::class, 'importStudentClasses']);`
  - [ ] `php -l routes/api.php` で構文確認
- **完了条件**: 構文エラーなし、ルートが正しい場所に追加されている

---

### タスク T7-5: CsvImport.vue に「生徒クラス一括更新」セクション追加
- **優先度**: 高
- **依存**: T7-4
- **対象ファイル**: `resources/js/pages/admin/CsvImport.vue`
- **内容**:
  - [ ] `files` / `uploading` / `results` の reactive オブジェクトに `'student-classes'` キー追加
  - [ ] 既存の第2行グリッドに「生徒クラス一括更新」カード追加（lg:grid-cols-3 に変更、または第3行として独立）
  - [ ] カード内容: フォーマット説明（seito_id, class_id）・注意書き・ファイル選択・インポートボタン・結果表示
  - [ ] 結果表示は成功件数とスキップ件数の両方を表示
  - [ ] CSV形式サンプルセクションに「生徒クラス更新CSV」サンプルを追加
- **完了条件**: UIに新カードが表示され、ファイル選択・インポートボタンが機能する

---

### タスク T7-6: 動作確認
- **優先度**: 高
- **依存**: T7-5
- **内容**:
  - [ ] `php artisan route:list | grep student-classes` でルート登録確認
  - [ ] tinker で `CsvImportService::importStudentClasses()` の戻り値確認
  - [ ] 正常系: 有効な seito_id + class_id CSV → 全件更新成功
  - [ ] 異常系1: 存在しない seito_id → skipped に計上
  - [ ] 異常系2: 存在しない class_id → skipped に計上
  - [ ] 異常系3: 必須項目欠落 → errors に計上
- **完了条件**: 全ケースで正しい動作を確認

---

## 🔵 Phase 2: 基盤構築

### タスク2-1: データベース環境設定 [✓]
- **優先度**: 最高
- **見積**: 30分
- **内容**:
  - [✓] .envファイルのDB設定をSQLiteに変更
  - [✓] database/database.sqliteファイル作成
  - [✓] config/database.phpの確認
- **完了条件**: SQLite接続が正常に動作する

### タスク2-2: マイグレーションファイル作成 [✓]
- **優先度**: 最高
- **見積**: 1時間
- **依存**: タスク2-1
- **内容**:
  - [✓] create_admins_tableマイグレーション作成
  - [✓] create_classes_tableマイグレーション作成
  - [✓] create_students_tableマイグレーション作成
  - [✓] create_parents_tableマイグレーション作成
  - [✓] create_absences_tableマイグレーション作成
  - [✓] create_two_factor_codes_tableマイグレーション作成
- **完了条件**: 全マイグレーションファイルが作成され、php -l で構文エラーがない

### タスク2-3: マイグレーション実行とテーブル確認 [✓]
- **優先度**: 最高
- **見積**: 15分
- **依存**: タスク2-2
- **内容**:
  - [✓] php artisan migrate実行
  - [✓] テーブル作成確認
  - [✓] 外部キー制約の確認
- **完了条件**: 全テーブルが正常に作成される

### タスク2-4: Modelクラス作成 [✓]
- **優先度**: 高
- **見積**: 45分
- **依存**: タスク2-3
- **内容**:
  - [✓] Adminモデル作成（認証用Authenticatableトレイト含む）
  - [✓] ClassModelモデル作成
  - [✓] Studentモデル作成
  - [✓] ParentModelモデル作成（認証用Authenticatableトレイト含む）
  - [✓] Absenceモデル作成
  - [✓] TwoFactorCodeモデル作成
  - [✓] 各モデルにリレーション定義
- **完了条件**: 全モデルが作成され、php -l で構文エラーがない

### タスク2-5: 認証ガード設定 [✓]
- **優先度**: 高
- **見積**: 30分
- **依存**: タスク2-4
- **内容**:
  - [✓] config/auth.phpにadminガード追加
  - [✓] config/auth.phpにparentガード追加
  - [✓] プロバイダー設定
- **完了条件**: ガード設定が正しく反映される

### タスク2-6: ミドルウェア作成 [✓]
- **優先度**: 高
- **見積**: 45分
- **依存**: タスク2-5
- **内容**:
  - [✓] AdminAuthミドルウェア作成
  - [✓] ParentAuthミドルウェア作成
  - [✓] TwoFactorVerifiedミドルウェア作成
  - [✓] app/Http/Kernel.phpに登録
- **完了条件**: 全ミドルウェアが作成され、php -l で構文エラーがない

### タスク2-7: 2段階認証サービス作成 [✓]
- **優先度**: 中
- **見積**: 1時間
- **依存**: タスク2-4
- **内容**:
  - [✓] TwoFactorServiceクラス作成
  - [✓] コード生成メソッド実装
  - [✓] コード検証メソッド実装
  - [✓] コード削除メソッド実装
- **完了条件**: サービスが作成され、php -l で構文エラーがない

### タスク2-8: メール設定 [✓]
- **優先度**: 中
- **見積**: 30分
- **内容**:
  - [✓] .envにメール設定追加（開発環境はlog）
  - [✓] config/mail.phpの確認
  - [✓] 2段階認証メールテンプレート作成
- **完了条件**: メール送信設定が完了する

### タスク2-9: parentsテーブルマイグレーション修正
- **優先度**: 最高
- **見積**: 30分
- **依存**: タスク2-3
- **内容**:
  - [ ] parent_initial_emailをNOT NULL, UNIQUEに変更
  - [ ] parent_initial_passwordをNOT NULLに変更（bcrypt暗号化前提）
  - [ ] parent_emailをNULLABLE, UNIQUEに変更（初回ログイン時に登録）
  - [ ] parent_passwordをNULLABLEに変更（将来の拡張用）
  - [ ] マイグレーションファイルの修正
  - [ ] php artisan migrate:freshで再作成テスト
- **完了条件**: parentsテーブルが新仕様に合わせて作成される

### タスク2-10: ParentModelの修正
- **優先度**: 高
- **見積**: 20分
- **依存**: タスク2-9
- **内容**:
  - [ ] $fillableにparent_initial_email, parent_initial_password追加
  - [ ] $hiddenにparent_initial_password追加
  - [ ] casts()でparent_initial_passwordをhashed指定
  - [ ] getAuthPassword()をparent_initial_passwordに変更
  - [ ] php -l で構文チェック
- **完了条件**: ParentModelが新仕様に対応する

---

## 🟢 Phase 3: 管理者機能

### タスク3-1: 管理者認証コントローラー作成 [✓]
- **優先度**: 高
- **見積**: 1時間
- **依存**: タスク2-6, タスク2-7
- **内容**:
  - [✓] AdminLoginController作成
  - [✓] ログインメソッド実装
  - [✓] ログアウトメソッド実装
  - [✓] 2FA検証メソッド実装
- **完了条件**: コントローラーが作成され、php -l で構文エラーがない

### タスク3-2: クラス管理機能 [✓]
- **優先度**: 高
- **見積**: 1.5時間
- **依存**: タスク2-4, タスク3-1
- **内容**:
  - [✓] ClassController作成
  - [✓] index（一覧）メソッド実装
  - [✓] store（登録）メソッド実装
  - [✓] show（詳細）メソッド実装
  - [✓] update（更新）メソッド実装
  - [✓] destroy（削除）メソッド実装
  - [✓] StoreClassRequestバリデーション作成
  - [✓] UpdateClassRequestバリデーション作成
- **完了条件**: CRUD機能が実装され、php -l で構文エラーがない

### タスク3-3: 生徒管理機能 [✓]
- **優先度**: 高
- **見積**: 1.5時間
- **依存**: タスク3-2
- **内容**:
  - [✓] StudentController作成
  - [✓] index（一覧）メソッド実装（クラス情報含む）
  - [✓] store（登録）メソッド実装
  - [✓] show（詳細）メソッド実装
  - [✓] update（更新）メソッド実装
  - [✓] destroy（削除）メソッド実装
  - [✓] StoreStudentRequestバリデーション作成
  - [✓] UpdateStudentRequestバリデーション作成
- **完了条件**: CRUD機能が実装され、php -l で構文エラーがない

### タスク3-4: 保護者管理機能 [✓]
- **優先度**: 高
- **見積**: 1.5時間
- **依存**: タスク3-3
- **内容**:
  - [✓] ParentController作成
  - [✓] index（一覧）メソッド実装（生徒情報含む）
  - [✓] store（登録）メソッド実装（初期パスワード生成）
  - [✓] show（詳細）メソッド実装
  - [✓] update（更新）メソッド実装
  - [✓] destroy（削除）メソッド実装
  - [✓] StoreParentRequestバリデーション作成
  - [✓] UpdateParentRequestバリデーション作成
- **完了条件**: CRUD機能が実装され、php -l で構文エラーがない

### タスク3-5: CSVインポートサービス作成 [✓]
- **優先度**: 中
- **見積**: 2時間
- **依存**: タスク3-3, タスク3-4
- **内容**:
  - [✓] CsvImportServiceクラス作成
  - [✓] CSVパースメソッド実装
  - [✓] データバリデーションメソッド実装
  - [✓] 生徒インポートメソッド実装
  - [✓] クラスインポートメソッド実装
  - [✓] 教員（クラス更新）インポートメソッド実装
- **完了条件**: サービスが作成され、php -l で構文エラーがない

### タスク3-5-1: 保護者CSVインポート機能追加
- **優先度**: 高
- **見積**: 1時間
- **依存**: タスク3-5, タスク2-10
- **内容**:
  - [ ] CsvImportServiceにimportParentsメソッド追加
  - [ ] CSVフォーマット: seito_id, parent_name, parent_initial_email, parent_initial_password（平文）
  - [ ] バリデーション実装（seito_idの存在チェック、メール重複チェック）
  - [ ] parent_initial_passwordをHash::make()で暗号化してDB保存
  - [ ] parent_emailはNULL（初回ログイン時に登録）
  - [ ] parent_relationshipはデフォルト「保護者」を設定
  - [ ] エラーハンドリング実装
  - [ ] php -l で構文チェック
- **完了条件**: 保護者CSVインポートが正常に動作する

### タスク3-6: CSVインポートコントローラー作成 [✓]
- **優先度**: 中
- **見積**: 1時間
- **依存**: タスク3-5
- **内容**:
  - [✓] ImportController作成
  - [✓] importStudentsメソッド実装
  - [✓] importClassesメソッド実装
  - [✓] importTeachersメソッド実装
  - [✓] ファイルバリデーション実装
- **完了条件**: コントローラーが作成され、php -l で構文エラーがない

### タスク3-6-1: 保護者CSVインポートエンドポイント追加
- **優先度**: 高
- **見積**: 30分
- **依存**: タスク3-5-1, タスク3-6
- **内容**:
  - [ ] ImportControllerにimportParentsメソッド追加
  - [ ] CSVファイルバリデーション実装
  - [ ] CsvImportService::importParents()を呼び出し
  - [ ] レスポンス実装（成功件数、エラー詳細）
  - [ ] php -l で構文チェック
- **完了条件**: POST /admin/import/parentsエンドポイントが動作する

### タスク3-7: 管理者ルート定義
- **優先度**: 高
- **見積**: 30分
- **依存**: タスク3-1〜3-6
- **内容**:
  - [ ] routes/api.phpに管理者ルート追加
  - [ ] ミドルウェア適用
  - [ ] プレフィックス設定（/api/admin）
- **完了条件**: 全ルートが定義され、ルートリストで確認できる

---

## 🟡 Phase 4: 保護者機能

### タスク4-1: 保護者認証コントローラー作成
- **優先度**: 高
- **見積**: 1.5時間
- **依存**: タスク2-6, タスク2-7, タスク2-10
- **内容**:
  - [ ] ParentLoginController作成
  - [ ] ログインメソッド実装（parent_initial_email/parent_initial_passwordで認証）
  - [ ] parent_emailチェック機能実装（未登録の場合はメール登録へ誘導）
  - [ ] ログアウトメソッド実装
  - [ ] php -l で構文チェック
- **完了条件**: コントローラーが作成され、基本認証が動作する

### タスク4-1-1: メールアドレス登録機能実装
- **優先度**: 最高
- **見積**: 1時間
- **依存**: タスク4-1
- **内容**:
  - [ ] ParentLoginControllerにregisterEmailメソッド追加
  - [ ] parent_emailの登録機能実装
  - [ ] メール形式バリデーション実装
  - [ ] メール重複チェック実装
  - [ ] 登録後、2段階認証コード生成・送信
  - [ ] php -l で構文チェック
- **完了条件**: POST /parent/register-emailエンドポイントが動作する

### タスク4-1-2: 2段階認証機能実装
- **優先度**: 最高
- **見積**: 1時間
- **依存**: タスク4-1-1
- **内容**:
  - [ ] ParentLoginControllerにverify2FAメソッド追加
  - [ ] 2段階認証コード検証機能実装
  - [ ] 有効期限チェック実装
  - [ ] コード使用後の削除処理実装
  - [ ] セッション確立処理実装
  - [ ] php -l で構文チェック
- **完了条件**: POST /parent/verify-2faエンドポイントが動作する

### タスク4-2: 欠席連絡管理機能
- **優先度**: 高
- **見積**: 1.5時間
- **依存**: タスク4-1
- **内容**:
  - [ ] AbsenceController作成
  - [ ] index（一覧）メソッド実装（自分の子供のみ）
  - [ ] store（登録）メソッド実装
  - [ ] show（詳細）メソッド実装
  - [ ] update（更新）メソッド実装
  - [ ] destroy（削除）メソッド実装
  - [ ] StoreAbsenceRequestバリデーション作成
  - [ ] UpdateAbsenceRequestバリデーション作成
- **完了条件**: CRUD機能が実装され、php -l で構文エラーがない

### タスク4-3: 保護者ルート定義
- **優先度**: 高
- **見積**: 15分
- **依存**: タスク4-1, タスク4-2
- **内容**:
  - [ ] routes/api.phpに保護者ルート追加
  - [ ] ミドルウェア適用
  - [ ] プレフィックス設定（/api/parent）
- **完了条件**: 全ルートが定義され、ルートリストで確認できる

---

## 🟣 Phase 5: フロントエンド

### タスク5-1: Vue.js環境構築
- **優先度**: 最高
- **見積**: 1時間
- **内容**:
  - [ ] Vue 3インストール（npm install vue@next）
  - [ ] Vue Routerインストール
  - [ ] Piniaインストール
  - [ ] Axiosインストール
  - [ ] vite.config.jsにVueプラグイン設定
  - [ ] resources/js/app.jsをVue用に設定
- **完了条件**: Vue.jsが正常に動作する

### タスク5-2: Tailwind CSS設定
- **優先度**: 最高
- **見積**: 30分
- **依存**: タスク5-1
- **内容**:
  - [ ] Tailwind CSSインストール
  - [ ] postcss.config.js設定
  - [ ] tailwind.config.js設定
  - [ ] resources/css/app.cssにTailwindディレクティブ追加
- **完了条件**: Tailwind CSSが正常に適用される

### タスク5-3: ルーター設定
- **優先度**: 高
- **見積**: 45分
- **依存**: タスク5-1
- **内容**:
  - [ ] resources/js/router/index.js作成
  - [ ] 管理者ルート定義
  - [ ] 保護者ルート定義
  - [ ] ナビゲーションガード設定
- **完了条件**: ルーティングが正常に動作する

### タスク5-4: Pinia Store作成
- **優先度**: 高
- **見積**: 1時間
- **依存**: タスク5-1
- **内容**:
  - [ ] stores/auth.js作成（認証状態管理）
  - [ ] stores/admin.js作成（管理者機能状態管理）
  - [ ] stores/parent.js作成（保護者機能状態管理）
- **完了条件**: 全storeが作成され、動作する

### タスク5-5: 共通コンポーネント作成
- **優先度**: 高
- **見積**: 2時間
- **依存**: タスク5-2
- **内容**:
  - [ ] Header.vue作成（レスポンシブ対応）
  - [ ] Footer.vue作成
  - [ ] Modal.vue作成
  - [ ] Table.vue作成（ソート・ページネーション対応）
  - [ ] Button.vue作成
  - [ ] Input.vue作成
  - [ ] Select.vue作成
- **完了条件**: 全コンポーネントが作成され、レスポンシブ対応している

### タスク5-6: レイアウトコンポーネント作成
- **優先度**: 高
- **見積**: 1時間
- **依存**: タスク5-5
- **内容**:
  - [ ] AdminLayout.vue作成
  - [ ] ParentLayout.vue作成
  - [ ] GuestLayout.vue作成
- **完了条件**: 全レイアウトが作成され、動作する

### タスク5-7: 認証画面コンポーネント作成
- **優先度**: 高
- **見積**: 2.5時間
- **依存**: タスク5-5, タスク5-6
- **内容**:
  - [ ] AdminLogin.vue作成
  - [ ] ParentLogin.vue作成（parent_initial_email/parent_initial_password入力）
  - [ ] ParentEmailRegister.vue作成（初回ログイン時のparent_email登録画面）
  - [ ] TwoFactorVerify.vue作成（6桁コード入力、再送信機能、有効期限表示）
  - [ ] フォームバリデーション実装
  - [ ] レスポンシブ対応
- **完了条件**: 認証画面が正常に動作する

### タスク5-8: 管理者画面 - クラス管理
- **優先度**: 高
- **見積**: 2時間
- **依存**: タスク5-5, タスク5-6
- **内容**:
  - [ ] ClassList.vue作成（一覧・削除）
  - [ ] ClassForm.vue作成（登録・編集）
  - [ ] バリデーション実装
  - [ ] レスポンシブ対応
- **完了条件**: クラス管理画面が正常に動作する

### タスク5-9: 管理者画面 - 生徒管理
- **優先度**: 高
- **見積**: 2時間
- **依存**: タスク5-8
- **内容**:
  - [ ] StudentList.vue作成（一覧・削除・検索）
  - [ ] StudentForm.vue作成（登録・編集）
  - [ ] クラス選択機能実装
  - [ ] バリデーション実装
  - [ ] レスポンシブ対応
- **完了条件**: 生徒管理画面が正常に動作する

### タスク5-10: 管理者画面 - 保護者管理
- **優先度**: 高
- **見積**: 2時間
- **依存**: タスク5-9
- **内容**:
  - [ ] ParentList.vue作成（一覧・削除・検索）
  - [ ] ParentForm.vue作成（登録・編集）
  - [ ] 生徒選択機能実装
  - [ ] 初期パスワード表示機能
  - [ ] バリデーション実装
  - [ ] レスポンシブ対応
- **完了条件**: 保護者管理画面が正常に動作する

### タスク5-11: 管理者画面 - CSVインポート
- **優先度**: 中
- **見積**: 2時間
- **依存**: タスク5-5, タスク5-6
- **内容**:
  - [ ] CsvImport.vue作成
  - [ ] ファイル選択機能実装
  - [ ] プレビュー表示機能実装
  - [ ] インポート種別選択（生徒/クラス/教員/保護者）
  - [ ] エラー表示機能実装
  - [ ] レスポンシブ対応
- **完了条件**: CSVインポート画面が正常に動作する（保護者インポート含む）

### タスク5-12: 保護者画面 - 欠席連絡管理
- **優先度**: 高
- **見積**: 2.5時間
- **依存**: タスク5-5, タスク5-6
- **内容**:
  - [ ] AbsenceList.vue作成（一覧・削除）
  - [ ] AbsenceForm.vue作成（登録・編集）
  - [ ] 欠席/遅刻区分切替実装
  - [ ] 日付ピッカー実装
  - [ ] 時刻ピッカー実装（遅刻時のみ）
  - [ ] バリデーション実装
  - [ ] レスポンシブ対応
- **完了条件**: 欠席連絡管理画面が正常に動作する

### タスク5-13: ビルドとデプロイ設定
- **優先度**: 中
- **見積**: 30分
- **依存**: タスク5-1〜5-12
- **内容**:
  - [ ] npm run buildでビルド確認
  - [ ] public/build配下のファイル確認
  - [ ] Laravelビュー（resources/views/app.blade.php）作成
  - [ ] @viteディレクティブ設定
- **完了条件**: ビルドが正常に完了し、画面が表示される

---

## 🔴 Phase 6: テスト・調整

### タスク6-1: シーダー作成
- **優先度**: 中
- **見積**: 1時間
- **内容**:
  - [ ] AdminSeeder作成（管理者ユーザー）
  - [ ] ClassSeeder作成（テストクラス）
  - [ ] StudentSeeder作成（テスト生徒）
  - [ ] ParentSeeder作成（テスト保護者）
  - [ ] DatabaseSeederに登録
- **完了条件**: php artisan db:seedでテストデータが投入される

### タスク6-2: 機能テスト作成
- **優先度**: 中
- **見積**: 3時間
- **内容**:
  - [ ] 管理者認証テスト作成
  - [ ] 保護者認証テスト作成
  - [ ] 生徒CRUD テスト作成
  - [ ] 保護者CRUD テスト作成
  - [ ] クラスCRUD テスト作成
  - [ ] 欠席連絡CRUD テスト作成
  - [ ] CSVインポートテスト作成
  - [ ] 権限チェックテスト作成
- **完了条件**: php artisan testで全テストがパスする

### タスク6-3: バリデーションテスト
- **優先度**: 中
- **見積**: 2時間
- **内容**:
  - [ ] 各Requestクラスのバリデーションテスト作成
  - [ ] エラーメッセージ確認
  - [ ] 境界値テスト
- **完了条件**: 全バリデーションが正常に動作する

### タスク6-4: 2段階認証テスト
- **優先度**: 高
- **見積**: 1時間
- **内容**:
  - [ ] コード生成テスト
  - [ ] コード検証テスト
  - [ ] コード有効期限テスト
  - [ ] メール送信テスト
- **完了条件**: 2段階認証が正常に動作する

### タスク6-5: UIテスト
- **優先度**: 低
- **見積**: 2時間
- **内容**:
  - [ ] レスポンシブデザイン確認（各デバイス）
  - [ ] フォーム入力確認
  - [ ] エラー表示確認
  - [ ] ナビゲーション確認
- **完了条件**: 全画面がレスポンシブ対応している

### タスク6-6: パフォーマンステスト
- **優先度**: 低
- **見積**: 1時間
- **内容**:
  - [ ] N+1問題確認（Laravel Debugbar使用）
  - [ ] ページロード時間測定
  - [ ] 大量データでの動作確認
- **完了条件**: パフォーマンス基準を満たす

### タスク6-7: セキュリティチェック
- **優先度**: 高
- **見積**: 1時間
- **内容**:
  - [ ] CSRF保護確認
  - [ ] XSS対策確認
  - [ ] SQLインジェクション対策確認
  - [ ] 認証・認可チェック
  - [ ] パスワードハッシュ化確認
- **完了条件**: セキュリティ要件を満たす

### タスク6-8: ドキュメント作成
- **優先度**: 低
- **見積**: 2時間
- **内容**:
  - [ ] README.md更新（セットアップ手順）
  - [ ] API仕様書作成
  - [ ] CSVフォーマット仕様書作成
  - [ ] 運用マニュアル作成
- **完了条件**: 必要なドキュメントが揃う

---

## タスク実行順序（推奨）

### Week 1: 基盤構築
1. タスク2-1 → タスク2-2 → タスク2-3 （データベース）✓
2. タスク2-4 → タスク2-5 → タスク2-6 （認証基盤）✓
3. タスク2-7 → タスク2-8 （2段階認証・メール）✓
4. **タスク2-9 → タスク2-10 （parentsテーブル・モデル修正）← 今回実装**

### Week 2: バックエンド実装
5. タスク3-1 （管理者認証）✓
6. タスク3-2 → タスク3-3 → タスク3-4 （CRUD機能）✓
7. タスク3-5 → タスク3-6 （CSVインポート）✓
8. **タスク3-5-1 → タスク3-6-1 （保護者CSVインポート）← 今回実装**
9. タスク3-7 （ルート定義）
10. **タスク4-1 → タスク4-1-1 → タスク4-1-2 （保護者認証・メール登録・2FA）← 今回実装**
11. タスク4-2 → タスク4-3 （欠席連絡機能・ルート定義）

### Week 3-4: フロントエンド実装
12. タスク5-1 → タスク5-2 （環境構築）
13. タスク5-3 → タスク5-4 （ルーター・Store）
14. タスク5-5 → タスク5-6 （共通コンポーネント）
15. **タスク5-7 （認証画面：ParentEmailRegister.vue含む）← 今回更新**
16. タスク5-8 → タスク5-9 → タスク5-10 → タスク5-11 （管理者画面）
17. タスク5-12 （保護者画面）
18. タスク5-13 （ビルド設定）

### Week 5: テスト・調整
19. タスク6-1 （シーダー）
20. タスク6-2 → タスク6-3 （機能テスト）
21. タスク6-4 （2段階認証テスト）
22. タスク6-7 （セキュリティチェック）
23. タスク6-5 → タスク6-6 （UI・パフォーマンス）
24. タスク6-8 （ドキュメント）

---

## 進捗管理

### 凡例
- [ ] 未着手
- [進行中] 作業中
- [✓] 完了
- [保留] 一時保留
- [×] スキップ

### 現在の進捗
- Phase 1: 100% 完了 ✓
- Phase 2: 80% (タスク2-9, 2-10が未完了)
- Phase 3: 85% (タスク3-5-1, 3-6-1, 3-7が未完了)
- Phase 4: 0%
- Phase 5: 0%
- Phase 6: 0%

**全体進捗: 完了タスク/58タスク**

**今回追加されたタスク:**
- タスク2-9: parentsテーブルマイグレーション修正
- タスク2-10: ParentModelの修正
- タスク3-5-1: 保護者CSVインポート機能追加
- タスク3-6-1: 保護者CSVインポートエンドポイント追加
- タスク4-1-1: メールアドレス登録機能実装
- タスク4-1-2: 2段階認証機能実装

---

## 注意事項

1. **各タスク実行前に必ずphp -l でPHP構文チェックを実施**
2. **データベース操作後は必ずマイグレーション状態を確認**
3. **コントローラー作成後は必ずルート定義を確認**
4. **フロントエンド実装時は必ずレスポンシブ対応を確認**
5. **セキュリティに関わる実装は特に慎重に**
6. **エラーが発生した場合は次に進まず、必ず解決してから進む**
7. **想定外の問題が発生した場合は、ユーザーに報告し指示を仰ぐ**

## 次のステップ

タスク化が完了しました（2026年2月26日更新）。

---

## 🔴 バグ修正: 初回ログイン時メール登録フロー（2026-02-28）

### タスクBF-1: auth.js ストア修正 [✓]
- **優先度**: 最高
- **見積**: 15分
- **内容**:
  - [ ] `state` に `needsEmailRegistration: false` を追加
  - [ ] `parentLogin` アクションに `requires_email_registration` 分岐を追加
  - [ ] `needsEmailRegistration` リセット処理を `logout` に追加
- **完了条件**: APIレスポンスの `requires_email_registration: true` 時にストア状態が更新される

### タスクBF-2: ParentLogin.vue 修正 [✓]
- **優先度**: 最高
- **見積**: 15分
- **依存**: タスクBF-1
- **内容**:
  - [ ] `requires_email_registration` レスポンス時に `/parent/register-email` へ遷移
  - [ ] ログ出力整理
- **完了条件**: 初回ログイン時にメール登録画面へ遷移する

### タスクBF-3: ParentEmailRegister.vue 新規作成 [✓]
- **優先度**: 最高
- **見積**: 30分
- **依存**: タスクBF-1
- **内容**:
  - [ ] メールアドレス入力フォーム作成
  - [ ] POST `/api/parent/register-email` API呼び出し
  - [ ] 成功後に 2FA 画面（`parent.verify2fa`）へ遷移
  - [ ] バリデーション・エラー表示
- **完了条件**: メールアドレス登録後に2FA画面へ遷移する

### タスクBF-4: router/index.js 修正 [✓]
- **優先度**: 高
- **見積**: 10分
- **依存**: タスクBF-3
- **内容**:
  - [ ] `ParentEmailRegister` コンポーネントをインポート
  - [ ] `/parent/register-email` ルートを追加（name: `parent.registerEmail`）
- **完了条件**: `/parent/register-email` にアクセスできる



**今回追加された主な機能:**
1. 保護者認証フローの変更
   - parent_initial_email/parent_initial_password でログイン（常に）
   - 初回ログイン時にparent_email登録
   - 2回目以降も同じ初期認証情報でログイン
   - 毎回、登録済みparent_emailに2段階認証コード送信

2. 保護者CSVインポート機能
   - CSV形式: seito_id, parent_name, parent_initial_email, parent_initial_password（平文）
   - インポート時にparent_initial_passwordをbcrypt暗号化

3. データベース仕様変更
   - parent_initial_email: NOT NULL, UNIQUE
   - parent_initial_password: NOT NULL（bcrypt暗号化）
   - parent_email: NULLABLE, UNIQUE（初回ログイン時に登録）

実行フェーズへ進む前に、以下を確認してください：

1. タスクの粒度は適切か
2. 依存関係は正しいか
3. 見積時間は妥当か
4. 優先順位は適切か
5. 実行順序に問題はないか

**実装優先順位（緊急度順）:**
1. タスク2-9, 2-10: データベース・モデルの修正
2. タスク3-5-1, 3-6-1: 保護者CSVインポート
3. タスク4-1, 4-1-1, 4-1-2: 保護者認証・メール登録・2FA
4. タスク3-7, 4-3: ルート定義
5. その他のタスク

---

## 🔴 機能追加タスク（2026-03-01）

### タスクFA-1: 欠席連絡→担任メール通知修正 [x]
- **優先度**: 高
- **見積**: 20分
- **対象ファイル**: `app/Services/AbsenceNotificationService.php` のみ
- **内容**:
  - [ ] `notifyTeacher()` の担任取得を `admins` テーブルから `$class->teacher_email` / `$class->teacher_name` に変更
  - [ ] `teacher_email` が空の場合はスキップしてログ出力（WarningのみでOK）
  - [ ] メール本文の宛先を `{$teacherName} 先生` に変更
  - [ ] `Admin` モデルのインポートを削除（不要になる場合）
  - [ ] `php -l` で構文チェック
- **完了条件**: `classes.teacher_email` 宛にメールが送信される、`admins` 検索は不要

### タスクFA-2: 保護者2FA用メール再設定（バックエンド） [x]
- **優先度**: 高
- **見積**: 40分
- **対象ファイル**: `ParentLoginController.php`、`routes/api.php`
- **内容**:
  - [ ] `requestEmailChange()` メソッド追加
    - バリデーション: `required|email|unique:parents,parent_email,{$parentId}`（自分を除く重複チェック）
    - `TwoFactorService` で new_email 宛にコード生成・送信
    - セッションに `new_email_pending` を保存
  - [ ] `confirmEmailChange()` メソッド追加
    - セッションから `new_email_pending` 取得
    - `TwoFactorService::verify(new_email, code, 'parent')` 検証
    - 検証成功後 `parent_email` を更新、セッションから削除
  - [ ] `routes/api.php` に以下ルート追加（認証済みparentガード下）
    - `POST /api/parent/request-email-change`
    - `POST /api/parent/confirm-email-change`
  - [ ] `php -l` で構文チェック
- **完了条件**: 新メールに確認コードが届き、検証後に `parent_email` が更新される

### タスクFA-3: 保護者2FA用メール再設定（フロントエンド） [x]
- **優先度**: 高
- **見積**: 45分
- **対象ファイル**: `resources/js/pages/parent/Dashboard.vue`
- **依存**: タスクFA-2
- **内容**:
  - [ ] ダッシュボードに「2FA用メールアドレス変更」カードを追加
  - [ ] 現在のメールアドレス表示（`authStore.user?.email`）
  - [ ] Step1: 新メールアドレス入力フォーム
    - 「確認コードを送信」ボタン → `POST /api/parent/request-email-change`
  - [ ] Step2: コード入力フォーム（Step1成功後に表示切替）
    - 6桁コード入力
    - 「変更する」ボタン → `POST /api/parent/confirm-email-change`
    - 成功後: 完了メッセージ表示・フォームリセット・`authStore.user.email` 更新
  - [ ] エラー表示（重複・コード誤り等）
- **完了条件**: ダッシュボードからメール変更が一連のUIで完結する

### タスクFA-4: クラスCSVインポートテンプレート追加（バックエンド） [x]
- **優先度**: 中
- **見積**: 10分
- **対象ファイル**: `app/Http/Controllers/Admin/CsvImportController.php`
- **内容**:
  - [ ] `downloadTemplate()` の `$templates` 配列に `classes` を追加
    ```php
    'classes' => [
        'filename' => 'classes_template.csv',
        'headers'  => ['class_id', 'class_name', 'teacher_name', 'teacher_email', 'year_id'],
        'sample'   => ['1TOKUSHIN', '1特進', '田中先生', 'tanaka@seiei.ac.jp', '2026'],
    ],
    ```
  - [ ] `php -l` で構文チェック
- **完了条件**: `GET /api/admin/import/template/classes` でCSVがダウンロードできる

### タスクFA-5: クラスCSVインポートUI追加（フロントエンド） [x]
- **優先度**: 中
- **見積**: 30分
- **対象ファイル**: `resources/js/pages/admin/import/Index.vue`
- **依存**: タスクFA-4
- **内容**:
  - [ ] `selectedFiles` / `uploading` / `results` reactive オブジェクトに `classes` キーを追加
  - [ ] `<template>` のグリッドにクラスデータカードを追加
    - カラム: `class_id`, `class_name`, `teacher_name`, `teacher_email`, `year_id`
    - テンプレートダウンロード・ファイル選択・インポート実行・結果表示
  - [ ] `getTypeName()` に `classes: 'クラスデータ'` を追加
  - [ ] `$refs.classesFileInput` を追加
- **完了条件**: UI上からクラスCSVのインポートとテンプレートDLができる

---

## 🟡 Phase 8: 生徒クラス一括更新に seito_number 追加（2026/04/04 修正）

### タスク一覧

| ID | タスク名 | 状態 | 依存 |
|----|---------|------|------|
| T8-1 | CsvImportService::importStudentClasses() に seito_number 追加 | [✓] | なし |
| T8-2 | Index.vue フォーマット説明に seito_number 追加 | [✓] | T8-1 |
| T8-3 | README.md の生徒クラス更新CSVサンプルに seito_number 追加 | [✓] | T8-1 |
| T8-4 | 動作確認（php -l + tinker テスト） | [✓] | T8-1〜3 |

---

### タスク T8-1: CsvImportService::importStudentClasses() に seito_number 追加
- **優先度**: 最高
- **対象ファイル**: `app/Services/CsvImportService.php`
- **内容**:
  - [ ] バリデーションに `'seito_number' => 'required|integer|min:1'` を追加
  - [ ] `update()` に `'seito_number' => (int) $row['seito_number']` を追加
  - [ ] `php -l app/Services/CsvImportService.php` で構文確認
- **完了条件**: 構文エラーなし、seito_number が class_id と同時に更新される

---

### タスク T8-2: Index.vue フォーマット説明に seito_number 追加
- **優先度**: 高
- **依存**: T8-1
- **対象ファイル**: `resources/js/pages/admin/import/Index.vue`
- **内容**:
  - [ ] 「生徒クラス一括更新」カードの `<ul>` に `<li>seito_number （新しい出席番号）</li>` を追加
- **完了条件**: UIのフォーマット説明に seito_number が表示されている

---

### タスク T8-3: README.md の生徒クラス更新CSVサンプルに seito_number 追加
- **優先度**: 高
- **依存**: T8-1
- **対象ファイル**: `README.md`
- **内容**:
  - [ ] 生徒クラス更新CSVのサンプルヘッダーに `seito_number` カラムを追加
  - [ ] サンプルデータ行にも `seito_number` の値を追加
  - [ ] 年度切り替え手順 STEP 2 の説明文に `seito_number` の言及を追記
- **完了条件**: README の CSVサンプルが 3カラム（seito_id, class_id, seito_number）になっている

---

### タスク T8-4: 動作確認
- **優先度**: 高
- **依存**: T8-1〜3
- **内容**:
  - [ ] `php -l app/Services/CsvImportService.php` で構文確認
  - [ ] tinker で正常系: `['seito_id' => '1001', 'class_id' => '1TOKUSHIN', 'seito_number' => '5']` → success
  - [ ] tinker で異常系1: 存在しない seito_id → skipped に計上
  - [ ] tinker で異常系2: `'seito_number' => 'abc'`（文字列） → errors に計上
- **完了条件**: 全ケースで正しい動作を確認


