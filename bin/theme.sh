USER=
PASS=
BRANCH=master
THEMES_PATH=../themes/

echo "Enter theme name: "
read THEME

git clone --branch $BRANCH --depth 1 https://$USER:$PASS@bitbucket.org/yuriy_martynenko/eva.git $THEMES_PATH$THEME &&
rm -rf $THEMES_PATH$THEME/.git

find $THEMES_PATH$THEME -type f -and -name "composer.json" -exec sed -i "s/Eva/$THEME/g" {} +
find $THEMES_PATH$THEME/resources/locales -type f -and \( -name "*.po" -or -name "*.mo" -or -name "*.pot" \) -exec rename "s/eva/${THEME,,}/g" {} +
find $THEMES_PATH$THEME/templates -type f -and -name "*.php" -exec sed -i "s/__d('eva'/__d('${THEME,,}'/g" {} +
