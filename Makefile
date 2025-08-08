SHELL := /bin/bash

.PHONY: clean build invoke run

# Clean build artifacts
clean:
	@rm -rf .aws-sam .sam-tmp vendor composer.lock

# Build with SAM. Uses local Composer if available or Docker.
build:
	@if command -v composer >/dev/null 2>&1; then \
		composer install --no-dev --optimize-autoloader --classmap-authoritative; \
	else \
		docker run --rm -u $$(id -u):$$(id -g) -v "$$PWD":/app -w /app composer:2 install --no-dev --optimize-autoloader --classmap-authoritative; \
	fi
	sam build --template aws/template.local.yaml --cached --use-container

# Invoke locally (sample payload)
invoke: build
	sam local invoke PhpLambdaFunction \
		--template .aws-sam/build/template.yaml \
		--env-vars aws/env.json \
		--event tests/events/sample.json \
		--shutdown

# Alias for invoke
run: invoke

# SAM build target (called by sam build)
build-PhpLambdaFunction:
	cp -r vendor $(ARTIFACTS_DIR)/
	cp index.php $(ARTIFACTS_DIR)/
	@if [ -d "src" ]; then cp -r src $(ARTIFACTS_DIR)/; fi