# ERP
## Local development setup
- Apply project setup
    ```
    sudo bash project_setup.sh
    ```
- Run Vite
    ```
    sail npm run dev
    ```
- Run App
    ```
    sail up -d
    ```
- Generate autocompletion for Facades
    ```
    sail artisan ide-helper:generate
    ```
- Add PHPDoc for your models
    ```
    sail artisan ide-helper:models -RW
    ```
- Start analyzing your code using the PHPStan console command
    ```
    sail bin phpstan analyze
    ```
- Fix everything at once
    ```
    sail bin duster fix
    ```
- Format files and overwrite
    ```
    sail npm run blade:formatter
    ```
- Run Prettier and ESLint
    ```
    sail npm run format
    sail npm run lint
    ```
- Run PHPUnit test
    ```
    sail test --coverage-clover=coverage.xml
    ```
- Run Playwright e2e test
    ```
    sail npm run test:e2e
    ```
- Run SonarQube analyze
    ```
    docker run -u root --rm -v "$(pwd):/var/www/html" -w /var/www/html sonarsource/sonar-scanner-cli:11
    ```
## Technologies
- [x] Laravel, React.js, Inertia.js, Vite.js, Shadcn UI, Tailwind CSS, TypeScript, ESLint, Prettier, PHPUnit, Pest, FakerPHP. ([Link](https://ui.shadcn.com/docs/installation/laravel))
- [x] Sail. ([Link](https://laravel.com/docs/12.x/sail))
- [x] Laravel IDE Helper. ([Link](https://github.com/barryvdh/laravel-ide-helper))
- [x] Larastan, PHPStan. ([Link](https://github.com/larastan/larastan))
- [x] Duster (TLint, PHP_CodeSniffer, PHP CS Fixer, Pint). ([Link](https://github.com/tighten/duster))
- [x] blade-formatter. ([Link](https://github.com/shufo/blade-formatter))
- [x] Playwright. ([Link](https://playwright.dev/docs/intro))
- [!] Security Advisories Health Check. ([Link](https://github.com/spatie/security-advisories-health-check))
- [!] npm-check-updates. ([Link](https://github.com/raineorshine/npm-check-updates))
- [!] Laravel CSP. ([Link](https://github.com/spatie/laravel-csp))
- [x] SonarQube. ([Link](https://docs.sonarsource.com/sonarqube-community-build/server-installation/from-docker-image/installation-overview))
- [ ] Laravel Actions. ([Link](https://github.com/lorisleiva/laravel-actions))
- [ ] Laravel Actions IDE Helper. ([Link](https://github.com/Wulfheart/laravel-actions-ide-helper))
- [ ] Scramble. ([Link](https://github.com/dedoc/scramble))
- [ ] Octane. ([Link](https://laravel.com/docs/12.x/octane))
- [ ] Buggregator (XHProf, VarDumper, Ray, Sentry, SMTP Server, Monolog, Inspector). ([Link](https://docs.buggregator.dev/))
- [ ] Sentry. ([Link](https://docs.sentry.io/platforms/php/guides/laravel/))
- [ ] Paratest. ([Link](https://github.com/paratestphp/paratest))
- [ ] JWT Auth. ([Link](https://github.com/tymondesigns/jwt-auth))
- [ ] Socialite. ([Link](https://laravel.com/docs/12.x/socialite))
- [ ] Socialite Providers. ([Link](https://socialiteproviders.com/usage/))
- [ ] Scout. ([Link](https://laravel.com/docs/12.x/scout))
- [ ] Sanctum. ([Link](https://laravel.com/docs/12.x/sanctum))
- [ ] Image. ([Link](https://github.com/spatie/image))
- [ ] Image Optimizer. ([Link](https://github.com/spatie/image-optimizer))
- [ ] Laravel Permission. ([Link](https://github.com/spatie/laravel-permission))
- [ ] Laravel Backup. ([Link](https://github.com/spatie/laravel-backup))
- [ ] Laravel Health. ([Link](https://github.com/spatie/laravel-health))
- [ ] Laravel Migration Generator. ([Link](https://github.com/kitloong/laravel-migrations-generator))
- [ ] codemod. ([Link](https://docs.codemod.com/guides/migrations/react-18-19))
- [ ] Period. ([Link](https://github.com/spatie/period))
- [ ] Laravel Chunk Upload. ([Link](https://github.com/pionl/laravel-chunk-upload))
- [ ] Ziggy. ([Link](https://github.com/tighten/ziggy))
- [ ] Zustand. ([Link](https://github.com/pmndrs/zustand))
- [ ] Bref. ([Link](https://bref.sh/docs/laravel/getting-started))
- [ ] Bref Extra PHP Extension. ([Link](https://github.com/brefphp/extra-php-extensions))
- [ ] AWS CLI. ([Link](https://github.com/aws/aws-cli))
- [ ] Terraform. ([Link](https://developer.hashicorp.com/terraform/tutorials/aws-get-started/install-cli))
- [ ] Ansible. ([Link](https://docs.ansible.com/ansible/latest/installation_guide/installation_distros.html#installing-ansible-on-ubuntu))
- [ ] MinIO Client. ([Link](https://github.com/minio/mc))
- [ ] Builder Ubuntu Packages (build-essential).
- [ ] Deployment/Debugging Ubuntu Packages (redis-tools, cron).
- [ ] Development Ubuntu Packages (wget, software-properties-common, openssh-client, mandoc, lsb-release, docker.io).
- [ ] Deployment/Debugging Node.js Global Packages (serverless@3).
- [ ] Serverless Lift. ([Link](https://www.serverless.com/plugins/serverless-lift))
- [ ] Serverless Domain Manager. ([Link](https://www.serverless.com/plugins/serverless-domain-manager))
- [ ] Serverless Prune Plugin. ([Link](https://www.serverless.com/plugins/serverless-prune-plugin))
- [ ] Serverless Plugin Log Retention. ([Link](https://www.serverless.com/plugins/serverless-plugin-log-retention))
- [ ] Prometheus Node Exporter. ([Link](https://github.com/prometheus/node_exporter))
- [ ] Prometheus Postgres Exporter. ([Link](https://github.com/wrouesnel/postgres_exporter))
- [ ] Grafana Node Exporter Full. ([Link](https://grafana.com/grafana/dashboards/1860-node-exporter-full))
- [ ] Grafana PostgreSQL Database. ([Link](https://grafana.com/grafana/dashboards/9628-postgresql-database))

## Employee Module Refactor

The Employee module has been modernized and aligned with Laravel best practices.

### Key Changes
- **Model (`Employee.php`)**
  - Replaced custom `casts()` method with `$casts` property.
  - Added `$hidden` to hide the `salary` attribute.
  - Implemented `getSalaryAttribute` accessor for consistent formatting.
- **Form Requests**
  - `StoreEmployeeRequest`, `UpdateEmployeeRequest`, and `ExportEmployeeRequest` created for validation.
- **API Resources**
  - `EmployeeResource` and `EmployeeCollection` provide consistent JSON responses with ISO‑8601 dates.
- **Controller (`EmployeeController.php`)**
  - Uses Form Requests, API Resources, and proper type hints.
  - Added authorization checks via Laravel policies.
  - Simplified response handling and pagination.
- **Base Controller**
  - Integrated `AuthorizesRequests`, `DispatchesJobs`, and `ValidatesRequests` traits.
- **Tests**
  - Updated to reflect new response structures and validation logic.
  - All feature, unit, and e2e tests now pass.

These updates improve code readability, security, and API consistency across the project.
