<?php
/**
 * ⛔ NE PAS ÉDITER UNE COPIE — amorce de délégation au commun du hub (#1542) ;
 * la logique vit dans `.local/include/tests/_helpers/commons_wiring.php` ; empreinte
 * gardée par `scripts/tests/test_commons_wiring_canonical_fingerprint.py`.
 */

require_once __DIR__ . '/_hub.php';
jm_test_hub('commons_wiring.php');
exit(jm_commons_wiring_check(dirname(__DIR__)));
