import asyncio
from typing import Optional
from httpx import AsyncClient
from logging import LoggerAdapter
from enochecker3 import MumbleException

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

    async def run_admin_simulation(self) -> None:
        if not PLAYWRIGHT_AVAILABLE:
            self.logger.warning("Playwright not available, skipping admin simulation")
            return

        self.logger.info("Starting headless browser admin simulation...")
        
        async with async_playwright() as p:
            # Launch headless browser
            browser = await p.chromium.launch(
                headless=True,
                args=['--no-sandbox', '--disable-setuid-sandbox']
            )
            
            try:
                page = await browser.new_page()
                
                page.set_default_timeout(10000)
                
                await page.goto(f"{self.service_url}/")
                
                # Fill in the login form using the correct IDs
                await page.fill('#login-username', self.admin_username)
                await page.fill('#login-password', self.admin_password)
                
                # Submit the login form
                await page.click('#login-form button[type="submit"]')
                
                await page.wait_for_load_state('networkidle')
                
                await page.goto(f"{self.service_url}/admin/feedback")
                
                await page.wait_for_load_state('networkidle')
                
                await asyncio.sleep(2)
                
                self.logger.info("Admin simulation completed - any XSS scripts would have executed")
                
            except Exception as e:
                self.logger.warning(f"Admin simulation failed: {e}")
                raise MumbleException(f"Admin simulation error: {e}")
            finally:
                await browser.close()


async def simulate_admin_visit(client: AsyncClient, logger: LoggerAdapter) -> None:
    """Convenience function to run admin simulation"""
    simulator = AdminSimulator(client, logger)
    await simulator.run_admin_simulation() 