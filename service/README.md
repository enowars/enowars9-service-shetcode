# ShetCode Service

A web-based code challenge platform for CTF competitions.

## Secure Deployment for CTF

Follow these steps to securely deploy the service on a vulnbox:

1. Clone the repository:
   ```bash
   git clone <repository-url> shetcode
   cd shetcode
   ```

2. Set secure random credentials (optional, service will use defaults if not set):
   ```bash
   # Generate a random password and app secret
   export POSTGRES_PASSWORD=$(openssl rand -hex 16)
   export APP_SECRET=$(openssl rand -hex 32)
   
   # Or set them manually
   # export POSTGRES_PASSWORD=your_secure_password
   # export APP_SECRET=your_secure_secret
   ```

3. Start the service:
   ```bash
   docker compose up -d
   ```

4. The service will be available at http://localhost:8055

## Security Notes

- The database is not exposed outside of the Docker network
- Default database credentials are used if not specified at runtime
- For production use, always set secure credentials via environment variables
- The `.env` file should never be committed to version control

## Service Structure

- PHP-based web application using Symfony framework
- PostgreSQL database for storage
- Nginx web server with PHP-FPM

## Development Setup

1. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

2. Customize settings in `.env` as needed

3. Start the development environment:
   ```bash
   docker compose up -d
   ```
