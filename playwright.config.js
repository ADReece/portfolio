// Browser checks (accessibility). Run against a running app seeded with
// Database\Seeders\UxDemoSeeder:  APP_URL=http://127.0.0.1:8000 npm run test:a11y
import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    timeout: 60_000,
    reporter: process.env.CI ? [['github'], ['list']] : 'list',
    use: {
        baseURL: process.env.APP_URL || 'http://127.0.0.1:8000',
        trace: 'off',
        launchOptions: process.env.PW_CHROMIUM_PATH ? { executablePath: process.env.PW_CHROMIUM_PATH } : {},
    },
    projects: [
        { name: 'desktop', use: { viewport: { width: 1280, height: 900 } } },
        { name: 'phone', use: { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true } },
    ],
});
