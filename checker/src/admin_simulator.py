import asyncio
from typing import Optional
from httpx import AsyncClient
from logging import LoggerAdapter
from enochecker3 import MumbleException
from message_generator import generate_admin_message
import base64
import os
import re

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

    async def _solve_admin_challenge(self, encrypted_b64: str):
        if not CRYPTO_AVAILABLE:
            raise MumbleException("cryptography package missing for admin challenge")

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

        return decrypted_text


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
                
                await page.wait_for_url(f"{self.service_url}/admin-challenge")

                challenge_form = page.locator('#admin-challenge-form')
                if await challenge_form.count() == 0:
                    return
                encrypted_b64 = (await page.inner_text('pre')).strip()

                decrypted_text = await self._solve_admin_challenge(encrypted_b64)

                await page.fill('#decrypted_challenge', decrypted_text)
                await page.click('#admin-challenge-form button[type="submit"]')
                await page.wait_for_url(f"{self.service_url}/problems", timeout=10_000)
                
                await page.goto(f"{self.service_url}/admin/feedback")
                
                await page.wait_for_url(f"{self.service_url}/admin/feedback", timeout=2000)

                if "/admin/feedback" not in page.url:
                    raise MumbleException(f"Admin was redirected to problems page - service unavailable")
                
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

        response = await self.client.post(
            "/login",
            headers={"Accept": "application/json"},
            data={
                "username": self.admin_username,
                "password": self.admin_password
            }
        )
        
        if response.status_code not in [200, 201, 302]:
            raise MumbleException("Failed to login as admin")

        response = await self.client.get("/admin-challenge")
        if response.status_code != 200:
            raise MumbleException("Failed to get admin challenge")

        match = re.search(r'<pre[^>]*>(.*?)</pre>', response.text, re.DOTALL)
        if not match:
            raise MumbleException("Could not find encrypted challenge on page")
        
        encrypted_b64 = match.group(1).strip()
        
        decrypted_text = await self._solve_admin_challenge(encrypted_b64)

        response = await self.client.post(
            "/admin-challenge",
            headers={"Accept": "application/json"},
            data={"decrypted_challenge": decrypted_text}
        )

        if response.status_code not in [200, 201, 302]:
            raise MumbleException(f"Failed to submit admin challenge solution: {response.text}, {response.status_code}")

        response = await self.client.post(
            "/admin/message",
            data={
                "year": str(message_year),
                "message": message_text
            }
        )

        if response.status_code not in [200, 201, 302]:
            self.logger.warning(f"Admin message posting failed with status {response.status_code}")
            raise MumbleException(f"Failed to post admin message: HTTP {response.status_code}")

        self.logger.info(f"Admin message posted successfully from year {message_year}")


