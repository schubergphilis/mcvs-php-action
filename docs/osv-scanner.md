# osv-scanner

## Overview

The `mcvs-php-action` uses [osv-scanner](https://github.com/google/osv-scanner)
by Google to scan the `composer.lock` for vulnerabilities. osv-scanner is
actively maintained and provides robust vulnerability scanning using the OSV
(Open Source Vulnerabilities) database.

It complements [`composer audit`](https://getcomposer.org/doc/03-cli.md#audit),
which uses the [Packagist security advisories](https://packagist.org/security-advisories)
database. Both are run by the `security-composer-packages` testing type, as the
two databases do not fully overlap.

Note: both scanners inspect the `composer.lock`, so that file has to be
committed. Constraints in the `composer.json` are not sufficient to determine
whether a resolved version is vulnerable.

## Ignoring Vulnerabilities

Add an `osv-scanner.toml` file to your project to ignore certain vulnerabilities
that cannot be fixed right away. This allows you to acknowledge known issues while
preventing the CI/CD pipeline from failing.

### Configuration Format

Create an `osv-scanner.toml` file in your project root:

```toml
# osv-scanner.toml
# Documentation: https://google.github.io/osv-scanner/configuration/

# Ignore specific vulnerabilities
[[IgnoredVulns]]
id = "GHSA-3xq5-wjfh-ppjc"
ignoreUntil = 2026-09-13
reason = "Waiting for upstream fix: https://github.com/some/package/issues/1234"

[[IgnoredVulns]]
id = "CVE-2024-1234"
ignoreUntil = 2026-09-13
reason = "False positive - not applicable to our usage"
```

### Important Notes

- Each ignored vulnerability should have a clear `reason` explaining why it's ignored
- Review and update the ignore list regularly
- Ignored vulnerabilities should be temporary - aim to fix or update dependencies

## Additional Resources

- [osv-scanner GitHub Repository](https://github.com/google/osv-scanner)
- [osv-scanner Documentation](https://google.github.io/osv-scanner/)
- [OSV Database](https://osv.dev/)
