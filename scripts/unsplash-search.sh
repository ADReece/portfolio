#!/usr/bin/env bash
# Lists free (non-Unsplash+) Unsplash photos for a search, to pick ids for
# resources/marketing/sources.json.  Usage: scripts/unsplash-search.sh "wedding couple" [orientation]
set -euo pipefail
: "${UNSPLASH_ACCESS_KEY:?Set UNSPLASH_ACCESS_KEY}"
q=$(jq -rn --arg q "$1" '$q|@uri')
curl -fsS -H "Authorization: Client-ID $UNSPLASH_ACCESS_KEY" -H 'Accept-Version: v1' \
    "https://api.unsplash.com/search/photos?query=$q&per_page=30&content_filter=high${2:+&orientation=$2}" |
    jq -r '.results[] | select((.premium // false) | not) | "\(.id)\t\(.width)x\(.height)\t\(.user.name)\t\(.alt_description // .description // "")\t\(.urls.small)"'
