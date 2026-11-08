import { defineConfig, devices } from '@playwright/test';

/**
 * E2E panel admin Filament. Pest tetap menangani feature test PHP;
 * Playwright hanya menguji alur browser terhadap aplikasi Sail yang sudah jalan.
 */
const baseURL = process.env.BASE_URL ?? 'http://localhost:8000';

/**
 * Sail menjalankan `artisan serve` (server PHP bawaan, satu worker), sehingga
 * beberapa browser paralel saling menunggu dan login timeout. Default 1 worker;
 * naikkan lewat E2E_WORKERS bila server mendukung request paralel.
 */
const workers = Number(process.env.E2E_WORKERS ?? 1);

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './test-results',
    fullyParallel: workers > 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers,
    timeout: 60_000,
    expect: { timeout: 10_000 },
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
        ['junit', { outputFile: 'test-results/junit.xml' }],
    ],
    use: {
        baseURL,
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
        trace: 'retain-on-failure-and-retries',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
        },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
});
