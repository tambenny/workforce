# Git Workflow

Use this basic workflow to keep changes safe and easy to recover.

## Before You Start

```powershell
git status
```

Read the output before editing. Do not assume the tree is clean.

## Create A Backup First

If the change is risky, create a local backup folder or zip before editing.

The project already keeps local backups under:

```text
backups/
```

## Normal Workflow

1. Make the change.
2. Check what changed:

```powershell
git status
git diff
```

3. Stage the files:

```powershell
git add .
```

4. Commit:

```powershell
git commit -m "Describe the change"
```

5. Push:

```powershell
git push
```

## Safer Habit

Prefer smaller commits instead of one large commit.

Good examples:

- `Fix kiosk token handling`
- `Add punch photo review page`
- `Improve camera kiosk styling`

## Things That Should Stay Out Of Git

Do not commit:

- `.env`
- `backups/`
- `storage/app/public/kiosk-punches`
- SQL dumps with real data
- runtime cache/session/view files

## If You Need To Undo Local Work

Use caution. Avoid destructive commands unless you are sure.

Safer checks first:

```powershell
git status
git diff
git log --oneline -10
```

If you are unsure, make another backup before undoing anything.
