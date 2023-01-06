#!/bin/sh

cd /var/www/html/wp-content/plugins/static-manager

git fetch origin

STAGE="${STAGE:=master}"
echo $STAGE
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
echo $BRANCH

if [ $STAGE != $BRANCH ] ; then
    echo "branch aren't the same checkout to stage"
    git switch -f $STAGE
    wp cron event schedule generate_app --allow-root
fi
reslog=$(git log HEAD..origin/$STAGE --oneline)
echo $reslog
if [ "${reslog}" != "" ] ; then
    echo "we have some changes pull and update!" 
    git pull
    wp cron event schedule generate_app --allow-root
fi