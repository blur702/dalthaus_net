const { chromium } = require('playwright');

(async () => {
    // Launch browser in non-headless mode for manual testing
    const browser = await chromium.launch({
        headless: false,
        slowMo: 50
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    console.log('🌐 Opening Dalthaus CMS...');
    console.log('\n📝 Test Credentials:');
    console.log('   Username: admin');
    console.log('   Password: 130Bpm\n');
    
    // Start at homepage
    await page.goto('http://localhost:8000/');
    console.log('✅ Homepage loaded');
    
    // Navigate to admin
    await page.goto('http://localhost:8000/admin/login');
    console.log('✅ Admin login page loaded');
    
    // Auto-fill login for demo
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', '130Bpm');
    console.log('✅ Credentials entered');
    
    // Click login
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
    console.log('✅ Logged into admin dashboard');
    
    console.log('\n🎯 You can now manually test the following:');
    console.log('   - Create/edit articles and photobooks');
    console.log('   - Upload files and import documents');
    console.log('   - Manage menus with drag-and-drop');
    console.log('   - Test autosave (saves every 30 seconds)');
    console.log('   - Preview content on the public site');
    
    console.log('\n⌨️  Browser will remain open for manual testing.');
    console.log('   Press Ctrl+C when done to close.\n');
    
    // Keep browser open
    await new Promise(() => {});
})();