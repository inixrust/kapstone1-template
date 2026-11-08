import { expect, test } from '../support/fixtures';

test.describe('Otorisasi per role', () => {
    test('staff tidak melihat kolom Harga Modal, manager melihatnya', async ({ pageAs }) => {
        // Given: sesi staff dan manager
        const staffPage = await pageAs('staff');
        const managerPage = await pageAs('manager');

        // When: keduanya membuka daftar produk
        await staffPage.goto('/admin/products');
        await managerPage.goto('/admin/products');

        // Then: harga modal tersembunyi total untuk staff
        await expect(managerPage.getByRole('columnheader', { name: 'Harga Jual' })).toBeVisible();
        await expect(managerPage.getByRole('columnheader', { name: 'Harga Modal' })).toBeVisible();
        await expect(staffPage.getByRole('columnheader', { name: 'Harga Jual' })).toBeVisible();
        await expect(staffPage.getByRole('columnheader', { name: 'Harga Modal' })).toHaveCount(0);
    });

    test('staff hanya boleh membaca kategori (create → 403)', async ({ pageAs }) => {
        const staffPage = await pageAs('staff');

        const response = await staffPage.goto('/admin/categories/create');

        expect(response?.status()).toBe(403);
    });

    test('tamu diarahkan ke halaman login', async ({ page }) => {
        await page.goto('/admin/products');

        await expect(page).toHaveURL(/\/admin\/login/);
    });
});
