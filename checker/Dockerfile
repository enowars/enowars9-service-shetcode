FROM python:3.13.5-slim-bookworm
RUN apt-get update && apt-get upgrade -y 

# Install Playwright system dependencies for Chromium
RUN apt-get install -y \
    libnss3 \
    libnspr4 \
    libdbus-1-3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libxkbcommon0 \
    libasound2 \
    && rm -rf /var/lib/apt/lists/*

# add checker user
RUN useradd -ms /bin/bash -u 1000 checker

WORKDIR /checker

COPY requirements.txt .
COPY ./src .
RUN chmod 644 ./admin_private.pem

USER checker
RUN python3 -m pip install -r requirements.txt
RUN python3 -m playwright install chromium


ENTRYPOINT [ "/home/checker/.local/bin/gunicorn", "-c", "gunicorn.conf.py", "checker:app" ]
