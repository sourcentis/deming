<?php

namespace Database\Factories;

use App\Models\Framework;
use Illuminate\Database\Eloquent\Factories\Factory;

class FrameworkFactory extends Factory
{
    protected $model = Framework::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->bothify('REF-####-??'));

        return [
            'code' => $code,
            'name' => $code,
            'version' => (string) $this->faker->year(),
            'publisher' => $this->faker->company(),
            'jurisdiction' => null,
            'source_url' => $this->faker->url(),
            'status' => 'published',
            'publication_date' => $this->faker->date(),
            'effective_date' => null,
            'notes' => null,
        ];
    }
}
