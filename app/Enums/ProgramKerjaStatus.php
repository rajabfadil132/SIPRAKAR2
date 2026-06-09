<?php

namespace App\Enums;

enum ProgramKerjaStatus: string
{
    case RAB_SUBMITTED = 'rab_submitted';
    case RAB_REVISION = 'rab_revision';
    case RAB_APPROVED = 'rab_approved';
    case READY_FOR_WORK = 'ready_for_work';
    case CONVERTED = 'converted';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::RAB_SUBMITTED => 'RAB Diajukan',
            self::RAB_REVISION => 'RAB Direvisi',
            self::RAB_APPROVED => 'RAB Disetujui',
            self::READY_FOR_WORK => 'Siap Dijadikan Pekerjaan',
            self::CONVERTED => 'Dijadikan Pekerjaan',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public static function activeKeys(): array
    {
        return [
            self::RAB_SUBMITTED->value,
            self::RAB_REVISION->value,
            self::RAB_APPROVED->value,
            self::READY_FOR_WORK->value,
        ];
    }

    public static function finalKeys(): array
    {
        return [
            self::CONVERTED->value,
            self::COMPLETED->value,
            self::CANCELLED->value,
        ];
    }

    public static function activeLabels(): array
    {
        return array_map(fn (self $status) => $status->label(), [
            self::RAB_SUBMITTED,
            self::RAB_REVISION,
            self::RAB_APPROVED,
            self::READY_FOR_WORK,
        ]);
    }

    public static function finalLabels(): array
    {
        return array_map(fn (self $status) => $status->label(), [
            self::CONVERTED,
            self::COMPLETED,
            self::CANCELLED,
        ]);
    }

    public static function labels(): array
    {
        return array_map(fn (self $status) => $status->label(), self::cases());
    }

    public static function fromLabelOrKey(?string $value): self
    {
        $normalized = trim((string) $value);

        foreach (self::cases() as $status) {
            if ($normalized === $status->value || $normalized === $status->label()) {
                return $status;
            }
        }

        return match ($normalized) {
            'Berjalan' => self::CONVERTED,
            'Tertunda' => self::CANCELLED,
            default => self::READY_FOR_WORK,
        };
    }

    public static function keyFromLabel(?string $label): string
    {
        return self::fromLabelOrKey($label)->value;
    }

    public static function labelFromKey(?string $key): string
    {
        return self::fromLabelOrKey($key)->label();
    }

    public static function fromRabStatus(RabStatus|string|null $status): self
    {
        $rabStatus = $status instanceof RabStatus ? $status : RabStatus::fromLabelOrKey($status);

        return match ($rabStatus) {
            RabStatus::SUBMITTED => self::RAB_SUBMITTED,
            RabStatus::REVISION => self::RAB_REVISION,
            RabStatus::APPROVED => self::RAB_APPROVED,
            RabStatus::REJECTED => self::READY_FOR_WORK,
        };
    }
}
