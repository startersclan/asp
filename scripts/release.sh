#!/bin/sh
# This script makes it easy to create a new release.
# It requires git, which is only used to detect the previous tag.

set -eu

TAG=${1:-}
COMMENT=${2:-}
if [ -z "$TAG" ]; then
    echo "Please specify a tag as a first argument."
    exit 1
fi
TAG_REGEX='^[0-9]+\.[0-9]+\.[0-9]+$'
if ! echo "$TAG" | grep -E "$TAG_REGEX" > /dev/null; then
    echo "Tag does not match regex: $TAG_REGEX"
    exit 1
fi
if [ -z "$COMMENT" ]; then
    echo "Please specify a release comment as a second argument."
    exit 1
fi
TAG_PREV=$( git --no-pager tag -l --sort=-version:refname | head -n1 )
if ! echo "$TAG_PREV" | grep -E "$TAG_REGEX" > /dev/null; then
    echo "Previous git tag is invalid. It does not match regex: $TAG_REGEX"
    exit 1
fi

get_version_number() {
    local TAG; local MAJOR; local MINOR; local PATCH;
    TAG=${1:?TAG is missing}
    MAJOR=$( echo "$TAG" | cut -d '.' -f1 )
    MINOR=$( echo "$TAG" | cut -d '.' -f2 )
    PATCH=$( echo "$TAG" | cut -d '.' -f3 )
    echo "${MAJOR}0${MINOR}${PATCH}0"     # E.g. '30100' for a tag like '3.1.0'
}

# Update version in docs, .php, and .sql files
sed -i "s/$TAG_PREV/$TAG/" README.md
sed -i "s/$TAG_PREV/$TAG/" docs/full-bf2-stack-example/docker-compose.yml
sed -i "s/$TAG_PREV/$TAG/" src/ASP/index.php
sed -i "s/CODE_VERSION_DATE.*/CODE_VERSION_DATE', '$( date -u '+%Y-%m-%d' )');/" src/ASP/index.php
VERSION_NUMBER_PREV=$( get_version_number "$TAG_PREV" )
VERSION_NUMBER=$( get_version_number "$TAG" )
(
    FILE=src/ASP/system/sql/data.sql
    CONTENT=$( cat "$FILE" | grep -v "($VERSION_NUMBER, '$TAG')"; )
    echo "$CONTENT" > "$FILE"
    echo "INSERT INTO \`_version\`(\`updateid\`, \`version\`) VALUES ("$VERSION_NUMBER", '$TAG');" >> "$FILE"
)

cat - > "src/ASP/system/sql/migrations/down/$VERSION_NUMBER_PREV.sql" <<EOF
--
-- Always delete record from version table!!!
--
DELETE FROM \`_version\` WHERE updateid = $VERSION_NUMBER;
EOF

cat - > "src/ASP/system/sql/migrations/up/$VERSION_NUMBER.sql" <<EOF
--
-- Always update version table!!!
--
INSERT INTO \`_version\`(\`updateid\`, \`version\`) VALUES ($VERSION_NUMBER, '$TAG');
EOF

(
    FILE=src/ASP/system/sql/migrations/migrations.php
    CONTENT=$( cat "$FILE" | grep "\"$TAG_PREV\" =>" -B9999 -A5 )
    CONTENT=$( echo "$CONTENT" | sed "s/\"up\" => null/\"up\" => \"$VERSION_NUMBER\"/" )
    CONTENT=$( echo "$CONTENT" | sed "s/\"up_string\" => \"\"/\"up_string\" => \"$TAG\"/" )
    echo "$CONTENT" > "$FILE"
    cat - >> "$FILE" <<EOF
    ],
    "$TAG" => [
        "comment" => "$COMMENT",
        "up" => null,
        "up_string" => "",
        "down" => "$VERSION_NUMBER_PREV",
        "down_string" => "$TAG_PREV",
    ]
];
EOF
)

echo "Done bumping version to $TAG in all files. The release comment has been added in src/ASP/system/sql/migrations/migrations.php"
