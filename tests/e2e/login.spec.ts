import { accountFor, roles } from '../support/helpers/accounts';
import { expect, test } from '../support/fixtures';
import { LoginPage } from '../support/page-objects/login-page';

test.describe('Login panel admin', () => {
    test('akun demo bisa login lewat form dan diarahkan ke dashboard', async ({ page }) => {
        // Given: halaman login
        const loginPage = new LoginPage(page);
        const { email, password } = accountFor('staff');
        await loginPage.goto();

        // When: login dengan kredensial demo
        await loginPage.login(email, password);

        // Then: diarahkan ke dashboard panel
        await loginPage.expectLoggedIn();
        await expect(page.getByRole('navigation').getByText('Produk')).toBeVisible();
    });

    test('password salah ditolak dan tetap di halaman login', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();

        await loginPage.login(accountFor('staff').email, 'password-salah');

        await loginPage.expectInvalidCredentials();
    });

    for (const role of roles) {
        test(`sesi ${role} membuka dashboard tanpa login ulang`, async ({ pageAs }) => {
            const rolePage = await pageAs(role);

            await rolePage.goto('/admin');

            await expect(rolePage).toHaveURL(/\/admin\/?$/);
            await expect(rolePage.getByRole('navigation').getByText('Produk')).toBeVisible();
        });
    }
});
