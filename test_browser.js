const { chromium } = require('playwright');

(async () => {
    // Launch browser
    const browser = await chromium.launch({
        headless: false,  // Show the browser
        slowMo: 100      // Slow down actions for visibility
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    console.log('Loading homepage...');
    await page.goto('http://localhost:8000/');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    
    // Take screenshot of homepage
    await page.screenshot({ 
        path: 'screenshots/homepage.png',
        fullPage: true 
    });
    console.log('Homepage screenshot saved');
    
    // Test navigation to admin
    console.log('Navigating to admin login...');
    await page.goto('http://localhost:8000/admin/login');
    await page.screenshot({ 
        path: 'screenshots/admin-login.png',
        fullPage: true 
    });
    console.log('Admin login screenshot saved');
    
    // Test login
    console.log('Testing login...');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', '130Bpm');
    await page.click('button[type="submit"]');
    
    await page.waitForNavigation();
    await page.screenshot({ 
        path: 'screenshots/admin-dashboard.png',
        fullPage: true 
    });
    console.log('Admin dashboard screenshot saved');
    
    // View articles page
    await page.goto('http://localhost:8000/admin/articles');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ 
        path: 'screenshots/admin-articles.png',
        fullPage: true 
    });
    console.log('Articles page screenshot saved');
    
    // Go back to homepage to view public content
    await page.goto('http://localhost:8000/');
    
    // Test responsive design
    await context.setViewportSize({ width: 375, height: 667 }); // iPhone size
    await page.screenshot({ 
        path: 'screenshots/homepage-mobile.png',
        fullPage: true 
    });
    console.log('Mobile homepage screenshot saved');
    
    console.log('\nAll screenshots saved in screenshots/ directory');
    console.log('Browser will remain open for manual testing...');
    console.log('Press Ctrl+C to close');
    
    // Keep browser open for manual testing
    await new Promise(() => {});
})();