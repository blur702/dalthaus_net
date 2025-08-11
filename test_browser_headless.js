const { chromium } = require('playwright');

(async () => {
    // Launch browser in headless mode for screenshots
    const browser = await chromium.launch({
        headless: true
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    try {
        console.log('Loading homepage...');
        await page.goto('http://localhost:8000/', { waitUntil: 'networkidle' });
        
        // Take screenshot of homepage
        await page.screenshot({ 
            path: 'screenshots/homepage.png',
            fullPage: true 
        });
        console.log('✓ Homepage screenshot saved');
        
        // Test navigation to admin
        console.log('Navigating to admin login...');
        await page.goto('http://localhost:8000/admin/login', { waitUntil: 'networkidle' });
        await page.screenshot({ 
            path: 'screenshots/admin-login.png',
            fullPage: true 
        });
        console.log('✓ Admin login screenshot saved');
        
        // Test responsive mobile view
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('http://localhost:8000/', { waitUntil: 'networkidle' });
        await page.screenshot({ 
            path: 'screenshots/homepage-mobile.png',
            fullPage: true 
        });
        console.log('✓ Mobile homepage screenshot saved');
        
        // Test tablet view
        await page.setViewportSize({ width: 768, height: 1024 });
        await page.goto('http://localhost:8000/', { waitUntil: 'networkidle' });
        await page.screenshot({ 
            path: 'screenshots/homepage-tablet.png',
            fullPage: true 
        });
        console.log('✓ Tablet homepage screenshot saved');
        
        console.log('\n✅ All screenshots captured successfully!');
        console.log('Screenshots saved in: ' + process.cwd() + '/screenshots/');
        
    } catch (error) {
        console.error('Error:', error);
    } finally {
        await browser.close();
    }
})();