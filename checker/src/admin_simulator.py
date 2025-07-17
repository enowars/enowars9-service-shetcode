import asyncio
from typing import Optional
from httpx import AsyncClient
from logging import LoggerAdapter
from enochecker3 import MumbleException
from message_generator import generate_admin_message
import base64
import os
import re
import os
from playwright.async_api import async_playwright, Playwright, Browser, BrowserContext

_browsers: dict[int, dict[str, any]] = {}


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

    async def _solve_admin_challenge(self, encrypted_b64: str):
        if not CRYPTO_AVAILABLE:
            raise MumbleException("Cryptography package missing for admin challenge")

        try:
            ciphertext = base64.b64decode(encrypted_b64)
        except Exception as e:
            raise MumbleException(f"Invalid base64 in challenge")

        pem_path = os.path.join(os.path.dirname(__file__), 'admin_private.pem')
        try:
            with open(pem_path, 'rb') as f:
                private_key = serialization.load_pem_private_key(f.read(), password=None)
        except FileNotFoundError:
            raise MumbleException("checker's side (1)")
        except Exception as e:
            raise MumbleException(f"checker's side (2)")

        try:
            plaintext = private_key.decrypt(
                ciphertext,
                padding.PKCS1v15()
            )
            decrypted_text = plaintext.decode()
        except Exception as e:
            raise MumbleException(f"checker's side (3)")

        return decrypted_text


    async def load_feedback_page(self) -> None:
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
            raise MumbleException("Failed to login as admin")

        match = re.search(r'<pre[^>]*>(.*?)</pre>', response.text, re.DOTALL)
        if not match:
            raise MumbleException("Could not find encrypted challenge on page (please wrap it in <pre> tags)")
        
        encrypted_b64 = match.group(1).strip()
        
        decrypted_text = await self._solve_admin_challenge(encrypted_b64)

        response = await self.client.post(
            "/admin-challenge",
            headers={"Accept": "application/json"},
            data={"decrypted_challenge": decrypted_text}
        )

        if response.status_code not in [200, 201, 302]:
            raise MumbleException(f"Failed to login as admin")
        
        cookies = []
        for ck in self.client.cookies.jar:
            cookies.append({
                'name': ck.name,
                'value': ck.value,
                'domain': ck.domain,
                'path': ck.path,
                'httpOnly': ck.secure,
                'secure': ck.secure
            })
        
        try:
            pid = os.getpid()
            entry = _browsers.get(pid)
            if not entry or not entry["browser"].is_connected():
                if entry:
                    await entry["browser"].close()
                    await entry["playwright"].stop()

                p: Playwright = await async_playwright().start()
                b: Browser = await p.chromium.launch(
                    headless=True,
                    args=["--no-sandbox", "--disable-setuid-sandbox"]
                )
                _browsers[pid] = {"playwright": p, "browser": b}
            browser = _browsers[pid]["browser"]
        except Exception as e:
            if browser:
                await browser.close()
            if _browsers[pid]["playwright"]:
                await _browsers[pid]["playwright"].stop()
            if pid in _browsers:
                _browsers.pop(pid, None)
            raise MumbleException(f"checker's side (4)")

        try:
            context: BrowserContext = await browser.new_context()
            await context.route("**/*", lambda route, request: 
                route.abort() if request.resource_type in ("image", "stylesheet", "font") 
                else route.continue_()
            )
            await context.add_cookies(cookies)
            page    = await context.new_page()
            page.set_default_timeout(10_000)
            await page.goto(f"{self.service_url}/admin/feedback")
            await page.wait_for_url(f"{self.service_url}/admin/feedback", timeout=2000)

            if "/admin/feedback" not in page.url:
                raise MumbleException(f"Admin was redirected to problems page - service unavailable")
        except Exception as e:
            if context:
                await context.close()
            if browser:
                await browser.close()
            if _browsers[pid]["playwright"]:
                await _browsers[pid]["playwright"].stop()
            if pid in _browsers:
                _browsers.pop(pid, None)
            raise MumbleException(f"checker's side (5)")
        else:
            await context.close()

    async def post_new_message(self) -> None:
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


