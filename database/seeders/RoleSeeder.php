<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * Resource domain (master data + transaksi stok). "Role" sengaja tidak
     * dimasukkan agar hanya admin (super admin) yang bisa mengelola role.
     *
     * @var list<string>
     */
    private const DOMAIN_RESOURCES = ['Product', 'Category', 'Supplier', 'StockMovement', 'PurchaseOrder'];

    /**
     * Manager: kelola penuh data domain KECUALI hapus permanen (ForceDelete*).
     *
     * @var list<string>
     */
    private const MANAGER_ACTIONS = [
        'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
        'Restore', 'RestoreAny', 'Reorder', 'Replicate',
    ];

    /**
     * Staff Product/StockMovement: lihat + input, TANPA delete/adjustment.
     *
     * @var list<string>
     */
    private const STAFF_WRITE_ACTIONS = ['ViewAny', 'View', 'Create', 'Update'];

    /**
     * Staff untuk data master lain dan PurchaseOrder: hanya baca (PO dilihat
     * tanpa harga agar bisa mencatat penerimaan).
     *
     * @var list<string>
     */
    private const STAFF_READ_ACTIONS = ['ViewAny', 'View'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin', self::GUARD);
        $manager = Role::findOrCreate('manager', self::GUARD);
        $staff = Role::findOrCreate('staff', self::GUARD);

        // admin = super admin (intercept gate 'before'), tetap diberi semua
        // permission eksplisit agar konsisten bila intercept dimatikan.
        $admin->syncPermissions(Permission::where('guard_name', self::GUARD)->get());

        $manager->syncPermissions($this->permissionsFor(self::DOMAIN_RESOURCES, self::MANAGER_ACTIONS));

        $staffPermissions = $this->permissionsFor(['Product', 'StockMovement'], self::STAFF_WRITE_ACTIONS)
            ->merge($this->permissionsFor(['Category', 'Supplier', 'PurchaseOrder'], self::STAFF_READ_ACTIONS))
            ->unique('id');
        $staff->syncPermissions($staffPermissions);

        $this->assignRolesToDemoUsers();
    }

    /**
     * Ambil permission yang BENAR-BENAR ada untuk kombinasi resource × action.
     * Permission yang belum di-generate (mis. StockMovement sebelum m3.1)
     * otomatis dilewati, jadi seeder aman dijalankan ulang tiap modul.
     *
     * @param  list<string>  $resources
     * @param  list<string>  $actions
     * @return Collection<int, Permission>
     */
    private function permissionsFor(array $resources, array $actions): Collection
    {
        $names = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $names[] = "{$action}:{$resource}";
            }
        }

        return Permission::where('guard_name', self::GUARD)
            ->whereIn('name', $names)
            ->get();
    }

    private function assignRolesToDemoUsers(): void
    {
        $map = [
            'admin@demo.com' => 'admin',
            'manager@demo.com' => 'manager',
            'staff@demo.com' => 'staff',
        ];

        foreach ($map as $email => $role) {
            $user = User::where('email', $email)->first();
            $user?->syncRoles([$role]);
        }
    }
}
