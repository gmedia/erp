<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'draft',
            'pending_approval',
            'approved',
            'rejected',
            'partially_ordered',
            'fully_ordered',
            'cancelled',
        ]);

        return [
            'pr_number' => null,
            'branch_id' => Branch::factory(),
            'department_id' => Department::factory(),
            'requested_by' => Employee::factory(),
            'request_date' => $this->faker->date(),
            'required_date' => $this->faker->date(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => $status,
            'estimated_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'notes' => $this->faker->optional()->sentence(),
            'approved_by' => in_array($status, ['approved', 'partially_ordered', 'fully_ordered'], true) ? User::factory() : null,
            'approved_at' => in_array($status, ['approved', 'partially_ordered', 'fully_ordered'], true) ? now() : null,
            'rejection_reason' => $status === 'rejected' ? $this->faker->sentence() : null,
            'created_by' => User::factory(),
        ];
    }
}
