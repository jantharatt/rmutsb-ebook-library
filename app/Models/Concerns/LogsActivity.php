<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

/**
 * บันทึกการเพิ่ม/แก้ไข/ลบข้อมูลของ model ลง audit log อัตโนมัติ (เฉพาะเมื่อผู้กระทำเป็นแอดมิน)
 *
 * model ที่ใช้ trait นี้กำหนดได้:
 *   protected string $auditLabelAttribute = 'title';   // ฟิลด์ที่ใช้เป็นชื่อรายการ (ค่าเริ่มต้น name)
 *   protected array  $auditExclude = ['view_count'];    // ฟิลด์ที่ไม่ต้องบันทึก
 *   public ?string   $auditDescription                  // ข้อความเพิ่มเติม ใส่ก่อนเรียก delete()/save()
 *
 * หมายเหตุ: saveQuietly() และ query แบบ mass (Model::where(...)->update()/delete()) จะไม่ถูกบันทึก
 */
trait LogsActivity
{
    /** ฟิลด์ที่ไม่บันทึกเสมอ */
    protected static array $auditAlwaysExclude = [
        'created_at', 'updated_at', 'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    ];

    public ?string $auditDescription = null;

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            AuditLogger::log('created', $model, null, $model->auditFilter($model->getAttributes()), $model->auditDescription);
        });

        static::updated(function ($model) {
            $new = $model->auditFilter($model->getChanges());

            // เปลี่ยนเฉพาะฟิลด์ที่ไม่ต้องบันทึก (เช่น ตัวนับยอดเปิดอ่าน) → ข้าม
            if (! $new) {
                return;
            }

            $old = array_intersect_key($model->getRawOriginal(), $new);

            AuditLogger::log('updated', $model, $old, $new, $model->auditDescription);
        });

        static::deleted(function ($model) {
            AuditLogger::log('deleted', $model, $model->auditFilter($model->getRawOriginal()), null, $model->auditDescription);
        });
    }

    /** ชื่อรายการที่แสดงใน log */
    public function auditLabel(): ?string
    {
        $attribute = property_exists($this, 'auditLabelAttribute') ? $this->auditLabelAttribute : 'name';

        return $this->getAttribute($attribute) ?? ('#'.$this->getKey());
    }

    protected function auditFilter(array $values): array
    {
        $exclude = array_merge(
            static::$auditAlwaysExclude,
            property_exists($this, 'auditExclude') ? $this->auditExclude : [],
        );

        return array_diff_key($values, array_flip($exclude));
    }
}
