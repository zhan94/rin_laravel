# Publication readiness

**19 November 2026 · Local acceptance approved · Publication NO-GO**

The owner approved the [local product acceptance](product-quality-review.md) and froze feature development. This pass addresses translation, accessibility, repository documentation, and distribution readiness. It uses the [existing Git history](final-acceptance.md#git-history-and-confidentiality) and the verified application architecture, schema, and lockfiles.

## Localization and accessibility

- German and Spanish now cover every English UI catalog key. Each was missing 356 entries before this pass. Regression tests compare the keys and interpolation parameters across languages. The account settings, password recovery, verification, two-factor authentication, and shared loading and close controls use the same catalog. Laravel also translates the validation, authentication, passkey, and booking errors exercised by the regression suite. Booking and season errors retain their dates and API error codes.
- The staff manual has English, German, and Spanish editions with the same workflow sections, localized links and headings, and keyboard-operable troubleshooting disclosures. The account menu opens the edition for the selected language.
- The customer form has persistent visible labels, including its date input. Its error summary is announced as an alert. German and Spanish dialog close buttons have localized accessible names. The appearance choices announce their selected state, and hiding the recovery codes also hides them from the accessibility tree.
- Chrome exercised keyboard entry and focus in the named Location dialog, Escape and focus return, mobile navigation, the customer form and localized staff manual. The manual header and customer status text were darkened within the existing palette to improve contrast. This is a practical check of the sampled workflows. Full assistive-technology and WCAG conformance testing remain open. [W3C text contrast criteria](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
- User-entered catalog names, historical notes and stored notification text retain their original language. Horizon is a vendor interface. The app's staff UI and manual have separate translation coverage.

## Final local verification

| Check | Result |
| --- | --- |
| Laravel tests on isolated SQLite | 202 passed with 1,203 assertions. |
| Frontend unit tests | 86 passed across 25 files. |
| Build and code checks | Vite production build, TypeScript, ESLint, Prettier, Pint, and PHPStan passed. |
| Targeted Chrome regressions | Five passed in 32.9 seconds. Covered localized customer forms, password settings and staff manual, modal and mobile keyboard navigation, screenshot capture, and the customer and booking workflow. |
| Git integrity and secret scan | `git fsck` passed in a clean clone. Gitleaks 8.30.1 reported zero findings in the shareable tree and all 31 reachable commits. |
| Chrome localization sweep | 18 pages in German and 18 in Spanish on the fresh Docker MySQL demo. Correct document language and a localized heading on each page. Zero browser exceptions or HTTP responses at 400 or above during the sweep. |
| Practical contrast measurements | The white manual header text measured at least 6.30:1 against the sampled gradient endpoints. The customer Active badge measured 6.13:1 over a white table surface, up from 3.02:1. |
| Release scope | The prior ARM64 clean-checkout timing, 100,000-customer performance measurements, and role and business workflow acceptance remain the baseline. The final branch keeps the schema and dependency lockfiles. [Baseline measurements](product-quality-review.md#performance) |

## Screenshot provenance

The release images in [`docs/screenshots/`](screenshots/) came from the isolated MySQL demonstration project before any browser mutations. The fixture contained 60 generated customers. The customer and dashboard views show generated names, reserved `example.test` addresses, and fictional telephone numbers from Ofcom's 01632 960000–960999 drama range. The mobile image shows the generated demo navigation. The three images were reviewed visually. The background and logo in the screenshots require the same rights clearance as their source assets. [Ofcom number ranges](https://www.ofcom.org.uk/phones-and-broadband/phone-numbers/numbers-for-drama)

## Repository and installation

The Laravel demo checkout includes the Compose and Docker build files, both dependency lockfiles, migrations, synthetic seeders, source assets, and test workflow. Docker Compose pulls public base images and locked dependencies, builds the assets, and creates its own MySQL volume. The optional OOP stack has its own external checkout requirement.

The 770-path tracked-file inventory and historical path review found the two example environment files and intentional test fixtures. They found no committed working environment files, database exports, dependency directories, generated frontend builds, private interview notes, or test output. The credentials check uses Gitleaks and the prior acceptance records. Review the legacy identifier and business aggregates below before approving the shareable history.

## Distribution rights

| Work or asset | Verified evidence | Remaining decision |
| --- | --- | --- |
| RIN-specific application code, CRM design and styling, manual and copy, schema and migration logic, and original release documentation | The local history begins with the verified source import. The repository has no project-wide license grant. Framework and package licenses cover their respective upstream work. | Confirm who can authorize redistribution of all application-specific and inherited company material, including historical versions, and what public or private sharing terms apply. |
| `public/img/bg.jpeg` | The 3000×2000 local mountain photograph differs visually and by SHA-256 from the separate Pexels photo linked by the legacy CSS. Local Git history reaches the source import. | Identify the photographer or supplier and confirm permission to redistribute the JPEG and screenshots containing it. |
| `public/img/big.jpg` and `public/css/logo.jpg` | Both are RIN Sports artwork. The initial import contains the two files without a provenance or licensing record. | Confirm rights to copy the logo artwork and use the RIN Sports name and mark in the repository, demo, and screenshots. |
| `docs/screenshots/dashboard.png`, `docs/screenshots/customers.png`, `docs/screenshots/mobile-navigation.png` | All three use synthetic records and display the local mountain photograph and RIN Sports mark. | Confirm permission to share the rendered uses of the photograph and mark, including earlier versions. |
| `app/Console/Commands/ImportLegacyDatabase.php`, `config/database.php`, `tests/Feature/LegacyImport/LegacyImportTest.php`, and `docs/legacy-data-migration.md` | The frozen migration mechanism and its test contain a legacy schema identifier. The report includes operational counts and financial aggregates from the historical verification. | Confirm permission to share this identifier and the report's metadata and aggregates. Treat their historical copies as part of the same decision. |
| Pexels photo 414459 referenced in `public/css/styles.css` and `resources/css/legacy-stack.css` | The stylesheets contain a URL for this separate photo. The downloaded photo bytes are outside the repository. | Apply the source terms if the remote image is used or copied into another distribution. [Pexels license](https://www.pexels.com/license/) |
| `public/favicon.svg`, `public/favicon.ico`, `public/apple-touch-icon.png` | All three match the official Laravel Vue starter kit byte for byte. The upstream README identifies its MIT license. | Retain upstream attribution with redistribution. [Laravel starter-kit license statement](https://github.com/laravel/vue-starter-kit#license) |
| Bootstrap and Normalize CSS in `public/css/styles/css/`, including source maps | The Bootstrap 3.3.6 and Normalize 3.0.3 source headers identify MIT terms. The upstream notices are bundled next to the assets. `boot.css` is application styling and is covered by the application-specific clearance above. | Retain the bundled notices. [Bootstrap 3.3.6](https://github.com/twbs/bootstrap/blob/v3.3.6/LICENSE) · [Normalize 3.0.3](https://github.com/necolas/normalize.css/blob/3.0.3/LICENSE.md) |
| `public/css/styles/fonts/glyphicons-halflings-regular.*` | EOT, TTF, WOFF, and WOFF2 match the official Bootstrap release byte for byte. The SVG matches its upstream XML content after formatting normalization. Bootstrap documents the creator's grant for the bundled set. | Retain the Glyphicons credit and use within the Bootstrap bundle. [Bootstrap Glyphicons documentation](https://getbootstrap.com/docs/3.3/components/#glyphicons) |
| Fraunces and Space Grotesk webfonts in `public/fonts/` | Both families include their upstream SIL Open Font License 1.1 text and copyright notices. | Keep the notices with the font files. [Bundled notices](../public/fonts/) |

Clearance must cover the Git objects that will be shared. Replacing a file at the current tip leaves its earlier contents in the history. The same applies to screenshots that include the logo or background.

### Original replacement options

- **Mountain background** — Create a repository-owned SVG of layered alpine ridgelines using the current blue and slate palette. Use original vector paths and a quiet gradient behind text. If photography is important, use an original owner-supplied or commissioned image with written permission for the source file and screenshots.
- **Login and header branding** — Draw a simple original peak symbol and pair it with a neutral text wordmark for the demo, such as “Ski School CRM.” Use the same mark for the login and small header sizes. Recreate affected screenshots from the synthetic demo after approval.

Both options are proposals. Approval for a replacement and any necessary handling of affected history are separate decisions.

## GitHub Actions

The sole intended remote is [`zhan94/rin_laravel`](https://github.com/zhan94/rin_laravel). An unauthenticated GitHub API request returned HTTP 404 and a credential-free Git ref check could not read HEAD. Access, visibility, and branch configuration remain unverified.

The [workflow](../.github/workflows/ci.yml) passed actionlint 1.7.12 locally. It asks for read-only repository permissions, pins checkout by commit, and runs the container-based quality gate and a full-history secret scan. Checkout uses `persist-credentials: false`. The separate scan runs offline with a read-only checkout mount. A hosted run has yet to be observed.

## Publication checklist

- [ ] Clear redistribution rights for the application, branded and photographic assets, synthetic screenshots that contain those assets, and historical copies.
- [ ] Clear the legacy schema identifier and aggregate migration evidence for the intended audience.
- [ ] Verify GitHub write access, expected refs, and intended visibility for `zhan94/rin_laravel`.
- [ ] Obtain explicit approval for the first upload of the final local `main` commit.
- [ ] Match the hosted `Verification` run to the release commit and confirm both jobs pass.
- [ ] Give the evaluator access to the authorized repository and share the commit hash and passing run link. Keep private preparation material outside the submission.

### First upload after approval

Run from the reviewed checkout. A clean `main` and the expected HTTPS origin are required. With GitHub CLI installed, authenticate to the intended account, confirm the repository visibility and write access, and inspect existing refs. Pause if the origin or remote history differs from expectations. [GitHub's first-push instructions](https://docs.github.com/en/repositories/creating-and-managing-repositories/adding-locally-hosted-code-to-github)

```sh
git status --short
git branch --show-current
git rev-parse HEAD
git remote get-url origin
gh auth status --hostname github.com
gh repo view zhan94/rin_laravel --json nameWithOwner,visibility,viewerPermission,defaultBranchRef
git ls-remote --heads --tags origin
```

An empty destination is suitable for a first push. If the destination already has commits, verify its ancestry before publishing. Once the checklist clears, push `main` with a regular Git push.

```sh
test -z "$(git status --porcelain)" &&
test "$(git branch --show-current)" = main &&
test "$(git remote get-url origin)" = https://github.com/zhan94/rin_laravel.git &&
git push --set-upstream origin main
```

### Hosted GitHub Actions

Open the repository's **Actions → Verification** page and select the run for the pushed commit. Confirm that `quality` and `secret-scan` both succeeded. Record the run URL and full commit SHA. The GitHub CLI can show the same results. [List workflow runs](https://cli.github.com/manual/gh_run_list) · [Watch a run](https://cli.github.com/manual/gh_run_watch)

```sh
release_sha=$(git rev-parse HEAD)
run_id=$(gh run list --repo zhan94/rin_laravel --workflow ci.yml --branch main \
  --commit "$release_sha" --limit 1 --json databaseId --jq '.[0].databaseId // empty')
test -n "$run_id" &&
gh run watch "$run_id" --repo zhan94/rin_laravel --exit-status &&
gh run view "$run_id" --repo zhan94/rin_laravel --json headSha,conclusion,jobs,url
```

If the push run is missing, check whether Actions is enabled. With the workflow present on the default branch, use **Run workflow** with `main`, or run `gh workflow run ci.yml --repo zhan94/rin_laravel --ref main` after publication approval. If a run fails, use `gh run view "$run_id" --repo zhan94/rin_laravel --log-failed` or inspect the failing job in the browser.
