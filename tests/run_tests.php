<?php
/**
 * ⛔ NE PAS ÉDITER UNE COPIE — amorce de délégation au lanceur du hub (#1123) ;
 * la logique vit dans `.local/include/tests/_helpers/runner.php` ; empreinte
 * gardée par `scripts/tests/test_runner_canonical_fingerprint.py`.
 */

require_once __DIR__ . '/_hub.php';
jm_test_hub('runner.php');
exit(jm_run_tests(__DIR__, isset($_SERVER['argv']) ? (array) $_SERVER['argv'] : array(), array('project_root' => dirname(__DIR__))));
