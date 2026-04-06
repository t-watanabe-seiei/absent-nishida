<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnnouncementNotificationService
{
    /**
     * お知らせの対象保護者にメール通知する
     */
    public function notifyParents(Announcement $announcement): void
    {
        $announcement->load('admin');

        $parents = $this->resolveTargetParents($announcement);

        if ($parents->isEmpty()) {
            Log::info('お知らせ通知: 対象保護者が0件のためスキップ', ['announcement_id' => $announcement->id]);
            return;
        }

        $subject = "【お知らせ】{$announcement->title}";

        foreach ($parents as $parent) {
            // 2FA用メールが登録済みならそちらを優先、未登録なら初期メールにフォールバック
            $email = $parent->parent_email ?: $parent->parent_initial_email;
            if (empty($email)) {
                Log::warning('お知らせ通知: メールアドレス未登録のためスキップ', [
                    'announcement_id' => $announcement->id,
                    'parent_id'       => $parent->id,
                ]);
                continue;
            }

            try {
                $body = "{$parent->parent_name} 様\n\n";
                $body .= "学校からお知らせがあります。\n\n";
                $body .= "━━━━━━━━━━━━━━━━━━━━\n";
                $body .= "【件名】{$announcement->title}\n\n";
                $body .= "{$announcement->body}\n\n";
                $body .= "有効期限: {$announcement->expires_at->format('Y年m月d日')}\n";
                $body .= "━━━━━━━━━━━━━━━━━━━━\n\n";
                $body .= "※詳細・添付ファイルは欠席連絡システムのダッシュボードでご確認ください。\n";
                $body .= "※このメールは欠席連絡システムから自動送信されています。\n";

                Mail::raw($body, function ($message) use ($email, $parent, $subject) {
                    $message->to($email)->subject($subject);
                });

                Log::info('お知らせ通知メール送信成功', [
                    'announcement_id' => $announcement->id,
                    'parent_id'       => $parent->id,
                    'parent_email'    => $email,
                ]);
            } catch (\Exception $e) {
                Log::error('お知らせ通知メール送信エラー', [
                    'announcement_id' => $announcement->id,
                    'parent_id'       => $parent->id,
                    'error'           => $e->getMessage(),
                ]);
                // 一人の失敗で全体を止めない
            }
        }
    }

    /**
     * お知らせの対象保護者コレクションを解決する
     */
    private function resolveTargetParents(Announcement $announcement)
    {
        $targetParentIds = $announcement->target_parent_ids;

        // 個別保護者指定がある場合
        if (!is_null($targetParentIds) && count($targetParentIds) > 0) {
            return ParentModel::whereIn('id', $targetParentIds)->get();
        }

        // クラス全員の場合: 対象クラスに所属する生徒の保護者を取得
        $targetClassIds = $announcement->target_class_ids ?? [];
        if (empty($targetClassIds)) {
            return collect();
        }

        $seitoIds = Student::whereIn('class_id', $targetClassIds)
            ->pluck('seito_id')
            ->toArray();

        if (empty($seitoIds)) {
            return collect();
        }

        return ParentModel::whereIn('seito_id', $seitoIds)->get();
    }
}
