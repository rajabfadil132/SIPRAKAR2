<?php

namespace App\Enums;

enum PekerjaanStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Belum Diproses',
            self::IN_PROGRESS => 'Diproses',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public static function editableKeys(): array
    {
        return [self::NOT_STARTED->value, self::IN_PROGRESS->value, self::CANCELLED->value];
    }

    public static function editableLabels(): array
    {
        return [self::NOT_STARTED->label(), self::IN_PROGRESS->label(), self::CANCELLED->label()];
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
            'Pending', 'Belum dilaksanakan' => self::NOT_STARTED,
            'Sedang berjalan', 'Berjalan', 'Tertunda' => self::IN_PROGRESS,
            default => self::NOT_STARTED,
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
}
