<?php

use App\Enums\WorkOrderStatus;

it('defines active work order statuses', function (): void {
    expect(WorkOrderStatus::activeValues())->toBe([
        'received',
        'in_progress',
        'ready',
    ])->and(WorkOrderStatus::activeCases())->toHaveCount(3);
});

it('excludes delivered from active statuses', function (): void {
    expect(WorkOrderStatus::activeValues())->not->toContain(WorkOrderStatus::Delivered->value);
});
