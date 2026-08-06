# Sanad Local SQL QA

Use this workflow before production deployment to test Sanad against a local MySQL database restored from the Kangoo SQL dump.

## What It Does

The runner `scripts/sanad_local_sql_qa.sh` creates an isolated Docker Compose project named `kangoo-sanad-qa` by default. It:

1. Starts a local MySQL 8 container on port `3307`.
2. Starts the Kangoo Laravel app on port `8092`.
3. Restores `database/dumps/kangoo_sa.sql.gz` into a fresh local QA database.
4. Runs Laravel migrations against that local SQL database.
5. Seeds a local QA Sanad request when the restored dump has no bookings.
6. Runs `scripts/sanad_integrated_qa.sh` against `http://127.0.0.1:8092/api` with request lifecycle verification required.

It does not edit the existing `.env`, and it does not connect to production.

## Command

```bash
scripts/sanad_local_sql_qa.sh
```

## Default Local Connection

| Item | Value |
| --- | --- |
| API URL | `http://127.0.0.1:8092` |
| API base URL | `http://127.0.0.1:8092/api` |
| MySQL host | `127.0.0.1` |
| MySQL port | `3307` |
| Database | `kangoo_sanad_qa` |
| Username | `kangoo_qa` |
| Password | `kangoo_qa_password` |

## Latest Local SQL QA Result

The full local SQL QA gate passed against `http://127.0.0.1:8092/api`.

| Area | Result |
| --- | --- |
| Docker app container build | Passed |
| Local MySQL 8 container | Passed |
| Fresh restore from `database/dumps/kangoo_sa.sql.gz` | Passed |
| Laravel migrations on local SQL | Passed |
| Local QA Sanad request seed | Passed |
| Authenticated API session | Passed |
| Foundation role, terminology, lifecycle, privacy, and AI metadata | Passed |
| Request list mobile contract | Passed |
| Request lifecycle update | Passed |
| Buzz create and acknowledge workflow | Passed |
| Document vault privacy metadata and admin verification | Passed |
| Secure chat thread/message workflow | Passed |
| AI knowledge/ask workflow | Passed |
| Payment and wallet API contracts | Passed |
| Customer mobile source and Android debug APK artifact contract | Passed |
| Admin/provider/employee mobile source and Android debug APK artifact contract | Passed |

## Optional Overrides

| Variable | Purpose | Default |
| --- | --- | --- |
| `SANAD_QA_PROJECT_NAME` | Docker Compose project name | `kangoo-sanad-qa` |
| `SANAD_QA_APP_PORT` | Local app port | `8092` |
| `SANAD_QA_MYSQL_PORT` | Local MySQL public port | `3307` |
| `SANAD_QA_DB_DATABASE` | Local QA database name | `kangoo_sanad_qa` |
| `SANAD_QA_DB_USERNAME` | Local QA database user | `kangoo_qa` |
| `SANAD_QA_DB_PASSWORD` | Local QA database password | `kangoo_qa_password` |
| `SANAD_QA_MYSQL_ROOT_PASSWORD` | Local QA MySQL root password | `kangoo_qa_root_password` |
| `SANAD_TEST_EMAIL` | QA login email | `demo@admin.com` |
| `SANAD_TEST_PASSWORD` | QA login password | `12345678` |

## Cleanup

To stop the local QA environment:

```bash
docker compose -p kangoo-sanad-qa -f docker-compose.yml down
```

To remove the local QA database volume and force a clean restore on the next run:

```bash
docker compose -p kangoo-sanad-qa -f docker-compose.yml down -v
```
