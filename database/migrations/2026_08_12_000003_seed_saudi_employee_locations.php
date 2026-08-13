<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedSaudiEmployeeLocations extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('countries') || !Schema::hasTable('states') || !Schema::hasTable('cities')) {
            return;
        }

        $countryId = DB::table('countries')->where('code', 'SA')->value('id');

        if (!$countryId) {
            $countryId = DB::table('countries')->insertGetId([
                'code' => 'SA',
                'name' => 'Saudi Arabia',
                'dial_code' => 966,
                'currency_name' => 'Saudi Riyal',
                'symbol' => 'SAR',
                'currency_code' => 'SAR',
            ]);
        }

        $regions = [
            'Riyadh' => ['Riyadh', 'Diriyah', 'Al Kharj', 'Dawadmi', 'Majmaah', 'Wadi ad-Dawasir'],
            'Makkah' => ['Makkah', 'Jeddah', 'Taif', 'Rabigh', 'Al Qunfudhah'],
            'Madinah' => ['Madinah', 'Yanbu', 'Al Ula', 'Badr'],
            'Eastern Province' => ['Dammam', 'Khobar', 'Dhahran', 'Al Ahsa', 'Jubail', 'Qatif', 'Hafar Al Batin'],
            'Asir' => ['Abha', 'Khamis Mushait', 'Bisha', 'Muhayil'],
            'Tabuk' => ['Tabuk', 'Duba', 'Umluj', 'Tayma'],
            'Qassim' => ['Buraidah', 'Unaizah', 'Ar Rass'],
            'Hail' => ['Hail', 'Baqaa'],
            'Northern Borders' => ['Arar', 'Rafha', 'Turaif'],
            'Jazan' => ['Jazan', 'Sabya', 'Abu Arish'],
            'Najran' => ['Najran', 'Sharurah'],
            'Al Bahah' => ['Al Bahah', 'Baljurashi'],
            'Al Jouf' => ['Sakaka', 'Qurayyat', 'Dumat Al Jandal'],
        ];

        foreach ($regions as $region => $cities) {
            $stateId = DB::table('states')
                ->where('country_id', $countryId)
                ->where('name', $region)
                ->value('id');

            if (!$stateId) {
                $stateId = DB::table('states')->insertGetId([
                    'name' => $region,
                    'country_id' => $countryId,
                ]);
            }

            foreach ($cities as $city) {
                $exists = DB::table('cities')
                    ->where('state_id', $stateId)
                    ->where('name', $city)
                    ->exists();

                if (!$exists) {
                    DB::table('cities')->insert([
                        'name' => $city,
                        'state_id' => $stateId,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('countries') || !Schema::hasTable('states') || !Schema::hasTable('cities')) {
            return;
        }

        $countryId = DB::table('countries')->where('code', 'SA')->value('id');

        if (!$countryId) {
            return;
        }

        $stateIds = DB::table('states')->where('country_id', $countryId)->pluck('id');

        DB::table('cities')->whereIn('state_id', $stateIds)->delete();
        DB::table('states')->whereIn('id', $stateIds)->delete();
        DB::table('countries')->where('id', $countryId)->delete();
    }
}
