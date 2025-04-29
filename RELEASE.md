# Releasing BakeKit

When you're ready to publish a new version of BakeKit, follow this process:

## 1. Prepare `develop`

- Ensure all intended features, bug fixes, and updates are merged into the `develop` branch.
- Run all tests.
- Verify that the application installs and runs correctly.

## 2. Tag the Release

From the `develop` branch:

```bash
git checkout develop
git pull origin develop
git tag vX.Y.Z -m "Release version X.Y.Z"
git push origin vX.Y.Z
```

Replace `X.Y.Z` with the appropriate version number.

## 3. (Optional) Merge to `main`

If you use a `main` branch for stable code:

```bash
git checkout main
git merge develop
git push origin main
```

## 4. Create a GitHub Release

- Go to https://github.com/bakewizard/BakeKit/releases
- Click “Create a new release”
- Choose the tag (e.g., `vX.Y.Z`)
- Add release notes
- Publish release

## 5. Celebrate 🎉

You've shipped a new version of BakeKit!
