<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => AuditLog::ACTION_LOGIN,
            'target_type' => null,
            'target_id' => null,
            'details' => [],
        ];
    }
}
