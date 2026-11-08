import { randomUUID } from 'node:crypto';

export type CategoryData = {
    name: string;
    description: string;
};

/**
 * Nama unik per panggilan agar test paralel tidak bentrok dengan aturan
 * `unique` di CategoryForm maupun dengan data demo.
 */
export function buildCategory(overrides: Partial<CategoryData> = {}): CategoryData {
    const suffix = randomUUID().slice(0, 8);

    return {
        name: `E2E Kategori ${suffix}`,
        description: `Dibuat otomatis oleh Playwright (${suffix})`,
        ...overrides,
    };
}
