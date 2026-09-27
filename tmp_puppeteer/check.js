const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({ 
    headless: 'new'
  });
  const page = await browser.newPage();

  // Tangkap error dari page
  page.on('pageerror', error => {
    console.error('=== PAGE ERROR ===');
    console.error(error.message);
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      console.error('=== CONSOLE ERROR ===');
      console.error(msg.text());
    }
  });

  // Karena ini butuh login, mari kita login dulu
  await page.goto('http://bill.viruzs.my.id/login');
  await page.type('input[type="email"]', 'admin@isp.local');
  await page.type('input[type="password"]', 'password');
  await page.click('button[type="submit"]');

  await page.waitForNavigation();

  console.log("Login successful, navigating to ODCs...");

  await page.goto('http://bill.viruzs.my.id/odcs');
  
  // Tunggu sejenak barangkali ada Vue crash
  await new Promise(r => setTimeout(r, 2000));
  
  await browser.close();
})();
