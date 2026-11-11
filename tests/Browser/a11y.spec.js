// Automated accessibility checks (axe-core, WCAG 2.2 A/AA) on the pages
// customers and their clients use most. Needs UxDemoSeeder data.
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

// Let the page settle before measuring: the portfolio grid places photos
// after their images load and animates them in, and axe's target-size rule
// sees overlapping links if it runs mid-animation.
async function settle(page) {
    await page.waitForLoadState('networkidle');
    if (await page.locator('#masonry').count()) {
        await page.waitForSelector('#masonry[data-masonry-initialized="true"]');
        // Photos get .loaded one by one (50ms apart), which starts their fade-in.
        await page.waitForFunction(() => [...document.querySelectorAll('#masonry .masonry-item')].every(i => i.classList.contains('loaded')));
    }
    await page.evaluate(() => Promise.all(document.getAnimations().map(a => a.finished.catch(() => {}))));
}

async function expectNoViolations(page, name) {
    await settle(page);
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
    ['sample portfolio', '/examples/weddings'],
    ['photo credits', '/photo-credits'],
];

test('public: home photo viewer and private gallery demo', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Open photo 1 of 9' }).click();
    await expect(page.getByRole('dialog', { name: 'Photo viewer' })).toBeVisible();
    await expectNoViolations(page, 'home photo viewer');
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog', { name: 'Photo viewer' })).toBeHidden();

    await page.getByRole('button', { name: 'Open gallery' }).click();
    await expect(page.getByRole('button', { name: 'Favourite photo 1' })).toBeVisible();
    await expectNoViolations(page, 'home private gallery demo');
});

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

test('app: admin edit print product', async ({ page }) => {
    await logIn(page);
    await page.goto('/admin/print-catalogue');
    // Sizes are grouped by product and collapsed: open the group first.
    await page.getByRole('button', { name: /^Classic frames with mount GLOBAL-CFPM/ }).click();
    await expectNoViolations(page, 'print catalogue with a group open');
    const href = await page.getByRole('link', { name: 'Edit Classic framed print 16×20' }).getAttribute('href');
    await page.goto(href);
    await expect(page.getByRole('heading', { name: 'Classic framed print 16×20' })).toBeVisible();
    await expectNoViolations(page, 'edit print product');
});

const appPages = [
    ['dashboard', '/dashboard'],
    ['collections', '/collections'],
    ['new collection', '/collections/create'],
    ['settings', '/profile'],
    ['customize', '/profile/customize'],
    ['print products', '/profile/print-products'],
    ['photo downloads', '/profile/downloads'],
    ['plan & billing', '/pricing'],
    ['payouts', '/connect/stripe'],
    ['print templates', '/templates'],
    ['admin: print catalogue', '/admin/print-catalogue'],
    ['admin: add print product', '/admin/print-catalogue/create'],
    ['your domain', '/settings/domain'],
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
