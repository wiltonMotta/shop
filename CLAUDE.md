# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Fork of [ShopXO](https://github.com/gongfuxiang/shopxo) (open-source B2C e-commerce, ThinkPHP 8 / PHP 8) being customized into a clothing mall (服装商城) that will target a WeChat mini-program plus H5 and a PC admin. The engine is upstream ShopXO; customization work lives in feature branches per `WORKFLOW.md`.

Project governance rules live in `.codewhale/constitution.md` — it takes precedence over defaults. Most relevant: never modify `vendor/`; never commit secrets (`.env`, `config/database.php`, `rsakeys/*`); never push directly to `main`/`develop` (use PRs); reasoning and replies in Simplified Chinese, while code/paths/commands stay verbatim.

## Commands

There is no PHP test framework, linter config, or build step in this repo. Verification is `php -l` syntax checking (what CI runs) plus manual testing through the app.

```bash
composer install                              # install deps (runs think service:discover + vendor:publish)
php think <command>                           # ThinkPHP console; `php think list` to enumerate
php think clear                               # clear runtime cache after code/config changes
find app/ -name "*.php" -print0 | xargs -0 -n1 php -l   # syntax check (mirrors CI lint job)
composer validate --strict                    # validate composer.json (CI runs this)
```

Local dev uses Laragon (see `WORKFLOW.md` §5): serve `public/` as web root, front store at the site root, admin at `/admin9s3lkc.php`. DB is MySQL, database `shopxo_clothing`, table prefix `sxo_`.

## Entry points

The web root is `public/`, not the project root. Each app has its own front controller:
- `public/index.php` → runs the `index` app (PC + H5 storefront); root `index.php` just forwards here.
- `public/admin9s3lkc.php` → runs the `admin` app. The filename is deliberately obfuscated for security; the `admin` app name is pinned via `$http->name('admin')`.
- `public/api.php` → runs the `api` app (mini-program / uni-app clients).
- `public/core.php` → shared bootstrap loaded by every entry.
- `think` → CLI entry for `php think`.

## Architecture

ThinkPHP 8 multi-app (`topthink/think-multi-app`). Default app is `index` (`config/app.php`). The four apps under `app/` are:

- `app/index/` — storefront (PC + H5), has `controller/`, `view/`, `form/`, `route/`, `lang/`, `config/`.
- `app/admin/` — admin backend, same layout.
- `app/api/` — JSON API for mobile/mini-program clients.
- `app/install/` — installation wizard.

### Service layer is where the logic lives

The dominant pattern: **controllers are thin and delegate to `app/service/*Service.php` (90+ classes) whose methods are almost entirely `public static`.** Business logic, queries, and cross-cutting concerns all live in services, called as e.g. `GoodsService::GoodsList($params)`. When adding or changing behavior, find or extend the relevant service rather than putting logic in a controller.

Data access goes through the query builder `think\facade\Db` (e.g. `Db::name('goods')->where(...)`), **not** ActiveRecord/ORM model classes — there is no `app/*/model/` directory. Table names in code omit the `sxo_` prefix (`Db::name('goods')` → `sxo_goods`).

Shared pieces: `app/BaseController.php` (base for all controllers, provides `validate()`), `app/common.php` (global helper functions like `MyFileConfig`, autoloaded via `config/app.php`), `app/AppService.php` (registered in `app/service.php`, runs global init). Reusable non-service code lives under `extend/` (PSR-0 autoloaded per `composer.json`): `extend/base/` (utilities: Excel, FileUpload, Email…), `extend/payment/` (payment gateway drivers — Alipay, Weixin, Allinpay variants), `extend/qrcode/`, `extend/library/`.

`app/module/` holds feature engines (DIY visual page builder `DiyModule`, form builder `FormInputModule`, data-print `DataPrintHandleModule`, layouts). `app/plugins/` is the plugin install target; `app/service/PluginsService.php` drives the plugin/hook system.

### Config

`config/*.php` are ThinkPHP config files. `config/shopxo.php` is the app-specific config; `config/shopxo.sql` is the install schema. `config/database.php` is generated at install time and is git-ignored. Runtime config values are often stored in the `sxo_config` DB table and read via `MyFileConfig` / config services rather than only from files.

Multi-language: `app/lang/{zh,en,cht,spa}.php` plus per-app `lang/` dirs.

## CI/CD and deploy

`.github/workflows/ci-cd.yml`: on every push it runs the PHP lint + `composer validate`; on push to `main` it rsyncs to an Aliyun ECS host, runs `composer install --no-dev`, clears `runtime/`, and health-checks. `WORKFLOW.md` documents a `develop`/`feature/*`/`hotfix/*` branch model and commit convention (`<type>(<scope>): <subject>`, types feat|fix|docs|style|refactor|perf|test|chore). Note `deploy-aliyun.sh` and `deploy-remote.sh` are one-off provisioning scripts that contain hardcoded server IPs and DB credentials — treat them as environment-specific, not templates.
