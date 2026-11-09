import { expect, test } from '../support/fixtures';

test.describe('Kategori', () => {
    test('kategori baru dari manager bisa dicari oleh staff', async ({ createCategory, pageAs }) => {
        // Given: kategori dibuat lewat factory (dibersihkan otomatis)
        const category = await createCategory();
        const staffPage = await pageAs('staff');
        await staffPage.goto('/admin/categories');

        // When: staff mencari nama kategori; tunggu request Livewire yang dipicu pencarian
        const searchResponse = staffPage.waitForResponse(
            (response) => /\/livewire[^/]*\/update/.test(response.url()) && response.request().method() === 'POST',
        );
        await staffPage.getByRole('searchbox', { name: 'Search', exact: true }).fill(category.name);
        expect((await searchResponse).ok()).toBe(true);

        // Then: hanya kategori tersebut yang tampil
        await expect(staffPage.getByRole('cell', { name: category.name })).toBeVisible();
        await expect(staffPage.getByRole('cell', { name: category.description })).toBeVisible();
    });
});
