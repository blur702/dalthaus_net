const { test, expect } = require('@playwright/test');

test.describe('Authentication', () => {
    test('Admin login with default credentials', async ({ page }) => {
        await page.goto('/admin/login');
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', '130Bpm');
        await page.click('button[type="submit"]');
        
        await expect(page).toHaveURL('/admin');
        await expect(page.locator('.password-rotation-prompt')).toBeVisible();
    });
    
    test('Failed login shows error', async ({ page }) => {
        await page.goto('/admin/login');
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'wrong');
        await page.click('button[type="submit"]');
        
        await expect(page.locator('.error-message')).toBeVisible();
    });
    
    test('Logout redirects to login', async ({ page }) => {
        // Login first
        await page.goto('/admin/login');
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', '130Bpm');
        await page.click('button[type="submit"]');
        
        // Logout
        await page.goto('/admin/logout');
        await expect(page).toHaveURL('/admin/login');
    });
    
    test('CSRF token present in forms', async ({ page }) => {
        await page.goto('/admin/login');
        const csrfToken = await page.locator('input[name="csrf_token"]').getAttribute('value');
        expect(csrfToken).toBeTruthy();
    });
});