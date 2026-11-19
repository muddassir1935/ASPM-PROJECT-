# Bug Fix Pull Request Workflow

Use one feature branch and pull request per assigned unit. Start from the latest
`main`, keep the diff within the unit brief, and include the relevant Memory Bank
story and regression tests with the code change.

Before requesting review:

1. Verify the reported defect in the current code.
2. Check every applicable story acceptance criterion.
3. Run `php tests/run_all.php` locally and include its result in the pull request.
4. Confirm the **PHP Tests** check passes. It runs the full suite with SQLite and
   a temporary MySQL 8 database.
5. Have the designated human reviewer inspect the diff and test evidence. Only
   the reviewer merges to `main` after approval.

The repository administrator must require the **PHP Tests / test** status check
in the `main` branch protection settings. A workflow file can run the check on
every pull request, but cannot enforce branch protection through Git commits.
