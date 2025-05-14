const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/chromium',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        headless: true,
    });
    const page = await browser.newPage();

    await page.goto(`${process.env.APP_URL}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=username]', process.env.ADMIN_USER);
    await page.type('input[name=password]', process.env.ADMIN_PASS);
    await page.click('button[type=submit]');
    await page.waitForNavigation({ waitUntil: 'networkidle0' });

    await page.goto(`${process.env.APP_URL}/admin/feedback`, { waitUntil: 'networkidle0' });

    await page.waitForTimeout(2000);

    await browser.close();
})();