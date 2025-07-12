import asyncio
from typing import Optional
from httpx import AsyncClient
from logging import LoggerAdapter
from enochecker3 import MumbleException
from message_generator import generate_admin_message
import base64
import os

try:
    from playwright.async_api import async_playwright
    PLAYWRIGHT_AVAILABLE = True
except ImportError:
    PLAYWRIGHT_AVAILABLE = False

try:
    from cryptography.hazmat.primitives import serialization
    from cryptography.hazmat.primitives.asymmetric import padding
    CRYPTO_AVAILABLE = True
except ImportError:
    CRYPTO_AVAILABLE = False


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

    async def _solve_admin_challenge(self, page):
        challenge_form = page.locator('#admin-challenge-form')
        if await challenge_form.count() == 0:
            return

        if not CRYPTO_AVAILABLE:
            raise MumbleException("cryptography package missing for admin challenge")

        encrypted_b64 = (await page.inner_text('pre')).strip()

        try:
            ciphertext = base64.b64decode(encrypted_b64)
        except Exception as e:
            raise MumbleException(f"Invalid base64 in challenge: {e}")

        pem_path = os.path.join(os.path.dirname(__file__), 'admin_private.pem')
        try:
            with open(pem_path, 'rb') as f:
                private_key = serialization.load_pem_private_key(f.read(), password=None)
        except FileNotFoundError:
            raise MumbleException("admin_private.pem not found for admin challenge")
        except Exception as e:
            raise MumbleException(f"Unable to load admin private key: {e}")

        try:
            plaintext = private_key.decrypt(
                ciphertext,
                padding.PKCS1v15()
            )
            decrypted_text = plaintext.decode()
        except Exception as e:
            raise MumbleException(f"RSA decryption failed: {e}")

        await page.fill('#decrypted_challenge', decrypted_text)
        await page.click('#admin-challenge-form button[type="submit"]')
        await page.wait_for_url(f"{self.service_url}/problems", timeout=10_000)


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

                await self._solve_admin_challenge(page)
                
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

                await self._solve_admin_challenge(page)
                
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


