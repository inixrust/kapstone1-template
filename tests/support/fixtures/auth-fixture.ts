import { test as base, type Page } from '@playwright/test';
import { storageStateFor, type Role } from '../helpers/accounts';

type AuthFixtures = {
    /** Buka halaman baru yang sudah login sebagai role tertentu (sesi dari auth.setup.ts). */
    pageAs: (role: Role) => Promise<Page>;
};

export const test = base.extend<AuthFixtures>({
    pageAs: async ({ browser }, use) => {
        const contexts: Awaited<ReturnType<typeof browser.newContext>>[] = [];

        await use(async (role: Role) => {
            const context = await browser.newContext({ storageState: storageStateFor(role) });
            contexts.push(context);

            return context.newPage();
        });

        await Promise.all(contexts.map((context) => context.close()));
    },
});
