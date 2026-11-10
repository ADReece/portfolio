#!/usr/bin/env bash
# Prints the version tag for the commit being released (HEAD by default).
#   - If HEAD already has a vX.Y.Z tag (a re-run or a manually pushed tag),
#     prints that tag and "existing".
#   - Otherwise bumps the latest vX.Y.Z tag from the commits since it:
#       major: a "type!:" subject, or a "BREAKING CHANGE:" footer line
#       minor: a "feat:" / "feat(scope):" subject
#     (Conventional Commits: the words merely appearing in a message don't count.)
#       patch: anything else
#     With no tags yet, the base is v1.0.0.
# Output (for $GITHUB_OUTPUT):  version=vX.Y.Z  and  status=new|existing
set -euo pipefail

ref="${1:-HEAD}"
semver='^v[0-9]+\.[0-9]+\.[0-9]+$'

existing=$(git tag --points-at "$ref" | grep -E "$semver" | sort -V | tail -n1 || true)
if [ -n "$existing" ]; then
    echo "version=$existing"
    echo "status=existing"
    exit 0
fi

latest=$(git tag --list 'v*' | grep -E "$semver" | sort -V | tail -n1 || true)
if [ -n "$latest" ]; then
    range="$latest..$ref"
else
    latest="v1.0.0"
    range="$ref"
fi

subjects=$(git log --format='%s' "$range")
bodies=$(git log --format='%b' "$range")
IFS=. read -r major minor patch <<<"${latest#v}"

if grep -qE '^[a-z]+(\([^)]*\))?!:' <<<"$subjects" || grep -qE '^BREAKING[ -]CHANGE: ' <<<"$bodies"; then
    major=$((major + 1)); minor=0; patch=0
elif grep -qE '^feat(\([^)]*\))?:' <<<"$subjects"; then
    minor=$((minor + 1)); patch=0
else
    patch=$((patch + 1))
fi

echo "version=v$major.$minor.$patch"
echo "status=new"
