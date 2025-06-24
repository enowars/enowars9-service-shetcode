import asyncio
from typing import Optional
from httpx import AsyncClient
from logging import LoggerAdapter
from enochecker3 import MumbleException
from message_generator import generate_admin_message

try:
    from playwright.async_api import async_playwright
    PLAYWRIGHT_AVAILABLE = True
except ImportError:
    PLAYWRIGHT_AVAILABLE = False


class AdminSimulator:
    def __init__(self, client: AsyncClient, logger: LoggerAdapter):
        self.client = client
        self.logger = logger
        self.admin_password = "]!V$JuOzx@fi%pvG,lF!"
        self.admin_username = "admin"
        self.service_url = str(client.base_url)

    def validate(self) -> None:
        if not PLAYWRIGHT_AVAILABLE:
            self.logger.warning("Playwright not available, skipping admin simulation")
            return
        
        self.logger.info("Starting headless browser admin simulation...")

    async def load_feedback_page(self) -> None:
        self.validate()
        
        async with async_playwright() as p:
            browser = await p.chromium.launch(
                headless=True,
                args=['--no-sandbox', '--disable-setuid-sandbox']
            )
            
            try:
                page = await browser.new_page()
                
                page.set_default_timeout(10000)
                
                await page.goto(f"{self.service_url}/")
                
                await page.fill('#login-username', self.admin_username)
                await page.fill('#login-password', self.admin_password)
                
                await page.click('#login-form button[type="submit"]')
                
                await page.wait_for_load_state('networkidle')
                
                await page.goto(f"{self.service_url}/admin/feedback")
                
                await page.wait_for_load_state('networkidle')

                current_url = page.url
                if "/admin/feedback" not in current_url:
                    raise MumbleException("Admin was redirected to problems page - service unavailable")
                
                await asyncio.sleep(2)
                
                self.logger.info("Admin simulation completed - any XSS scripts would have executed")
                
            except Exception as e:
                self.logger.warning(f"Admin simulation failed: {e}")
                raise MumbleException(f"Admin simulation error: {e}")
            finally:
                await browser.close()

    async def post_new_message(self) -> None:
        self.validate()

        message_data = generate_admin_message()
        message_text = message_data["message"]
        message_year = message_data["year"]
        
        self.logger.info(f"Posting time traveller message from {message_year}: {message_text[:50]}...")

        async with async_playwright() as p:
            browser = await p.chromium.launch(
                headless=True,
                args=['--no-sandbox', '--disable-setuid-sandbox']
            )
            
            try:
                page = await browser.new_page()
                
                page.set_default_timeout(10000)
                
                await page.goto(f"{self.service_url}/")
                
                await page.fill('#login-username', self.admin_username)
                await page.fill('#login-password', self.admin_password)
                
                await page.click('#login-form button[type="submit"]')
                
                await page.wait_for_load_state('networkidle')
                
                await page.goto(f"{self.service_url}/admin/message")
                
                await page.wait_for_load_state('networkidle')

                self.logger.info(f"Admin message page URL: {page.url}")
                
                current_url = page.url
                if "/admin/message" not in current_url:
                    raise MumbleException("Admin was redirected to problems page - service unavailable")
                
                await page.fill('#year', str(message_year))
                await page.fill('#message', message_text)
                
                await page.click('button[type="submit"]')
                
                await page.wait_for_load_state('networkidle')
                
                await asyncio.sleep(2)
                
                self.logger.info(f"Admin message posted successfully from year {message_year}")
                
            except Exception as e:
                self.logger.warning(f"Admin message posting failed: {e}")
                raise MumbleException(f"Admin message posting error: {e}")
            finally:
                await browser.close()


