# MCVS PHP Action


[![GitHub release](https://img.shields.io/github/v/release/schubergphilis/mcvs-php-action)](https://github.com/schubergphilis/mcvs-php-action/releases)
[![License](https://img.shields.io/github/license/schubergphilis/mcvs-php-action)](LICENSE)

<img width="250" alt="635815722-64fe5d87-54bc-426f-9433-a5f775b31e0b" src="https://github.com/user-attachments/assets/8f28f87b-fa78-4d5b-8f4c-1bef8cbd6687" />

The Mission Critical Vulnerability Scanner (MCVS) PHP Action repository is a
collection of standardized tools to ensure a certain level of quality of a
project with PHP code.

It is the PHP counterpart of the
[mcvs-golang-action](https://github.com/schubergphilis/mcvs-golang-action) and
follows the same structure: a composite GitHub Action and a remote Taskfile
that are used both in CI and locally.

## Github Action

The [GitHub Action](https://github.com/features/actions) in this repository
consists of the following steps:

- Install the PHP version that is defined in the project `composer.json`.
- Verify that the `composer.json` is valid and that the `composer.lock` is in
  sync with it.
- Verify the downloaded composer packages.
- Code security scanning and suppression of certain CVEs for a maximum of one
  month. In some situations a particular CVE will be resolved in a couple of
  weeks and this allows the developer to continue in a safe way while knowing
  that the pipeline will fail again if the issue has not been resolved in a
  couple of weeks.
- Linting, using `php -l`, PHP-CS-Fixer and PHP_CodeSniffer.
- Static analysis, using PHPStan.
- Mess detection, using PHPMD.
- Automated refactoring checks, using Rector.
- Unit tests.
- Integration tests.
- Component tests.
- End-to-end tests.
- Code coverage.
- A test summary, including the name of every test that has been run for the
  testing type at hand, using the PHPUnit `--testdox` output.

In summary, using this action will ensure that PHP code meets certain standards
before it will be deployed to production as the assembly line will fail if an
issue arises.

Note: there is an [internal action](.github/workflows/package-version-updater.yml)
that will update package versions that cannot be updated by Dependabot.

## Versioning

This action follows semantic versioning. When using this action in your workflows:

- **Latest stable version**: Use the latest `v1.x.x` tag for production workflows
- **Major version tracking**: Use `@v1` to automatically get the latest v1.x.x updates
- **Taskfile references**: When including the remote Taskfile, use a specific version tag that matches your needs
- **Breaking changes**: Major version bumps (v1 → v2) may introduce breaking changes and require workflow updates

Check the [releases page](https://github.com/schubergphilis/mcvs-php-action/releases) for the latest version and changelog.

## Taskfile

Another tool is configuration for [Task](https://taskfile.dev/). This repository
offers a `./build/task.yml` which contains standard tasks, like installing and
running a linter.

This `./build/task.yml` can then be used by other projects. This has the
advantage that you do not need to copy and paste Makefile snippets from one
project to another. As a consequence each project using this `./build/task.yml`
immediately benefits from improvements made here (e.g. new tasks or
improvements in the tasks).

If you are new to Task, you may want to check out the following resources:

- [Installation instructions](https://taskfile.dev/installation/)
- Instructions to [configure completions](https://taskfile.dev/installation/#setup-completions)
- [Integrations](https://taskfile.dev/integrations/) with e.g. Visual Studio Code, Sublime and IntelliJ.

### Tooling

The tools are pinned in `./build/task.yml` and are installed as PHARs in
`~/.mcvs-php-action/bin`, unless the `MCVS_PHP_ACTION_BIN` environment variable
points somewhere else. No global composer installation is modified.

| Tool                                                                  | Purpose                              |
| :-------------------------------------------------------------------- | :----------------------------------- |
| [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)          | Formatting                           |
| [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)  | Coding standard, e.g. PSR-12         |
| [PHPStan](https://phpstan.org/)                                       | Static analysis                      |
| [PHPMD](https://phpmd.org/)                                           | Mess detection                       |
| [PHPUnit](https://phpunit.de/)                                        | Testing and code coverage            |
| [Rector](https://getrector.com/)                                      | Automated refactoring                |
| [box](https://github.com/box-project/box)                             | Compiling a PHAR                     |
| [osv-scanner](https://github.com/google/osv-scanner)                  | Vulnerability scanning               |

Two exceptions:

- **PHPUnit** is taken from `vendor/bin/phpunit` when the project depends on it,
  as the tests need the version that matches the project its dev dependencies.
  The pinned PHAR is only used as a fallback.
- **Rector** has no official PHAR distribution and is therefore always taken
  from `vendor/bin/rector`. Add it with
  `composer require --dev rector/rector` before using the `rector` testing type.

### Configuration

The `./build/task.yml` in this project defines a number of variables. Some of
these can be overridden when including this Taskfile in your project. See the
example below, where the `CODE_COVERAGE_STRICT` variable is overridden, for how
to do this.

The following variables can be overridden:

<!-- markdownlint-disable MD013 -->

| Variable                   | Description                                                                                                         |
| :------------------------- | :------------------------------------------------------------------------------------------------------------------ |
| `CODE_COVERAGE_STRICT`     | Enables or disables strict enforcement of setting the minimum coverage to the maximum observed coverage.            |
| `PHPCS_STANDARD`           | The PHP_CodeSniffer standard. Default: `PSR12`.                                                                    |
| `PHPMD_RULESETS`           | The comma separated PHPMD rulesets. Default: `cleancode,codesize,controversial,design,naming,unusedcode`.          |
| `PHPSTAN_CONFIG_PATH`      | Defines the path to the PHPStan configuration file. Default: `phpstan.dist.neon`.                                  |
| `PHPSTAN_MEMORY_LIMIT`     | The memory limit that PHPStan is run with. Default: `-1`.                                                          |
| `PHPUNIT_CONFIG_PATH`      | Defines the path to the PHPUnit configuration file. Default: `phpunit.xml.dist`.                                   |
| `PHP_CS_FIXER_CONFIG_PATH` | Defines the path to the PHP-CS-Fixer configuration file. Default: `.php-cs-fixer.dist.php`.                        |
| `PHP_SOURCE_DIRS`          | The space separated directories that are linted and analysed. Default: `src tests`.                                |
| `RECTOR_CONFIG_PATH`       | Defines the path to the Rector configuration file. Default: `rector.php`.                                          |

<!-- markdownlint-enable MD013 -->

## Usage

### Locally

Create a `Taskfile.yml` with the following content:

```yml
---
version: 3

vars:
  REMOTE_URL: https://raw.githubusercontent.com
  REMOTE_URL_REF: v1.0.0
  REMOTE_URL_REPO: schubergphilis/mcvs-php-action

includes:
  remote: >-
    {{.REMOTE_URL}}/{{.REMOTE_URL_REPO}}/{{.REMOTE_URL_REF}}/build/task.yml
```

and run:

```zsh
TASK_X_REMOTE_TASKFILES=1 \
task remote:test
```

Note that the `TASK_X_REMOTE_TASKFILES` variable is required as long as the
remote Taskfiles are still experimental. (See [issue
1317](https://github.com/go-task/task/issues/1317) for more information.)

You can use `task --list-all` to get a list of all available tasks.
Alternatively, if you have [configured
completions](https://taskfile.dev/installation/#setup-completions) in your
shell, you can tab to get a list of available tasks.

The most frequently used tasks:

```zsh
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
```

### Automatically Fixing Linting Issues

When the linters report issues that can be automatically fixed, you can use the
`fix-linting-issues` task:

```zsh
TASK_X_REMOTE_TASKFILES=1 \
task remote:fix-linting-issues --yes
```

This task automatically fixes common linting issues using two tools:

- **PHP-CS-Fixer**: Applies the formatting rules of the
  `.php-cs-fixer.dist.php` configuration
- **phpcbf**: Fixes the PHP_CodeSniffer violations that can be resolved
  automatically

After running this task, review the changes and commit them. Note that some
linting issues may still require manual fixes.

If you want to override one of the variables in our Taskfile, you will have to
adjust the `includes` sections like this:

```yml
---
includes:
  remote:
    taskfile: >-
      {{.REMOTE_URL}}/{{.REMOTE_URL_REPO}}/{{.REMOTE_URL_REF}}/build/task.yml
    vars:
      CODE_COVERAGE_STRICT: "false"
```

## Testsuites

PHP has no equivalent of the Go build tags. The testing type is therefore
mapped onto a [PHPUnit
testsuite](https://docs.phpunit.de/en/11.5/organizing-tests.html#composing-a-test-suite-using-xml-configuration).
Define the following testsuites in the `phpunit.xml.dist`, see the
[phpunit.xml.dist](phpunit.xml.dist) in this repository for a complete example:

- **`unit`**: For unit tests that do not depend on anything external
- **`integration`**: For integration tests that require external services or databases
- **`component`**: For component tests that test multiple units working together
- **`e2e`**: For end-to-end tests that test the entire application flow

The `test-groups` input maps onto the PHPUnit `--group` option and can be used
to narrow a run down further, e.g. `test-groups: slow`.

### GitHub

#### Basic Example

For a simple project that needs standard testing and linting, create a
`.github/workflows/php.yml` file:

```yml
---
name: PHP
"on": pull_request
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
      - uses: schubergphilis/mcvs-php-action@v1
        with:
          testing-type: ${{ matrix.args.testing-type }}
          token: ${{ secrets.GITHUB_TOKEN }}
```

This basic configuration will run unit tests, linting, static analysis, code
coverage checks and security scanning on your PHP code.

#### Advanced Example

For projects with integration tests, mess detection or custom requirements,
create a `.github/workflows/php.yml` file with the following content:

```yml
---
name: PHP
"on": pull_request
permissions:
  contents: read
  packages: read
jobs:
  mcvs-php-action:
    strategy:
      matrix:
        args:
          - testing-type: "component"
          - testing-type: "composer-validate"
          - testing-type: "coverage"
          - testing-type: "e2e"
          - testing-type: "integration"
          - testing-type: "lint"
          - testing-type: "mess-detection"
          - testing-type: "rector"
          - testing-type: "security-composer-packages"
          - testing-type: "security-grype"
          - testing-type: "static-analysis"
          - testing-type: "unit"
    runs-on: ubuntu-24.04
    env:
      TASK_X_REMOTE_TASKFILES: 1
      test-timeout: 10m
    steps:
      - uses: actions/checkout@v4.2.2
      - uses: schubergphilis/mcvs-php-action@v1.0.0
        with:
          code-coverage-expected: "84.2"
          code-coverage-timeout: ${{ env.test-timeout }}
          coverage-driver: pcov
          php-extensions: "mbstring, intl, json, dom, xml, pdo_pgsql"
          task-install: yes
          test-timeout: ${{ env.test-timeout }}
          testing-type: ${{ matrix.args.testing-type }}
          token: ${{ secrets.GITHUB_TOKEN }}
```

and a [phpstan.dist.neon](https://phpstan.org/config-reference), a
[.php-cs-fixer.dist.php](https://cs.symfony.com/doc/config.html) and a
[phpunit.xml.dist](https://docs.phpunit.de/en/11.5/configuration.html).

<!-- markdownlint-disable MD013 -->

| Option                                                 | Default | Required | Description                                                                                          |
| :----------------------------------------------------- | :------ | -------- | :--------------------------------------------------------------------------------------------------- |
| code-coverage-expected                                 | x       |          | Minimum expected code coverage percentage                                                            |
| code-coverage-timeout                                  |         |          | Timeout duration for code coverage analysis (e.g., "10m")                                            |
| composer-install-args                                  |         |          | Additional arguments that are passed to `composer install`, e.g. `--ignore-platform-reqs`            |
| coverage-driver                                        | x       |          | The PHP code coverage driver: `pcov`, `xdebug` or `none`                                             |
| github-token-for-downloading-private-composer-packages |         |          | GitHub token with permissions to download composer packages from private repositories                |
| grype-version                                          |         |          | Specific version of Grype vulnerability scanner to use                                               |
| php-extensions                                         | x       |          | The PHP extensions that have to be installed, comma separated                                        |
| php-ini-values                                         | x       |          | The php.ini values that have to be set, comma separated                                              |
| php-version                                            |         |          | The PHP version to install. Overrules the version that is derived from the php-version-file          |
| php-version-file                                       | x       |          | The composer.json from which the PHP version is derived                                              |
| release-application-name                               |         |          | Name of the application to build (required when release-dir is set)                                  |
| release-box-config                                     | x       |          | The box configuration file that is used to compile the PHAR                                          |
| release-dir                                            |         |          | Directory that contains the box configuration of the PHAR to build                                   |
| release-type                                           | x       |          | Type of release to build (e.g., "phar")                                                              |
| task-install                                           | x       |          | Whether to install Task runner ("yes" or "no")                                                       |
| task-version                                           | x       |          | Version of Task runner to install                                                                    |
| test-groups                                            |         |          | The PHPUnit groups that should be run, comma separated                                               |
| test-timeout                                           |         |          | Timeout duration for test execution (e.g., "10m")                                                    |
| testing-type                                           |         |          | Type of testing to run (e.g., "unit", "integration", "lint", "coverage", "security-composer-packages") |
| token                                                  |         |          | GitHub token for authentication (typically ${{ secrets.GITHUB_TOKEN }})                              |

Note: If an **x** is registered in the Default column, refer to the
[action.yml](action.yml) for the corresponding value.

<!-- markdownlint-enable MD013 -->

### Releases

In some cases, you may want a [PHAR](https://www.php.net/manual/en/book.phar.php)
to be built and released automatically. This action will compile the PHAR with
[box](https://github.com/box-project/box), which could then be used as a
release asset.

Add a `box.json` with an explicit `output` key to the project:

```json
{
    "main": "bin/some-app",
    "output": "some-app.phar",
    "directories": ["src"]
}
```

and create a `.github/workflows/php-releases.yml` file with the following
content:

```yml
---
name: php-releases
"on": push
permissions:
  contents: write
  packages: read
jobs:
  mcvs-php-action:
    strategy:
      matrix:
        args:
          - release-application-name: some-app
            release-dir: .
            release-type: phar
    runs-on: ubuntu-24.04
    env:
      TASK_X_REMOTE_TASKFILES: 1
    steps:
      - uses: actions/checkout@v4.2.2
      - uses: schubergphilis/mcvs-php-action@v1.0.0
        with:
          release-application-name: ${{ matrix.args.release-application-name }}
          release-dir: ${{ matrix.args.release-dir }}
          release-type: ${{ matrix.args.release-type }}
          token: ${{ secrets.GITHUB_TOKEN }}
```

### Integration

To execute integration tests, make sure that the code is located in the
directory that is registered in the `integration` testsuite of the
`phpunit.xml.dist`, such as `tests/Integration`.

After adding the test, issue the command `task remote:test-integration --yes`
as demonstrated in this example. If `task remote:test --yes` is executed, only
unit tests will be run.

### Component

See the integration paragraph for the steps and replace `integration` with
`component` to run them.

### Security scanning

The `security-composer-packages` testing type runs both `composer audit` and
`osv-scanner` against the `composer.lock`. Therefore the `composer.lock` has to
be committed, also for a library. See [docs/osv-scanner.md](docs/osv-scanner.md)
for the way to temporarily suppress a CVE.

### Differences with the mcvs-golang-action

- **Partial testing**: the `gta` based partial testing of the
  mcvs-golang-action has no PHP equivalent and is therefore not part of this
  action. Use `test-groups` to narrow a run down.
- **mcvs-texttidy, OPA/Regal, gqlgen and mockery**: these are distributed as Go
  binaries or are Go specific and are therefore not part of this action.
- **Build tags**: replaced by PHPUnit testsuites, see the [Testsuites](#testsuites)
  paragraph.
- **Releases**: a PHAR is compiled instead of an OS and architecture specific
  binary, so the `release-os` and `release-architecture` inputs do not exist.
