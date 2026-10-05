const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  page.on('response', response => {
    if (!response.ok()) console.log('NETWORK ERROR:', response.status(), response.url());
  });

  console.log("Navigating...");
  await page.goto('https://bill.viruzs.my.id/login', { waitUntil: 'networkidle0' });
  console.log("Done.");
  await browser.close();
})();
