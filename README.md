# AWS PHP Lambda SAM – Skeleton (PoC)

This project is a minimal yet practical PHP Lambda function skeleton that shows how to run PHP code on AWS Lambda using AWS SAM (Serverless Application Model) and the Bref runtime layer. It is optimized for local development and is a good starting point for real PHP-based Lambda functions.

## Key features

- ✅ AWS SAM integration: local build and invoke using Docker (SAM CLI)
- ✅ PHP 8.4 + Bref layer: official PHP runtime on Lambda
- ✅ Structured logging: Monolog + CloudWatch formatter (Docker stdout/stderr)
- ✅ Clean code organization: Composer PSR-4 autoloading
- ✅ Simple Makefile workflow: build + local run
- ✅ "Echo" handler: returns the received event (ideal for PoC)

## Why AWS SAM?

- Local execution and debugging in a Docker container (close to real runtime)
- Unified build and packaging (sam build/package/deploy)
- Infrastructure as Code (CloudFormation-based)
- Easy extensibility and integration with AWS services

## Prerequisites

- PHP 8.4+ (Composer 2.x recommended)
- Docker Desktop (required for SAM local)
- AWS SAM CLI
- Make (optional but recommended; on Windows use WSL2 or Git Bash for Makefile)

## Project structure and main files

```
aws-php-lambda-sam/
├── aws/
│   ├── template.local.yaml   # SAM template for local development
│   └── env.json              # Environment variables (for SAM local invoke)
├── src/                      # PHP classes (PSR-4 autoload)
├── tests/
│   └── events/
│       └── sample.json       # Sample event for local invoke
├── index.php                 # Lambda handler entry point
├── composer.json             # Dependencies and autoload settings
├── Makefile                  # Build and invoke commands (Bash)
└── README.md                 # This file
```

Note: The Makefile uses Bash (SHELL=/bin/bash). On Windows, use WSL2 or Git Bash for make targets.

## Dependencies (from composer.json)

- php: ^8.4
- bref/bref
- bref/monolog-bridge
- monolog/monolog
- (dev) phpunit/phpunit

Official sources:
- AWS SAM: https://docs.aws.amazon.com/serverless-application-model/
- Bref: https://bref.sh/
- Monolog: https://seldaek.github.io/monolog/
- Composer: https://getcomposer.org/

## How it works

- `aws/template.local.yaml` uses the `provided.al2023` runtime with the Bref PHP 8.4 layer (eu-central-1: `arn:aws:lambda:eu-central-1:534081306603:layer:php-83:58`).
- `index.php` returns an instance of `AwsPhpLambdaSam\LambdaHandler`, which implements the `Bref\Event\Handler` interface.
- The handler uses Monolog; logs are formatted by CloudWatchFormatter and written to STDERR (visible in the console when running SAM local).
- Environment variables (`STAGE`, `AWS_REGION`) come from `aws/env.json` during local execution.
- The response is an associative array (returned as JSON) that includes status, message, requestId, duration, and the received event (echo pattern).

## Quick start (local run)

### A) Using the Makefile (recommended on Linux/macOS/WSL/Git Bash)

```bash
# optional: clean workspace
make clean

# build (composer install + sam build in container)
make build

# invoke locally with a sample event
make run
# or:
make invoke
```

### B) Direct SAM CLI commands (Windows PowerShell example)

```powershell
# install dependencies
composer install

# build
sam build --template aws\template.local.yaml --cached --use-container

# local invoke
sam local invoke PhpLambdaFunction `
  --template .aws-sam\build\template.yaml `
  --env-vars aws\env.json `
  --event tests\events\sample.json `
  --shutdown
```

## Makefile targets

- `clean`: Remove .aws-sam, .sam-tmp, vendor, composer.lock
- `build`: Run composer install (no-dev) + `sam build` (in container)
- `invoke`: After build, run `sam local invoke` with `tests/events/sample.json`
- `run`: Alias for `invoke`
- `build-PhpLambdaFunction`: Special target used by SAM to stage artifacts

### About build-PhpLambdaFunction (important in Bref/SAM environments)

When SAM detects a Makefile, it uses the "Makefile builder" during `sam build`. For each Lambda function defined in the template, SAM will call a target named `build-<FunctionLogicalId>`. In this project the logical ID is `PhpLambdaFunction`, so SAM runs:

```
make build-PhpLambdaFunction
```

Key points:
- SAM sets the `ARTIFACTS_DIR` environment variable to a staging directory (e.g., `.aws-sam/build/PhpLambdaFunction`). Your target must place the built code there.
- Our target simply copies the necessary runtime files into `$(ARTIFACTS_DIR)`: the `vendor/` directory, `index.php`, and the `src/` directory. This is enough for a function handler using Bref.
- Composer dependencies must already be installed before this step. That’s why the top-level `build` target runs `composer install` and then calls `sam build`.
- You normally do not run `build-PhpLambdaFunction` manually; `sam build` invokes it for you.
- If your function requires extra files (e.g., configuration, additional assets, PHP INI, etc.), extend this target to copy them into `$(ARTIFACTS_DIR)`.

This behavior is standard for SAM + Makefile and is especially useful with Bref, where the Lambda runtime is provided by a layer and your artifact only needs your PHP sources plus dependencies.

## Sample event

Excerpt from `tests/events/sample.json`:

```json
{
  "requestId": "00000000-0000-0000-0000-000000000000",
  "source": "local-cli",
  "action": "process_order",
  "user": { "id": "u-12345" }
}
```

The handler echoes back the received event in the response.

## Configuration

- `aws/env.json`: local environment variables (e.g., `STAGE`, `AWS_REGION`).
- `aws/template.local.yaml`: memory (512 MB), timeout (30s), architecture (x86_64), layer ARN. Extend here with IAM permissions and additional resources as needed.

## Logging

Monolog uses the `CloudWatchFormatter` and writes to STDERR. With local execution the logs appear in your terminal; in AWS they go to CloudWatch Logs.

## Deployment (guide)

This template is for local development. For deployment, create a `template.yaml` with the required resources, then:

```bash
sam deploy --guided
# later
sam deploy
```

## Windows tips

- Use WSL2 or Git Bash for the Makefile; in PowerShell run the SAM commands directly.
- Start Docker Desktop before running `sam build`.
- If you hit file locking issues (e.g., WinError 32), try `make clean` and rebuild; consider excluding the project folder from antivirus scans.

## Extensibility

- Place your own classes under `src/` in the `AwsPhpLambdaSam\\` namespace.
- Use them inside `index.php` in the handler (add your business logic).
- For tests use the `tests/` folder (phpunit).

## License

MIT

## References

- AWS SAM Documentation: https://docs.aws.amazon.com/serverless-application-model/
- Bref: https://bref.sh/
- Bref runtimes (layers) list: https://runtimes.bref.sh/
- Monolog: https://seldaek.github.io/monolog/
- Composer: https://getcomposer.org/
- PHP: https://www.php.net/

## Testing

- Run unit tests (phpunit):

```bash
composer test
```

- Integration test (SAM local invoke): see the "Quick start" and "Makefile targets" sections; you can use `tests/events/sample.json`.

- Refresh autoload after changes:

```bash
composer dump-autoload -o
```
