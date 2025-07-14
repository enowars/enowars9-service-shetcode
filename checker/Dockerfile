FROM python:3.13.5-slim-bookworm
RUN apt-get update && apt-get upgrade -y 

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates \
      libatk-bridge2.0-0 \
      libatk1.0-0 \
      libcups2 \
      libdbus-1-3 \
      libdrm2 \
      libgbm1 \
      libgtk-3-0 \
      libnspr4 \
      libnss3 \
      libx11-6 \
      libx11-xcb1 \
      libxcb1 \
      libxcomposite1 \
      libxcursor1 \
      libxdamage1 \
      libxext6 \
      libxfixes3 \
      libxi6 \
      libxrandr2 \
      libxrender1 \
      libxss1 \
      libxtst6 \
      libpangocairo-1.0-0 \
      libpango-1.0-0 \
      libasound2 \
      fonts-liberation \
      wget \
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
