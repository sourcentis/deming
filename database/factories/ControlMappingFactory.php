<?php

namespace Database\Factories;

use App\Models\Control;
use App\Models\ControlMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

class ControlMappingFactory extends Factory
{
    protected $model = ControlMapping::class;

    public function definition(): array
    {
        return [
            'source_control_id' => Control::factory(),
            'target_control_id' => Control::factory(),
            'mapping_type' => $this->faker->randomElement(ControlMapping::MAPPING_TYPES),
            'coverage' => $this->faker->randomElement(ControlMapping::COVERAGE_LEVELS),
            'confidence' => null,
            'rationale' => $this->faker->sentence(),
            'source_reference' => null,
            'source_url' => null,
            'validated' => false,
            'validated_by' => null,
            'validated_at' => null,
        ];
    }
}
