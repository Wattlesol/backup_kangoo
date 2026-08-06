# Sanad Deployment Readiness

This note records the current Dokploy deployment target and the remaining release-to-UAT action for the Sanad work.

## Dokploy Target

| Item | Value |
| --- | --- |
| Dokploy project | `Kangoo` |
| Project ID | `jO2CNKR-r3w0uFM7V7DAv` |
| Environment | `production` |
| Environment ID | `UgU7h5yarG_-kPX6VAWxk` |
| Deployable type | Docker Compose |
| Compose name | `Kangoo app` |
| Compose ID | `dt3y93oBMayMC8VBFjXVJ` |
| Domain | `https://kangoo.sa` |
| Repository | `Wattlesol/backup_kangoo` |
| Dokploy branch | `prod` |
| Dokploy compose path | `./docker-compose.yml` |
| Dokploy status | `done` |

## Current Sanad Branches

| Application | Branch | PR |
| --- | --- | --- |
| Backend and dashboards | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/backup_kangoo/pull/1 |
| Customer mobile app | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/handyman_user_flutter_v11.13.2/pull/1 |
| Admin/provider mobile app | `codex/sanad-phase-1-foundation` | https://github.com/Wattlesol/handyman_admin_flutter_app-v3.9.0/pull/1 |

## Prod Alignment PR

| Target | PR |
| --- | --- |
| Backend Sanad branch into Dokploy `prod` branch | https://github.com/Wattlesol/backup_kangoo/pull/2 |

## Deployment Decision

Do not trigger Dokploy redeploy yet. The live Kangoo Dokploy compose is configured to deploy the `prod` branch, while the completed Sanad backend work is currently on `codex/sanad-phase-1-foundation`.

To deploy Sanad for UAT, use one of these paths:

1. Review and merge backend PR #2 into the branch that Dokploy deploys from, then redeploy compose ID `dt3y93oBMayMC8VBFjXVJ`.
2. Temporarily point the Dokploy compose to `codex/sanad-phase-1-foundation`, deploy for UAT, then move it back to the approved production branch after review.

The safer path is to merge/review first, then redeploy the configured branch.

## Post-Deploy UAT Verification

Before any production deployment, run full local SQL QA:

```bash
scripts/sanad_local_sql_qa.sh
scripts/sanad_web_sql_qa.sh
```

After deployment, run:

```bash
BASE_URL=https://kangoo.sa/api SANAD_TEST_EMAIL=<uat-admin-email> SANAD_TEST_PASSWORD=<uat-admin-password> scripts/sanad_integrated_qa.sh
```

Then complete the role acceptance checks in `docs/sanad-uat-checklist.md`.

## Guarded Dokploy Deploy

After PR #2 is reviewed and merged into `prod`, use the guarded deployment runner:

```bash
DOKPLOY_API_KEY=<dokploy-api-key> \
SANAD_EXPECTED_COMMIT=<merged-sanad-commit-sha> \
SANAD_TEST_EMAIL=<uat-admin-email> \
SANAD_TEST_PASSWORD=<uat-admin-password> \
scripts/sanad_dokploy_deploy_and_qa.sh
```

The runner will not deploy unless `origin/prod` contains `SANAD_EXPECTED_COMMIT` and the Dokploy compose target still points to `Wattlesol/backup_kangoo:prod`.
