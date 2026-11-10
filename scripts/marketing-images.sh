#!/usr/bin/env bash
# Builds the homepage photos from resources/marketing/sources.json.
#
# Each source is a Pexels photo ({"pexels": id}, Pexels License) or an
# Unsplash photo ({"unsplash": id}, Unsplash License, never Unsplash+).
# For each one this script:
#   1. for Unsplash, looks the photo up to get the photographer and download
#      URL, and refuses Unsplash+ (premium) photos, which aren't free to use;
#   2. downloads it once and self-hosts it (no hotlinking);
#   3. encodes AVIF and WebP at several widths (default 480/800/1200, or the
#      source's own "widths"), plus one 800px JPEG fallback for old browsers;
#   4. writes resources/marketing/images.json (sizes for width/height/srcset)
#      and CREDITS.md (source and licence for every image).
#
# Unsplash sources use the official API: set UNSPLASH_ACCESS_KEY (a free
# developer app's Access Key; never commit it). As the API guidelines ask,
# each download is reported to Unsplash via the photo's download_location.
# Pexels sources are downloaded from images.pexels.com; "photographer" in
# the source is optional and only used for the credits.
# Needs curl, jq, ImageMagick (convert/identify) and ffmpeg with libaom-av1.
# Usage: scripts/marketing-images.sh            (only new or changed sources)
#        scripts/marketing-images.sh --force    (re-encode everything)
set -euo pipefail
cd "$(dirname "$0")/.."

SOURCES=resources/marketing/sources.json
OUT=public/images/marketing
CACHE=storage/app/marketing-originals
MANIFEST=resources/marketing/images.json
CREDITS=public/images/marketing/CREDITS.md
FORCE=${1:-}

api() { curl -fsS -H "Authorization: Client-ID $UNSPLASH_ACCESS_KEY" -H 'Accept-Version: v1' "https://api.unsplash.com$1"; }

mkdir -p "$OUT" "$CACHE"
manifest='{}'
[ -f "$MANIFEST" ] && [ "$FORCE" != "--force" ] && manifest=$(cat "$MANIFEST")

for key in $(jq -r 'keys[]' "$SOURCES"); do
    src=$(jq -c --arg k "$key" '.[$k]' "$SOURCES")
    if [ "$(jq -r 'has("pexels")' <<<"$src")" = "true" ]; then provider=pexels; else provider=unsplash; fi
    id=$(jq -r ".$provider" <<<"$src")
    if [ "$(jq -r --arg k "$key" --arg id "$id" '.[$k].source_id == $id' <<<"$manifest")" = "true" ] && [ -f "$OUT/$key-800.jpg" ]; then
        echo "= $key ($id)"; continue
    fi
    echo "+ $key ($id)"

    orig="$CACHE/$provider-$id.jpg"
    if [ "$provider" = unsplash ]; then
        : "${UNSPLASH_ACCESS_KEY:?Set UNSPLASH_ACCESS_KEY to an Unsplash developer Access Key}"
        meta=$(api "/photos/$id")
        if [ "$(jq -r '(.premium // false) or (.plus // false)' <<<"$meta")" = "true" ]; then
            echo "  $id is an Unsplash+ photo, which isn't free to use. Choose another." >&2; exit 1
        fi
        if [ ! -f "$orig" ]; then
            api "$(jq -r '.links.download_location' <<<"$meta" | sed 's#https://api.unsplash.com##')" >/dev/null
            curl -fsS -o "$orig" "$(jq -r '.urls.raw' <<<"$meta")&w=2400&q=90&fm=jpg"
        fi
        credit=$(jq -c '{photographer: .user.name, photographer_url: ("https://unsplash.com/@" + .user.username),
            source_url: .links.html, licence: "Unsplash License", licence_url: "https://unsplash.com/license"}' <<<"$meta")
    else
        [ -f "$orig" ] || curl -fsS -o "$orig" "https://images.pexels.com/photos/$id/pexels-photo-$id.jpeg?auto=compress&cs=tinysrgb&w=2400"
        credit=$(jq -c --arg id "$id" '{photographer: (.photographer // null), photographer_url: (.photographer_url // null),
            source_url: ("https://www.pexels.com/photo/" + $id + "/"), licence: "Pexels License", licence_url: "https://www.pexels.com/license/"}' <<<"$src")
    fi

    read -r W H < <(identify -format '%w %h\n' "$orig" | head -1)
    # Height scaled to 2400 wide; the manifest keeps the original aspect ratio.
    rm -f "$OUT/$key"-*.avif "$OUT/$key"-*.webp "$OUT/$key"-*.jpg
    widths=()
    for w in $(jq -r '(.widths // [480, 800, 1200])[]' <<<"$src"); do
        [ "$w" -gt "$W" ] && continue
        widths+=("$w")
        base="$OUT/$key-$w"
        convert "$orig" -strip -resize "${w}x" -quality 82 "$base.png.tmp.jpg"
        convert "$orig" -strip -resize "${w}x" -quality 76 "$base.webp"
        ffmpeg -loglevel error -y -i "$base.png.tmp.jpg" -c:v libaom-av1 -still-picture 1 -crf 34 -cpu-used 4 -pix_fmt yuv420p "$base.avif"
        rm "$base.png.tmp.jpg"
    done
    convert "$orig" -strip -resize 800x -quality 76 -sampling-factor 4:2:0 -interlace JPEG "$OUT/$key-800.jpg"
    # A tiny blurred placeholder colour while the photo loads.
    colour=$(convert "$orig" -resize 1x1\! -format '#%[hex:p{0,0}]' info:)

    manifest=$(jq --arg k "$key" --argjson s "$src" --arg id "$id" --argjson cr "$credit" --argjson w "$W" --argjson h "$H" \
        --argjson ws "$(printf '%s\n' "${widths[@]}" | jq -s .)" --arg c "$colour" '
        .[$k] = ({
            source_id: $id,
            alt: $s.alt,
            width: $w, height: $h, widths: $ws, colour: $c,
            position: ($s.position // "center")
        } + $cr)' <<<"$manifest")
done

# Drop entries whose source was removed.
manifest=$(jq --argjson s "$(cat "$SOURCES")" 'with_entries(select(.key as $k | $s | has($k)))' <<<"$manifest")
jq -S . <<<"$manifest" > "$MANIFEST"

{
    echo "# Homepage photo credits"
    echo
    echo "Every photo here is self-hosted (not hotlinked) from Pexels under the [Pexels License](https://www.pexels.com/license/)"
    echo "or from Unsplash under the [Unsplash License](https://unsplash.com/license). Both allow free commercial use"
    echo "without asking permission. No Unsplash+ photos are used."
    echo "Generated by \`scripts/marketing-images.sh\` from \`resources/marketing/sources.json\`."
    echo
    echo "| File | Photographer | Source | Licence |"
    echo "|---|---|---|---|"
    jq -r 'to_entries | sort_by(.key)[] | "| `\(.key)-*` | \(if .value.photographer then "[\(.value.photographer)](\(.value.photographer_url))" else "See source" end) | [\(.value.source_url)](\(.value.source_url)) | [\(.value.licence)](\(.value.licence_url)) |"' "$MANIFEST"
} > "$CREDITS"

echo "Wrote $MANIFEST and $CREDITS"
