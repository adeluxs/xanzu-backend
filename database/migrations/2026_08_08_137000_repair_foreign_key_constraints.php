<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add imported-schema foreign keys only after every referenced table exists.
     *
     * This also repairs a table left behind by a previously failed ALTER TABLE:
     * each operation checks the table, columns, existing constraint and orphaned
     * rows before changing the schema.
     */
    public function up(): void
    {
        $this->addForeignKey(
            'model_has_permissions',
            'model_has_permissions_permission_id_foreign',
            'permission_id',
            'permissions',
            'CASCADE'
        );
        $this->addForeignKey(
            'model_has_roles',
            'model_has_roles_role_id_foreign',
            'role_id',
            'roles',
            'CASCADE'
        );
        $this->addForeignKey(
            'role_has_permissions',
            'role_has_permissions_permission_id_foreign',
            'permission_id',
            'permissions',
            'CASCADE'
        );
        $this->addForeignKey(
            'role_has_permissions',
            'role_has_permissions_role_id_foreign',
            'role_id',
            'roles',
            'CASCADE'
        );
        $this->addForeignKey(
            'card_applications',
            'fk_card_applications_user_id',
            'user_id',
            'users',
            'CASCADE'
        );
        $this->addForeignKey(
            'orders',
            'orders_courier_partner_id_foreign',
            'courier_partner_id',
            'courier_partners',
            'SET NULL',
            'CASCADE'
        );
        $this->addForeignKey(
            'bnpl_checkout_sessions',
            'bnpl_checkout_sessions_order_id_foreign',
            'order_id',
            'orders',
            'SET NULL'
        );
    }

    public function down(): void
    {
        foreach ([
            ['bnpl_checkout_sessions', 'bnpl_checkout_sessions_order_id_foreign'],
            ['orders', 'orders_courier_partner_id_foreign'],
            ['card_applications', 'fk_card_applications_user_id'],
            ['role_has_permissions', 'role_has_permissions_role_id_foreign'],
            ['role_has_permissions', 'role_has_permissions_permission_id_foreign'],
            ['model_has_roles', 'model_has_roles_role_id_foreign'],
            ['model_has_permissions', 'model_has_permissions_permission_id_foreign'],
        ] as [$table, $constraint]) {
            if ($this->foreignKeyExists($table, $constraint)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            }
        }
    }

    private function addForeignKey(
        string $table,
        string $constraint,
        string $column,
        string $referencedTable,
        string $onDelete,
        ?string $onUpdate = null
    ): void {
        if (! Schema::hasTable($table)
            || ! Schema::hasTable($referencedTable)
            || ! Schema::hasColumn($table, $column)
            || ! Schema::hasColumn($referencedTable, 'id')
            || $this->foreignKeyExists($table, $constraint)) {
            return;
        }

        // Preserve existing data: do not delete orphaned pivot or relation rows
        // merely to make a constraint installable.
        $hasOrphans = DB::table("{$table} as child")
            ->leftJoin("{$referencedTable} as parent", "child.{$column}", '=', 'parent.id')
            ->whereNotNull("child.{$column}")
            ->whereNull('parent.id')
            ->exists();

        if ($hasOrphans) {
            return;
        }

        $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` "
            ."FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}` (`id`) "
            ."ON DELETE {$onDelete}";

        if ($onUpdate !== null) {
            $sql .= " ON UPDATE {$onUpdate}";
        }

        DB::statement($sql);
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
