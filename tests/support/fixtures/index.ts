import { mergeTests } from '@playwright/test';
import { test as authTest } from './auth-fixture';
import { test as categoryTest } from './category-fixture';

/** Satu-satunya titik impor `test` untuk spec E2E. */
export const test = mergeTests(authTest, categoryTest);

export { expect } from '@playwright/test';
