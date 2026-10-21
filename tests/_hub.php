<?php
/**
 * Amorce : localise le hub `.local/` en REMONTANT l'arbre, et charge un commun
 * de test depuis `include/tests/_helpers/` (#677).
 *
 * ⛔ NE PAS ÉDITER UNE COPIE. Ce fichier est la source canonique ; chaque dépôt
 * en porte une copie nommée `_hub.php`, et `test_hub_fingerprint.py` compare
 * leurs empreintes (ainsi que celles de `_hub.mjs`). Éditer une copie fait
 * ROUGIR cette garde — c'est voulu.
 *
 * 🔴 POURQUOI UNE REMONTÉE ET PAS UN CHEMIN RELATIF. Un
 * `dirname(__DIR__, 3) . '/.local/…'` casse en worktree : depuis
 * `<plugin>/.worktrees/x/tests/`, trois niveaux mènent à `<plugin>/.worktrees/`
 * et non au workspace. La remontée jusqu'au marqueur franchit `.worktrees/`
 * sans avoir à le connaître.
 *
 * 🔴 POURQUOI UNE AMORCE LOCALE EXISTE MALGRÉ TOUT. Un consommateur doit trouver
 * le hub AVANT de pouvoir charger quoi que ce soit du hub. Ce premier pas est
 * irréductible : cette amorce est le SEUL fichier recopié du dispositif, et sa
 * conformité est gardée.
 *
 * ⚠ Le canal `INJECT_PHP_COMMONS` ne peut PAS servir ici : il écrit dans
 * `include/` au build et au deploy, et la copie est gitignorée côté plugin. Une
 * suite de test, qui s'exécute depuis l'arbre de travail, ne la verrait jamais.
 *
 * 🔵 `jm_test_hub_root()` (#1038) EXPOSE la racine `.local/` elle-même — pour
 * tout consommateur qui a besoin d'un chemin sous `.local/` autre que
 * `include/tests/_helpers/` (ex. `.local/include/jm-*.php`,
 * `.local/assets/js/*.js`, `.local/scripts/.env.project`). `jm_test_hub()` reste
 * la primitive existante pour charger un commun de test, désormais un
 * consommateur de `jm_test_hub_root()` plutôt qu'un duplicata de sa remontée.
 */

if (!function_exists('jm_test_hub_root')) {
  /**
   * @return string Chemin absolu de `.local/` (racine du hub), par remontée
   *         jusqu'au marqueur `.local/include/tests/_helpers/`.
   * @throws RuntimeException si la racine du système de fichiers est franchie
   *         sans trouver le marqueur — ⛔ jamais de repli muet.
   */
  function jm_test_hub_root(): string {
    static $racine = null;

    if ($racine === null) {
      $remontes = array();
      $courant  = __DIR__;
      while (true) {
        $remontes[] = $courant;
        // Cas nominal : un consommateur situé quelque part sous le workspace.
        $candidat = $courant . '/.local';
        if (is_dir($candidat . '/include/tests/_helpers')) {
          $racine = $candidat;
          break;
        }
        // Cas du hub lui-même : `.local/` EST le dépôt courant.
        if (basename($courant) === '.local'
            && is_dir($courant . '/include/tests/_helpers')) {
          $racine = $courant;
          break;
        }
        $parent = dirname($courant);
        if ($parent === $courant) {                      // racine atteinte
          throw new RuntimeException(
            "jm_test_hub_root : hub introuvable. Dossiers remontés :\n  - "
            . implode("\n  - ", $remontes)
            . "\nAttendu quelque part au-dessus : .local/include/tests/_helpers/"
          );
        }
        $courant = $parent;
      }
    }

    return $racine;
  }
}

if (!function_exists('jm_test_hub')) {
  /**
   * @param string $rel Nom d'un fichier de `include/tests/_helpers/`.
   * @return string Chemin absolu, le fichier étant déjà chargé.
   * @throws RuntimeException si le fichier est introuvable — ⛔ jamais de repli
   *         muet : une suite verte sans sa garde ne garde rien.
   */
  function jm_test_hub(string $rel): string {
    $chemin = jm_test_hub_root() . '/include/tests/_helpers/' . $rel;
    if (!is_file($chemin)) {
      throw new RuntimeException("jm_test_hub : commun introuvable « $rel » (cherché : $chemin)");
    }
    require_once $chemin;
    return $chemin;
  }
}

jm_test_hub('strip_comments.php');
