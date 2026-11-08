import { expect, test } from '../support/fixtures';

test.describe('Purchase order', () => {
    test('[P0] AC-10 manager mengajukan, admin menyetujui, staff menerima, dan stok bertambah sebesar jumlah PO', async ({
        pageAs,
        createCriticalProduct,
        currentStockOf,
        purchaseOrderNumberFor,
    }) => {
        // Given: supplier + produk kritis khusus test (stok 0), sesi tiga role
        const product = await createCriticalProduct();
        const managerPage = await pageAs('manager');
        const adminPage = await pageAs('admin');
        const staffPage = await pageAs('staff');

        // When: manager membuat draft dari produk kritis supplier lalu mengajukannya
        await managerPage.goto('/admin/purchase-orders/create');
        await managerPage.getByRole('combobox', { name: 'Supplier' }).click();
        await managerPage.getByRole('textbox', { name: 'Search' }).fill(product.supplierName);
        await managerPage.getByRole('option', { name: product.supplierName }).click();
        await managerPage.getByLabel('Jumlah').fill('12');
        await managerPage.getByRole('button', { name: 'Create', exact: true }).click();
        await expect(managerPage).toHaveURL(/\/admin\/purchase-orders\/\d+(\/edit)?$/);
        const purchaseOrderUrl = managerPage.url().replace(/\/edit$/, '');

        await managerPage.goto(purchaseOrderUrl);
        await managerPage.getByRole('button', { name: 'Ajukan' }).click();
        await managerPage.locator('.fi-modal-window').getByRole('button', { name: 'Ajukan' }).click();
        await expect(managerPage.getByText('Purchase order diajukan')).toBeVisible();

        // When: admin (bukan pembuat) menyetujui
        await adminPage.goto(purchaseOrderUrl);
        await adminPage.getByRole('button', { name: 'Setujui' }).click();
        await adminPage.locator('.fi-modal-window').getByRole('button', { name: 'Setujui' }).click();
        await expect(adminPage.getByText('Purchase order disetujui')).toBeVisible();

        // When: staff mencatat penerimaan
        await staffPage.goto(purchaseOrderUrl);
        await staffPage.getByRole('button', { name: 'Terima' }).click();
        await staffPage.locator('.fi-modal-window').getByRole('button', { name: 'Terima' }).click();

        // Then: PO diterima dan stok produk naik tepat sebesar jumlah item
        await expect(staffPage.getByText('Diterima', { exact: true })).toBeVisible();
        expect(currentStockOf(product.productId)).toBe(12);

        // Then: movement IN tertelusur ke nomor PO dan terlihat oleh staff
        const purchaseOrderNumber = purchaseOrderNumberFor(product.supplierId);
        await staffPage.goto('/admin/stock-movements');
        const searchResponse = staffPage.waitForResponse(
            (response) => /\/livewire[^/]*\/update/.test(response.url()) && response.request().method() === 'POST',
        );
        await staffPage.getByRole('searchbox', { name: 'Search', exact: true }).fill(product.productName);
        expect((await searchResponse).ok()).toBe(true);
        await expect(
            staffPage.getByRole('row').filter({ hasText: product.productName }).filter({ hasText: purchaseOrderNumber }),
        ).toHaveCount(1);

        // Then: harga modal tetap tersembunyi total untuk staff di detail PO
        await staffPage.goto(purchaseOrderUrl);
        await expect(staffPage.getByText('Harga per Item')).toHaveCount(0);
        await expect(staffPage.getByText('Subtotal')).toHaveCount(0);
        await expect(staffPage.getByText(/Rp\s?\d/)).toHaveCount(0);
    });
});
