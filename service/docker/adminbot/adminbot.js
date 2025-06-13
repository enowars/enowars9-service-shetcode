const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/chromium',
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage'
        ],
    });

    try {
        console.log('Starting adminbot');
        const page = await browser.newPage();

        await page.goto(`${process.env.APP_URL}`, {
            waitUntil: 'networkidle0'
        });

        await page.type('input[name="username"]', process.env.ADMIN_USER);
        await page.type('input[name="password"]', process.env.ADMIN_PASS);
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0' })
        ]);

        await page.goto(`${process.env.APP_URL}/admin/feedback`, {
            waitUntil: 'networkidle0'
        });

        await new Promise(r => setTimeout(r, 2000));

        page.on('console', msg => {
            console.log(`PAGE LOG: ${msg.text()}`);
        });

        await page.evaluate(() => {
            Array.from(document.querySelectorAll('script'))
                .forEach(old => {
                    const s = document.createElement('script');
                    if (old.src) {
                        s.src = old.src;
                        s.async = false;
                    } else {
                        s.textContent = old.textContent;
                    }
                    document.head.appendChild(s);
                });
        });
        await new Promise(r => setTimeout(r, 2000));
    } catch (err) {
        console.error('Adminbot error:', err);
    } finally {
        await browser.close();
    }
})();
