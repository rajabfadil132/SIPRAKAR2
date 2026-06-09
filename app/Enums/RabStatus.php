<?php

namespace App\Enums;

enum RabStatus: string
{
    case SUBMITTED = 'submitted';
    case REVISION = 'revision';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Diajukan',
            self::REVISION => 'Direvisi',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public static function labels(): array
    {
        return array_map(fn (self $status) => $status->label(), self::cases());
    }

    public static function editableKeys(): array
    {
        return [self::SUBMITTED->value, self::REVISION->value];
    }

    public static function editableLabels(): array
    {
        return [self::SUBMITTED->label(), self::REVISION->label()];
    }

    public static function fromLabelOrKey(?string $value): self
    {
        $normalized = trim((string) $value);

        foreach (self::cases() as $status) {
            if ($normalized === $status->value || $normalized === $status->label()) {
                return $status;
            }
        }

        return self::SUBMITTED;
    }

    public static function keyFromLabel(?string $label): string
    {
        return self::fromLabelOrKey($label)->value;
    }

    public static function labelFromKey(?string $key): string
    {
        return self::fromLabelOrKey($key)->label();
    }
}
