<?php

namespace Database\Factories;

use App\Models\Devices;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DevicesFactory extends Factory
{
    protected $model = Devices::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'device_name'  => $this->faker->name(),
            'manufacturer' => $this->faker->word(),
            'modelName'    => $this->faker->name(),
            'osName'       => $this->faker->name(),
            'status'       => $this->faker->word(),
            'last_seen'    => Carbon::now(),
            'extras'       => $this->faker->words(),
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
