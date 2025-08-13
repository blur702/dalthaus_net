// @ts-check
const { test, expect } = require('@playwright/test');

const BASE_URL = 'http://localhost:8000';
const ADMIN_USER = 'admin';
const ADMIN_PASS = '130Bpm';

test.describe('CMS Public Pages', () => {
  test('should load homepage', async ({ page }) => {
    await page.goto(BASE_URL);
    await expect(page).toHaveTitle(/Dalthaus/);
    
    // Check for main sections
    await expect(page.locator('.site-header')).toBeVisible();
    await expect(page.locator('.site-footer')).toBeVisible();
    
    // Check for navigation menu
    await expect(page.locator('#hamburger-menu')).toBeVisible();
  });

  test('should navigate to articles list', async ({ page }) => {
    await page.goto(`${BASE_URL}/articles`);
    
    // Check page loaded
    await expect(page.locator('main h1')).toContainText('Articles');
    
    // Check for article items
    const articles = page.locator('.article-item');
    const count = await articles.count();
    expect(count).toBeGreaterThan(0);
  });

  test('should load an article', async ({ page }) => {
    await page.goto(`${BASE_URL}/article/welcome`);
    
    // Check article loaded
    await expect(page.locator('.article-header h1')).toBeVisible();
    await expect(page.locator('.article-meta')).toBeVisible();
    await expect(page.locator('.article-content')).toBeVisible();
  });

  test('should navigate to photobooks list', async ({ page }) => {
    await page.goto(`${BASE_URL}/photobooks`);
    
    // Check page loaded
    await expect(page.locator('main h1')).toContainText('Photo Books');
    
    // Check for photobook items
    const photobooks = page.locator('.photobook-item');
    const count = await photobooks.count();
    expect(count).toBeGreaterThan(0);
  });

  test('should load a photobook', async ({ page }) => {
    await page.goto(`${BASE_URL}/photobook/storytellers-legacy`);
    
    // Check photobook loaded
    await expect(page.locator('.book-header')).toBeVisible();
    await expect(page.locator('.book-title')).toBeVisible();
    await expect(page.locator('.book-content')).toBeVisible();
  });

  test('should handle photobook pagination', async ({ page }) => {
    await page.goto(`${BASE_URL}/photobook/storytellers-legacy`);
    
    // Check if pagination exists
    const pageNav = page.locator('.page-navigation');
    const navCount = await pageNav.count();
    
    if (navCount > 0) {
      // Check page selector dropdown
      await expect(page.locator('#page-selector')).toBeVisible();
      
      // Check navigation buttons
      const nextBtn = page.locator('#next-btn-bottom');
      if (await nextBtn.isVisible()) {
        await nextBtn.click();
        await page.waitForTimeout(500); // Wait for animation
        
        // Check that page changed
        const pageNumbers = page.locator('.page-number.active');
        await expect(pageNumbers.first()).toBeVisible();
      }
    }
  });

  test('should open and close hamburger menu', async ({ page }) => {
    await page.goto(BASE_URL);
    
    const hamburger = page.locator('#hamburger-menu');
    const slideMenu = page.locator('#slide-menu');
    
    // Menu should be hidden initially
    await expect(slideMenu).not.toHaveClass(/active/);
    
    // Click hamburger to open
    await hamburger.click();
    await expect(slideMenu).toHaveClass(/active/);
    
    // Click hamburger to close
    await hamburger.click();
    await expect(slideMenu).not.toHaveClass(/active/);
  });
});

test.describe('CMS Admin Pages', () => {
  test('should show login page', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/login.php`);
    
    // Check login form elements
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should login successfully', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/login.php`);
    
    // Fill login form
    await page.fill('input[name="username"]', ADMIN_USER);
    await page.fill('input[name="password"]', ADMIN_PASS);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Should redirect to dashboard
    await page.waitForURL(`${BASE_URL}/admin/dashboard.php`);
    await expect(page.locator('h1')).toContainText('Dashboard');
  });

  test('should access admin pages after login', async ({ page }) => {
    // First login
    await page.goto(`${BASE_URL}/admin/login.php`);
    await page.fill('input[name="username"]', ADMIN_USER);
    await page.fill('input[name="password"]', ADMIN_PASS);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin/dashboard.php`);
    
    // Test articles management
    await page.goto(`${BASE_URL}/admin/articles.php`);
    await expect(page.locator('h1')).toContainText('Articles');
    await expect(page.locator('.btn-primary')).toBeVisible(); // Add Article button
    
    // Test photobooks management
    await page.goto(`${BASE_URL}/admin/photobooks.php`);
    await expect(page.locator('h1')).toContainText('Photobooks');
    await expect(page.locator('.btn-primary')).toBeVisible(); // Add Photobook button
    
    // Test menus management
    await page.goto(`${BASE_URL}/admin/menus.php`);
    await expect(page.locator('h1')).toContainText('Menu');
    
    // Test file upload
    await page.goto(`${BASE_URL}/admin/upload.php`);
    await expect(page.locator('h1')).toContainText('Upload');
    
    // Test import
    await page.goto(`${BASE_URL}/admin/import.php`);
    await expect(page.locator('h1')).toContainText('Import');
  });

  test('should handle article creation', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/admin/login.php`);
    await page.fill('input[name="username"]', ADMIN_USER);
    await page.fill('input[name="password"]', ADMIN_PASS);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin/dashboard.php`);
    
    // Go to articles page
    await page.goto(`${BASE_URL}/admin/articles.php`);
    
    // Click Add Article if form not visible
    const addBtn = page.locator('a:has-text("Add Article")');
    if (await addBtn.isVisible()) {
      await addBtn.click();
    }
    
    // Check form is visible
    const titleInput = page.locator('input[name="title"]');
    if (await titleInput.isVisible()) {
      await expect(titleInput).toBeVisible();
      await expect(page.locator('input[name="slug"]')).toBeVisible();
      await expect(page.locator('textarea[name="excerpt"]')).toBeVisible();
    }
  });

  test('should logout successfully', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/admin/login.php`);
    await page.fill('input[name="username"]', ADMIN_USER);
    await page.fill('input[name="password"]', ADMIN_PASS);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin/dashboard.php`);
    
    // Logout
    await page.goto(`${BASE_URL}/admin/logout.php`);
    
    // Should redirect to login
    await expect(page).toHaveURL(`${BASE_URL}/admin/login.php`);
  });
});

test.describe('CMS Error Handling', () => {
  test('should handle 404 for non-existent article', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/article/non-existent-article`);
    expect(response.status()).toBe(404);
  });

  test('should handle 404 for non-existent photobook', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/photobook/non-existent-book`);
    expect(response.status()).toBe(404);
  });

  test('should redirect non-logged-in users from admin pages', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/articles.php`);
    
    // Should redirect to login
    await expect(page).toHaveURL(`${BASE_URL}/admin/login.php`);
  });
});

test.describe('CMS Responsive Design', () => {
  test('should be responsive on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto(BASE_URL);
    
    // Check hamburger menu is visible
    await expect(page.locator('#hamburger-menu')).toBeVisible();
    
    // Check content adjusts
    await expect(page.locator('.container')).toBeVisible();
  });

  test('should be responsive on tablet', async ({ page }) => {
    // Set tablet viewport
    await page.setViewportSize({ width: 768, height: 1024 });
    
    await page.goto(BASE_URL);
    
    // Check layout adjusts
    await expect(page.locator('.container')).toBeVisible();
  });
});