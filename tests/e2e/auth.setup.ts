import { existsSync } from 'node:fs';
import { test as setup } from '@playwright/test';
import { accountFor, roles, storageStateFor } from '../support/helpers/accounts';
import { LoginPage } from '../support/page-objects/login-page';

/**
 * Login Filament dibatasi 5 percobaan/menit per IP, jadi sesi tersimpan dipakai
 * ulang selama masih valid dan form login hanya diisi bila sesi kedaluwarsa.
 */
for (const role of roles) {
    setup(`login sebagai ${role}`, async ({ browser }) => {
        const storageState = storageStateFor(role);

        if (existsSync(storageState)) {
            const savedContext = await browser.newContext({ storageState });
            const savedPage = await savedContext.newPage();
            await savedPage.goto('/admin');
            const isSessionValid = !savedPage.url().includes('/admin/login');
            await savedContext.close();

            if (isSessionValid) {
                return;
            }
        }

        const context = await browser.newContext();
        const page = await context.newPage();
        const loginPage = new LoginPage(page);
        const { email, password } = accountFor(role);

        await loginPage.goto();
        await loginPage.login(email, password);
        await loginPage.expectLoggedIn();

        await context.storageState({ path: storageState });
        await context.close();
    });
}
