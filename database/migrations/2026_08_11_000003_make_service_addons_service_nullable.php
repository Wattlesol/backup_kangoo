<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeServiceAddonsServiceNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('service_addons')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(true);
            return;
        }

        DB::statement('ALTER TABLE service_addons MODIFY service_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        if (!Schema::hasTable('service_addons')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::table('service_addons')->whereNull('service_id')->update(['service_id' => 0]);
            $this->rebuildSqliteTable(false);
            return;
        }

        DB::statement('ALTER TABLE service_addons MODIFY service_id BIGINT UNSIGNED NOT NULL');
    }

    private function rebuildSqliteTable(bool $nullable): void
    {
        $serviceIdDefinition = $nullable ? 'INTEGER NULL' : 'INTEGER NOT NULL';

        DB::statement('PRAGMA foreign_keys=OFF');
        DB::statement('ALTER TABLE service_addons RENAME TO service_addons_old');
        DB::statement("
            CREATE TABLE service_addons (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name varchar NOT NULL,
                service_id {$serviceIdDefinition},
                price float NOT NULL DEFAULT '0',
                qty INTEGER NOT NULL DEFAULT '1',
                status INTEGER DEFAULT '1',
                deleted_at datetime,
                created_at datetime,
                updated_at datetime,
                created_by INTEGER,
                name_ar varchar
            )
        ");
        DB::statement('
            INSERT INTO service_addons (id, name, service_id, price, qty, status, deleted_at, created_at, updated_at, created_by, name_ar)
            SELECT id, name, service_id, price, qty, status, deleted_at, created_at, updated_at, created_by, name_ar
            FROM service_addons_old
        ');
        DB::statement('DROP TABLE service_addons_old');
        DB::statement('PRAGMA foreign_keys=ON');
    }
}
