# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

**mcvs-php-action** is a GitHub composite action and reusable Taskfile that standardizes quality checks for PHP projects. It provides:

- A GitHub Action ([action.yml](action.yml)) for CI/CD pipelines
- A remote Taskfile ([build/task.yml](build/task.yml)) for local development and CI automation

The action orchestrates: PHP version installation (from composer.json), composer validation, security scanning (composer audit, osv-scanner, Grype), linting (PHP-CS-Fixer, PHP_CodeSniffer), static analysis (PHPStan), mess detection (PHPMD), refactoring checks (Rector), unit/integration/component/e2e tests, code coverage enforcement, and optional PHAR releases.

It is the PHP counterpart of [mcvs-golang-action](https://github.com/schubergphilis/mcvs-golang-action) and deliberately mirrors its layout, naming and workflows.

## Architecture

### Key Components

1. **action.yml** - Composite GitHub Action definition

   - Defines all inputs (testing-type, php-version-file, release configs, etc.)
   - Derives the PHP version from `composer.json` and installs it via `shivammathur/setup-php`
   - Installs the Task runner through the taskfile.dev install script, since there is no `go install` in a PHP toolchain
   - Conditionally executes different testing/security/build workflows based on `testing-type` input
   - Supports these testing types: `component`, `composer-validate`, `coverage`, `e2e`, `integration`, `lint`, `mess-detection`, `rector`, `security-composer-packages`, `security-grype`, `static-analysis`, `unit`

2. **build/task.yml** - Reusable Taskfile

   - Contains all task definitions that the action executes
   - Designed to be included remotely by other projects: `{{.REMOTE_URL}}/{{.REMOTE_URL_REPO}}/{{.REMOTE_URL_REF}}/build/task.yml`
   - Defines versions for all tools (phpstan, php-cs-fixer, phpunit, etc.)
   - Provides both CI-specific tasks (`test-cicd`, `coverage`) and development tasks (`test`, `lint`, `format`)

3. **scripts/package-version-updater.sh** - Automation
   - Periodically updates pinned tool versions in build/task.yml
   - Opens PRs with version updates using gh CLI
   - Resolves versions from GitHub releases, except for phpunit, whose PHAR is published outside of the GitHub releases and is therefore resolved from packagist

### Tool Installation Strategy

PHP has no `go install` equivalent, so every tool is downloaded as a pinned PHAR (or static binary) into `${MCVS_PHP_ACTION_BIN:-$HOME/.mcvs-php-action/bin}` by the internal `download-phar` task. This keeps the project its own composer dependencies untouched.

Two exceptions:

- **PHPUnit** resolves to `vendor/bin/phpunit` when present, because tests must run against the version that the project its dev dependencies expect. The pinned PHAR is a fallback.
- **Rector** has no official PHAR and therefore always resolves to `vendor/bin/rector`. The task fails with an actionable message when it is absent.

### Testing Architecture

Go build tags have no PHP equivalent, so tests are organized using PHPUnit testsuites, defined in `phpunit.xml.dist`:

- **`unit`** - Unit tests (`tests/Unit`)
- **`integration`** - Integration tests requiring external services (`tests/Integration`)
- **`component`** - Component tests (`tests/Component`)
- **`e2e`** - End-to-end tests (`tests/E2E`)

The `TEST_GROUPS` variable maps onto the PHPUnit `--group` option for a further narrowing. CI runs use `--testdox`, which prints every test that has been run for the testing type at hand.

Timeouts are enforced by wrapping the PHPUnit invocation in `timeout` (or `gtimeout` on macOS), since PHPUnit has no global timeout option.

### Coverage

Coverage requires a driver: `pcov` (default, fast) or `xdebug`, configured through the `coverage-driver` input. The `coverage` task writes a clover report to `build/coverage/clover.xml` and derives the percentage from its `statements`/`coveredstatements` metrics. As in the Golang action, `CODE_COVERAGE_STRICT` (default `true`) also fails the build when the actual coverage *exceeds* the expected coverage, forcing the threshold to be raised.

### Security Scanning Flow

1. **composer audit** - Packagist security advisories, run against the `composer.lock`

2. **osv-scanner** - The OSV database, run against the `composer.lock`

   - Configured via `osv-scanner.toml` (see [osv-scanner.toml.example](osv-scanner.toml.example))
   - Allows temporary ignores (max 1 month) via `IgnoredVulns` with expiration dates
   - See [docs/osv-scanner.md](docs/osv-scanner.md) for detailed usage

3. **Grype** - Optional additional vulnerability scanning via Anchore

   - Triggered when `testing-type: security-grype`
   - Severity cutoff: HIGH or above

Both lock file scanners fail when the `composer.lock` is missing and skip when it holds no packages.

## Common Development Commands

### Using the Remote Taskfile (in consuming projects)

Set up `Taskfile.yml` in your project:

```yaml
version: 3
vars:
  REMOTE_URL: https://raw.githubusercontent.com
  REMOTE_URL_REF: v1.0.0 # Use latest stable version
  REMOTE_URL_REPO: schubergphilis/mcvs-php-action
includes:
  remote: >-
    {{.REMOTE_URL}}/{{.REMOTE_URL_REPO}}/{{.REMOTE_URL_REF}}/build/task.yml
```

Then run tasks with:

```bash
# Required: enable experimental remote taskfiles support
export TASK_X_REMOTE_TASKFILES=1

# Install the composer dependencies
task remote:composer-install --yes

# Run unit tests
task remote:test --yes

# Run integration tests
task remote:test-integration --yes

# Run component tests
task remote:test-component --yes

# Run linting
task remote:lint --yes

# Run static analysis
task remote:phpstan --yes

# Run code coverage
task remote:coverage --yes

# Run security scanning
task remote:composer-audit --yes
task remote:osv-scanner --yes

# Automatically fix linting issues
task remote:fix-linting-issues --yes

# List all available tasks
task --list-all
```

### Fixing Linting Issues

The `fix-linting-issues` task automatically fixes common linting problems:

```bash
task remote:fix-linting-issues --yes
```

This task uses:

- **PHP-CS-Fixer** - Applies the rules of `.php-cs-fixer.dist.php`
- **phpcbf** - Fixes the PHP_CodeSniffer violations that can be resolved automatically

After running, review the changes as some linting issues may still require manual intervention.

### Testing in This Repository

This repository uses itself for CI. Check [.github/workflows/php.yml](.github/workflows/php.yml) for examples. As it contains no PHP code, only the language agnostic testing types run there: `composer-validate`, `security-composer-packages` and `security-grype`.

### Overriding Variables

Override Taskfile variables when including remotely:

```yaml
includes:
  remote:
    taskfile: >-
      {{.REMOTE_URL}}/{{.REMOTE_URL_REPO}}/{{.REMOTE_URL_REF}}/build/task.yml
    vars:
      CODE_COVERAGE_STRICT: "false" # Disable strict coverage enforcement
      PHP_SOURCE_DIRS: "app tests" # Lint and analyse other directories
```

Available override variables (see the `vars` section of [build/task.yml](build/task.yml)):

- `CODE_COVERAGE_STRICT` - Enforce minimum coverage (default: "true")
- `PHPCS_STANDARD` - PHP_CodeSniffer standard (default: "PSR12")
- `PHPMD_RULESETS` - PHPMD rulesets (default: "cleancode,codesize,controversial,design,naming,unusedcode")
- `PHPSTAN_CONFIG_PATH` - Path to the PHPStan config (default: "phpstan.dist.neon")
- `PHPSTAN_MEMORY_LIMIT` - PHPStan memory limit (default: "-1")
- `PHPUNIT_CONFIG_PATH` - Path to the PHPUnit config (default: "phpunit.xml.dist")
- `PHP_CS_FIXER_CONFIG_PATH` - Path to the PHP-CS-Fixer config (default: ".php-cs-fixer.dist.php")
- `PHP_SOURCE_DIRS` - Directories that are linted and analysed (default: "src tests")
- `RECTOR_CONFIG_PATH` - Path to the Rector config (default: "rector.php")

## Using the GitHub Action

### Basic Usage

```yaml
name: PHP
on: pull_request
permissions:
  contents: read
  packages: read
jobs:
  mcvs-php-action:
    strategy:
      matrix:
        args:
          - testing-type: "unit"
          - testing-type: "lint"
          - testing-type: "static-analysis"
          - testing-type: "coverage"
          - testing-type: "security-composer-packages"
    runs-on: ubuntu-24.04
    env:
      TASK_X_REMOTE_TASKFILES: 1
    steps:
      - uses: actions/checkout@v4.2.2
      - uses: schubergphilis/mcvs-php-action@v1 # Use @v1 for latest v1.x.x
        with:
          testing-type: ${{ matrix.args.testing-type }}
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Advanced Usage with Releases

For compiling a PHAR on tagged releases:

```yaml
- uses: schubergphilis/mcvs-php-action@v1
  with:
    release-application-name: "my-app"
    release-box-config: "box.json"
    release-dir: "."
    release-type: "phar"
    token: ${{ secrets.GITHUB_TOKEN }}
```

The `box.json` must define an explicit `output` key, so that the location of the compiled PHAR is deterministic.

### Key Action Inputs

- **testing-type** - Main selector: `unit`, `integration`, `component`, `e2e`, `coverage`, `lint`, `static-analysis`, `mess-detection`, `rector`, `composer-validate`, `security-composer-packages`, `security-grype`
- **test-groups** - PHPUnit groups to narrow a run down (e.g., "slow")
- **coverage-driver** - `pcov`, `xdebug` or `none` (default: pcov)
- **code-coverage-expected** - Minimum coverage percentage (default: 80)
- **php-extensions** / **php-ini-values** - Passed straight to setup-php
- **test-timeout** / **code-coverage-timeout** - Timeouts for test execution (e.g., "10m")

## Important Implementation Details

### Composer Verification

- `composer validate --strict` runs before every test type, and the `composer.lock` is verified to be in sync with the `composer.json`
- Private composer packages: use `github-token-for-downloading-private-composer-packages` to configure the composer github-oauth credentials

### PHP Version Management

The PHP version is derived from `composer.json`: `config.platform.php` takes precedence over the `require.php` constraint, which is normalized (e.g. `^8.3` becomes `8.3`). The `php-version` input overrules both. This ensures CI uses the same PHP version as defined in the project.

### Tool Versions

All tool versions are pinned in the `vars` section of [build/task.yml](build/task.yml):

- phpstan: 2.2.8
- php-cs-fixer: v3.95.18
- phpunit: 13.3.1
- phpcs/phpcbf: 4.0.4
- phpmd: 2.15.0
- box: 4.7.0
- osv-scanner: v2.4.0
- yq: v4.53.3
- Task runner: 3.52.0 (defined in action.yml)

The [package-version-updater workflow](.github/workflows/package-version-updater.yml) automatically opens PRs to update these versions weekly.

## Project-Specific Notes

### This Repository Structure

```
.
├── action.yml                 # Composite action definition
├── build/
│   └── task.yml               # Remote Taskfile with all tasks
├── scripts/
│   └── package-version-updater.sh  # Tool version updater
├── .github/workflows/
│   ├── php.yml                # Self-testing workflow (uses this action)
│   └── package-version-updater.yml  # Weekly tool updates
├── docs/
│   └── osv-scanner.md         # OSV scanner usage guide
├── .php-cs-fixer.dist.php     # Reference PHP-CS-Fixer configuration
├── phpstan.dist.neon          # Reference PHPStan configuration
├── phpunit.xml.dist           # Reference PHPUnit configuration
├── osv-scanner.toml.example   # Example vulnerability ignore config
├── composer.json              # Defines the PHP version for the action
└── composer.lock              # Committed, as the scanners audit it
```

### No Application Code

This repository contains no `.php` application code - it's purely tooling. The `composer.json` exists to define the PHP version for the action, and the root `.php-cs-fixer.dist.php`, `phpstan.dist.neon` and `phpunit.xml.dist` are reference configurations that consuming projects can copy.

### Guardrail Workflows

Three workflows enforce the conventions of this repository and fail the build when they are broken:

- `action-sorted-inputs.yml` - the keys under `inputs` in action.yml must be sorted alphabetically
- `taskfile-sorted-units.yml` - the keys of every task in build/task.yml must be sorted alphabetically (`cmds`, `desc`, `internal`, `silent`, ...)
- `taskfile-without-empty-lines.yml` - build/task.yml may not contain empty lines outside of `- |` blocks

### Versioning

- Use `@v1` in workflows to automatically get latest v1.x.x updates
- Use specific tags (e.g., `@v1.0.0`) for pinned versions
- Breaking changes only occur on major version bumps (v1 → v2)
- Check the [releases page](https://github.com/schubergphilis/mcvs-php-action/releases) for changelog
