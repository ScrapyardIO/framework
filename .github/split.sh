#!/usr/bin/env bash
#
# Splits every component of this monorepo into its own repository and tags it with the
# monorepo's tag. A component is a src/<Namespace>/<Component>/ directory holding a
# composer.json; its repository is $SPLIT_REMOTE/<package name after the vendor>.git.
#
#   SPLIT_REMOTE=https://x-access-token:<token>@github.com/<org> .github/split.sh v0.10.9
#
# The tag must sit at the head of the X.Y.x line branch or of main. Each split lands on X.Y.x,
# and on main too when main is at the tag. A line branch missing from the monorepo is created
# at the tag, and one that main's tag has moved past is fast-forwarded to it.

set -euo pipefail

fail() { echo "split: $*" >&2; exit 1; }

TAG=${1:-}
[[ -n ${SPLIT_REMOTE:-} ]] || fail "SPLIT_REMOTE is not set"
[[ $TAG =~ ^v([0-9]+)\.([0-9]+)\.([0-9]+)$ ]] || fail "tag '$TAG' is not vX.Y.Z"

VERSION=${TAG#v}
LINE="${BASH_REMATCH[1]}.${BASH_REMATCH[2]}"
BRANCH="$LINE.x"
COMMIT=$(git rev-parse "$TAG^{commit}")

cd "$(git rev-parse --show-toplevel)"
git fetch --quiet origin '+refs/heads/*:refs/remotes/origin/*'

head_of() { git rev-parse --verify --quiet "refs/remotes/origin/$1^{commit}" || true; }

# actions/checkout leaves the workflow's own token as an http.<url>.extraheader for github.com,
# which git sends instead of the credentials in $SPLIT_REMOTE. An empty value clears it.
split_git() { git -c http.https://github.com/.extraheader= "$@"; }

# --- Components --------------------------------------------------------------------------

DIRS=()
NAMES=()
while IFS= read -r manifest; do
    DIRS+=("$(dirname "$manifest")")
    NAMES+=("$(jq -r .name "$manifest")")
done < <(git ls-tree -r --name-only "$COMMIT" -- src | grep -E '^src/[^/]+/[^/]+/composer\.json$' | sort)

[[ ${#DIRS[@]} -gt 0 ]] || fail "no src/<Namespace>/<Component>/composer.json at $TAG"

# --- Manifests agree with the tag --------------------------------------------------------

OWN=$(printf '%s\n' "${NAMES[@]}" | jq -R . | jq -s .)
problems=()

check_manifest() {
    local path=$1
    local json
    json=$(git show "$COMMIT:$path")

    local version
    version=$(jq -r '.version // empty' <<<"$json")
    [[ -z $version || $version == "$VERSION" ]] || problems+=("$path: version is $version, tag is $VERSION")

    local alias
    alias=$(jq -r '.extra["branch-alias"]["dev-main"] // empty' <<<"$json")
    [[ $alias == "$BRANCH-dev" ]] || problems+=("$path: branch-alias dev-main is '${alias}', expected '$BRANCH-dev'")

    while IFS=$'\t' read -r package constraint; do
        [[ -z $package ]] && continue
        [[ $constraint =~ ^\^${LINE//./\\.}(\.[0-9]+)?$ ]] \
            || problems+=("$path: requires $package '$constraint', expected '^$LINE'")
    done < <(jq -r --argjson own "$OWN" '
        [(.require // {}), (.["require-dev"] // {})] | add | to_entries[]
        | select(.key as $k | $own | index($k)) | "\(.key)\t\(.value)"' <<<"$json")
}

check_manifest composer.json
for dir in "${DIRS[@]}"; do
    check_manifest "$dir/composer.json"
done

if [[ ${#problems[@]} -gt 0 ]]; then
    printf 'split: %s\n' "${problems[@]}" >&2
    fail "manifests do not match $TAG"
fi

# --- Which branches receive the splits ---------------------------------------------------

MAIN_HEAD=$(head_of main)
LINE_HEAD=$(head_of "$BRANCH")
TARGETS=()

if [[ $LINE_HEAD == "$COMMIT" ]]; then
    TARGETS+=("$BRANCH")
elif [[ $MAIN_HEAD == "$COMMIT" ]]; then
    if [[ -z $LINE_HEAD ]]; then
        echo "Creating $BRANCH at $TAG"
    elif git merge-base --is-ancestor "$LINE_HEAD" "$COMMIT"; then
        echo "Fast-forwarding $BRANCH to $TAG"
    else
        fail "$BRANCH has commits main does not; tag $TAG on $BRANCH or merge it into main"
    fi
    git push origin "$COMMIT:refs/heads/$BRANCH"
    TARGETS+=("$BRANCH")
else
    fail "$TAG is not at the head of $BRANCH or main"
fi

[[ $MAIN_HEAD == "$COMMIT" ]] && TARGETS+=(main)

# --- Split, then publish only when every repository answers ------------------------------

# splitsh-lite takes a full ref name, not a SHA, and reports errors on stdout.
SHAS=()
for dir in "${DIRS[@]}"; do
    sha=$(splitsh-lite --prefix="$dir" --origin="refs/tags/$TAG" --quiet) || fail "splitsh-lite on $dir: $sha"
    [[ $sha =~ ^[0-9a-f]{40}$ ]] || fail "splitsh-lite on $dir returned '$sha'"
    SHAS+=("$sha")
done

missing=()
for name in "${NAMES[@]}"; do
    split_git ls-remote "$SPLIT_REMOTE/${name#*/}.git" >/dev/null 2>&1 || missing+=("${name#*/}")
done
[[ ${#missing[@]} -eq 0 ]] || fail "repositories not reachable: ${missing[*]}"

for i in "${!DIRS[@]}"; do
    repo="$SPLIT_REMOTE/${NAMES[$i]#*/}.git"
    refspecs=()
    for target in "${TARGETS[@]}"; do
        refspecs+=("+${SHAS[$i]}:refs/heads/$target")
    done
    echo "${NAMES[$i]}: ${SHAS[$i]} -> ${TARGETS[*]}, $TAG"
    split_git push --quiet "$repo" "${refspecs[@]}"
    split_git push --quiet "$repo" "${SHAS[$i]}:refs/tags/$TAG"
done
