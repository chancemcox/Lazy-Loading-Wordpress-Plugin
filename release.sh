#!/bin/bash

# WordPress Plugin Release Script
# Automates version bumping and release creation

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Get current version
CURRENT_VERSION=$(node -p "require('./package.json').version")

echo -e "${BLUE}🚀 WordPress Plugin Release Tool${NC}"
echo -e "${YELLOW}Current version: ${CURRENT_VERSION}${NC}"
echo ""

# Ask for new version
echo "What type of release?"
echo "1. Patch (bug fixes) - ${CURRENT_VERSION} → $(npm version patch --dry-run | sed 's/v//')"
echo "2. Minor (new features) - ${CURRENT_VERSION} → $(npm version minor --dry-run | sed 's/v//')"
echo "3. Major (breaking changes) - ${CURRENT_VERSION} → $(npm version major --dry-run | sed 's/v//')"
echo "4. Custom version"
echo "5. Exit"
echo ""

read -p "Choose option (1-5): " choice

case $choice in
    1)
        VERSION_TYPE="patch"
        ;;
    2)
        VERSION_TYPE="minor"
        ;;
    3)
        VERSION_TYPE="major"
        ;;
    4)
        read -p "Enter custom version (e.g., 1.2.3): " CUSTOM_VERSION
        if [[ ! $CUSTOM_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            echo "❌ Invalid version format. Use x.y.z"
            exit 1
        fi
        ;;
    5)
        echo "👋 Goodbye!"
        exit 0
        ;;
    *)
        echo "❌ Invalid option"
        exit 1
        ;;
esac

# Confirm release
echo ""
if [ ! -z "$CUSTOM_VERSION" ]; then
    NEW_VERSION=$CUSTOM_VERSION
    echo -e "${YELLOW}📦 Preparing release: ${CURRENT_VERSION} → ${NEW_VERSION}${NC}"
else
    NEW_VERSION=$(npm version $VERSION_TYPE --dry-run | sed 's/v//')
    echo -e "${YELLOW}📦 Preparing ${VERSION_TYPE} release: ${CURRENT_VERSION} → ${NEW_VERSION}${NC}"
fi

read -p "Continue with release? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo "❌ Release cancelled"
    exit 0
fi

echo ""
echo -e "${BLUE}🔄 Starting release process...${NC}"

# Check git status
if [ -n "$(git status --porcelain)" ]; then
    echo "❌ Working directory not clean. Please commit or stash changes."
    exit 1
fi

# Check if on main branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "⚠️  You're on branch '${CURRENT_BRANCH}', not 'main'"
    read -p "Continue anyway? (y/N): " continue_branch
    if [[ ! $continue_branch =~ ^[Yy]$ ]]; then
        echo "❌ Release cancelled"
        exit 0
    fi
fi

# Update version in package.json
if [ ! -z "$CUSTOM_VERSION" ]; then
    npm version $CUSTOM_VERSION --no-git-tag-version
else
    npm version $VERSION_TYPE --no-git-tag-version
fi

# Update version in main plugin file
sed -i.bak "s/Version: .*/Version: $NEW_VERSION/" lazy-loading-plugin.php
rm lazy-loading-plugin.php.bak

echo -e "${GREEN}✅ Version updated to ${NEW_VERSION}${NC}"

# Build the plugin
echo -e "${YELLOW}🔨 Building WordPress plugin...${NC}"
npm run build-zip

# Commit changes
git add package.json lazy-loading-plugin.php
git commit -m "🚀 Release v${NEW_VERSION}"

# Create git tag
git tag "v${NEW_VERSION}"

echo -e "${GREEN}✅ Git tag created: v${NEW_VERSION}${NC}"

# Push to repository
echo -e "${YELLOW}📤 Pushing to GitHub...${NC}"
git push origin main
git push origin "v${NEW_VERSION}"

echo ""
echo -e "${GREEN}🎉 Release v${NEW_VERSION} completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next steps:${NC}"
echo "1. The GitHub Action will automatically create a release"
echo "2. Download: lazy-loading-plugin-v${NEW_VERSION}.zip"
echo "3. Upload to WordPress via Plugins → Add New → Upload"
echo ""
echo -e "${YELLOW}🔗 Links:${NC}"
echo "• Releases: https://github.com/chancemcox/Lazy-Loading-Wordpress-Plugin/releases"
echo "• Issues: https://github.com/chancemcox/Lazy-Loading-Wordpress-Plugin/issues"
echo ""
echo -e "${GREEN}🚀 Happy WordPressing!${NC}"