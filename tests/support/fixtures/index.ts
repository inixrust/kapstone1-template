import { mergeTests } from '@playwright/test';
import { test as authTest } from './auth-fixture';
import { test as categoryTest } from './category-fixture';
import { test as purchaseOrderTest } from './purchase-order-fixture';

/** Satu-satunya titik impor `test` untuk spec E2E. */
export const test = mergeTests(authTest, categoryTest, purchaseOrderTest);

export { expect } from '@playwright/test';
