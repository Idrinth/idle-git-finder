# Idle Git Finder

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/2634af63258a4f0b93ed83619d929f22)](https://www.codacy.com/app/Idrinth/idle-git-finder?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=Idrinth/idle-git-finder&amp;utm_campaign=Badge_Grade) [![Codacy Badge](https://api.codacy.com/project/badge/Coverage/2634af63258a4f0b93ed83619d929f22)](https://www.codacy.com/app/Idrinth/idle-git-finder?utm_source=github.com&utm_medium=referral&utm_content=Idrinth/idle-git-finder&utm_campaign=Badge_Coverage) [![Build Status](https://travis-ci.org/Idrinth/idle-git-finder.svg?branch=master)](https://travis-ci.org/Idrinth/idle-git-finder)

Looks recursively through cwd and displays the status of all git repositorief found. this excludes any repositories within other repositoried and will only list the number of modified/deleted files.
Anything without uncommitted changes - or being empty will be marked with a checkmark, so you know what can be deleted.

Output:

```
x /my-git (7)
✓ /your-git (0)
✓ /abc/your-git (3)
```

would be:
- a repository with 7 uncommitted modifications
- a repository without uncommitted changes
- a repository that is now empty, but has 3 deleted files

## ignores

Any argument given will be considered an ignore, given the example above adding `abc` as an argument would filter out example 3, adding `your-git` would filter out examples 2 and 3.
