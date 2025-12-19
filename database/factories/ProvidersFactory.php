<?php

namespace Database\Factories;

use App\Models\Providers;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProvidersFactory extends Factory
{
    protected $model = Providers::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'connection_type' => $this->faker->word(),
            'scope'           => $this->faker->word(),
            'client_id'       => $this->faker->word(),
            'client_secret'   => $this->faker->word(),
            'refresh_token'   => Str::random(10),
            'access_token'    => Str::random(10),
            'domain'          => $this->faker->word(),
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
