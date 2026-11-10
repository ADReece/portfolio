// Automated accessibility checks (axe-core, WCAG 2.2 A/AA) on the pages
// customers and their clients use most. Needs UxDemoSeeder data.
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

async function expectNoViolations(page, name) {
    const results = await new AxeBuilder({ page })
        .withTags(WCAG)
        // Third-party card field iframe (Stripe) is outside our control.
        .exclude('#card-element')
        .analyze();
    const summary = results.violations.map(v => `${v.id} (${v.impact}): ${v.help} → ${v.nodes.slice(0, 3).map(n => n.target.join(' ')).join(' | ')}`);
    expect(summary, `Accessibility problems on ${name}`).toEqual([]);
}

async function logIn(page) {
    await page.goto('/login');
    await page.fill('#email', 'ava@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type=submit]');
    await page.waitForURL(/dashboard/);
}

const publicPages = [
    ['home', '/'],
    ['pricing', '/pricing'],
    ['log in', '/login'],
    ['sign up', '/register'],
    ['portfolio', '/@ava'],
    ['public collections', '/@ava/collections'],
];

for (const [name, path] of publicPages) {
    test(`public: ${name}`, async ({ page }) => {
        await page.goto(path);
        await expectNoViolations(page, name);
    });
}

test('client: private gallery password page', async ({ page }) => {
    await page.goto('/@ava/collections');
    await page.goto('/@ava');
    // Find the private collection's URL via the photographer's view of it.
    await logIn(page);
    await page.goto('/collections?show=private');
    await page.locator("main ul[role=list] a").first().click();
    const link = await page.locator('#collection-link').inputValue();
    await page.context().clearCookies();
    await page.goto(link);
    await expectNoViolations(page, 'gallery password page');
});

const appPages = [
    ['dashboard', '/dashboard'],
    ['collections', '/collections'],
    ['new collection', '/collections/create'],
    ['settings', '/profile'],
    ['customize', '/profile/customize'],
    ['print products', '/profile/print-products'],
    ['plan & billing', '/pricing'],
    ['payouts', '/connect/stripe'],
    ['print templates', '/templates'],
];

for (const [name, path] of appPages) {
    test(`app: ${name}`, async ({ page }) => {
        await logIn(page);
        await page.goto(path);
        await expectNoViolations(page, name);
    });
}

test('app: collection workspace', async ({ page }) => {
    await logIn(page);
    await page.goto('/collections?show=published');
    await page.locator("main ul[role=list] a").first().click();
    await page.waitForURL(/edit$/);
    await expectNoViolations(page, 'collection workspace');
});
