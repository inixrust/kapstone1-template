import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Form login Filament tidak memiliki data-testid; id field (`form.email`,
 * `form.password`) dihasilkan Filament dan stabil antar-render.
 */
export class LoginPage {
    readonly throttleNotice: Locator;

    readonly invalidCredentialsError: Locator;

    constructor(private readonly page: Page) {
        this.throttleNotice = page.getByText(/too many login attempts/i);
        this.invalidCredentialsError = page.getByText(/these credentials do not match/i);
    }

    async goto(): Promise<void> {
        await this.page.goto('/admin/login');
    }

    async login(email: string, password: string): Promise<void> {
        await this.page.locator('[id="form.email"]').fill(email);
        await this.page.locator('[id="form.password"]').fill(password);
        await this.page.getByRole('button', { name: /sign in|masuk/i }).click();
    }

    async expectLoggedIn(): Promise<void> {
        await expect(this.page.getByRole('navigation', { name: 'Sidebar navigation' }).or(this.throttleNotice)).toBeVisible();
        await this.failIfThrottled();
        await expect(this.page).toHaveURL(/\/admin\/?$/);
    }

    async expectInvalidCredentials(): Promise<void> {
        await expect(this.invalidCredentialsError.or(this.throttleNotice)).toBeVisible();
        await this.failIfThrottled();
        await expect(this.page).toHaveURL(/\/admin\/login/);
    }

    /**
     * Filament membatasi login 5 kali/menit per IP. Gagal dengan pesan jelas
     * alih-alih timeout yang menyesatkan.
     */
    private async failIfThrottled(): Promise<void> {
        if (await this.throttleNotice.isVisible()) {
            throw new Error(
                'Login Filament ter-throttle (maks. 5/menit per IP). Tunggu 1 menit atau jalankan '
                    + '`docker compose exec -T -u sail laravel.test php artisan cache:clear`, lalu ulangi.',
            );
        }
    }
}
