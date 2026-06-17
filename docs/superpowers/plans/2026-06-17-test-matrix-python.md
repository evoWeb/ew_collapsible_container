# test.py Matrix Runner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `Build/Scripts/test.sh` with `Build/Scripts/test.py`, a Python script that mirrors the GitHub Actions CI matrix and runs all 16 PHP×prefer×package combinations locally.

**Architecture:** Single Python script with `#!/usr/bin/env python3` shebang. Matrix is defined as nested Python structures and expanded via list comprehension. All subprocess calls delegate to the existing `runTests.sh` and `additionalTests.sh`. The `os.chdir()` to `Build/Scripts/` happens only inside `if __name__ == '__main__'` to avoid side effects on import.

**Tech Stack:** Python 3 (stdlib only: `os`, `subprocess`, `sys`), existing `runTests.sh` / `additionalTests.sh` (unchanged)

## Global Constraints

- Python stdlib only — no pip installs, no third-party packages
- `runTests.sh` and `additionalTests.sh` must not be modified
- Matrix must match `ci.yml` exactly: PHP `['8.2','8.3','8.4','8.5']`, prefer `['','--prefer-lowest']`, packages `[{core:'^13.4',framework:'^9.2.1'},{core:'^14.3',framework:'^9.5.0'}]`
- 16 total combinations (4 PHP × 2 prefer × 2 packages)
- Script must be callable as `./test.py` and `./test.py --debug` from any working directory
- `test.sh` is deleted as part of this plan

---

### Task 1: Create test.py

**Files:**
- Create: `Build/Scripts/test.py`

**Interfaces:**
- Produces: `matrix` (list of `[str, str, dict]`), `run_functional_tests(php, core, framework, prefer)`, `check_resources()`, `cleanup()`

- [ ] **Step 1: Confirm target file does not exist**

```bash
ls Build/Scripts/test.py 2>&1
```
Expected output: `ls: cannot access 'Build/Scripts/test.py': No such file or directory`

- [ ] **Step 2: Write the matrix verification check — expect it to fail**

```bash
python3 -c "
import importlib.util, sys
spec = importlib.util.spec_from_file_location('test_runner', 'Build/Scripts/test.py')
mod = importlib.util.load_from_spec(spec)
spec.loader.exec_module(mod)
assert len(mod.matrix) == 16, f'Expected 16, got {len(mod.matrix)}'
for php, prefer, pkg in mod.matrix:
    assert php in mod.PHP_VERSIONS
    assert prefer in mod.PREFER_OPTIONS
    assert pkg in mod.PACKAGES
print('Matrix OK: 16 combinations')
"
```
Expected output: `FileNotFoundError` or `AttributeError` — file does not exist yet.

- [ ] **Step 3: Create `Build/Scripts/test.py` with full content**

```python
#!/usr/bin/env python3

import os
import subprocess
import sys

_SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

RED = '\033[0;31m'
GREEN = '\033[0;32m'
NC = '\033[0m'

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


def run(cmd: str) -> None:
    result = subprocess.run(cmd, shell=True)
    if result.returncode != 0:
        sys.exit(result.returncode)


def cleanup() -> None:
    run('./runTests.sh -s clean')
    run('./additionalTests.sh -s clean')


def check_resources() -> None:
    print('################################################################')
    print(' Checking documentation and xliff files')
    print('################################################################')
    run('./additionalTests.sh -s lintXliff')
    run('./additionalTests.sh -s buildDocumentation')
    print(f'{GREEN}Resources valid{NC}')
    cleanup()


def run_functional_tests(php: str, core: str, framework: str, prefer: str = '') -> None:
    prefer_arg = f'{prefer} ' if prefer else ''
    print('###########################################################################')
    print(f' Run functional tests with PHP {php}, TYPO3 {core}, framework {framework}')
    if prefer:
        print(f' Additional: {prefer}')
    print('###########################################################################')
    cleanup()
    run(f'./runTests.sh -p {php} -s lintPhp')
    run(f'./runTests.sh -p {php} -s composer require {prefer_arg}"typo3/cms-core:{core}"')
    run(f'./runTests.sh -p {php} -s composer require --dev {prefer_arg}"typo3/testing-framework:{framework}"')
    run(f'./runTests.sh -p {php} -s composerValidate')
    run(f'./runTests.sh -p {php} -d sqlite -s functional Tests/Functional')
    print(f'{GREEN}SUCCESS{NC}')


def main() -> None:
    debug = '--debug' in sys.argv

    if debug:
        cleanup()
        run_functional_tests('8.2', '^14.3', '^9.5.0', '--prefer-lowest')
        return

    check_resources()
    for php, prefer, pkg in matrix:
        run_functional_tests(php, pkg['core'], pkg['framework'], prefer)


if __name__ == '__main__':
    os.chdir(_SCRIPT_DIR)
    main()
```

- [ ] **Step 4: Run the matrix verification — expect it to pass**

```bash
python3 -c "
import importlib.util, sys
spec = importlib.util.spec_from_file_location('test_runner', 'Build/Scripts/test.py')
mod = importlib.util.load_from_spec(spec)
spec.loader.exec_module(mod)
assert len(mod.matrix) == 16, f'Expected 16, got {len(mod.matrix)}'
for php, prefer, pkg in mod.matrix:
    assert php in mod.PHP_VERSIONS
    assert prefer in mod.PREFER_OPTIONS
    assert pkg in mod.PACKAGES
print('Matrix OK: 16 combinations')
"
```
Expected output: `Matrix OK: 16 combinations`

- [ ] **Step 5: Verify syntax is clean**

```bash
python3 -m py_compile Build/Scripts/test.py && echo "Syntax OK"
```
Expected output: `Syntax OK`

- [ ] **Step 6: Make executable**

```bash
chmod +x Build/Scripts/test.py
ls -la Build/Scripts/test.py
```
Expected: file listed with `-rwxr-xr-x` permissions.

- [ ] **Step 7: Commit**

```bash
git add Build/Scripts/test.py
git commit -m "[TASK] Add test.py as Python replacement for test.sh"
```

---

### Task 2: Remove test.sh

**Files:**
- Delete: `Build/Scripts/test.sh`

**Interfaces:**
- Consumes: `Build/Scripts/test.py` from Task 1 (must exist and be executable)

- [ ] **Step 1: Confirm test.py exists and is executable**

```bash
ls -la Build/Scripts/test.py
```
Expected: file with execute bit set.

- [ ] **Step 2: Confirm both files currently exist**

```bash
ls Build/Scripts/test*
```
Expected:
```
Build/Scripts/test.py
Build/Scripts/test.sh
```

- [ ] **Step 3: Remove test.sh via git**

```bash
git rm Build/Scripts/test.sh
```
Expected: `rm 'Build/Scripts/test.sh'`

- [ ] **Step 4: Verify only test.py remains**

```bash
ls Build/Scripts/test*
```
Expected: `Build/Scripts/test.py` only.

- [ ] **Step 5: Commit**

```bash
git commit -m "[TASK] Remove test.sh, replaced by test.py"
```