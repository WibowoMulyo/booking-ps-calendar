<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => User::inRandomOrder()->first()->name ?? User::factory()->create()->name,
            'date' => $this->faker->dateTimeBetween('+1 days', '+1 month')->format('Y-m-d'),
            'type' => $this->faker->randomElement(['PS4', 'PS5']),
            'total' => function (array $attributes) {
                $basePrice = $attributes['type'] === 'PS4' ? 30000 : 40000;
                $isWeekend = in_array(date('N', strtotime($attributes['date'])), [6, 7]);
                return number_format($isWeekend ? $basePrice + 50000 : $basePrice, 2, '.', '');
            },
            'status' => 'paid',
        ];
    }
}
