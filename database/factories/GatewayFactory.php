<?php

namespace Database\Factories;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GatewayFactory extends Factory
{
    protected $model = Gateway::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'device_provider' => $this->faker->words(),
            'name'            => $this->faker->name(),
            'type'            => $this->faker->word(),
            'share'           => $this->faker->boolean(),
            'team_id'         => $this->faker->words(),
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
