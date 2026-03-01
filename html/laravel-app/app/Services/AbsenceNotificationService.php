<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AbsenceNotificationService
{
    /**
     * 欠席連絡を担任にメール通知
     */
    public function notifyTeacher(Absence $absence): bool
    {
        try {
            // 欠席情報を取得
            $absence->load(['student.classModel']);
            
            if (!$absence->student || !$absence->student->classModel) {
                Log::warning('欠席通知: 生徒またはクラス情報が見つかりません', ['absence_id' => $absence->id]);
                return false;
            }

            $student = $absence->student;
            $class = $student->classModel;

            // 担任メールアドレスをclassesテーブルから直接取得
            $teacherEmail = $class->teacher_email;
            $teacherName  = $class->teacher_name;

            if (empty($teacherEmail)) {
                Log::warning('欠席通知: teacher_emailが未設定のためスキップ', [
                    'class_id'   => $class->class_id,
                    'class_name' => $class->class_name,
                ]);
                return false;
            }

            // メール本文を作成
            $subject = "【欠席連絡】{$class->class_name} {$student->seito_name}";

            $body = "{$teacherName} 先生\n\n";
            $body .= "保護者から欠席連絡がありました。\n\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━\n";
            $body .= "【生徒情報】\n";
            $body .= "クラス: {$class->class_name}\n";
            $body .= "出席番号: {$student->seito_number}\n";
            $body .= "氏名: {$student->seito_name}\n\n";
            $body .= "【欠席情報】\n";
            $body .= "日付: {$absence->absence_date}\n";
            $body .= "区分: {$absence->division}\n";
            $body .= "理由: {$absence->reason}\n";

            if ($absence->scheduled_time) {
                $body .= "予定時刻: {$absence->scheduled_time}\n";
            }

            $body .= "\n連絡時刻: " . $absence->created_at->format('Y年m月d日 H:i') . "\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $body .= "※このメールは欠席連絡システムから自動送信されています。\n";

            // メール送信
            Mail::raw($body, function ($message) use ($teacherEmail, $subject) {
                $message->to($teacherEmail)
                        ->subject($subject);
            });

            Log::info('欠席通知メール送信成功', [
                'teacher_email' => $teacherEmail,
                'student_name'  => $student->seito_name,
                'absence_id'    => $absence->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('欠席通知メール送信エラー', [
                'error' => $e->getMessage(),
                'absence_id' => $absence->id ?? null
            ]);
            return false;
        }
    }

    /**
     * スーパー管理者にも通知（オプション）
     */
    public function notifySuperAdmin(Absence $absence): bool
    {
        try {
            $absence->load(['student.classModel']);
            
            if (!$absence->student || !$absence->student->classModel) {
                return false;
            }

            $student = $absence->student;
            $class = $student->classModel;

            // スーパー管理者を取得
            $superAdmins = Admin::where('is_super_admin', true)->get();

            if ($superAdmins->isEmpty()) {
                return false;
            }

            $subject = "【欠席連絡】{$class->class_name} {$student->seito_name}";
            
            $body = "スーパー管理者様\n\n";
            $body .= "保護者から欠席連絡がありました。\n\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━\n";
            $body .= "【生徒情報】\n";
            $body .= "クラス: {$class->class_name}\n";
            $body .= "出席番号: {$student->seito_number}\n";
            $body .= "氏名: {$student->seito_name}\n\n";
            $body .= "【欠席情報】\n";
            $body .= "日付: {$absence->absence_date}\n";
            $body .= "区分: {$absence->division}\n";
            $body .= "理由: {$absence->reason}\n";
            
            if ($absence->scheduled_time) {
                $body .= "予定時刻: {$absence->scheduled_time}\n";
            }
            
            $body .= "\n連絡時刻: " . $absence->created_at->format('Y年m月d日 H:i') . "\n";
            $body .= "━━━━━━━━━━━━━━━━━━━━\n\n";

            foreach ($superAdmins as $admin) {
                Mail::raw($body, function ($message) use ($admin, $subject) {
                    $message->to($admin->email)
                           ->subject($subject);
                });
            }

            return true;

        } catch (\Exception $e) {
            Log::error('スーパー管理者への通知エラー: ' . $e->getMessage());
            return false;
        }
    }
}
