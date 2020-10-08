## How to release a new version

1. Make sure all changes are tested and committed
2. Update package.json with a new version number (eg. to `1.1.0`) and commit.
3. Create a git tag with that version (`git tag sdk-node-typescript/v1.1.0`)
4. Run `yarn publish`
