import path from 'node:path';

export type Role = 'admin' | 'manager' | 'staff';

export const roles: Role[] = ['admin', 'manager', 'staff'];

/**
 * Akun demo dari UserSeeder + RoleSeeder. Password bisa dioverride lewat
 * E2E_PASSWORD bila lingkungan selain lokal memakai kredensial lain.
 */
export function accountFor(role: Role): { email: string; password: string } {
    return {
        email: process.env[`E2E_${role.toUpperCase()}_EMAIL`] ?? `${role}@demo.com`,
        password: process.env.E2E_PASSWORD ?? 'password',
    };
}

export function storageStateFor(role: Role): string {
    return path.join(import.meta.dirname, '..', '..', '.auth', `${role}.json`);
}
