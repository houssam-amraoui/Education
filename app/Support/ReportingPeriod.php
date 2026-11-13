<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

final readonly class ReportingPeriod
{
    private function __construct(public CarbonImmutable $start, public CarbonImmutable $end) {}

    public static function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'through' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public static function fromDates(mixed $from, mixed $through): self
    {
        Validator::make(
            ['from' => $from, 'through' => $through],
            self::rules(),
        )->validate();

        return new self(
            CarbonImmutable::createFromFormat('!Y-m-d', $from, 'UTC'),
            CarbonImmutable::createFromFormat('!Y-m-d', $through, 'UTC')->addDay(),
        );
    }
}
