import { expect } from '@playwright/test';
import { buildCategory, type CategoryData } from './factories/category-factory';
import { test as authTest } from './auth-fixture';

type CategoryFixtures = {
    /**
     * Buat kategori lewat UI sebagai manager; kategori dihapus otomatis
     * setelah test selesai sehingga database dev tidak tercemar.
     */
    createCategory: (overrides?: Partial<CategoryData>) => Promise<CategoryData & { editUrl: string }>;
};

export const test = authTest.extend<CategoryFixtures>({
    createCategory: async ({ pageAs }, use) => {
        const managerPage = await pageAs('manager');
        const createdEditUrls: string[] = [];

        await use(async (overrides = {}) => {
            const category = buildCategory(overrides);

            await managerPage.goto('/admin/categories/create');
            await managerPage.getByLabel('Nama Kategori').fill(category.name);
            await managerPage.getByLabel('Deskripsi').fill(category.description);
            await managerPage.getByRole('button', { name: 'Create', exact: true }).click();
            await expect(managerPage).toHaveURL(/\/admin\/categories\/\d+\/edit/);

            const editUrl = managerPage.url();
            createdEditUrls.push(editUrl);

            return { ...category, editUrl };
        });

        for (const editUrl of createdEditUrls) {
            await managerPage.goto(editUrl);
            await managerPage.getByRole('button', { name: 'Delete' }).first().click();
            await managerPage
                .locator('.fi-modal-window')
                .filter({ hasText: 'Delete Kategori' })
                .getByRole('button', { name: 'Delete' })
                .click();
            await expect(managerPage).toHaveURL(/\/admin\/categories\/?$/);
        }
    },
});
