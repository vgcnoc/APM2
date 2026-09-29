const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('BROWSER_LOG:', msg.text()));
  page.on('pageerror', err => console.log('BROWSER_ERROR:', err.message));
  
  await page.goto('http://localhost:8082/login', {waitUntil: 'networkidle0'});
  
  // Login
  await page.type('input[type="email"]', 'admin@isp.local');
  await page.type('input[type="password"]', 'password');
  await page.click('button[type="submit"]');
  
  await page.waitForNavigation({waitUntil: 'networkidle0'});
  
  console.log('URL after login:', page.url());
  
  const html = await page.content();
  if (html.includes('DATA CUSTOMERS')) {
    console.log('Sidebar rendered successfully!');
  } else {
    console.log('Sidebar is MISSING!');
  }
  
  await browser.close();
})();
