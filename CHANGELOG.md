# Release Notes

## v0.1.1 - 2026-09-24

### Stripe Watcher v0.1.1

#### Enhancements

- Added warnings when webhook signature verification is missing or not boolean.
- Preserved webhook recording even if logging fails.
- Added support and documentation for custom signature verification attributes.
- Improved Composer metadata and Stripe SDK integration guidance.
- Expanded webhook capture test coverage.

#### Documentation

- Clarified that Stripe Watcher does not verify signatures itself.
- Documented integration with stripe/stripe-php, Laravel Cashier, or custom verification logic.

#### Verification

- 65 tests passed.
- PHPStan and Laravel Pint passed.

## [Unreleased](https://github.com/ivalin-venkov/stripe-watcher/compare/Release v0.1.0...HEAD)

## [Release v0.1.0](https://github.com/ivalin-venkov/stripe-watcher/compare/v0.1.0...Release v0.1.0) - 2026-09-24

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Other Changes

* Bump actions/checkout from 7.0.0 to 7.0.1 by @dependabot[bot] in https://github.com/IvalinV/stripe-watcher/pull/1

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/IvalinV/stripe-watcher/pull/1

**Full Changelog**: https://github.com/IvalinV/stripe-watcher/commits/v0.1.0

## [v0.1.0](https://github.com/ivalin-venkov/stripe-watcher/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
