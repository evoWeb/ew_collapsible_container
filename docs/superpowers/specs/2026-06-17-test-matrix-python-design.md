# Design: test.py — Python-Ersatz für test.sh

**Datum:** 2026-06-17
**Status:** Approved

## Ziel

`test.sh` wird durch `test.py` ersetzt. Das Script orchestriert dieselbe Testmatrix wie die
GitHub Actions CI (`ci.yml`) — aber lokal ausführbar. `runTests.sh` und `additionalTests.sh`
bleiben unverändert; Python ruft sie per `subprocess` auf.

## Abdeckungsvergleich CI vs. lokal

Beide decken dieselben Schritte pro Kombination ab:

1. `lintPhp`
2. `composer require [prefer] typo3/cms-core:<core>`
3. `composer require --dev [prefer] typo3/testing-framework:<framework>`
4. `composerValidate`
5. `functional Tests/Functional -d sqlite`

Und beide führen vorher den Resources-Check aus (XLIFF-Lint + Dokumentations-Build).

## Dateistruktur

```
Build/Scripts/
├── test.py            ← neu (ersetzt test.sh)
├── test.sh            ← wird gelöscht
├── runTests.sh        ← bleibt unverändert
└── additionalTests.sh ← bleibt unverändert
```

## Matrix-Definition

Direkte Übersetzung der `ci.yml`-Matrix:

```python
PHP_VERSIONS = ['8.2', '8.3', '8.4', '8.5']
PREFER_OPTIONS = ['', '--prefer-lowest']
PACKAGES = [
    {'core': '^13.4', 'framework': '^9.2.1'},
    {'core': '^14.3', 'framework': '^9.5.0'},
]

matrix = [
    [php, prefer, pkg]
    for php in PHP_VERSIONS
    for prefer in PREFER_OPTIONS
    for pkg in PACKAGES
]
# → 16 Einträge: [php, prefer, {core, framework}]
```

## Funktionen

```python
def check_resources():
    run('./additionalTests.sh -s lintXliff')
    run('./additionalTests.sh -s buildDocumentation')

def cleanup():
    run('./runTests.sh -s clean')
    run('./additionalTests.sh -s clean')

def run_functional_tests(php, core, framework, prefer=''):
    cleanup()
    run(f'./runTests.sh -p {php} -s lintPhp')
    run(f'./runTests.sh -p {php} -s composer require {prefer} "typo3/cms-core:{core}"')
    run(f'./runTests.sh -p {php} -s composer require --dev {prefer} "typo3/testing-framework:{framework}"')
    run(f'./runTests.sh -p {php} -s composerValidate')
    run(f'./runTests.sh -p {php} -d sqlite -s functional Tests/Functional')
```

`run()` ist ein Thin-Wrapper um `subprocess.run(shell=True)` — bei Exit-Code ≠ 0 bricht er
mit `sys.exit()` ab (entspricht `|| exit 1` in Bash).

## Einstiegspunkt

```python
#!/usr/bin/env python3

def main():
    debug = '--debug' in sys.argv

    if debug:
        cleanup()
        run_functional_tests('8.2', '^14.3', '^9.5.0', '--prefer-lowest')
        return

    check_resources()
    for php, prefer, pkg in matrix:
        run_functional_tests(php, pkg['core'], pkg['framework'], prefer)
```

Das Script wechselt beim Start via `os.chdir()` in sein eigenes Verzeichnis (`Build/Scripts/`),
damit die relativen Pfade zu `./runTests.sh` und `./additionalTests.sh` stimmen.

## Änderungen gegenüber test.sh

| | `test.sh` | `test.py` |
|---|---|---|
| Matrix | 16 explizite Aufrufe | Loop über generierte Liste |
| Debug-Modus | `DEBUG_TESTS=false` hardcoded | `--debug` CLI-Flag |
| Tote Variablen | `EXIT_CODE_SCSS`, `EXIT_CODE_TYPESCRIPT`, `EXIT_CODE_INSTALL` | entfernt |
| Versionsstring | `^13.4.0` | `^13.4` (Konsistenz mit CI) |
| Ausführbar | `chmod +x` | `chmod +x` (Shebang: `#!/usr/bin/env python3`) |

## Ausführung

```bash
./test.py           # Vollständiger Lauf (resources + alle 16 Kombinationen)
./test.py --debug   # Einzelkombination: PHP 8.2, TYPO3 ^14.3, prefer-lowest
```